<?php

namespace App\Services\BusinessImport;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class PageReader
{
    public function textFrom(string $html, string $pageUrl): string
    {
        $text = $this->visibleText($html);

        if (strlen($text) >= 400) {
            return $text;
        }

        $extra = $this->scriptSentences($html, $pageUrl);
        $combined = trim($text.' '.$extra);

        return Str::limit($combined, 12000, '');
    }

    public function visibleText(string $html): string
    {
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $html) ?? $html;
        $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $html) ?? $html;
        $text = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($html))) ?? '');

        return Str::limit($text, 12000, '');
    }

    public function render(string $url): ?string
    {
        $binary = $this->browserBinary();

        if ($binary === null) {
            return null;
        }

        $profile = sys_get_temp_dir().DIRECTORY_SEPARATOR.'stl-chrome-'.uniqid();
        mkdir($profile);

        try {
            $process = new Process([
                $binary,
                '--headless=new',
                '--disable-gpu',
                '--no-first-run',
                '--no-default-browser-check',
                '--disable-extensions',
                '--user-data-dir='.$profile,
                '--virtual-time-budget=15000',
                '--timeout=20000',
                '--dump-dom',
                $url,
            ]);
            $process->setTimeout(30);
            $process->run();

            $html = $process->getOutput();

            if (! str_contains($html, '<')) {
                return null;
            }

            return $html;
        } finally {
            $this->deleteDirectory($profile);
        }
    }

    private function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }

        @rmdir($directory);
    }

    private function scriptSentences(string $html, string $pageUrl): string
    {
        preg_match_all('/<(?:script|link)[^>]+(?:src|href)=["\']([^"\']+)["\']/i', $html, $matches);
        $sentences = [];

        foreach (array_slice(array_unique($matches[1] ?? []), 0, 8) as $src) {
            if (! preg_match('/\.m?js(\?|$)/i', $src)) {
                continue;
            }

            try {
                $body = Http::timeout(20)
                    ->withHeaders(['User-Agent' => 'SpeedToLead/1.0'])
                    ->get($this->absoluteUrl($pageUrl, $src))
                    ->throw()
                    ->body();
            } catch (\Throwable) {
                continue;
            }

            foreach ($this->quotedSentences($body) as $sentence) {
                $sentences[$sentence] = str_contains($sentence, '. ');
            }

        }

        $prose = array_keys(array_filter($sentences));
        $headings = array_keys(array_filter($sentences, fn (bool $isSentence): bool => ! $isSentence));

        return Str::limit(trim(implode(' ', [...$prose, ...$headings])), 8000, '');
    }

    /**
     * @return array<int, string>
     */
    private function quotedSentences(string $source): array
    {
        preg_match_all('/"([^"\\\\]{15,500})"/', $source, $matches);
        $sentences = [];
        $headings = [];

        foreach ($matches[1] as $raw) {
            $text = trim(html_entity_decode($raw));

            if (! preg_match('/^[A-Z]/', $text) || preg_match_all('/[A-Za-z]{3,}/', $text) < 3) {
                continue;
            }

            if (preg_match('/[a-z][A-Z]/', $text) || preg_match('/[()\[\]{}=;<>\\\\`]|\\d\\.\\d|function|return |undefined|className|React|Error|null|children|component|expected|argument|http|hydrat|element |render |adapter|dispatch/i', $text)) {
                continue;
            }

            if (str_contains($text, '. ')) {
                $sentences[$text] = true;
            } else {
                $headings[$text] = true;
            }
        }

        return array_keys($sentences + $headings);
    }

    private function absoluteUrl(string $pageUrl, string $src): string
    {
        if (Str::startsWith($src, ['http://', 'https://'])) {
            return $src;
        }

        $parts = parse_url($pageUrl);
        $origin = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '');

        if (str_starts_with($src, '//')) {
            return ($parts['scheme'] ?? 'https').':'.$src;
        }

        if (str_starts_with($src, '/')) {
            return $origin.$src;
        }

        return $origin.'/'.ltrim($src, '/');
    }

    private function browserBinary(): ?string
    {
        $candidates = array_filter([
            getenv('BROWSER_BIN') ?: null,
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
            '/usr/bin/google-chrome',
            '/usr/bin/google-chrome-stable',
            '/usr/bin/chromium',
            '/usr/bin/chromium-browser',
        ]);

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
