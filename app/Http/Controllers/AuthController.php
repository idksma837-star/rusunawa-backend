<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

    private function userData(User $user): array
    {
        return $user->only(['id', 'name', 'email', 'role', 'phone']);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated())->refresh();

        return $this->ok([
            'user' => $this->userData($user),
            'token' => $user->createToken('mobile')->plainTextToken,
        ], 'Registrasi berhasil', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        return $this->ok([
            'user' => $this->userData($user),
            'token' => $user->createToken('mobile')->plainTextToken,
        ], 'Login berhasil');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->ok($this->userData($request->user()));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->ok(null, 'Logout berhasil');
    }
}
