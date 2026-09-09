<?php

namespace Tests\Feature;

use Tests\TestCase;

class SolicitudColumnFiltersTest extends TestCase
{
    public function test_approval_and_next_process_use_the_same_filters_as_the_data_columns(): void
    {
        $html = view('admin.solicitudes._table-header')->render();
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="UTF-8"><table><thead>'.$html.'</thead></table>');
        $headers = $document->getElementsByTagName('th');
        $this->assertSame(19, $headers->length);
        foreach (range(0, 18) as $index) {
            $header = $headers->item($index);
            $filterable = $index < 9 || in_array($index, [10, 11], true);
            $this->assertSame($filterable, $header->hasAttribute('data-force-column-filter'));
            $this->assertSame(! $filterable, $header->hasAttribute('data-command-column'));
        }
    }
}
