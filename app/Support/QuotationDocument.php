<?php

namespace App\Support;

use Carbon\CarbonInterface;
use NumberFormatter;

class QuotationDocument
{
    public static function issuer(bool $embedLogo = false): array
    {
        $logo = 'img/promesa-logo.png';

        return [
            'brand' => 'PROMESA',
            'name' => "Central de Mezclas Est\u{00e9}riles PROMESA",
            'legal_name' => config('prodifem.legal_name'),
            'rfc' => config('prodifem.rfc'),
            'address' => config('prodifem.fiscal_address'),
            'phone' => config('prodifem.phone'),
            'email' => config('prodifem.email'),
            'logo' => $embedLogo ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path($logo))) : asset($logo),
        ];
    }

    public static function metadata(array $snapshot, ?CarbonInterface $date = null): array
    {
        $date = ($date ?? now())->copy()->locale('es');

        return [
            'date' => $date->translatedFormat('j \\d\\e F \\d\\e Y'),
            'date_iso' => $date->toDateString(),
            'total_in_words' => self::amountInWords((float) ($snapshot['total'] ?? 0)),
        ];
    }

    public static function amountInWords(float $amount): ?string
    {
        if (!class_exists(NumberFormatter::class) || !is_finite($amount) || $amount < 0) return null;

        $cents = (int) round($amount * 100);
        $pesos = intdiv($cents, 100);
        $formatter = new NumberFormatter('es_MX', NumberFormatter::SPELLOUT);
        $formatter->setTextAttribute(NumberFormatter::DEFAULT_RULESET, '%spellout-cardinal-masculine');
        $words = $formatter->format($pesos);
        if ($words === false) return null;

        $words = str_replace("\u{00AD}", '', $words);
        $currency = $pesos === 1 ? ' peso' : ($pesos > 0 && $pesos % 1000000 === 0 ? ' de pesos' : ' pesos');

        return mb_strtoupper(mb_substr($words, 0, 1)).mb_substr($words, 1).$currency.' '
            .str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT).'/100 M.N.';
    }
}
