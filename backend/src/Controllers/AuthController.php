<?php

namespace Src\Controllers;

use Src\Models\User;
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
            unset($user['password']);
            Response::success($user, 'Registration successful', 201);
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
        unset($user['password']);
        Response::success([
            'user' => $user,
            'token' => $jwt
        ], 'Login successful');
    }

    // GET /me
    public function me(): void
    {
        $user = $this->requireAuth();
        if (!$user) return;
        unset($user['password']);
        Response::success($user, 'Authenticated user');
    }
}
