<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

define('LARAVEL_START', microtime(true));

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '');

/*
 * Serve Vite-built assets and other public files directly. On Vercel the whole
 * project (except .vercelignore entries) is available to the function, and the
 * filesystem is read-only, so we stream them from /public ourselves.
 */
if ($uri !== '/' && is_file($file = __DIR__.'/../public'.$uri)) {
    header('Content-Type: '.mime_for($file));
    header('Cache-Control: public, max-age=31536000, immutable');
    readfile($file);

    return;
}

require __DIR__.'/../vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

/*
 * Boot the framework (loads .env/config/facades/providers) so runtime
 * provisioning can inspect configuration before the HTTP kernel handles the
 * request. Provisioning is idempotent and cheap.
 */
$app->make(Kernel::class)->bootstrap();

/*
 * Vercel's filesystem is read-only outside /tmp. Provision writable runtime
 * paths and, when no external database is configured, an ephemeral SQLite
 * database seeded with a usable admin account on the first request of each
 * function instance.
 */
provision_runtime($app);

$app->handleRequest(Request::capture());

function provision_runtime(Application $app): void
{
    prepare_font_cache($app);

    if (($app['config']->get('database.default') ?? 'sqlite') !== 'sqlite') {
        return;
    }

    $database = $app['config']->get('database.connections.sqlite.database');

    if (! is_string($database) || $database === ':memory:') {
        return;
    }

    if (! is_file($database)) {
        /*
         * Cold start: whenever the deployment ships a database with the
         * project it becomes the baseline for every function instance.
         * Otherwise fall back to creating an empty one from migrations.
         */
        $shipped = $app->basePath('database/database.sqlite');

        if (is_file($shipped)) {
            if (! is_dir($dir = dirname($database))) {
                @mkdir($dir, 0755, true);
            }

            @copy($shipped, $database);
        } else {
            if (! is_dir($dir = dirname($database))) {
                @mkdir($dir, 0755, true);
            }

            @touch($database);
        }
    }

    try {
        Artisan::call('migrate', ['--force' => true]);

        if (DB::table('users')->count() === 0) {
            Artisan::call('db:seed', ['--force' => true]);
        }
    } catch (Throwable $e) {
        report($e);
    }
}

/**
 * Mirror the shipped dompdf font cache into a writable directory when the
 * configured cache lives on the read-only deployment filesystem.
 */
function prepare_font_cache(Application $app): void
{
    $cache = $app['config']->get('dompdf.options.font_cache');

    if (! is_string($cache) || is_dir($cache)) {
        return;
    }

    @mkdir($cache, 0755, true);

    foreach (glob(__DIR__.'/../storage/fonts/*') ?: [] as $font) {
        @copy($font, $cache.'/'.basename($font));
    }
}

function mime_for(string $path): string
{
    return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
        'css' => 'text/css',
        'js', 'mjs' => 'application/javascript',
        'json', 'map' => 'application/json',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'ico' => 'image/vnd.microsoft.icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'otf' => 'font/otf',
        'xml' => 'application/xml',
        'txt' => 'text/plain',
        default => 'application/octet-stream',
    };
}
