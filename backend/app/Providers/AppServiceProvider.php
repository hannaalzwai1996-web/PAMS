<?php

namespace App\Providers;

use App\Domain\LearningOutcome\Models\ObjectiveOutcomeMatrix;
use App\Domain\LearningOutcome\Policies\MatrixPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // See MatrixPolicy's docblock for why this can't be auto-discovered.
        Gate::policy(ObjectiveOutcomeMatrix::class, MatrixPolicy::class);

        // SRS-0001 SEC-09: rate-limit authentication attempts. Keyed by
        // email+IP (not IP alone) so a credential-stuffing attempt against
        // one account is throttled without an attacker being able to lock
        // out a victim's account by hammering it from many IPs, and
        // without one noisy IP throttling every other user behind it (a
        // shared NAT/office network).
        RateLimiter::for('login', function (Request $request) {
            $key = strtolower((string) $request->input('email')).'|'.$request->ip();

            return Limit::perMinute(5)->by($key);
        });
    }
}
