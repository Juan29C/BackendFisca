<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserService
{
    protected UserRepository $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll(): Collection
    {
        return $this->repository->listAll();
    }

    public function getById(int $id): ?User
    {
        return $this->repository->find($id);
    }

    public function getByEmail(string $email): ?User
    {
        return $this->repository->findByEmail($email);
    }

    public function createWithToken(array $data): array
    {
        // Hashear la contraseña
        $data['password'] = Hash::make($data['password']);

        // Crear el usuario
        $user = $this->repository->create($data);

        // Generar token JWT
        $token = JWTAuth::fromUser($user);

        return [
            'user' => $user,
            'token' => $token,
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ];
    }

    public function update(int $id, array $data): ?User
    {
        // Hashear la contraseña si se proporciona
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
