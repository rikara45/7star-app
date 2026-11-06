<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecretHygieneTest extends TestCase
{
    private const EXTENSIONS = ['php', 'js', 'json', 'css'];

    public function test_tidak_ada_key_google_tertanam_di_kode(): void
    {
        $files = [base_path('.env.example')];

        foreach (['config', 'app', 'routes', 'resources', 'database', 'tests', 'public/build/assets'] as $directory) {
            $files = array_merge($files, $this->textFiles(base_path($directory)));
        }

        $matches = [];

        foreach ($files as $file) {
            if (preg_match('/AIza[0-9A-Za-z_-]{20,}/', file_get_contents($file))) {
                $matches[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file);
            }
        }

        $this->assertSame([], $matches, 'Key Google ditemukan di: '.implode(', ', $matches));
    }

    /**
     * @return list<string>
     */
    private function textFiles(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
        $files = [];

        foreach ($iterator as $file) {
            if (! $file->isFile() || ! in_array($file->getExtension(), self::EXTENSIONS, true)) {
                continue;
            }

            if ($file->getSize() > 2 * 1024 * 1024) {
                continue;
            }

            $files[] = $file->getPathname();
        }

        return $files;
    }
}
