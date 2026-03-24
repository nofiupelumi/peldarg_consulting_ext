<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Student;
use App\Models\User;
use App\Models\AppSetting;
use App\Services\CreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DocumentController extends Controller
{
    private const API_TIERS = ['paid_1', 'paid_2', 'paid_3'];

    public function __construct(private CreditService $creditService)
    {
    }

    public function upload(Request $req)
    {
        $settings = AppSetting::current();
        $maxUploadKb = $settings->effectiveMaxUploadMb() * 1024;

        $req->validate([
            'file' => 'required|mimes:pdf|max:' . $maxUploadKb,
            'session' => 'nullable|string',
            'start_page' => 'nullable|integer|min:1',
            'end_page' => 'nullable|integer|min:1|gte:start_page',
            'api_tier' => 'required|string|in:paid_1,paid_2,paid_3',
        ]);
        $userId = (int) $req->session()->get('user_id');
        $user = User::findOrFail($userId);

        $selectedApiTier = strtolower(trim((string) $req->input('api_tier', '')));
        $allowedApiTiers = (bool) $user->is_admin
            ? self::API_TIERS
            : array_values(array_intersect(self::API_TIERS, (array) ($user->allowed_api_tiers ?? [])));
        if ($allowedApiTiers === []) {
            $allowedApiTiers = ['paid_1'];
        }

        if (!in_array($selectedApiTier, $allowedApiTiers, true)) {
            throw ValidationException::withMessages([
                'api_tier' => 'Selected API tier is not permitted for your account.',
            ]);
        }

        $file = $req->file('file');

        // Auto-count pages from the PDF.
        try {
            $pdf = new \setasign\Fpdi\Fpdi();
            $totalPages = (int) $pdf->setSourceFile($file->getRealPath());
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'file' => 'Unable to read PDF page count. Please re-export the PDF and try again.',
            ]);
        }
        if ($totalPages < 1) {
            throw ValidationException::withMessages([
                'file' => 'PDF appears to have no pages.',
            ]);
        }

        $startPage = $req->filled('start_page') ? (int) $req->input('start_page') : null;
        $endPage = $req->filled('end_page') ? (int) $req->input('end_page') : null;
        $effectiveStart = $startPage ?: 1;
        $effectiveEnd = $endPage ?: $totalPages;
        if ($effectiveStart < 1 || $effectiveEnd < 1 || $effectiveStart > $effectiveEnd) {
            throw ValidationException::withMessages([
                'end_page' => 'End page must be greater than or equal to start page.',
            ]);
        }
        if ($effectiveEnd > $totalPages) {
            throw ValidationException::withMessages([
                'end_page' => 'End page cannot exceed total pages (' . $totalPages . ').',
            ]);
        }
        $pagesRequested = ($effectiveEnd - $effectiveStart) + 1;

        $path = $file->store('convocation', 'public');

        $requestId = (string) Str::uuid();

        $doc = Document::create([
            'user_id' => $user->id,
            'request_id' => $requestId,
            'api_tier' => $selectedApiTier,
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'session' => $req->input('session'),
            'status' => 'processing',
            'page_start' => $effectiveStart,
            'page_end' => $effectiveEnd,
            'pages_requested' => $pagesRequested,
            'credit_status' => 'none',
        ]);

        $reserve = $this->creditService->reserveForUpload(
            userId: $user->id,
            documentId: $doc->id,
            pagesRequested: $pagesRequested,
            actorUserId: $user->id,
        );

        $doc->credits_reserved = $reserve['reserved'];
        $doc->credit_status = 'reserved';
        $doc->save();

        // Extend expiry to 24h to accommodate long/parallel processing in CI
        $sourceUrl = URL::temporarySignedRoute('documents.download', now()->addHours(24), ['doc' => $doc->id]);

        $pat = config('services.github.pat');
        if (!empty($pat)) {
            $payload = [
                'source_url' => $sourceUrl,
                'original_filename' => $file->getClientOriginalName(),
                'session' => $doc->session,
                'callback_url' => url(route('github.callback', [], false)),
                'result_upload_url' => url(route('github.uploadResults', [], false)),
                'doc_id' => (string)$doc->id,
                'request_id' => (string)$doc->request_id,
                'api_tier' => $selectedApiTier,
                'pages_requested' => $pagesRequested,
                'page_start' => $effectiveStart,
                'page_end' => $effectiveEnd,
            ];

            try {
                $response = Http::withToken($pat)
                    ->acceptJson()
                    ->connectTimeout(5)
                    ->timeout(15)
                    ->post('https://api.github.com/repos/' . config('services.github.dispatch_repo') . '/dispatches', [
                        'event_type' => 'process_pdf',
                        'client_payload' => $payload,
                    ]);

                if (!$response->successful()) {
                    throw ValidationException::withMessages([
                        'file' => 'Processing dispatch failed. Please try again shortly.',
                    ]);
                }
            } catch (\Throwable $exception) {
                Log::error('document upload dispatch failed', [
                    'document_id' => $doc->id,
                    'request_id' => $doc->request_id,
                    'user_id' => $user->id,
                    'message' => $exception->getMessage(),
                ]);

                $this->creditService->finalizeDocument(
                    document: $doc,
                    pagesProcessed: 0,
                    pagesWithResults: 0,
                    status: 'failed',
                    failedReason: 'Unable to queue processing job.',
                );

                if ($doc->path) {
                    Storage::disk('public')->delete($doc->path);
                }

                throw ValidationException::withMessages([
                    'file' => 'Unable to queue processing right now. Please try again shortly.',
                ]);
            }
        }

        return response()->json([
            'id' => $doc->id,
            'status' => 'processing',
            'credits_reserved' => $doc->credits_reserved,
            'credit_balance' => (int) $user->fresh()->credit_balance,
            'pages_requested' => (int) $doc->pages_requested,
            'api_tier' => (string) $doc->api_tier,
            'request_id' => (string) $doc->request_id,
        ]);
    }

    public function download(Request $req, Document $doc)
    {
        if (!$req->hasValidSignature()) abort(401);
        $full = Storage::disk('public')->path($doc->path);
        if (!file_exists($full)) abort(404);
        return response()->file($full, ['Content-Type' => 'application/pdf']);
    }

    public function downloadOutput(Request $req, Document $doc, string $type)
    {
        if (!$req->hasValidSignature()) abort(401);
        $url = $type === 'csv' ? $doc->csv_url : $doc->xlsx_url;
        if (!$url) abort(404);
        // Convert public URL back to storage path
        $publicPrefix = Storage::disk('public')->url('');
        if (!str_starts_with($url, $publicPrefix)) abort(404);
        $rel = ltrim(substr($url, strlen($publicPrefix)), '/');
        $full = Storage::disk('public')->path($rel);
        if (!file_exists($full)) abort(404);
        $filename = $doc->filename;
        $base = pathinfo($filename, PATHINFO_FILENAME);
        $downloadName = $base . '.' . $type;
        $mime = $type === 'csv' ? 'text/csv' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        return response()->download($full, $downloadName, [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function index()
    {
        $userId = (int) session('user_id');
        $isAdmin = (bool) session('is_admin');

        $docs = Document::query()
            ->when(!$isAdmin, fn ($q) => $q->where('user_id', $userId))
            ->when($isAdmin, fn ($q) => $q->with(['user:id,company_name,name']))
            ->latest()
            ->get();
        // Attach signed download links for CSV/XLSX if present
        $docs->transform(function($d){
            $d->csv_download = $d->csv_url ? URL::temporarySignedRoute('documents.downloadOutput', now()->addHours(12), ['doc' => $d->id, 'type' => 'csv']) : null;
            $d->xlsx_download = $d->xlsx_url ? URL::temporarySignedRoute('documents.downloadOutput', now()->addHours(12), ['doc' => $d->id, 'type' => 'xlsx']) : null;
            if ((bool) session('is_admin')) {
                $d->user_company_name = $d->user?->company_name;
                $d->user_name = $d->user?->name;
            }
            return $d;
        });
        return $docs;
    }

    public function delete(Request $req, Document $doc)
    {
        $isAdmin = (bool) $req->session()->get('is_admin');
        $userId = (int) $req->session()->get('user_id');
        if (!$isAdmin && (int) $doc->user_id !== $userId) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        // Delete associated students
        Student::where('document_id', $doc->id)->delete();
        
        // Delete PDF file from storage
        if ($doc->path) {
            Storage::disk('public')->delete($doc->path);
        }
        
        // Delete CSV/XLSX files if they exist
        if ($doc->csv_url) {
            $publicPrefix = Storage::disk('public')->url('');
            if (str_starts_with($doc->csv_url, $publicPrefix)) {
                $rel = ltrim(substr($doc->csv_url, strlen($publicPrefix)), '/');
                Storage::disk('public')->delete($rel);
            }
        }
        if ($doc->xlsx_url) {
            $publicPrefix = Storage::disk('public')->url('');
            if (str_starts_with($doc->xlsx_url, $publicPrefix)) {
                $rel = ltrim(substr($doc->xlsx_url, strlen($publicPrefix)), '/');
                Storage::disk('public')->delete($rel);
            }
        }
        
        // Delete document record
        $doc->delete();
        
        return response()->json(['deleted' => true]);
    }
}
