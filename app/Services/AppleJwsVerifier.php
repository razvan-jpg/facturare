<?php

namespace App\Services;

use RuntimeException;

/**
 * Verifică JWS StoreKit 2 / App Store Server Notifications (ES256 + lanț x5c → Apple Root CA G3).
 */
class AppleJwsVerifier
{
    /**
     * @return array{header: array<string, mixed>, payload: array<string, mixed>}
     */
    public function decodeAndVerify(string $jws): array
    {
        $parts = explode('.', $jws);
        if (count($parts) !== 3) {
            throw new RuntimeException('JWS invalid.');
        }

        [$h64, $p64, $s64] = $parts;
        $headerJson = $this->b64urlDecode($h64);
        $payloadJson = $this->b64urlDecode($p64);
        $header = json_decode($headerJson, true);
        $payload = json_decode($payloadJson, true);

        if (! is_array($header) || ! is_array($payload)) {
            throw new RuntimeException('JWS payload invalid.');
        }

        if (($header['alg'] ?? '') !== 'ES256') {
            throw new RuntimeException('Algoritm JWS nesuportat.');
        }

        $skip = (bool) config('dateconta.ios_subscription.allow_unverified', false);
        if (! $skip) {
            $this->verifySignature($h64.'.'.$p64, $s64, $header);
        }

        return ['header' => $header, 'payload' => $payload];
    }

    /**
     * @param  array<string, mixed>  $header
     */
    private function verifySignature(string $signingInput, string $s64, array $header): void
    {
        $x5c = $header['x5c'] ?? null;
        if (! is_array($x5c) || $x5c === []) {
            throw new RuntimeException('JWS fără lanț de certificate (x5c).');
        }

        $certs = [];
        foreach ($x5c as $derB64) {
            if (! is_string($derB64) || strlen($derB64) > 16384) {
                throw new RuntimeException('Certificat x5c invalid.');
            }
            $pem = "-----BEGIN CERTIFICATE-----\n"
                .chunk_split($derB64, 64, "\n")
                ."-----END CERTIFICATE-----\n";
            $res = openssl_x509_read($pem);
            if ($res === false) {
                throw new RuntimeException('Nu pot citi certificatul x5c.');
            }
            $certs[] = $res;
        }

        $rootPath = (string) config('dateconta.ios_subscription.apple_root_ca_path');
        if ($rootPath === '' || ! is_readable($rootPath)) {
            throw new RuntimeException('Apple Root CA G3 lipsește pe server.');
        }
        $rootPem = file_get_contents($rootPath);
        $root = openssl_x509_read($rootPem ?: '');
        if ($root === false) {
            throw new RuntimeException('Apple Root CA G3 invalid.');
        }

        // Pin root fingerprint (SHA-256) pentru CA-ul local.
        $expected = strtoupper(str_replace(':', '', (string) config(
            'dateconta.ios_subscription.apple_root_ca_sha256',
            '63343ABFB89A6A03EBB57E9B3F5FA7BE7C4F5C756F3017B3A8C488C3653E9179'
        )));
        $fp = strtoupper(str_replace(':', '', (string) openssl_x509_fingerprint($root, 'sha256')));
        if ($fp !== $expected) {
            throw new RuntimeException('Root CA nu corespunde Apple Root CA - G3.');
        }

        // Verifică leaf → intermediate(s); ultimul din lanț trebuie semnat de Apple Root CA G3
        // (sau să fie chiar root-ul Apple).
        for ($i = 0; $i < count($certs) - 1; $i++) {
            $parentPub = openssl_pkey_get_public($certs[$i + 1]);
            if ($parentPub === false || openssl_x509_verify($certs[$i], $parentPub) !== 1) {
                throw new RuntimeException('Lanț certificat Apple invalid.');
            }
        }
        $last = $certs[count($certs) - 1];
        $lastFp = strtoupper(str_replace(':', '', (string) openssl_x509_fingerprint($last, 'sha256')));
        if ($lastFp !== $expected) {
            $rootPub = openssl_pkey_get_public($root);
            if ($rootPub === false || openssl_x509_verify($last, $rootPub) !== 1) {
                throw new RuntimeException('Lanțul nu se ancorează în Apple Root CA - G3.');
            }
        }

        $leafPub = openssl_pkey_get_public($certs[0]);
        if ($leafPub === false) {
            throw new RuntimeException('Cheie publică leaf invalidă.');
        }

        $sigRaw = $this->b64urlDecode($s64);
        $sigDer = $this->ecdsaRawToDer($sigRaw);
        $ok = openssl_verify($signingInput, $sigDer, $leafPub, OPENSSL_ALGO_SHA256);
        if ($ok !== 1) {
            throw new RuntimeException('Semnătură JWS invalidă.');
        }
    }

    private function b64urlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        $decoded = base64_decode(strtr($data, '-_', '+/'), true);
        if ($decoded === false) {
            throw new RuntimeException('Base64url invalid.');
        }

        return $decoded;
    }

    /** Conversie semnătură ECDSA P-256 raw (r||s, 64 bytes) → DER. */
    private function ecdsaRawToDer(string $raw): string
    {
        if (strlen($raw) === 64) {
            $r = substr($raw, 0, 32);
            $s = substr($raw, 32, 32);

            return $this->encodeDerEcdsa($r, $s);
        }

        // Unele librării trimit deja DER.
        return $raw;
    }

    private function encodeDerEcdsa(string $r, string $s): string
    {
        $r = ltrim($r, "\x00");
        $s = ltrim($s, "\x00");
        if ($r === '' || (ord($r[0]) & 0x80) !== 0) {
            $r = "\x00".$r;
        }
        if ($s === '' || (ord($s[0]) & 0x80) !== 0) {
            $s = "\x00".$s;
        }
        $seq = "\x02".chr(strlen($r)).$r."\x02".chr(strlen($s)).$s;

        return "\x30".chr(strlen($seq)).$seq;
    }
}
