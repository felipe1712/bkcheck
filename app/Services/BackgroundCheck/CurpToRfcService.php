<?php

namespace App\Services\BackgroundCheck;

use App\Models\Subject;

/**
 * Servicio de salvaguarda CURP -> RFC (Algoritmo oficial SAT/SHCP).
 *
 * Permite calcular el RFC de 13 posiciones (con homoclave y dígito verificador)
 * a partir de los datos validados en RENAPO, así como evaluar la coincidencia de
 * identidad para evitar falsos positivos por homonimia.
 */
class CurpToRfcService
{
    private const INCONVENIENT_WORDS = [
        'BUEI', 'BUEY', 'CACA', 'CACO', 'CAGA', 'CAGO', 'CAKA', 'CAKO',
        'COGE', 'COGI', 'COJA', 'COJE', 'COJI', 'COJO', 'COLA', 'CULO',
        'FALO', 'FETO', 'GETA', 'GUEI', 'GUEY', 'JETA', 'JOTO', 'KACA',
        'KACO', 'KAGA', 'KAGO', 'KAKA', 'KAKO', 'KOGE', 'KOGI', 'KOJA',
        'KOJE', 'KOJI', 'KOJO', 'KOLA', 'KULO', 'LILO', 'LOCA', 'LOCO',
        'LOKA', 'LOKO', 'MAME', 'MAMO', 'MEAR', 'MEAS', 'MEON', 'MIAR',
        'MION', 'MOCO', 'MOKO', 'MULA', 'MULO', 'NACA', 'NACO', 'PEDA',
        'PEDO', 'PENE', 'PIPI', 'PITO', 'POPO', 'PUTA', 'PUTO', 'QULO',
        'RATA', 'ROBA', 'ROBE', 'ROBO', 'RUIN', 'SENO', 'TETA', 'VACA',
        'VAGA', 'VAGO', 'VAKA', 'VUEI', 'VUEY', 'WUEI', 'WUEY',
    ];

    private const IGNORED_PARTICLES = [
        'DE', 'DEL', 'LA', 'LAS', 'LOS', 'Y', 'MC', 'MAC', 'VON', 'VAN',
    ];

    private const HOMOCLAVE_TABLE = [
        ' ' => '00', '0' => '00', '1' => '01', '2' => '02', '3' => '03',
        '4' => '04', '5' => '05', '6' => '06', '7' => '07', '8' => '08',
        '9' => '09', '&' => '10', 'A' => '11', 'B' => '12', 'C' => '13',
        'D' => '14', 'E' => '15', 'F' => '16', 'G' => '17', 'H' => '18',
        'I' => '19', 'J' => '21', 'K' => '22', 'L' => '23', 'M' => '24',
        'N' => '25', 'O' => '26', 'P' => '27', 'Q' => '28', 'R' => '29',
        'S' => '32', 'T' => '33', 'U' => '34', 'V' => '35', 'W' => '36',
        'X' => '37', 'Y' => '38', 'Z' => '39', 'Ñ' => '40',
    ];

    private const HOMOCLAVE_CHARS = '123456789ABCDEFGHIJKLMNPQRSTUVWXYZ';

    private const CHECKSUM_TABLE = [
        '0' => 0,  '1' => 1,  '2' => 2,  '3' => 3,  '4' => 4,  '5' => 5,
        '6' => 6,  '7' => 7,  '8' => 8,  '9' => 9,  'A' => 10, 'B' => 11,
        'C' => 12, 'D' => 13, 'E' => 14, 'F' => 15, 'G' => 16, 'H' => 17,
        'I' => 18, 'J' => 19, 'K' => 20, 'L' => 21, 'M' => 22, 'N' => 23,
        '&' => 24, 'O' => 25, 'P' => 26, 'Q' => 27, 'R' => 28, 'S' => 29,
        'T' => 30, 'U' => 31, 'V' => 32, 'W' => 33, 'X' => 34, 'Y' => 35,
        'Z' => 36, ' ' => 37, 'Ñ' => 38,
    ];

    /**
     * Calcula el RFC completo de 13 caracteres (incluyendo homoclave y dígito verificador)
     * a partir de nombre(s), apellidos y fecha de nacimiento (YYYY-MM-DD) o CURP.
     */
    public function calculate(
        string $nombre,
        string $paterno,
        string $materno,
        string $fechaNacimientoYmd
    ): string {
        $nombreClean  = $this->normalizeText($nombre);
        $paternoClean = $this->normalizeText($paterno);
        $maternoClean = $this->normalizeText($materno);

        $paternoWord = $this->stripIgnoredParticles($paternoClean);
        $maternoWord = $this->stripIgnoredParticles($maternoClean);
        $nombreWord  = $this->stripIgnoredParticles($this->selectEffectiveFirstName($nombreClean));

        // 1. Primeras 4 letras
        $c1 = mb_substr($paternoWord, 0, 1);
        $c2 = $this->firstInternalVowel(mb_substr($paternoWord, 1));
        $c3 = $maternoWord !== '' ? mb_substr($maternoWord, 0, 1) : 'X';
        $c4 = mb_substr($nombreWord, 0, 1);

        $prefix = $c1 . $c2 . $c3 . $c4;
        if (in_array($prefix, self::INCONVENIENT_WORDS, true)) {
            $prefix = mb_substr($prefix, 0, 3) . 'X';
        }

        // 2. Fecha YYMMDD
        $digits = preg_replace('/\D/', '', $fechaNacimientoYmd);
        $yy = substr($digits, 2, 2);
        $mm = substr($digits, 4, 2);
        $dd = substr($digits, 6, 2);
        $rfc10 = $prefix . $yy . $mm . $dd;

        // 3. Homoclave (2 caracteres)
        $fullNameForHomo = trim("{$paternoClean} {$maternoClean} {$nombreClean}");
        $homoclave = $this->computeHomoclave($fullNameForHomo);

        // 4. Dígito verificador (1 carácter)
        $rfc12 = $rfc10 . $homoclave;
        $checkDigit = $this->computeCheckDigit($rfc12);

        return $rfc12 . $checkDigit;
    }

