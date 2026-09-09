<?php

namespace Tests\Unit;

use App\Models\Oncologicos\MedicinePresentation;
use PHPUnit\Framework\TestCase;

class MedicinePresentationTest extends TestCase
{
    public function test_remainder_conversion_uses_the_dispensing_concentration_without_rounding(): void
    {
        $this->assertSame(50.0, MedicinePresentation::remainderInMilligramsFrom(2, 250, 10));
        $this->assertSame(625.0, MedicinePresentation::remainderInMilligramsFrom(2.5, 1000, 4));
        $this->assertEqualsWithDelta(0.0617, MedicinePresentation::remainderInMilligramsFrom('0.1234', 5, 10), 0.0000001);
    }

    public function test_empty_remainders_are_zero_even_without_concentration(): void
    {
        $this->assertSame(0.0, MedicinePresentation::remainderInMilligramsFrom(0, null, null));
    }

    public function test_nonempty_remainders_without_concentration_are_not_mislabeled_as_zero_mg(): void
    {
        foreach ([[null, 10], [250, null], [250, 0], [0, 10]] as [$mg, $ml]) {
            $this->assertNull(MedicinePresentation::remainderInMilligramsFrom(2, $mg, $ml));
        }
    }

    public function test_it_reads_milligrams_from_structured_content(): void
    {
        $presentation = new MedicinePresentation([
            'contenido_valor' => 50,
            'contenido_unidad' => 'mg',
            'presentacion' => 'Ampolleta 50mg/4ml',
        ]);

        $this->assertSame(50.0, $presentation->contentInMilligrams());
    }

    public function test_it_converts_grams_to_milligrams(): void
    {
        $presentation = new MedicinePresentation([
            'contenido_valor' => 1,
            'contenido_unidad' => 'g',
            'presentacion' => 'Frasco 1g',
        ]);

        $this->assertSame(1000.0, $presentation->contentInMilligrams());
    }

    public function test_it_can_read_micrograms_from_the_presentation_text(): void
    {
        $presentation = new MedicinePresentation([
            'contenido_valor' => 0,
            'contenido_unidad' => 'mg',
            'cantidad_medicamento' => 0,
            'presentacion' => 'Frasco 35mcg',
        ]);

        $this->assertSame(0.035, $presentation->contentInMilligrams());
    }

    public function test_presentation_text_takes_priority_when_structured_content_disagrees(): void
    {
        $presentation = new MedicinePresentation([
            'contenido_valor' => 500,
            'contenido_unidad' => 'mg',
            'cantidad_medicamento' => 500,
            'presentacion' => 'Frasco 35mcg',
        ]);

        $this->assertSame(0.035, $presentation->contentInMilligrams());
    }
}
