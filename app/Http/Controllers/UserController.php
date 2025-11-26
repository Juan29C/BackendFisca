<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(private UserService $service) {}

    public function index(): JsonResponse
    {
        $users = $this->service->getAll();
        
        return response()->json([
            'ok' => true,
            'data' => UserResource::collection($users),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $user = $this->service->getById($id);
        
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => new UserResource($user),
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $result = $this->service->createWithToken($request->validated());

        return response()->json([
            'ok' => true,
            'message' => 'Usuario registrado correctamente',
            'data' => new UserResource($result['user']),
            'access_token' => $result['token'],
            'token_type' => 'bearer',
            'expires_in' => $result['expires_in'],
        ], 201);
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = $this->service->update($id, $request->validated());

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Usuario actualizado correctamente',
            'data' => new UserResource($user),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->service->delete($id);

        if (!$deleted) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Usuario eliminado correctamente',
        ]);
    }
}
