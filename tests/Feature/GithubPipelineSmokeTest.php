<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use App\Services\CreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GithubPipelineSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_results_then_callback_is_authenticated_and_idempotent(): void
    {
        config()->set('services.extractor.token', 'test-token');
        config()->set('services.extractor.secret', 'test-secret');

        /** @var User $user */
        $user = User::factory()->create([
            'company_name' => 'Acme Ltd',
            'status' => 'active',
            'credit_balance' => 50,
            'credit_cap' => 0,
            'must_change_password' => false,
            'is_admin' => false,
        ]);

        /** @var Document $doc */
        $doc = Document::create([
            'user_id' => $user->id,
            'request_id' => '11111111-1111-1111-1111-111111111111',
            'filename' => 'test.pdf',
            'path' => 'convocation/test.pdf',
            'status' => 'processing',
            'pages_requested' => 5,
            'page_start' => 1,
            'page_end' => 5,
            'credits_reserved' => 0,
            'credit_status' => 'none',
        ]);

        /** @var CreditService $credit */
        $credit = app(CreditService::class);
        $reserve = $credit->reserveForUpload(
            userId: $user->id,
            documentId: $doc->id,
            pagesRequested: 5,
            actorUserId: $user->id,
        );
        $this->assertSame(5, (int) $reserve['reserved']);

        $doc->refresh();
        $doc->credits_reserved = 5;
        $doc->credit_status = 'reserved';
        $doc->save();

        $user->refresh();
        $this->assertSame(45, (int) $user->credit_balance);

        // Simulate CI results upload (should finalize credits once)
        $upload = $this->withHeaders([
            'X-Extractor-Token' => 'test-token',
        ])->post('/api/github/upload-results', [
            'doc_id' => $doc->id,
            'request_id' => $doc->request_id,
            'pages_processed' => 3,
            'pages_with_results' => 2,
        ]);

        $upload->assertOk();

        $doc->refresh();
        $user->refresh();

        $this->assertSame('finalized', $doc->credit_status);
        $this->assertSame('complete', $doc->status);
        $this->assertSame(3, (int) $doc->credits_consumed);
        $this->assertSame(2, (int) $doc->credits_refunded);
        $this->assertSame(47, (int) $user->credit_balance);

        // Callback requires BOTH token and signature, but must be idempotent for already-finalized docs.
        $payload = [
            'doc_id' => (string) $doc->id,
            'request_id' => (string) $doc->request_id,
            'status' => 'success',
            'counts' => [
                'pages_processed' => 3,
                'pages_with_results' => 2,
            ],
        ];
        $body = json_encode($payload);
        $sig = hash_hmac('sha256', (string) $body, 'test-secret');

        $cb = $this->withHeaders([
            'X-Extractor-Token' => 'test-token',
            'X-Extractor-Signature' => $sig,
        ])->postJson('/api/github/callback', $payload);

        $cb->assertOk();

        $doc->refresh();
        $user->refresh();
        $this->assertSame('finalized', $doc->credit_status);
        $this->assertSame(47, (int) $user->credit_balance);
    }

    public function test_callback_rejects_missing_signature(): void
    {
        config()->set('services.extractor.token', 'test-token');
        config()->set('services.extractor.secret', 'test-secret');

        $user = User::factory()->create([
            'company_name' => 'Sig Test Ltd',
            'status' => 'active',
            'credit_balance' => 10,
            'credit_cap' => 0,
            'must_change_password' => false,
            'is_admin' => false,
        ]);

        $doc = Document::create([
            'user_id' => $user->id,
            'request_id' => '22222222-2222-2222-2222-222222222222',
            'filename' => 'test.pdf',
            'path' => 'convocation/test.pdf',
            'status' => 'processing',
            'pages_requested' => 1,
            'page_start' => 1,
            'page_end' => 1,
            'credits_reserved' => 1,
            'credit_status' => 'reserved',
        ]);

        $payload = ['doc_id' => (string) $doc->id, 'request_id' => (string) $doc->request_id, 'status' => 'success'];

        $res = $this->withHeaders([
            'X-Extractor-Token' => 'test-token',
        ])->postJson('/api/github/callback', $payload);

        $res->assertStatus(401);
    }
}
