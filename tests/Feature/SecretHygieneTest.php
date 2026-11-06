<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecretHygieneTest extends TestCase
{
    public function test_tidak_ada_key_google_tertanam_di_kode(): void
    {
        $files = array_merge(
            glob(base_path('config/*.php')) ?: [],
            $this->phpFiles(base_path('app')),
        );

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
    private function phpFiles(string $directory): array
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
        $files = [];

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
