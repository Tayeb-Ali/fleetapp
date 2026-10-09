<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /** @var string[] */
    protected array $available = ['en', 'ar'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = (string) ($request->session()->get('locale', config('app.locale', 'en')));

        if (! in_array($locale, $this->available, true)) {
            $locale = (string) config('app.fallback_locale', 'en');
        }

        app()->setLocale($locale);

        try {
            Carbon::setLocale($locale);
        } catch (\Throwable) {
            // Carbon locale is best-effort only.
        }

        return $next($request);
    }
}
