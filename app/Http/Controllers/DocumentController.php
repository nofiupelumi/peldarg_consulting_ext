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
        $maxUploadMb = $settings->effectiveMaxUploadMb();

        $req->validate([
            'file' => 'required|mimes:pdf|max:' . $maxUploadKb,
            'session' => 'nullable|string',
            'start_page' => 'nullable|integer|min:1',
            'end_page' => 'nullable|integer|min:1|gte:start_page',
            'api_tier' => 'required|string|in:paid_1,paid_2,paid_3',
        ], [
            'file.required' => 'No PDF file was received. If your file is large, increase PHP upload_max_filesize and post_max_size on the server.',
            'file.mimes' => 'Only PDF files are allowed.',
            'file.max' => 'PDF is too large. Maximum allowed is ' . $maxUploadMb . 'MB.',
            'api_tier.required' => 'Please select an API Key Tier.',
            'api_tier.in' => 'Selected API Key Tier is invalid.',
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

        // Auto-count pages from the PDF with fallbacks for host-specific parser limitations.
        $pageCountError = null;
        $totalPages = $this->detectPdfPageCount($file->getRealPath(), $pageCountError);
        if ($totalPages < 1) {
            Log::warning('pdf page count detection failed', [
                'filename' => $file->getClientOriginalName(),
                'reason' => $pageCountError,
            ]);

            throw ValidationException::withMessages([
                'file' => 'Unable to read PDF page count. The PDF may be encrypted/corrupted or uses an unsupported format. Please export as a standard PDF and try again.',
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

        // Use explicit extension to avoid mime-extension guessing that depends on PHP fileinfo.
        $path = $file->storeAs('convocation', (string) Str::uuid() . '.pdf', 'public');

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

        $pat = (string) config('services.github.pat');
        $dispatchRepo = trim((string) config('services.github.dispatch_repo'));
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
                'page_start' => $effectiveStart,
                'page_end' => $effectiveEnd,
            ];

            $dispatchStatus = null;
            $dispatchBody = null;

            try {
                if ($dispatchRepo === '') {
                    throw new \RuntimeException('GitHub dispatch repo is not configured.');
                }

                $response = Http::withToken($pat)
                    ->acceptJson()
                    ->connectTimeout(5)
                    ->timeout(15)
                    ->post('https://api.github.com/repos/' . $dispatchRepo . '/dispatches', [
                        'event_type' => 'process_pdf',
                        'client_payload' => $payload,
                    ]);

                $dispatchStatus = $response->status();
                $dispatchBody = mb_substr((string) $response->body(), 0, 1000);

                if (!$response->successful()) {
                    throw new \RuntimeException('GitHub dispatch API returned HTTP ' . $response->status());
                }
            } catch (\Throwable $exception) {
                Log::error('document upload dispatch failed', [
                    'document_id' => $doc->id,
                    'request_id' => $doc->request_id,
                    'user_id' => $user->id,
                    'dispatch_repo' => $dispatchRepo,
                    'dispatch_status' => $dispatchStatus,
                    'dispatch_response' => $dispatchBody,
                    'pat_configured' => $pat !== '',
                    'exception_class' => get_class($exception),
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

    private function detectPdfPageCount(?string $path, ?string &$error = null): int
    {
        $error = null;

        if (!$path || !is_readable($path)) {
            $error = 'upload temp file is not readable';
            return 0;
        }

        // Primary strategy: FPDI parser.
        try {
            $pdf = new \setasign\Fpdi\Fpdi();
            $count = (int) $pdf->setSourceFile($path);
            if ($count > 0) {
                return $count;
            }
        } catch (\Throwable $e) {
            $error = 'fpdi: ' . $e->getMessage();
        }

        // Secondary strategy: system pdfinfo (if available in host environment).
        if (is_callable('exec')) {
            try {
                $cmd = 'pdfinfo ' . escapeshellarg($path) . ' 2>/dev/null';
                $output = [];
                $exitCode = 1;
                @exec($cmd, $output, $exitCode);

                if ($exitCode === 0) {
                    foreach ($output as $line) {
                        if (preg_match('/^Pages:\s*(\d+)/i', trim((string) $line), $matches) === 1) {
                            $count = (int) ($matches[1] ?? 0);
                            if ($count > 0) {
                                return $count;
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                $error = $error ?: ('pdfinfo: ' . $e->getMessage());
            }
        }

        // Final fallback: approximate page count from PDF page markers.
        try {
            $bytes = @file_get_contents($path);
            if ($bytes !== false) {
                preg_match_all('/\/Type\s*\/Page\b/i', $bytes, $matches);
                $count = count($matches[0] ?? []);
                if ($count > 0) {
                    return $count;
                }
            }
        } catch (\Throwable $e) {
            $error = $error ?: ('regex fallback: ' . $e->getMessage());
        }

        return 0;
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

    /**
     * Recover CSV/XLSX outputs from a GitHub Actions artifact for a completed
     * document whose upload-results callback failed (csv_url/xlsx_url are null).
     *
     * POST body: { artifact_id: "6531056114" }
     */
    public function recoverArtifact(Request $req, Document $doc)
    {
        $isAdmin = (bool) $req->session()->get('is_admin');
        $userId  = (int)  $req->session()->get('user_id');
        if (!$isAdmin && (int) $doc->user_id !== $userId) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $req->validate(['artifact_id' => 'required|string|max:40']);
        $artifactId = $req->input('artifact_id');

        $pat  = (string) config('services.github.pat');
        $repo = 'nofiupelumi/peldarg_consulting_ext';

        // Step 1: get the redirect URL for the artifact zip.
        $apiUrl   = "https://api.github.com/repos/{$repo}/actions/artifacts/{$artifactId}/zip";
        $response = Http::withToken($pat)
            ->withOptions(['allow_redirects' => false])
            ->withHeaders(['Accept' => 'application/vnd.github+json'])
            ->get($apiUrl);

        if ($response->status() === 302) {
            $zipUrl = $response->header('Location');
        } elseif ($response->successful()) {
            // Some clients follow redirects automatically
            $zipUrl = $apiUrl;
        } else {
            Log::error('recover-artifact: failed to get artifact URL', [
                'doc_id'      => $doc->id,
                'artifact_id' => $artifactId,
                'status'      => $response->status(),
            ]);
            return response()->json(['error' => 'Could not fetch artifact URL from GitHub (HTTP ' . $response->status() . ')'], 502);
        }

        // Step 2: download the zip (follow redirect now).
        $zipResponse = Http::withToken($pat)
            ->withOptions(['allow_redirects' => true])
            ->get((string) $zipUrl);

        if (!$zipResponse->successful()) {
            return response()->json(['error' => 'Could not download artifact zip (HTTP ' . $zipResponse->status() . ')'], 502);
        }

        // Step 3: unzip in memory and extract CSV/XLSX.
        $tmpZip = tempnam(sys_get_temp_dir(), 'gh_artifact_') . '.zip';
        file_put_contents($tmpZip, $zipResponse->body());

        $zip = new \ZipArchive();
        if ($zip->open($tmpZip) !== true) {
            @unlink($tmpZip);
            return response()->json(['error' => 'Failed to open artifact zip'], 500);
        }

        $savedCsv = false;
        $savedXlsx = false;
        $base = pathinfo($doc->filename, PATHINFO_FILENAME);

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $ext  = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));
            if (!in_array($ext, ['csv', 'xlsx'], true)) {
                continue;
            }
            $contents = $zip->getFromIndex($i);
            if ($contents === false) {
                continue;
            }
            $storedName = Str::uuid() . '.' . $ext;
            $storedPath = 'processed/' . $storedName;
            Storage::disk('public')->put($storedPath, $contents);
            $url = Storage::disk('public')->url($storedPath);
            if ($ext === 'csv' && !$doc->csv_url) {
                $doc->csv_url = $url;
                $savedCsv = true;
            } elseif ($ext === 'xlsx' && !$doc->xlsx_url) {
                $doc->xlsx_url = $url;
                $savedXlsx = true;
            }
        }

        $zip->close();
        @unlink($tmpZip);

        if (!$savedCsv && !$savedXlsx) {
            return response()->json(['error' => 'No CSV or XLSX files found in the artifact zip'], 422);
        }

        $doc->save();

        Log::info('recover-artifact: restored output URLs', [
            'doc_id'      => $doc->id,
            'artifact_id' => $artifactId,
            'csv'         => $doc->csv_url,
            'xlsx'        => $doc->xlsx_url,
        ]);

        return response()->json([
            'ok'   => true,
            'csv'  => $doc->csv_url ? true : false,
            'xlsx' => $doc->xlsx_url ? true : false,
        ]);
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