    /**
     * Extrae la fecha de nacimiento YYYY-MM-DD directamente de una CURP válida (posiciones 4-9).
     */
    public function extractBirthDateFromCurp(string $curp): ?string
    {
        $curp = strtoupper(trim($curp));
        if (strlen($curp) !== 18) {
            return null;
        }
        $yy = substr($curp, 4, 2);
        $mm = substr($curp, 6, 2);
        $dd = substr($curp, 8, 2);

        // Posición 16 (índice 16) de la CURP indica siglo: dígito (0-9) => 1900s, letra (A-Z) => 2000s
        $centuryChar = $curp[16] ?? '0';
        $century = ctype_digit($centuryChar) ? '19' : '20';

        return "{$century}{$yy}-{$mm}-{$dd}";
    }

    /**
     * Evalúa el nivel de confianza de identidad (0-100) cruzando RENAPO y SAT.
     */
    public function evaluateIdentityConfidence(
        string $renapoFullName,
        ?string $subjectFullName,
        bool $satRfcValid
    ): int {
        $score = 50; // Base por CURP validada en RENAPO

        if ($satRfcValid) {
            $score += 35;
        }

        if (!empty($subjectFullName) && !str_starts_with($subjectFullName, 'Por resolver')) {
            similar_text(
                $this->normalizeText($renapoFullName),
                $this->normalizeText($subjectFullName),
                $percent
            );
            if ($percent >= 85) {
                $score += 15;
            } elseif ($percent >= 60) {
                $score += 5;
            } else {
                $score -= 20; // Posible homonimia o dato inconsistente
            }
        } else {
            // Si el abogado solo proporcionó la CURP y RENAPO + SAT validaron
            $score += $satRfcValid ? 15 : 0;
        }

        return max(0, min(100, $score));
    }

    private function normalizeText(string $text): string
    {
        $text = mb_strtoupper(trim($text), 'UTF-8');
        $replacements = [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U',
            'À' => 'A', 'È' => 'E', 'Ì' => 'I', 'Ò' => 'O', 'Ù' => 'U',
        ];
        $text = strtr($text, $replacements);
        $text = preg_replace('/[^A-ZÑ ]/u', '', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    private function stripIgnoredParticles(string $text): string
    {
        $parts = array_values(array_filter(explode(' ', $text)));
        $filtered = array_values(array_filter($parts, function ($word) {
            return !in_array($word, self::IGNORED_PARTICLES, true);
        }));
        return implode('', $filtered ?: $parts);
    }

    private function selectEffectiveFirstName(string $nombre): string
    {
        $parts = array_values(array_filter(explode(' ', $nombre)));
        if (count($parts) > 1 && in_array($parts[0], ['JOSE', 'MARIA', 'J', 'MA'], true)) {
            return $parts[1];
        }
        return $parts[0] ?? '';
    }

    private function firstInternalVowel(string $str): string
    {
        $len = mb_strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $ch = mb_substr($str, $i, 1);
            if (in_array($ch, ['A', 'E', 'I', 'O', 'U'], true)) {
                return $ch;
            }
        }
        return 'X';
    }

    private function computeHomoclave(string $fullName): string
    {
        $digits = '0';
        $chars = preg_split('//u', $fullName, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($chars as $ch) {
            $digits .= self::HOMOCLAVE_TABLE[$ch] ?? '00';
        }

        $sum = 0;
        $len = strlen($digits);
        for ($i = 0; $i < $len - 1; $i++) {
            $pair = (int) substr($digits, $i, 2);
            $next = (int) $digits[$i + 1];
            $sum += $pair * $next;
        }

        $rem = $sum % 1000;
        $q = intdiv($rem, 34);
        $r = $rem % 34;

        $table = self::HOMOCLAVE_CHARS;
        return ($table[$q] ?? '1') . ($table[$r] ?? '1');
    }

    private function computeCheckDigit(string $rfc12): string
    {
        $sum = 0;
        $chars = preg_split('//u', $rfc12, -1, PREG_SPLIT_NO_EMPTY);
        for ($i = 0; $i < 12; $i++) {
            $ch = $chars[$i] ?? ' ';
            $val = self::CHECKSUM_TABLE[$ch] ?? 0;
            $sum += $val * (13 - $i);
        }

        $rem = $sum % 11;
        if ($rem === 0) {
            return '0';
        }
        $diff = 11 - $rem;
        if ($diff === 10) {
            return 'A';
        }
        return (string) $diff;
    }
}
