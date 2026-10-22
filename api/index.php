<?php

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

/*
 * Serverless defaults. Vercel never writes vercel.json `env` into the function's
 * runtime environment, so the values the app needs are applied here unless the
 * project already provides them as real environment variables. Everything is
 * written with putenv() before Composer's autoloader runs, so Dotenv leaves the
 * platform-provided values untouched.
 */
$runtimeDefaults = [
    'APP_ENV' => 'production',
    'APP_DEBUG' => 'false',
    'APP_NAME' => 'fleet',
    'APP_URL' => 'https://fleet.adaa.sd',
    'APP_LOCALE' => 'ar',
    'APP_FALLBACK_LOCALE' => 'en',
    'APP_FAKER_LOCALE' => 'ar_SA',
    'LOG_CHANNEL' => 'stderr',
    'LOG_LEVEL' => 'error',
    'SESSION_DRIVER' => 'cookie',
    'CACHE_STORE' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'FILESYSTEM_DISK' => 'local',
    'ASSET_URL' => '/',
    'DB_CONNECTION' => 'sqlite',
    // Only /tmp is writable, so the shipped database becomes the instance's working copy.
    'DB_DATABASE' => '/tmp/database.sqlite',
    'DOMPDF_FONT_DIR' => '/tmp/fonts',
    'DOMPDF_FONT_CACHE' => '/tmp/fonts',
    'VIEW_COMPILED_PATH' => '/tmp/views',
    'APP_CONFIG_CACHE' => '/tmp/config.php',
    'APP_EVENTS_CACHE' => '/tmp/events.php',
    'APP_PACKAGES_CACHE' => '/tmp/packages.php',
    'APP_ROUTES_CACHE' => '/tmp/routes.php',
    'APP_SERVICES_CACHE' => '/tmp/services.php',
    'APP_KEY' => 'base64:UUGl5arvZt/1FYKTC1nrKJzyjCtQ15m8aOvPAq5yBQI=',
];

foreach ($runtimeDefaults as $key => $value) {
    if (getenv($key) === false && ! isset($_SERVER[$key]) && ! isset($_ENV[$key])) {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

require __DIR__.'/../vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

/*
 * Register a booting callback that provisions the serverless runtime before
 * the first request is handled. This runs after all config/providers have
 * loaded — no Console Kernel needed (removed in Laravel 13).
 */
$app->booting(function (Application $app): void {
    provision_runtime($app);
});

$app->handleRequest(Request::capture());

// ---------------------------------------------------------------------------
// Serverless provisioning helpers
// ---------------------------------------------------------------------------

function provision_runtime(Application $app): void
{
    // Ensure writable temporary directories exist
    foreach (['/tmp/views', '/tmp/fonts', '/tmp/cache'] as $tmpDir) {
        if (! is_dir($tmpDir)) {
            @mkdir($tmpDir, 0755, true);
        }
    }

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

        try {
            Artisan::call('migrate', ['--force' => true]);

            if (DB::table('users')->count() === 0) {
                Artisan::call('db:seed', ['--force' => true]);
            }
        } catch (Throwable $e) {
            report($e);
        }
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
