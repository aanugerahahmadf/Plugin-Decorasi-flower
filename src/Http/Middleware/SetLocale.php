<?php

namespace Aanugerah\WeddingPro\Http\Middleware;


use Aanugerah\WeddingPro\Models\UserLanguage;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $sessionLocale = (string) session()->get('locale');
        $locale = $sessionLocale ?: null;

        // Force check across all defined guards to find the authenticated user
        $user = null;
        $guards = ['web', 'filament', 'admin', 'mobile', 'nativephp', 'api'];
        foreach ($guards as $guard) {
            try {
                $guardInstance = Auth::guard($guard);
                if ($guardInstance->check()) {
                    $user = $guardInstance->user();
                    break;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        if ($user) {
            // Get current DB locale via accessor. Assuming user model has a 'lang' property or relation.
            // On NativePHP mobile, skip DB sync to avoid proxy errors — use session only
            $isMobile = \Aanugerah\WeddingPro\NativeServiceProvider::isNativeMobile();
            $dbLocale = null;

            if (! $isMobile) {
                try {
                    $dbLocale = $user->lang;
                } catch (\Throwable $e) {
                    $dbLocale = null;
                }
            }

            if ($sessionLocale && $sessionLocale !== $dbLocale && ! $isMobile) {
                // SYNC: Session changed (e.g. from Welcome page switcher). Persist to Database.
                try {
                    UserLanguage::updateOrCreate(
                        ['model_id' => (string) $user->id, 'model_type' => get_class($user)],
                        ['lang' => $sessionLocale]
                    );
                    // Update user instance in memory if it's cached or loaded
                    $user->setRawAttributes(['lang' => $sessionLocale], true);
                } catch (\Exception $e) {
                    // Fail silently if DB is not reachable
                }
                $locale = $sessionLocale;
            } elseif ($dbLocale) {
                // SYNC: Database is source of truth if session is empty or old. Persist to Session.
                $locale = (string) $dbLocale;
                session()->put('locale', $locale);
            } elseif ($sessionLocale) {
                $locale = $sessionLocale;
            }
        }

        // Detect from browser if everything else fails (new visitor)
        if (! $locale) {
            // On NativePHP mobile: default to Indonesian, ignore device browser language
            if (\Aanugerah\WeddingPro\NativeServiceProvider::isNativeMobile()) {
                $locale = 'id';
            } else {
                $localsConfig = config('filament-language-switcher.locals', ['id' => [], 'en' => []]);
                $supported = array_keys($localsConfig);
                $locale = $request->getPreferredLanguage($supported ?: ['id', 'en']);
            }
        }

        if ($locale) {
            app()->setLocale($locale);
            session()->put('locale', (string) $locale);

            // Force update for all related parts of the system
            config(['app.locale' => $locale]);
            // Ensure Filament context also respects this
            if (class_exists(Filament::class)) {
                App::setLocale($locale);
            }
        }

        return $next($request);
    }
}
