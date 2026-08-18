<?php

namespace Tests\Unit;

use App\Models\Oncologicos\MedicinePresentation;
use PHPUnit\Framework\TestCase;

class MedicinePresentationTest extends TestCase
{
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
