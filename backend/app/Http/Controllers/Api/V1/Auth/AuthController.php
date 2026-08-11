<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\User\Services\AuthenticationService;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Reference implementation of the request flow every future controller
 * follows: FormRequest → (thin) Controller → Resource response
 * (ADR-0001 §2.2). The actual "are these credentials valid, is this
 * account active" decision lives in AuthenticationService — this class
 * only handles what's inherently tied to the Request/session object
 * (regenerate/invalidate), which a Service never touches.
 */
class AuthController extends Controller
{
    public function __construct(private readonly AuthenticationService $authentication) {}

    public function login(LoginRequest $request): UserResource
    {
        $user = $this->authentication->attempt($request->validated());

        $request->session()->regenerate();

        return UserResource::make($user);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['data' => ['message' => 'Logged out.']]);
    }

    public function me(Request $request): UserResource
    {
        return UserResource::make($request->user());
    }
}
