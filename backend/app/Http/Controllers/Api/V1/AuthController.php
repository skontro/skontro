<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
     * Provision a new tenant and its owner in one transaction, then start the
     * session. Registration runs without a bound tenant context — it creates
     * the very first tenant — so tenant_id is set explicitly here.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $tenant = Tenant::create([
                'name' => $request->string('company_name')->toString(),
            ]);

            return User::create([
                'tenant_id' => $tenant->id,
                'name' => $request->string('name')->toString(),
                'email' => $request->string('email')->toString(),
                'password' => $request->string('password')->toString(),
                'role' => Role::Owner->value,
            ]);
        });

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return UserResource::make($user->load('tenant'))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Authenticate against the web (session) guard. The failure message is
     * identical whether or not the email exists, so the endpoint does not
     * reveal account existence (NFR-013).
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = [
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
        ];

        if (! Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('These credentials do not match our records.'),
            ]);
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::guard('web')->user();

        return UserResource::make($user->load('tenant'))->response();
    }

    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    /**
     * The canonical "who am I" endpoint. The SPA calls it on boot and after
     * login to hydrate auth state.
     */
    public function me(Request $request): JsonResource
    {
        /** @var User $user */
        $user = $request->user();

        return UserResource::make($user->load('tenant'));
    }
}
