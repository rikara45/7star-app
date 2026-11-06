<?php

namespace Tests\Unit;

use Tests\TestCase;

class ConfigTest extends TestCase
{
    public function test_timezone_memakai_waktu_indonesia(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
    }

    public function test_contoh_env_memakai_nilai_produksi_aman(): void
    {
        $values = [];

        foreach (file(base_path('.env.example'), FILE_IGNORE_NEW_LINES) as $line) {
            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $values[trim($key)] = trim($value);
        }

        $this->assertSame('false', $values['APP_DEBUG'] ?? null);
        $this->assertSame('mysql', $values['DB_CONNECTION'] ?? null);
        $this->assertSame('smtp', $values['MAIL_MAILER'] ?? null);
        $this->assertSame('daily', $values['LOG_STACK'] ?? null);
        $this->assertSame('warning', $values['LOG_LEVEL'] ?? null);
    }
}
