<?php

namespace App\Services;

use App\Models\DocumentSignature;

class DocumentSignatureService
{
    public function currentKid(): string
    {
        return (string) config('document-signatures.current_kid', 'v1');
    }

    public function secretForKid(string $kid): string
    {
        $keys = (array) config('document-signatures.keys', []);
        $secret = (string) ($keys[$kid] ?? '');

        if ($secret === '') {
            throw new \RuntimeException(
                "No signature secret configured for key ID: {$kid}."
            );
        }

        return $secret;
    }

    public function canonicalJson(array $payload): string
    {
        $sorted = $this->ksortRecursive($payload);

        $json = json_encode($sorted, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new \RuntimeException('Failed to encode payload to JSON.');
        }

        return $json;
    }

    public function signPayload(array $payload, string $kid): string
    {
        $secret = $this->secretForKid($kid);
        $data = $this->canonicalJson($payload);
        $mac = hash_hmac('sha256', $data, $secret, true);

        return $this->base64UrlEncode($mac);
    }

    public function verify(DocumentSignature $signature): bool
    {
        try {
            $expected = $this->signPayload((array) $signature->payload, (string) $signature->kid);
        } catch (\Throwable) {
            return false;
        }

        return hash_equals($expected, (string) $signature->signature);
    }

    private function base64UrlEncode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private function ksortRecursive(array $value): array
    {
        foreach ($value as $k => $v) {
            if (is_array($v)) {
                $value[$k] = $this->ksortRecursive($v);
            }
        }
        ksort($value);

        return $value;
    }
}
