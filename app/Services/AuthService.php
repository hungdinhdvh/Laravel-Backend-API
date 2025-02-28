<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use App\Models\User;

class AuthService
{
    protected UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Register a new user
     *
     * @param array $data Validated registration data
     * @return User
     */
    public function register(array $data): User
    {
        $userData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'address' => $data['address'],
        ];

        return $this->userRepository->create($userData);
    }

    /**
     * Get the token array structure
     *
     * @param string $token
     * @return JsonResponse
     */
    public function respondWithToken(string $token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }

    /**
     * Validate current password
     *
     * @param string $current_password
     * @return bool
     */
    private function isCurrentPasswordValid(string $current_password): bool
    {
        return password_verify($current_password, auth()->user()->password);
    }

    /**
     * Change user password
     *
     * @param array $data
     * @return JsonResponse
     */
    public function changePassword(array $data): JsonResponse
    {
        if (!$this->isCurrentPasswordValid($data['current_password'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Current password is incorrect'
            ], 401);
        }

        try {
            $this->userRepository->update(
                ['password' => Hash::make($data['password'])],
                auth()->id()
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Password updated successfully'
            ]);
        } catch (\Exception $e) {
            report($e); // Log the error

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update password'
            ], 500);
        }
    }
}
