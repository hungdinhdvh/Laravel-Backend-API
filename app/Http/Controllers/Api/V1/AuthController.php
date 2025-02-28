<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegistrationRequest;
use App\Http\Requests\Auth\ResendEmailVerificationLinkRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Services\AuthService;
use App\Services\EmailVerificationService;
use Illuminate\Support\Facades\Log;

/**
 * @group Auth
 */
class AuthController extends Controller
{
    private $authService;
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct(
        AuthService $authService,
        private EmailVerificationService $emailVerificationService
    )
    {
//        $this->middleware('auth:api', ['except' => ['login']]);
            $this->authService = $authService;
    }

    /**
     * Registration Method
     * @unauthenticated
     * @bodyParam name string required The name of the user. Example: example
     * @bodyParam email string required The email of the user. Example: example@gmail.com
     * @bodyParam password string required The password of the user. Example: password
     * @bodyParam password_confirmation string required The password_confirmation of the user. Example: password
     * @bodyParam address string required The address of the user. Example: 11 Street, Tam Binh Ward, Thu Duc City, Ho Chi Minh City, Vietnam
     */
    public function register(RegistrationRequest $request){
            try {
                $data = $request->validated();
                 Log::info('Data received:', $data);

                $user = $this->authService->register($data);

                $this->emailVerificationService->sendVerificationEmail($user);

                Log::info('User created:', $user->toArray());

                return response()->json([
                    'message' => 'Registration successful',
                    "user" => $user
                ], 201);

            } catch (\Exception $exception) {
                Log::error('Registration error: ' . $exception->getMessage());
                throw $exception;
            }
    }

    /**
     * Login Method
     * @unauthenticated
     * @bodyParam email string required The email of the user. Example: example@gmail.com
     * @bodyParam password string required The password of the user. Example: password
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();
        Log::info('Data received:', $credentials);

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->authService->respondWithToken($token);
    }

    /**
     * Verify Email
     * @unauthenticated
     * @bodyParam email string required The email of the user. Example: example@gmail.com
     * @bodyParam token string required The token of the user. Example: sdjkfddfkjgfdkf213
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyUserEmail(VerifyEmailRequest $request) {

        return $this->emailVerificationService->verifyEmail($request->email, $request->token);
    }

    /**
     * Resend Verification Link
     * @unauthenticated
     * @bodyParam email string required The email of the user. Example: example@gmail.com
     * @return \Illuminate\Http\JsonResponse
     */
    public function resendEmailVerificationLink(ResendEmailVerificationLinkRequest $request)
    {
        return $this->emailVerificationService->resendLink($request->email);
    }

    /**
     * Get the authenticated User.
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->authService->respondWithToken(auth()->refresh());
    }

    /**
     * Change User Password
     * @bodyParam current_password string required The current_password of the user. Example: password
     * @bodyParam password string required The password of the user. Example: your_new_password
     * @bodyParam password_confirmation string required The password_confirmation of the user. Example: your_new_password
     * @return \Illuminate\Http\JsonResponse
     */

    public function changeUserPassword(ChangePasswordRequest $request){
      return $this->authService->changePassword($request->validated());
    }
}
