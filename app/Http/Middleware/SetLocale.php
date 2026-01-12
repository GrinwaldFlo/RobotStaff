<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Supported locales.
     */
    protected array $supportedLocales = ['en', 'fr'];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->determineLocale($request);
        
        App::setLocale($locale);
        
        $response = $next($request);
        
        // Set locale cookie if it changed or doesn't exist
        if (!$request->hasCookie('locale') || $request->cookie('locale') !== $locale) {
            Cookie::queue('locale', $locale, 525600); // 1 year
        }

        return $response;
    }

    /**
     * Determine the locale to use.
     */
    protected function determineLocale(Request $request): string
    {
        // 1. Check query parameter (for switching language)
        if ($request->has('lang') && in_array($request->get('lang'), $this->supportedLocales)) {
            return $request->get('lang');
        }

        // 2. Check cookie
        if ($request->hasCookie('locale') && in_array($request->cookie('locale'), $this->supportedLocales)) {
            return $request->cookie('locale');
        }

        // 3. Check browser preference
        $browserLocale = $request->getPreferredLanguage($this->supportedLocales);
        if ($browserLocale) {
            return $browserLocale;
        }

        // 4. Default to English
        return 'en';
    }
}
