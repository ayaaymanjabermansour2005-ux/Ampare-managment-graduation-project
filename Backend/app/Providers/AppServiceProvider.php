<?php

namespace App\Providers;

use App\Contracts\AiChatProviderContract;
use App\Notifications\Channels\WhatsAppChannel;
use App\Services\Ai\AnthropicChatProvider;
use App\Services\Ai\OpenAiChatProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AiChatProviderContract::class, function () {
            return match (config('services.ai.provider')) {
                'anthropic' => new AnthropicChatProvider,
                default => new OpenAiChatProvider,
            };
        });
    }

    public function boot(): void
    {
        Builder::defaultStringLength(191);

        Model::preventLazyLoading(! $this->app->isProduction());

        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        RateLimiter::for('email-verification-resend', function (Request $request) {
            $email = mb_strtolower((string) $request->input('email'));

            return [
                Limit::perMinute(5)->by('resend-ip:'.$request->ip()),
                Limit::perMinutes(10, 3)->by('resend-email:'.$email),
            ];
        });
        RateLimiter::for('login', function (Request $request) {

            $email = Str::lower(
                trim((string) $request->input('email'))
            );

            return [
                Limit::perMinute(10)
                    ->by('login-ip:'.$request->ip()),

                Limit::perMinute(5)
                    ->by('login-email:'.$email.'|'.$request->ip()),
            ];
        });

        RateLimiter::for('ai-chat', function (Request $request) {
            return Limit::perHour(20)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('ai-diagnostics', function (Request $request) {
            return Limit::perDay(10)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('payments', function (Request $request) {
            return [
                Limit::perMinute(10)->by('payments-user:'.($request->user()?->id ?: $request->ip())),
                Limit::perMinute(20)->by('payments-ip:'.$request->ip()),
            ];
        });

        Notification::extend('whatsapp', fn () => new WhatsAppChannel);
    }
}
