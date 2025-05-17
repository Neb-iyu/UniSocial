<?php

namespace Src\Controllers;

use Src\Models\User;
use Src\Models\PasswordReset;
use Src\Utilities\MailService;
use Src\Core\Response;
use Firebase\JWT\JWT;
use Src\Core\Auth;
use Src\Utilities\Validator;

class AuthController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
    }

    // POST /register
    public function register(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $input = Validator::sanitizeInput($input);
        $errors = [];
        if (empty($input['email']) || !Validator::email($input['email'])) {
            $errors['email'] = 'Valid email is required';
        }
        if (empty($input['password']) || !Validator::password($input['password'])) {
            $errors['password'] = 'Password must be at least 8 characters, include a letter and a number';
        }
        if (empty($input['username']) || !Validator::username($input['username'])) {
            $errors['username'] = 'Username must be 5-20 chars, alphanumeric or underscore';
        }
        if (empty($input['fullname'])) {
            $errors['fullname'] = 'Full Name is required';
        }
        if ($errors) {
            Response::validationError($errors);
            return;
        }
        if ($this->userModel->findByEmail($input['email'])) {
            Response::error('Email already registered', 409);
            return;
        }
        if ($this->userModel->findByUsername($input['username'])) {
            Response::error('Username already taken', 409);
            return;
        }
        $userId = $this->userModel->create($input);
        if ($userId) {
            $user = $this->userModel->find($userId);

            $filteredUser = $this->filterUserResponse($user);

            // JWT token for immediate login
            $jwt = $this->auth->generateToken($user);

            Response::success([
                'user' => $filteredUser,
                'token' => $jwt
            ], 'Registration successful', 201);
        } else {
            Response::error('Registration failed', 500);
        }
    }

    // POST /login
    public function login(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $input = Validator::sanitizeInput($input);
        if (empty($input['login']) || empty($input['password'])) {
            Response::validationError([
                'login' => 'Email or username is required',
                'password' => 'Password is required'
            ]);
            return;
        }
        $login = $input['login'];
        $user = Validator::email($login)
            ? $this->userModel->findByEmail($login)
            : $this->userModel->findByUsername($login);
        if (!$user || !password_verify($input['password'], $user['password'])) {
            Response::unauthorized('Invalid credentials');
            return;
        }
        $jwt = $this->auth->generateToken($user);
        // For login, only return minimal user data
        $userData = $this->filterUserResponse($user, true);
        Response::success([
            'user' => $userData,
            'token' => $jwt
        ], 'Login successful');
    }

    // GET /me
    public function me(): void
    {
        $user = $this->requireAuth();
        if (!$user) return;
        $filteredUser = $this->filterUserResponse($user);
        Response::success($filteredUser, 'Authenticated user');
    }

    // POST /password-reset/request
    public function sendResetCode(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';
        $email = Validator::sanitizeInput(['email' => $email])['email'];

        if (empty($email) || !Validator::email($email)) {
            Response::validationError(['email' => 'Valid email is required']);
            return;
        }

        $user = $this->userModel->findByEmail($email);
        if (!$user) {
            Response::error('No user found with this email', 404);
            return;
        }

        // Generate code (6-digit code)
        $code = rand(100000, 999999);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        // Store code
        $passwordResetModel = new PasswordReset();
        $passwordResetModel->createCode($user['id'], $code, $expiresAt);

        // Send email
        $subject = 'Your Unifyze Code';
        $sent = MailService::sendEmail(
            $user['email'],
            $subject,
            '',
            $user['fullname'] ?? '',
            ['code' => $code],
            'reset_code'
        );

        if ($sent) {
            Response::success(['message' => 'Reset code sent to your email.']);
        } else {
            Response::error('Failed to send email. Please try again later.', 500);
        }
    }

    // POST /password-reset/verify
    public function verifyResetCode(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $code = $input['code'] ?? '';
        $code = Validator::sanitizeInput(['code' => $code])['code'];

        if (empty($code)) {
            Response::validationError(['code' => 'Code is required']);
            return;
        }

        $passwordResetModel = new PasswordReset();
        $resetEntry = $passwordResetModel->getByCode($code);
        if (!$passwordResetModel->isCodeValid($resetEntry)) {
            Response::error('Invalid or expired code.', 400);
            return;
        }

        // Mark code as used (optional: defer until password is actually reset)
        $passwordResetModel->markCodeAsUsed($resetEntry['id']);
        Response::success(['user_id' => $resetEntry['user_id'], 'message' => 'Code verified. You may now reset your password.']);
    }

    private function filterUserResponse(array $user, bool $minimal = false): array
    {
        // sensitive fields
        unset($user['password'], $user['is_deleted'], $user['deleted_at']);


        $profilePictureUrl = $this->userModel->getProfilePictureUrl($user['id']);

        if ($minimal) {
            // Return only essential fields for login/authentication
            return [
                'public_uuid' => $user['public_uuid'],
                'username' => $user['username'],
                'email' => $user['email'],
                'fullname' => $user['fullname'] ?? null,
                'profile_picture_url' => $profilePictureUrl
            ];
        }

        // For other responses, include all non-sensitive fields
        $user['profile_picture_url'] = $profilePictureUrl;
        $user['bio'] = $user['bio'] ?? null;
        $user['university_id'] = $user['university_id'] ?? null;
        $user['year_of_study'] = $user['year_of_study'] ?? null;
        $user['gender'] = $user['gender'] ?? null;

        return $user;
    }
}
