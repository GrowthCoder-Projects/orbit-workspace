<?php

namespace App\Jobs;

use App\Models\Bookmark;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FetchBookmarkMetadataJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Bookmark $bookmark)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Ensure bookmark still exists
        if (!$this->bookmark->exists) {
            return;
        }

        $this->bookmark->update(['status' => 'fetching']);

        $url = $this->bookmark->url;
        $parsedUrl = parse_url($url);

        if (!$parsedUrl || !isset($parsedUrl['host'])) {
            $this->bookmark->update([
                'title' => $url,
                'status' => 'failed',
            ]);
            return;
        }

        $host = $parsedUrl['host'];
        $ip = gethostbyname($host);

        // SSRF Prevention: Block private/internal IPs
        if ($this->isPrivateIp($ip)) {
            $this->bookmark->update([
                'title' => $host,
                'description' => 'Fetching blocked: Target resolves to a private or loopback IP address.',
                'status' => 'failed',
            ]);
            return;
        }

        try {
            // Fetch HTML content with timeout
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            ])->timeout(10)->get($url);

            if (!$response->successful()) {
                $this->bookmark->update([
                    'title' => $host,
                    'status' => 'failed',
                ]);
                return;
            }

            $html = $response->body();
            
            // Suppress DOM parsing errors
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            
            // Convert to HTML entities to prevent UTF-8 encoding issues in DOMDocument
            @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
            libxml_clear_errors();

            $xpath = new \DOMXPath($dom);

            // Extract Title
            $title = $this->getXpathValue($xpath, '//meta[@property="og:title"]/@content')
                ?: $this->getXpathValue($xpath, '//meta[@name="twitter:title"]/@content')
                ?: $this->getXpathValue($xpath, '//title/text()')
                ?: $host;

            // Extract Description
            $description = $this->getXpathValue($xpath, '//meta[@name="description"]/@content')
                ?: $this->getXpathValue($xpath, '//meta[@property="og:description"]/@content')
                ?: $this->getXpathValue($xpath, '//meta[@name="twitter:description"]/@content');

            // Extract Preview Image
            $previewImage = $this->getXpathValue($xpath, '//meta[@property="og:image"]/@content')
                ?: $this->getXpathValue($xpath, '//meta[@name="twitter:image"]/@content');

            // Extract Favicon
            $favicon = $this->getXpathValue($xpath, '//link[@rel="apple-touch-icon"]/@href')
                ?: $this->getXpathValue($xpath, '//link[@rel="shortcut icon"]/@href')
                ?: $this->getXpathValue($xpath, '//link[@rel="icon"]/@href');

            // Resolve relative URLs
            $baseUrl = $parsedUrl['scheme'] . '://' . $host;
            if ($previewImage && !Str::startsWith($previewImage, ['http://', 'https://'])) {
                $previewImage = $this->resolveRelativeUrl($baseUrl, $previewImage);
            }
            if ($favicon && !Str::startsWith($favicon, ['http://', 'https://'])) {
                $favicon = $this->resolveRelativeUrl($baseUrl, $favicon);
            } else if (!$favicon) {
                // Default fallback favicon
                $favicon = $baseUrl . '/favicon.ico';
            }

            // Upgrade schemas to HTTPS to prevent Mixed Content warnings on secure connections
            if ($favicon && Str::startsWith($favicon, 'http://')) {
                $favicon = 'https://' . Str::after($favicon, 'http://');
            }
            if ($previewImage && Str::startsWith($previewImage, 'http://')) {
                $previewImage = 'https://' . Str::after($previewImage, 'http://');
            }

            $this->bookmark->update([
                'title' => trim($title),
                'description' => $description ? trim($description) : null,
                'favicon_url' => $favicon,
                'preview_image_url' => $previewImage,
                'status' => 'success',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch metadata for bookmark ' . $this->bookmark->id . ': ' . $e->getMessage());
            
            $this->bookmark->update([
                'title' => $host,
                'status' => 'failed',
            ]);
        }
    }

    /**
     * Check if an IP address is in a private range or loopback.
     */
    private function isPrivateIp(string $ip): bool
    {
        if ($ip === '127.0.0.1' || $ip === '0.0.0.0' || Str::startsWith($ip, '127.')) {
            return true;
        }

        $octets = explode('.', $ip);
        if (count($octets) !== 4) {
            return true; // Invalid or IPv6 which we restrict for simplicity in this sandbox
        }

        $o1 = (int) $octets[0];
        $o2 = (int) $octets[1];

        // 10.0.0.0 - 10.255.255.255
        if ($o1 === 10) {
            return true;
        }

        // 172.16.0.0 - 172.31.255.255
        if ($o1 === 172 && $o2 >= 16 && $o2 <= 31) {
            return true;
        }

        // 192.168.0.0 - 192.168.255.255
        if ($o1 === 192 && $o2 === 168) {
            return true;
        }

        // 169.254.0.0 - 169.254.255.255 (Link-Local)
        if ($o1 === 169 && $o2 === 254) {
            return true;
        }

        return false;
    }

    /**
     * Helper to get value from XPath query.
     */
    private function getXpathValue(\DOMXPath $xpath, string $query): ?string
    {
        $nodes = $xpath->query($query);
        if ($nodes && $nodes->length > 0) {
            return $nodes->item(0)->nodeValue;
        }
        return null;
    }

    /**
     * Resolve a relative URL path to absolute URL.
     */
    private function resolveRelativeUrl(string $baseUrl, string $relativeUrl): string
    {
        if (Str::startsWith($relativeUrl, '//')) {
            return 'https:' . $relativeUrl;
        }

        if (Str::startsWith($relativeUrl, '/')) {
            return rtrim($baseUrl, '/') . $relativeUrl;
        }

        return rtrim($baseUrl, '/') . '/' . $relativeUrl;
    }
}
