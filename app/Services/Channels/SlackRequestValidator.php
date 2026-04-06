<?php

namespace App\Services\Channels;

use Illuminate\Http\Request;

class SlackRequestValidator
{
    private const MAX_AGE_SECONDS = 300;

    public function isValid(Request $request, string $signingSecret): bool
    {
        $timestamp = $request->header('X-Slack-Request-Timestamp');
        $signature = $request->header('X-Slack-Signature');

        if (! is_string($timestamp) || ! is_string($signature)) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > self::MAX_AGE_SECONDS) {
            return false;
        }

        $body = $request->getContent();
        $baseline = 'v0:'.$timestamp.':'.$body;
        $hash = hash_hmac('sha256', $baseline, $signingSecret, false);
        $expected = 'v0='.$hash;

        return hash_equals($expected, $signature);
    }
}
