<?php

use App\Jobs\FetchBookmarkMetadataJob;
use App\Models\Bookmark;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('job blocks loopback and private ip addresses (SSRF prevention)', function () {
    $user = User::factory()->create();
    $bookmark = Bookmark::factory()->create([
        'user_id' => $user->id,
        'url' => 'http://127.0.0.1/admin',
        'status' => 'pending',
    ]);

    $job = new FetchBookmarkMetadataJob($bookmark);
    $job->handle();

    $bookmark->refresh();
    expect($bookmark->status)->toBe('failed');
    expect($bookmark->description)->toContain('private or loopback IP');
});

test('job successfully parses metadata from html response', function () {
    $user = User::factory()->create();
    $bookmark = Bookmark::factory()->create([
        'user_id' => $user->id,
        'url' => 'https://laravel.com',
        'status' => 'pending',
    ]);

    $fakeHtml = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>Laravel - The PHP Framework For Web Artisans</title>
    <meta name="description" content="Laravel is a web application framework with expressive, elegant syntax.">
    <meta property="og:image" content="/assets/og-image.jpg">
    <link rel="icon" href="/favicon.png">
</head>
<body>
</body>
</html>
HTML;

    Http::fake([
        'https://laravel.com*' => Http::response($fakeHtml, 200),
    ]);

    $job = new FetchBookmarkMetadataJob($bookmark);
    $job->handle();

    $bookmark->refresh();
    expect($bookmark->status)->toBe('success');
    expect($bookmark->title)->toBe('Laravel - The PHP Framework For Web Artisans');
    expect($bookmark->description)->toBe('Laravel is a web application framework with expressive, elegant syntax.');
    expect($bookmark->favicon_url)->toBe('https://laravel.com/favicon.png');
    expect($bookmark->preview_image_url)->toBe('https://laravel.com/assets/og-image.jpg');
});
