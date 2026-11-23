<?php

namespace Tests\Unit;

use App\Services\BackgroundCheck\CurpToRfcService;
use PHPUnit\Framework\TestCase;

class CurpToRfcServiceTest extends TestCase
{
    public function test_calculates_rfc_13_chars_from_name_and_birth_date(): void
    {
        $service = new CurpToRfcService();
        $rfc = $service->calculate('Juan', 'Pérez', 'García', '1990-01-15');

        $this->assertSame(13, strlen($rfc));
        $this->assertStringStartsWith('PEGJ900115', $rfc);
    }

    public function test_handles_inconvenient_words_and_maria_jose_rule(): void
    {
        $service = new CurpToRfcService();
        // "JOSE" se omite cuando hay segundo nombre -> toma "ANTONIO"; "CAGA" es palabra inconveniente -> "CAGX"
        $rfc = $service->calculate('José Antonio', 'Camargo', 'Gómez', '1985-05-20');

        $this->assertSame(13, strlen($rfc));
        $this->assertStringStartsWith('CAGX850520', $rfc);
    }

    public function test_extracts_birth_date_from_curp_for_both_centuries(): void
    {
        $service = new CurpToRfcService();

        $this->assertSame('1990-01-15', $service->extractBirthDateFromCurp('PEGJ900115HDFRRN01'));
        $this->assertSame('2005-11-03', $service->extractBirthDateFromCurp('PEGJ051103HDFRRNA1'));
    }

    public function test_evaluates_identity_confidence_score(): void
    {
        $service = new CurpToRfcService();

        $high = $service->evaluateIdentityConfidence('JUAN PEREZ GARCIA', 'Juan Pérez García', true);
        $this->assertSame(100, $high);

        $curpOnly = $service->evaluateIdentityConfidence('JUAN PEREZ GARCIA', 'Por resolver vía RENAPO (PEGJ900115HDFRRN01)', true);
        $this->assertSame(100, $curpOnly);

        $mismatch = $service->evaluateIdentityConfidence('JUAN PEREZ GARCIA', 'CARLOS LOPEZ MARTINEZ', false);
        $this->assertLessThan(50, $mismatch);
    }
}
