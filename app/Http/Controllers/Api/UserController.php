<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(protected UserService $userService)
    {
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => ['required', 'string', 'min:1'],
        ]);

        $users = $this->userService->search($request->user(), $request->query('query'));

        return response()->json([
            'users' => UserResource::collection($users),
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->userService->updateProfile(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user' => new UserResource($user),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $user = \App\Models\User::findOrFail($id);

        return response()->json([
            'user' => new UserResource($user),
        ]);
    }
}