<?php

namespace App\Services\Channels;

use Illuminate\Http\Request;

class DiscordInteractionVerifier
{
    public function isValid(Request $request, string $publicKeyHex): bool
    {
        if (! function_exists('sodium_crypto_sign_verify_detached')) {
            return false;
        }

        $signature = $request->header('X-Signature-Ed25519');
        $timestamp = $request->header('X-Signature-Timestamp');
        if (! is_string($signature) || ! is_string($timestamp)) {
            return false;
        }

        $signatureBin = hex2bin($signature);
        $publicKeyBin = hex2bin($publicKeyHex);
        if ($signatureBin === false || $publicKeyBin === false) {
            return false;
        }

        $message = $timestamp.$request->getContent();

        return sodium_crypto_sign_verify_detached($signatureBin, $message, $publicKeyBin);
    }
}
