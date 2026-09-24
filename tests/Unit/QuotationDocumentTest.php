<?php

namespace Tests\Unit;

use App\Support\QuotationDocument;
use Carbon\Carbon;
use NumberFormatter;
use PHPUnit\Framework\TestCase;

class QuotationDocumentTest extends TestCase
{
    public function test_amount_in_words_uses_pesos_and_two_digit_cents(): void
    {
        if (!class_exists(NumberFormatter::class)) {
            $this->assertNull(QuotationDocument::amountInWords(351672));
            return;
        }

        foreach ([
            [0, 'Cero pesos 00/100 M.N.'],
            [1, 'Un peso 00/100 M.N.'],
            [21.01, "Veinti\u{00FA}n pesos 01/100 M.N."],
            [351672, 'Trescientos cincuenta y un mil seiscientos setenta y dos pesos 00/100 M.N.'],
            [1000000, "Un mill\u{00F3}n de pesos 00/100 M.N."],
            [0.995, 'Un peso 00/100 M.N.'],
        ] as [$amount, $expected]) {
            $this->assertSame($expected, QuotationDocument::amountInWords($amount));
        }
        $maximum = QuotationDocument::amountInWords(999999999.99);
        $this->assertStringEndsWith('pesos 99/100 M.N.', $maximum);
        $this->assertStringNotContainsString("\u{00AD}", $maximum);
    }

    public function test_document_date_is_spanish_and_does_not_mutate_the_original_date(): void
    {
        $date = Carbon::parse('2026-09-17 10:00:00')->locale('en');
        $document = QuotationDocument::metadata(['total' => 351672], $date);
        $this->assertSame('17 de septiembre de 2026', $document['date']);
        $this->assertSame('2026-09-17', $document['date_iso']);
        $this->assertSame('en', $date->locale());
        $this->assertNull(QuotationDocument::amountInWords(-1));
        $this->assertNull(QuotationDocument::amountInWords(INF));
    }
}
