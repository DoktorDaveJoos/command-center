<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\InboxItemSource;
use App\Enums\InboxItemStatus;
use App\Http\Controllers\Controller;
use App\Models\InboxItem;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ResendInboundController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        // Verify webhook signature
        if (! $this->verifySignature($request)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $payload = $request->all();

        // Extract the workspace token from the "to" address
        // Format: inbox+{token}@domain.com
        $token = $this->extractWorkspaceToken($payload);

        if (! $token) {
            Log::warning('Resend webhook: Could not extract workspace token', [
                'to' => $payload['to'] ?? null,
            ]);

            return response()->json(['status' => 'ok']);
        }

        // Look up workspace by token
        $workspace = Workspace::where('inbound_email_token', $token)->first();

        if (! $workspace) {
            Log::warning('Resend webhook: Workspace not found for token', [
                'token' => $token,
            ]);

            return response()->json(['status' => 'ok']);
        }

        // Create the inbox item
        InboxItem::create([
            'workspace_id' => $workspace->id,
            'source' => InboxItemSource::Email,
            'raw_subject' => $payload['subject'] ?? null,
            'raw_content' => $this->extractContent($payload),
            'received_at' => now(),
            'status' => InboxItemStatus::New,
        ]);

        return response()->json(['status' => 'ok']);
    }

    private function verifySignature(Request $request): bool
    {
        $secret = config('services.resend.webhook_secret');

        // If no secret is configured, skip verification (for testing)
        if (empty($secret)) {
            return true;
        }

        $signature = $request->header('svix-signature');

        if (! $signature) {
            return false;
        }

        $timestamp = $request->header('svix-timestamp');
        $webhookId = $request->header('svix-id');

        if (! $timestamp || ! $webhookId) {
            return false;
        }

        // Build the signed payload
        $signedPayload = "{$webhookId}.{$timestamp}.".$request->getContent();

        // Extract signature versions
        $signatures = explode(' ', $signature);
        foreach ($signatures as $sig) {
            if (str_starts_with($sig, 'v1,')) {
                $expectedSignature = substr($sig, 3);
                $computedSignature = base64_encode(hash_hmac('sha256', $signedPayload, base64_decode($secret), true));

                if (hash_equals($expectedSignature, $computedSignature)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Extract workspace token from the "to" address.
     * Expected format: inbox+{token}@domain.com
     *
     * @param  array<string, mixed>  $payload
     */
    private function extractWorkspaceToken(array $payload): ?string
    {
        $to = $payload['to'] ?? null;

        if (! $to) {
            return null;
        }

        // Handle array of recipients
        if (is_array($to)) {
            $to = $to[0] ?? null;
        }

        if (! is_string($to)) {
            return null;
        }

        // Match inbox+{token}@domain pattern
        if (preg_match('/^inbox\+([a-zA-Z0-9]+)@/', $to, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract content from the webhook payload.
     *
     * @param  array<string, mixed>  $payload
     */
    private function extractContent(array $payload): string
    {
        // Prefer HTML content, fall back to text
        return $payload['html'] ?? $payload['text'] ?? '';
    }
}
