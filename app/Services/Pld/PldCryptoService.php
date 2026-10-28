<?php

namespace App\Services\Pld;

/**
 * Capa de Cifrado Autenticado (AES-256-GCM) e Indexación Ciega (HMAC-SHA256) para el Módulo PLD/EBR (M2).
 *
 * DEUDA TÉCNICA DOCUMENTADA (MVP):
 * Actualmente obtiene las llaves maestras desde variables de entorno (`config('pld.encryption_key')` y
 * `config('pld.bindex_key')`). El diseño está desacoplado en los métodos `resolveEncryptionKey()` y
 * `resolveBlindIndexKey()` para sustituir el backend por AWS KMS o HashiCorp Vault sin modificar ningún
 * modelo, servicio o controlador.
 */
class PldCryptoService
{
    private const CIPHER = 'aes-256-gcm';
    private const NONCE_BYTES = 12; // 96 bits
    private const TAG_BYTES   = 16; // 128 bits

    /**
     * Normaliza un RFC (mayúsculas, sin espacios ni guiones).
     */
    public function normalizeRfc(?string $rfc): string
    {
        if (empty($rfc)) {
            return '';
        }
        return strtoupper(preg_replace('/[^A-Z0-9Ñ&]/i', '', trim($rfc)));
    }

    /**
     * Cifra un texto en claro usando AES-256-GCM (nonce de 96 bits, tag de 128 bits).
     * Retorna JSON codificado en base64 con {v, nonce, tag, ciphertext}.
     */
    public function encrypt(?string $plaintext): string
    {
        $text = (string) $plaintext;
        $key = $this->resolveEncryptionKey();
        $nonce = random_bytes(self::NONCE_BYTES);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $text,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            '',
            self::TAG_BYTES
        );

        if ($ciphertext === false) {
            throw new \RuntimeException('Error al cifrar con AES-256-GCM.');
        }

        $envelope = [
            'v'          => 1,
            'alg'        => 'AES-256-GCM',
            'nonce'      => base64_encode($nonce),
            'tag'        => base64_encode($tag),
            'ciphertext' => base64_encode($ciphertext),
        ];

        return base64_encode(json_encode($envelope, JSON_UNESCAPED_SLASHES));
    }

    /**
     * Descifra un valor previamente cifrado con AES-256-GCM y verifica su tag de autenticación de 128 bits.
     */
    public function decrypt(?string $encodedEnvelope): ?string
    {
        if (empty($encodedEnvelope)) {
            return null;
        }

        $decodedJson = base64_decode($encodedEnvelope, true);
        if ($decodedJson === false) {
            throw new \RuntimeException('Sobre cifrado inválido (base64 corrupto).');
        }

        $envelope = json_decode($decodedJson, true);
        if (!is_array($envelope) || !isset($envelope['nonce'], $envelope['tag'], $envelope['ciphertext'])) {
            throw new \RuntimeException('Estructura de sobre AES-256-GCM inválida.');
        }

        $nonce      = base64_decode($envelope['nonce'], true);
        $tag        = base64_decode($envelope['tag'], true);
        $ciphertext = base64_decode($envelope['ciphertext'], true);

        if ($nonce === false || strlen($nonce) !== self::NONCE_BYTES || $tag === false || strlen($tag) !== self::TAG_BYTES) {
            throw new \RuntimeException('Dimensiones de nonce (96b) o tag (128b) inválidas.');
        }

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $this->resolveEncryptionKey(),
            OPENSSL_RAW_DATA,
            $nonce,
            $tag
        );

        if ($plaintext === false) {
            throw new \RuntimeException('Fallo de autenticación GCM al descifrar (tag inválido o llave incorrecta).');
        }

        return $plaintext;
    }

    /**
     * Alias de encrypt() para identificadores sensibles (RFC, secretos de webhook).
     */
    public function encryptIdentifier(?string $plaintext): string
    {
        return $this->encrypt($plaintext);
    }

    /**
     * Alias de decrypt() para identificadores sensibles.
     */
    public function decryptIdentifier(?string $encodedEnvelope): ?string
    {
        return $this->decrypt($encodedEnvelope);
    }

    /**
     * Calcula el Blind Index determinístico HMAC-SHA256 del RFC normalizado,
     * usando una llave de indexación estrictamente separada de la llave de cifrado.
     */
    public function blindIndexRfc(?string $rfc): string
    {
        $normalized = $this->normalizeRfc($rfc);
        return hash_hmac('sha256', $normalized, $this->resolveBlindIndexKey());
    }

    /**
     * Alias de blindIndexRfc().
     */
    public function blindIndex(?string $rfc): string
    {
        return $this->blindIndexRfc($rfc);
    }

    /**
     * Firma un payload de webhook con HMAC-SHA256 incluyendo el timestamp: "{timestamp}.{rawJson}".
     */
    public function signWebhookPayload(string $rawJson, string $secret, ?int $timestamp = null): array
    {
        $ts = $timestamp ?? time();
        $sig = hash_hmac('sha256', $ts . '.' . $rawJson, $secret);
        return [
            'timestamp' => $ts,
            'signature' => $sig,
        ];
    }

    /**
     * Verifica la firma HMAC-SHA256 de un webhook y rechaza mensajes con más de 5 minutos (300 segundos) de antigüedad.
     */
    public function verifyWebhookSignature(string $rawJson, string $secret, int $timestamp, string $signature, ?int $nowTimestamp = null): bool
    {
        $now = $nowTimestamp ?? time();
        if (abs($now - $timestamp) > 300) {
            return false; // Rechazado por ventana > 5 minutos
        }

        $cleanSig = str_starts_with($signature, 'sha256=') ? substr($signature, 7) : $signature;
        $expected = $this->signWebhookPayload($rawJson, $secret, $timestamp)['signature'];
        return hash_equals($expected, $cleanSig);
    }

    /**
     * Obtiene la llave de cifrado de 32 bytes (256 bits).
     */
    protected function resolveEncryptionKey(): string
    {
        $raw = (string) config('pld.encryption_key', '');
        if (str_starts_with($raw, 'base64:')) {
            $decoded = base64_decode(substr($raw, 7), true);
            if ($decoded !== false && strlen($decoded) >= 32) {
                return substr($decoded, 0, 32);
            }
        }
        return hash('sha256', $raw, true);
    }

    /**
     * Obtiene la llave de Blind Index de 32 bytes, garantizando que sea distinta a la de cifrado.
     */
    protected function resolveBlindIndexKey(): string
    {
        $raw = (string) config('pld.bindex_key', '');
        return hash('sha256', 'bindex:' . $raw, true);
    }
}
