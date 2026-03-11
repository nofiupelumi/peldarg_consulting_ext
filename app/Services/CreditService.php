<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\CreditAuditLog;
use App\Models\CreditLedger;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreditService
{
    public function reserveForUpload(int $userId, int $documentId, int $pagesRequested, int $actorUserId): array
    {
        return DB::transaction(function () use ($userId, $documentId, $pagesRequested, $actorUserId) {
            $user = User::query()->lockForUpdate()->findOrFail($userId);

            if ($user->status !== 'active') {
                throw ValidationException::withMessages(['user' => 'User account is suspended.']);
            }

            $requiredCredits = max(1, (int) $pagesRequested);
            if ((int) $user->credit_balance < $requiredCredits) {
                throw ValidationException::withMessages([
                    'credit_balance' => 'Insufficient credits for this upload.',
                ]);
            }

            $settings = AppSetting::current();
            $rate = (float) $settings->unit_price_usd;
            $before = (int) $user->credit_balance;
            $after = $before - $requiredCredits;

            $this->writeLedger(
                userId: $user->id,
                documentId: $documentId,
                actionType: 'reserve',
                credits: -$requiredCredits,
                balanceBefore: $before,
                balanceAfter: $after,
                unitPriceUsd: $rate,
                amountUsd: $requiredCredits * $rate,
                actorUserId: $actorUserId,
                meta: ['pages_requested' => $pagesRequested]
            );

            $user->credit_balance = $after;
            $user->save();

            $this->writeAudit(
                actorUserId: $actorUserId,
                targetUserId: $user->id,
                eventKey: 'credit.reserve.created',
                entityType: 'document',
                entityId: $documentId,
                oldValues: ['credit_balance' => $before],
                newValues: ['credit_balance' => $after, 'reserved' => $requiredCredits]
            );

            return [
                'reserved' => $requiredCredits,
                'rate' => $rate,
            ];
        });
    }

    public function finalizeDocument(Document $document, int $pagesProcessed, int $pagesWithResults, string $status, ?string $failedReason = null): void
    {
        DB::transaction(function () use ($document, $pagesProcessed, $pagesWithResults, $status, $failedReason) {
            $doc = Document::query()->lockForUpdate()->findOrFail($document->id);
            if (in_array($doc->credit_status, ['finalized', 'failed'], true)) {
                return;
            }

            $user = User::query()->lockForUpdate()->find($doc->user_id);
            if (!$user) {
                return;
            }

            $settings = AppSetting::current();
            $rate = (float) $settings->unit_price_usd;

            $reserved = (int) $doc->credits_reserved;
            $processed = max(0, $pagesProcessed);
            $actorUserId = $doc->user_id;

            if ($status !== 'success') {
                if ($reserved > 0) {
                    $before = (int) $user->credit_balance;
                    $after = $before + $reserved;
                    $this->writeLedger(
                        userId: $user->id,
                        documentId: $doc->id,
                        actionType: 'refund',
                        credits: $reserved,
                        balanceBefore: $before,
                        balanceAfter: $after,
                        unitPriceUsd: $rate,
                        amountUsd: $reserved * $rate,
                        actorUserId: $actorUserId,
                        meta: ['reason' => 'upload_failed']
                    );
                    $user->credit_balance = $after;
                    $user->save();
                }

                $doc->credit_status = 'failed';
                $doc->credits_refunded = $reserved;
                $doc->failed_reason = $failedReason ?: 'Processing failed';
                $doc->pages_processed = $processed;
                $doc->pages_with_results = max(0, $pagesWithResults);
                $doc->status = 'failed';
                $doc->save();

                return;
            }

            $extraNeeded = max(0, $processed - $reserved);
            $refund = max(0, $reserved - $processed);

            if ($extraNeeded > 0) {
                $before = (int) $user->credit_balance;
                if ($before < $extraNeeded) {
                    throw ValidationException::withMessages([
                        'credit_balance' => 'Not enough credits to finalize this document.',
                    ]);
                }

                $after = $before - $extraNeeded;
                $this->writeLedger(
                    userId: $user->id,
                    documentId: $doc->id,
                    actionType: 'consume',
                    credits: -$extraNeeded,
                    balanceBefore: $before,
                    balanceAfter: $after,
                    unitPriceUsd: $rate,
                    amountUsd: $extraNeeded * $rate,
                    actorUserId: $actorUserId,
                    meta: ['reason' => 'processed_gt_reserved', 'pages_processed' => $processed]
                );
                $user->credit_balance = $after;
                $user->save();
            }

            if ($refund > 0) {
                $before = (int) $user->credit_balance;
                $after = $before + $refund;
                $this->writeLedger(
                    userId: $user->id,
                    documentId: $doc->id,
                    actionType: 'refund',
                    credits: $refund,
                    balanceBefore: $before,
                    balanceAfter: $after,
                    unitPriceUsd: $rate,
                    amountUsd: $refund * $rate,
                    actorUserId: $actorUserId,
                    meta: ['reason' => 'reserved_gt_processed', 'pages_processed' => $processed]
                );
                $user->credit_balance = $after;
                $user->save();
            }

            $doc->pages_processed = $processed;
            $doc->pages_with_results = max(0, $pagesWithResults);
            $doc->credits_consumed = (int) $processed;
            $doc->credits_refunded = (int) $refund;
            $doc->credit_status = 'finalized';
            $doc->status = 'complete';
            $doc->failed_reason = null;
            $doc->save();

            $this->writeAudit(
                actorUserId: $actorUserId,
                targetUserId: $user->id,
                eventKey: 'credit.finalized',
                entityType: 'document',
                entityId: $doc->id,
                oldValues: null,
                newValues: [
                    'pages_processed' => $processed,
                    'pages_with_results' => max(0, $pagesWithResults),
                    'extra_needed' => $extraNeeded,
                    'refund' => $refund,
                ]
            );
        });
    }

    public function adminAddCredits(int $targetUserId, int $credits, int $actorUserId, ?string $reason = null): array
    {
        return DB::transaction(function () use ($targetUserId, $credits, $actorUserId, $reason) {
            $user = User::query()->lockForUpdate()->findOrFail($targetUserId);
            $credits = max(0, (int) $credits);
            $before = (int) $user->credit_balance;
            $cap = (int) $user->credit_cap;
            $proposed = $before + $credits;
            if ($cap > 0 && $proposed > $cap) {
                throw ValidationException::withMessages([
                    'credit_cap' => 'Operation would exceed the user\'s credit cap. Increase cap first.',
                ]);
            }
            $after = $proposed;
            $applied = $credits;

            $this->writeLedger(
                userId: $user->id,
                documentId: null,
                actionType: 'admin_add',
                credits: $applied,
                balanceBefore: $before,
                balanceAfter: $after,
                unitPriceUsd: null,
                amountUsd: null,
                actorUserId: $actorUserId,
                meta: ['reason' => $reason]
            );

            $user->credit_balance = $after;
            $user->save();

            return ['applied' => $applied, 'balance' => $after, 'cap' => $cap];
        });
    }

    public function adminDeductCredits(int $targetUserId, int $credits, int $actorUserId, ?string $reason = null): array
    {
        return DB::transaction(function () use ($targetUserId, $credits, $actorUserId, $reason) {
            $user = User::query()->lockForUpdate()->findOrFail($targetUserId);
            $credits = max(0, (int) $credits);
            $before = (int) $user->credit_balance;
            $after = max(0, $before - $credits);
            $applied = $before - $after;

            $this->writeLedger(
                userId: $user->id,
                documentId: null,
                actionType: 'admin_deduct',
                credits: -$applied,
                balanceBefore: $before,
                balanceAfter: $after,
                unitPriceUsd: null,
                amountUsd: null,
                actorUserId: $actorUserId,
                meta: ['reason' => $reason]
            );

            $user->credit_balance = $after;
            $user->save();

            return ['applied' => $applied, 'balance' => $after];
        });
    }

    public function setCreditCap(int $targetUserId, int $cap, int $actorUserId): array
    {
        return DB::transaction(function () use ($targetUserId, $cap, $actorUserId) {
            $user = User::query()->lockForUpdate()->findOrFail($targetUserId);
            $cap = max(0, (int) $cap);
            $beforeCap = (int) $user->credit_cap;
            $beforeBalance = (int) $user->credit_balance;
            $afterBalance = $cap > 0 ? min($beforeBalance, $cap) : $beforeBalance;

            $user->credit_cap = $cap;
            $user->credit_balance = $afterBalance;
            $user->save();

            $this->writeAudit(
                actorUserId: $actorUserId,
                targetUserId: $user->id,
                eventKey: 'credit.cap.updated',
                entityType: 'user',
                entityId: $user->id,
                oldValues: ['credit_cap' => $beforeCap, 'credit_balance' => $beforeBalance],
                newValues: ['credit_cap' => $cap, 'credit_balance' => $afterBalance]
            );

            return ['cap' => $cap, 'balance' => $afterBalance];
        });
    }

    private function writeLedger(
        int $userId,
        ?int $documentId,
        string $actionType,
        int $credits,
        int $balanceBefore,
        int $balanceAfter,
        ?float $unitPriceUsd,
        ?float $amountUsd,
        ?int $actorUserId,
        ?array $meta = null,
        ?int $invoiceId = null,
    ): void {
        CreditLedger::create([
            'user_id' => $userId,
            'document_id' => $documentId,
            'invoice_id' => $invoiceId,
            'action_type' => $actionType,
            'credits' => $credits,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'unit_price_usd' => $unitPriceUsd,
            'amount_usd' => $amountUsd,
            'meta' => $meta,
            'created_by_user_id' => $actorUserId,
        ]);
    }

    private function writeAudit(
        ?int $actorUserId,
        ?int $targetUserId,
        string $eventKey,
        ?string $entityType,
        ?int $entityId,
        ?array $oldValues,
        ?array $newValues,
    ): void {
        CreditAuditLog::create([
            'actor_user_id' => $actorUserId,
            'target_user_id' => $targetUserId,
            'event_key' => $eventKey,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }
}
