<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Repositories\UserRepository;
use Ars\Responder\Facades\Responder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{

    public UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->userRepository->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return Responder::validationError(['email' => trans('auth.failed')]);
        }

        $token = $this->userRepository->createToken($user);

        return Responder::ok($token);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return Responder::ok();
    }

    public function register(RegisterRequest $request): JsonResponse
    {

        $user = $this->userRepository->create($request->validated());

        $token = $this->userRepository->createToken($user);

        return Responder::created($token);
    }

    public function me(Request $request): JsonResponse
    {
        $user = UserResource::make($request->user());

        return Responder::ok($user);
    }
}
