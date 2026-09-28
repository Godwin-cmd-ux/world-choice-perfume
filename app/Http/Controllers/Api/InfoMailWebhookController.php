<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\InfoMailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Where mail for info@worldchoiceperfume.com arrives.
 *
 * Cloudflare Email Routing (or any other provider) POSTs the raw RFC 822
 * message here; only a caller holding EMAIL_RECEIVING_WEBHOOK gets an answer.
 * Nothing is rendered and no session is touched — this is a machine endpoint
 * for the mail provider, not a page.
 */
class InfoMailWebhookController extends Controller
{
    public function __construct(private InfoMailService $mails)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $expected = (string) config('info_mail.inbound_webhook');

        if ($expected === '') {
            Log::error('inbound mail rejected: EMAIL_RECEIVING_WEBHOOK is not set');

            return response()->json([
                'ok' => false,
                'error' => 'inbound mail is not configured on this server',
            ], 503);
        }

        $given = (string) ($request->header('X-Webhook-Token') ?: $request->input('token', ''));
        if ($given === '' || ! hash_equals($expected, $given)) {
            Log::warning('inbound mail rejected: bad webhook token from ' . $request->ip());

            return response()->json(['ok' => false, 'error' => 'unauthorised'], 401);
        }

        $raw = $this->rawSource($request);
        if ($raw === null || trim($raw) === '') {
            return response()->json(['ok' => false, 'error' => 'no message content'], 422);
        }

        try {
            $stored = $this->mails->storeRawEmail($raw, [
                'from' => $request->input('from'),
                'to' => $request->input('to'),
            ]);
        } catch (\Throwable $e) {
            Log::error('inbound mail could not be stored: ' . $e->getMessage());

            return response()->json(['ok' => false, 'error' => 'could not store the message'], 500);
        }

        if ($stored === null) {
            // Either a re-delivery of a Message-ID already in the mailbox or
            // a message without a usable sender. Both are not failures: the
            // provider should not retry.
            return response()->json(['ok' => true, 'stored' => false], 200);
        }

        return response()->json([
            'ok' => true,
            'stored' => true,
            'id' => $stored['id'] ?? null,
        ], 200);
    }

    /**
     * The raw message, whether the provider posted it as the body itself
     * (text/plain) or wrapped it in JSON as `raw` / `message`.
     */
    private function rawSource(Request $request): ?string
    {
        $body = $request->getContent();

        if (is_string($body) && $body !== '' && ! str_starts_with(ltrim($body), '{')) {
            return $body;
        }

        $input = $request->input();

        foreach (['raw', 'raw_email', 'message', 'source', 'email'] as $key) {
            if (isset($input[$key]) && is_string($input[$key]) && $input[$key] !== '') {
                return $input[$key];
            }
        }

        return null;
    }
}
