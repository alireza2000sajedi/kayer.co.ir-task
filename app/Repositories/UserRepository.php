<?php

namespace App\Repositories;


use App\Models\User;
use App\Repositories\Src\BaseRepository;

class UserRepository extends BaseRepository
{

    public function model()
    {
        return User::class;
    }

    public function createToken(User $user): array
    {
        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'accessToken' => $token,
            'tokenType'   => 'Bearer',
            'expiresIn'   => config('sanctum.expiration'),
        ];
    }
}
