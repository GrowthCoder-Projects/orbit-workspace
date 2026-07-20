<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class DailyQuoteService
{
    /**
     * Curated local list of inspirational quotes.
     *
     * @var array<int, array{quote: string, author: string, category: string, language: string}>
     */
    protected array $curatedQuotes = [
        [
            'quote' => 'Cara terbaik untuk memprediksi masa depan adalah dengan menciptakannya.',
            'author' => 'Peter Drucker',
            'category' => 'Produktivitas',
            'language' => 'id',
        ],
        [
            'quote' => 'Kesuksesan adalah jumlah dari upaya-upaya kecil yang diulangi hari demi hari.',
            'author' => 'Robert Collier',
            'category' => 'Konsistensi',
            'language' => 'id',
        ],
        [
            'quote' => 'Fokuslah pada menjadi produktif, bukan sekadar sibuk.',
            'author' => 'Tim Ferriss',
            'category' => 'Fokus',
            'language' => 'id',
        ],
        [
            'quote' => 'Setiap hari adalah kesempatan baru untuk menjadi versi terbaik dari dirimu.',
            'author' => 'Anonim',
            'category' => 'Growth',
            'language' => 'id',
        ],
        [
            'quote' => 'Disiplin adalah jembatan antara tujuan dan pencapaian.',
            'author' => 'Jim Rohn',
            'category' => 'Disiplin',
            'language' => 'id',
        ],
        [
            'quote' => 'Tindakan adalah kunci dasar dari semua keberhasilan.',
            'author' => 'Pablo Picasso',
            'category' => 'Aksi',
            'language' => 'id',
        ],
        [
            'quote' => 'Kualitas bukanlah tindakan, melainkan sebuah kebiasaan.',
            'author' => 'Aristoteles',
            'category' => 'Habit',
            'language' => 'id',
        ],
        [
            'quote' => 'Jangan menunggu kesempatan, buatlah kesempatan itu sendiri.',
            'author' => 'George Bernard Shaw',
            'category' => 'Inisiatif',
            'language' => 'id',
        ],
        [
            'quote' => 'Kemenangan terkecil hari ini adalah fondasi untuk sukses besar esok hari.',
            'author' => 'GrowthCoder',
            'category' => 'Mindset',
            'language' => 'id',
        ],
        [
            'quote' => 'The secret of getting ahead is getting started.',
            'author' => 'Mark Twain',
            'category' => 'Productivity',
            'language' => 'en',
        ],
        [
            'quote' => 'Small daily improvements over time lead to stunning results.',
            'author' => 'Robin Sharma',
            'category' => 'Growth',
            'language' => 'en',
        ],
        [
            'quote' => 'You don’t have to be great to start, but you have to start to be great.',
            'author' => 'Zig Ziglar',
            'category' => 'Motivation',
            'language' => 'en',
        ],
    ];

    /**
     * Get deterministic quote of the day based on current date.
     *
     * @return array{quote: string, author: string, category: string, source: string}
     */
    public function getQuoteOfTheDay(): array
    {
        $dateSeed = (int) date('Ymd');
        $index = $dateSeed % count($this->curatedQuotes);
        $item = $this->curatedQuotes[$index];

        return [
            'quote' => $item['quote'],
            'author' => $item['author'],
            'category' => $item['category'],
            'source' => 'Kurasi Harian',
        ];
    }

    /**
     * Get a random quote, optionally attempting external API first.
     *
     * @return array{quote: string, author: string, category: string, source: string}
     */
    public function getRandomQuote(bool $tryExternal = false): array
    {
        if ($tryExternal) {
            $external = $this->fetchFromExternalApi();
            if ($external !== null) {
                return $external;
            }
        }

        $randomIndex = array_rand($this->curatedQuotes);
        $item = $this->curatedQuotes[$randomIndex];

        return [
            'quote' => $item['quote'],
            'author' => $item['author'],
            'category' => $item['category'],
            'source' => 'Koleksi Lokal',
        ];
    }

    /**
     * Attempt to fetch quote from external API with timeout and fallback.
     *
     * @return array{quote: string, author: string, category: string, source: string}|null
     */
    protected function fetchFromExternalApi(): ?array
    {
        try {
            $response = Http::timeout(2)->get('https://dummyjson.com/quotes/random');

            if ($response->successful()) {
                $data = $response->json();
                if (! empty($data['quote']) && ! empty($data['author'])) {
                    return [
                        'quote' => $data['quote'],
                        'author' => $data['author'],
                        'category' => 'Inspirasi Global',
                        'source' => 'External Quote API',
                    ];
                }
            }
        } catch (Throwable $e) {
            // Silently fall back to local curated collection
        }

        return null;
    }
}
