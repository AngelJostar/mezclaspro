<?php

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use Tests\TestCase;

class PrivacyPageTest extends TestCase
{
    public function test_public_notice_has_branded_navigation_and_the_original_update_date(): void
    {
        $response = $this->get('/aviso-de-privacidad')->assertOk()
            ->assertSee('Aviso de privacidad | PROMESA')
            ->assertSee('promesa-logo.png')
            ->assertSee('datetime="2024-12-10"', false)
            ->assertSee('10/12/2024')
            ->assertSee('href="'.url('/').'"', false)
            ->assertSee('href="tel:+525591862620"', false)
            ->assertSee('href="mailto:contacto@prodifem.com.mx"', false)
            ->assertDontSee('Logo_Prodifem.png');

        $xpath = $this->parseHtml($response->getContent());
        $links = $xpath->query('//details//nav//a');
        $this->assertCount(10, $links);
        foreach ($links as $link) {
            $id = substr($link->getAttribute('href'), 1);
            $this->assertCount(1, $xpath->query('//article/section[@id="'.$id.'"]'));
        }
    }

    public function test_all_original_notice_paragraphs_and_list_items_are_preserved(): void
    {
        $response = $this->get('/aviso-de-privacidad')->assertOk();
        $xpath = $this->parseHtml($response->getContent());
        $texts = [];
        foreach ($xpath->query('//article//p | //article//li') as $node) {
            $texts[] = preg_replace('/\s+/u', ' ', trim($node->textContent));
        }

        // Fingerprint of all body paragraphs and list items before the visual redesign.
        $this->assertCount(38, $texts);
        $this->assertSame('50566475156b034c1242621a6bd89877e9907c9f218cd3cc0b5e70d6ee739fbb', hash('sha256', implode("\n", $texts)));
        $this->assertCount(10, $xpath->query('//article//h2'));
        $response->assertSee('6. Uso de cookies y tecnologías similares')
            ->assertSee('8. Consentimiento');
    }

    public function test_connection_label_reflects_https_instead_of_claiming_ssl_on_local_http(): void
    {
        $this->get('http://localhost/aviso-de-privacidad')->assertOk()->assertSee('Conexión local')->assertDontSee('Conexión segura SSL');
        $this->get('https://localhost/aviso-de-privacidad')->assertOk()->assertSee('Conexión segura SSL');
    }

    private function parseHtml(string $html): DOMXPath
    {
        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($dom);
    }
}
