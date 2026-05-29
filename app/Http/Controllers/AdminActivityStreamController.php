<?php

namespace App\Http\Controllers;

use App\Models\ExtractionActivityStream;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AdminActivityStreamController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'day' => 'nullable|date',
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2000|max:2100',
            'partner' => 'nullable|string|max:100',
            'user' => 'nullable|string|max:255',
            'extraction_type' => 'nullable|string|max:60',
            'status' => 'nullable|string|max:60',
            'credit_outcome' => 'nullable|string|max:60',
            'partner_request_id' => 'nullable|string|max:80',
            'per_page' => 'nullable|integer|min:1|max:200',
        ]);

        $query = ExtractionActivityStream::query()->with([
            'events' => fn ($q) => $q->orderByDesc('sequence')->limit(10),
        ]);

        if (!empty($data['day'])) {
            $day = Carbon::parse((string) $data['day']);
            $query->whereBetween('last_event_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()]);
        } elseif (!empty($data['date_from']) || !empty($data['date_to'])) {
            $from = !empty($data['date_from']) ? Carbon::parse((string) $data['date_from'])->startOfDay() : now()->startOfMonth();
            $to = !empty($data['date_to']) ? Carbon::parse((string) $data['date_to'])->endOfDay() : now()->endOfDay();
            if ($from->gt($to)) {
                [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            }
            $query->whereBetween('last_event_at', [$from, $to]);
        } else {
            if (!empty($data['year'])) {
                $query->whereYear('last_event_at', (int) $data['year']);
            }
            if (!empty($data['month'])) {
                $query->whereMonth('last_event_at', (int) $data['month']);
            }
        }

        if (!empty($data['partner'])) {
            $query->where('partner_name', 'like', '%' . $data['partner'] . '%');
        }
        if (!empty($data['user'])) {
            $q = (string) $data['user'];
            $query->where(function ($inner) use ($q) {
                $inner->where('user_email', 'like', '%' . $q . '%')
                    ->orWhereHas('user', fn ($u) => $u->where('company_name', 'like', '%' . $q . '%')->orWhere('name', 'like', '%' . $q . '%'));
            });
        }
        if (!empty($data['extraction_type'])) {
            $query->where('extraction_type', (string) $data['extraction_type']);
        }
        if (!empty($data['status'])) {
            $query->where('status', (string) $data['status']);
        }
        if (!empty($data['credit_outcome'])) {
            $query->where('credit_outcome', (string) $data['credit_outcome']);
        }
        if (!empty($data['partner_request_id'])) {
            $query->where('partner_request_id', 'like', '%' . $data['partner_request_id'] . '%');
        }

        $perPage = (int) ($data['per_page'] ?? 50);
        $rows = $query->orderByDesc('last_event_at')->paginate($perPage);

        return response()->json([
            'filters' => [
                'date_from' => $data['date_from'] ?? null,
                'date_to' => $data['date_to'] ?? null,
                'day' => $data['day'] ?? null,
                'month' => isset($data['month']) ? (int) $data['month'] : null,
                'year' => isset($data['year']) ? (int) $data['year'] : null,
                'partner' => $data['partner'] ?? null,
                'user' => $data['user'] ?? null,
                'extraction_type' => $data['extraction_type'] ?? null,
                'status' => $data['status'] ?? null,
                'credit_outcome' => $data['credit_outcome'] ?? null,
                'partner_request_id' => $data['partner_request_id'] ?? null,
            ],
            'pagination' => [
                'total' => $rows->total(),
                'per_page' => $rows->perPage(),
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
            ],
            'streams' => collect($rows->items())->map(function (ExtractionActivityStream $row) {
                return [
                    'id' => (int) $row->id,
                    'partner_request_id' => (string) $row->partner_request_id,
                    'partner_name' => $row->partner_name,
                    'partner_domain' => $row->partner_domain,
                    'user_email' => $row->user_email,
                    'extraction_type' => $row->extraction_type,
                    'status' => $row->status,
                    'phase' => $row->phase,
                    'last_event_key' => $row->last_event_key,
                    'latest_sequence' => (int) $row->latest_sequence,
                    'pages_requested' => (int) $row->pages_requested,
                    'pages_processed' => (int) $row->pages_processed,
                    'pages_with_results' => (int) $row->pages_with_results,
                    'credits_reserved' => (int) $row->credits_reserved,
                    'credits_consumed' => (int) $row->credits_consumed,
                    'credits_refunded' => (int) $row->credits_refunded,
                    'credit_outcome' => $row->credit_outcome,
                    'failed_reason' => $row->failed_reason,
                    'run_id' => $row->run_id,
                    'last_event_at' => optional($row->last_event_at)->toIso8601String(),
                    'completed_at' => optional($row->completed_at)->toIso8601String(),
                    'events' => $row->events->map(fn ($event) => [
                        'id' => (int) $event->id,
                        'event_key' => (string) $event->event_key,
                        'sequence' => (int) $event->sequence,
                        'status' => $event->status,
                        'phase' => $event->phase,
                        'event_at' => optional($event->event_at)->toIso8601String(),
                    ])->values()->all(),
                ];
            })->values()->all(),
        ]);
    }
}
