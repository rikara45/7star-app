<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Satu-satunya jalur pemanggilan Gemini untuk narasi hasil penilaian.
 * Selalu mengembalikan narasi: kegagalan apa pun jatuh ke pesan fallback.
 */
class AiNarrativeService
{
    private const MODEL = 'gemini-2.5-flash';

    private const TIMEOUT = 60;

    private const CONNECT_TIMEOUT = 10;

    private const FALLBACK_TIMEOUT = 'Waktu analisis habis (timeout > 60 detik). Silakan coba lagi nanti.';

    private const FALLBACK_GENERIC = 'Analisis AI belum tersedia saat ini. Namun skor Anda telah berhasil dihitung dan disimpan.';

    /**
     * Minta narasi analisis dari Gemini, tanpa pernah melempar exception ke pemanggil.
     */
    public function generate(string $prompt): string
    {
        $apiKey = config('gemini.api_key');

        if (! $apiKey) {
            return self::FALLBACK_GENERIC;
        }

        try {
            $response = Http::timeout(self::TIMEOUT)
                ->connectTimeout(self::CONNECT_TIMEOUT)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post(config('gemini.base_uri').'/v1beta/models/'.self::MODEL.':generateContent?key='.$apiKey, [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                ]);

            if ($response->successful()) {
                $json = $response->json();

                $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;

                if (! empty($text)) {
                    return $text;
                }
            }
        } catch (ConnectionException $e) {
            Log::error('Gemini Timeout: '.$e->getMessage());

            return self::FALLBACK_TIMEOUT;
        } catch (\Exception $e) {
            Log::error('Gemini API Error: '.$e->getMessage());
        }

        return self::FALLBACK_GENERIC;
    }
}
