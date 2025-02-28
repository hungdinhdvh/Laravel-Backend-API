<?php

namespace App\Services;

use App\Models\User;
use App\Models\EmailVerificationToken;
use App\Notifications\EmailVerificationNotification;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;

class EmailVerificationService
{
    protected UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Send verification link to the user.
     *
     * @param User $user
     * @return void
     */
    public function sendVerificationEmail(User $user): void
    {
        try {
            $verificationLink = $this->generateVerificationLink($user->email);

            if ($verificationLink) {
                Notification::send($user, new EmailVerificationNotification($verificationLink));
            } else {
                Log::warning("Failed to generate a verification link for user: {$user->email}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to send verification email to user: {$user->email}. Error: {$e->getMessage()}");
        }
    }

    /*
     * Resend link with token
     */

    public function resendLink(string $email) {
        $user = $this->userRepository->findByEmail($email);
        if($user) {
            $this->sendVerificationEmail($user);
            return response()->json([
                "status" => "success",
                "message" => "Email verification link sent to user: {$user->email}"
            ], 201);
        }else {
            return response()->json([
                "status" => "failed",
                "message" => "User not found",
            ], 401);
        }
    }


    /**
     * Check if user email is already verified
     *
     * @param User $user
     * @return JsonResponse|null
     */
    public function checkIfEmailVerified(User $user): ?JsonResponse
    {
        if ($user->email_verified_at) {
            return response()->json([
                "status" => "failed",
                "message" => "Email already verified."
            ], 401);
        }
        return null;
    }

    /**
     * Verify user email with token
     *
     * @param string $email
     * @param string $token
     * @return JsonResponse
     */
    public function verifyEmail(string $email, string $token): JsonResponse
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            return response()->json([
                "status" => "failed",
                "message" => "User not found."
            ], 401);
        }

        $verifiedCheck = $this->checkIfEmailVerified($user);
        if ($verifiedCheck) {
            return $verifiedCheck;
        }

        $verificationToken = $this->verifyToken($email, $token);
        if (!$verificationToken instanceof EmailVerificationToken) {
            return $verificationToken;
        }

        if ($user->markEmailAsVerified()) {
            $verificationToken->delete();
            return response()->json([
                "status" => "success",
                "message" => "Email verified successfully."
            ], 200);
        }

        return response()->json([
            "status" => "failed",
            "message" => "Email verification failed, please try again."
        ], 401);
    }

    /**
     * Verify token validity and expiration
     *
     * @param string $email
     * @param string $token
     * @return EmailVerificationToken|JsonResponse
     */
    public function verifyToken(string $email, string $token): EmailVerificationToken|JsonResponse
    {
        $token = EmailVerificationToken::where("email", $email)
            ->where("token", $token)
            ->first();

        if (!$token) {
            $token->delete();
            return response()->json([
                'status' => 'failed',
                'message' => 'Invalid token',
            ], 401);
        }

        if ($token->expired_at < now()) {
            return response()->json([
                "status" => "failed",
                "message" => "Token expired"
            ], 401);
        }

        return $token;
    }

    /**
     * Generate a verification email link.
     *
     * @param string $email
     * @return string|null
     */
    public function generateVerificationLink(string $email): ?string
    {
        try {
            // Remove any existing token for the email
            EmailVerificationToken::where('email', $email)->delete();

            // Generate a new token and URL
            $token = Str::uuid();
            $url = config('app.url') . '/verify-email?token=' . $token . '&email=' . $email;

            // Save the token to the database
            $saveToken = EmailVerificationToken::create([
                'email' => $email,
                'token' => $token,
                'expired_at' => now()->addMinutes(60),
            ]);

            return $saveToken ? $url : null;
        } catch (\Exception $e) {
            Log::error("Failed to generate verification link for email: {$email}. Error: {$e->getMessage()}");
            return null;
        }
    }
}
