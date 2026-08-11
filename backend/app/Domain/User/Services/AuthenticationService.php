<?php

namespace App\Domain\User\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Extracted from AuthController during the pre-deployment quality review
 * (docs/reviews/0001-quality-review.md): the "deactivated users can't log
 * in even with correct credentials" check is a business rule (FR-USR-03),
 * and had been sitting directly in the Controller — a violation of the
 * project's own immutable rule (ADR-0001 §12: business rules live in
 * Services, Controllers only validate-and-delegate). Session mechanics
 * (regenerate/invalidate) stay in the Controller since they're bound to
 * the Request object, which Services never touch.
 */
class AuthenticationService
{
    /**
     * @param  array{email: string, password: string}  $credentials
     */
    public function attempt(array $credentials): User
    {
        if (! Auth::attempt($credentials, remember: false)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => ['This account has been deactivated.'],
            ]);
        }

        return $user;
    }
}
