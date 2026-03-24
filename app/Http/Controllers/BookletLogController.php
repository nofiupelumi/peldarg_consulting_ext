<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;

class BookletLogController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $userId = (int) session('user_id');
        $isAdmin = (bool) session('is_admin');

        $baseQuery = Document::query()
            ->when(!$isAdmin, fn ($q) => $q->where('user_id', $userId));

        $filteredQuery = Document::query()
            ->when(!$isAdmin, fn ($q) => $q->where('user_id', $userId))
            ->when(isset($validated['year']), fn ($q) => $q->whereYear('created_at', (int) $validated['year']))
            ->when(isset($validated['month']), fn ($q) => $q->whereMonth('created_at', (int) $validated['month']));

        $rowsQuery = Document::query()
            ->when(!$isAdmin, fn ($q) => $q->where('user_id', $userId))
            ->when(isset($validated['year']), fn ($q) => $q->whereYear('created_at', (int) $validated['year']))
            ->when(isset($validated['month']), fn ($q) => $q->whereMonth('created_at', (int) $validated['month']))
            ->withCount('students')
            ->when($isAdmin, fn ($q) => $q->with(['user:id,company_name,name']))
            ->latest();

        $rows = $rowsQuery->get()->map(function (Document $doc) use ($isAdmin) {
            $row = [
                'id' => $doc->id,
                'filename' => $doc->filename,
                'session' => $doc->session,
                'status' => $doc->status,
                'api_tier' => $doc->api_tier,
                'page_start' => $doc->page_start,
                'page_end' => $doc->page_end,
                'pages_requested' => $doc->pages_requested,
                'pages_processed' => $doc->pages_processed,
                'pages_with_results' => $doc->pages_with_results,
                'students_rows' => (int) ($doc->students_count ?? 0),
                'created_at' => optional($doc->created_at)->toISOString(),
                'credit_status' => $doc->credit_status,
                'failed_reason' => $doc->failed_reason,
            ];

            if ($isAdmin) {
                $row['user_company_name'] = $doc->user?->company_name;
                $row['user_name'] = $doc->user?->name;
                $row['user_id'] = $doc->user_id;
            }

            return $row;
        });

        $overallUploads = (clone $baseQuery)->count();
        $overallSuccessful = (clone $baseQuery)->where('status', 'complete')->count();
        $overallStudentRows = (clone $baseQuery)->withCount('students')->get()->sum('students_count');

        $filteredUploads = (clone $filteredQuery)->count();
        $filteredSuccessful = (clone $filteredQuery)->where('status', 'complete')->count();
        $filteredStudentRows = (clone $filteredQuery)->withCount('students')->get()->sum('students_count');

        return response()->json([
            'filters' => [
                'year' => isset($validated['year']) ? (int) $validated['year'] : null,
                'month' => isset($validated['month']) ? (int) $validated['month'] : null,
            ],
            'summary' => [
                'overall' => [
                    'uploaded_total' => (int) $overallUploads,
                    'successful_total' => (int) $overallSuccessful,
                    'student_rows_total' => (int) $overallStudentRows,
                ],
                'filtered' => [
                    'uploaded_total' => (int) $filteredUploads,
                    'successful_total' => (int) $filteredSuccessful,
                    'student_rows_total' => (int) $filteredStudentRows,
                ],
            ],
            'rows' => $rows,
        ]);
    }
}
