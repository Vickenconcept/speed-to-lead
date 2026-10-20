<?php

namespace Tests\Unit;

use App\Services\BusinessImport\PageReader;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PageReaderTest extends TestCase
{
    public function test_visible_text_ignores_the_empty_app_shell(): void
    {
        $html = <<<'HTML'
        <html><head><title>Construction Trust Platform</title>
        <script type="module" src="/assets/index.js"></script></head>
        <body><div id="root"></div></body></html>
        HTML;

        $text = (new PageReader)->visibleText($html);

        $this->assertStringContainsString('Construction Trust Platform', $text);
        $this->assertLessThan(400, strlen($text));
    }

    public function test_visible_text_keeps_the_rendered_page_copy(): void
    {
        $html = '<html><body><h1>Build Your Dream Home in Africa</h1><p>Secure escrow platform connecting families with verified construction companies.</p></body></html>';

        $text = (new PageReader)->visibleText($html);

        $this->assertStringContainsString('verified construction companies', $text);
    }

    public function test_script_bundles_are_read_when_the_html_shell_is_empty(): void
    {
        Http::fake([
            'https://example.test/assets/app.js' => Http::response(
                'const copy = "Secure escrow platform connecting families with verified construction companies.";'
            ),
        ]);

        $html = <<<'HTML'
        <html><head><title>Construction Trust Platform</title>
        <script type="module" src="/assets/app.js"></script></head>
        <body><div id="root"></div></body></html>
        HTML;

        $text = (new PageReader)->textFrom($html, 'https://example.test/');

        $this->assertStringContainsString('verified construction companies', $text);
    }
}
