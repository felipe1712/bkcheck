<?php

namespace App\Services\Pld;

/**
 * Serializador JSON Canónico conforme a RFC 8785 (JSON Canonicalization Scheme - JCS)
 * y cálculo de Hash SHA-256 para la cadena inmutable de versiones de expedientes PLD.
 */
class JcsCanonicalizer
{
    /**
     * Convierte cualquier estructura PHP (array/escalar) a su representación JSON canónica JCS (RFC 8785):
     * - Propiedades de objetos ordenadas lexicográficamente por código UTF-16/UTF-8.
     * - Sin espacios en blanco entre tokens.
     * - Sin escape de barras diagonales ni caracteres Unicode imprimibles.
     */
    public static function canonicalize(mixed $value): string
    {
        if (is_null($value) || is_bool($value) || is_int($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (is_float($value)) {
            if (is_nan($value) || is_infinite($value)) {
                throw new \InvalidArgumentException('JCS (RFC 8785) no permite NaN ni Infinity.');
            }
            if ($value == 0.0) {
                return '0';
            }
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
        }

        if (is_string($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (is_object($value)) {
            $value = (array) $value;
        }

        if (is_array($value)) {
            if (array_is_list($value)) {
                $elements = array_map([self::class, 'canonicalize'], $value);
                return '[' . implode(',', $elements) . ']';
            }

            // Objeto asociativo: ordenar claves lexicográficamente (RFC 8785 §3.2.3)
            $keys = array_keys($value);
            usort($keys, static fn($a, $b) => strcmp((string) $a, (string) $b));

            $pairs = [];
            foreach ($keys as $k) {
                $encodedKey = json_encode((string) $k, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $encodedVal = self::canonicalize($value[$k]);
                $pairs[] = $encodedKey . ':' . $encodedVal;
            }

            return '{' . implode(',', $pairs) . '}';
        }

        return 'null';
    }

    /**
     * Calcula el SHA-256 hexadecimal en minúsculas del JSON canónico JCS (RFC 8785).
     * Excluye el campo `hash_version` del propio documento si estuviera presente en raíz o en `meta_version`.
     */
    public static function hashPayload(array $payload): string
    {
        $clean = self::stripHashVersion($payload);
        $canonicalJson = self::canonicalize($clean);
        return hash('sha256', $canonicalJson);
    }

    /**
     * Remueve `hash_version` antes de canonicalizar para que el hash sea verificable
     * sobre el expediente entregado al cliente.
     */
    public static function stripHashVersion(array $payload): array
    {
        unset($payload['hash_version']);
        if (isset($payload['meta_version']) && is_array($payload['meta_version'])) {
            unset($payload['meta_version']['hash_version']);
        }
        return $payload;
    }
}
