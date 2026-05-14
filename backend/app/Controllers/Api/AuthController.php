<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\AuthContext;
use App\Models\PersonalAccessTokenModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class AuthController extends BaseController
{
    public function login(): ResponseInterface
    {
        $payload = $this->request->getJSON(true) ?? $this->request->getPost();

        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required',
        ];

        if (! $this->validateData($payload, $rules)) {
            return api_error('Validation failed', $this->validator->getErrors(), 422);
        }

        $userModel = new UserModel();
        $user = $userModel->findActiveByEmail($payload['email']);

        if (! $user || ! password_verify($payload['password'], $user['password_hash'])) {
            return api_error('Email or password is incorrect', [], 401);
        }

        $plainToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

        (new PersonalAccessTokenModel())->insert([
            'user_id'    => $user['id'],
            'token_hash' => hash('sha256', $plainToken),
            'name'       => 'api',
            'expires_at' => $expiresAt,
        ]);

        $userModel->update($user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);

        return api_success('Login successful', [
            'token'      => $plainToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt,
            'user'       => $this->publicUser($user),
        ]);
    }

    public function me(): ResponseInterface
    {
        return api_success('Success', [
            'user' => $this->publicUser(AuthContext::user()),
        ]);
    }

    public function logout(): ResponseInterface
    {
        $token = AuthContext::token();

        if ($token) {
            (new PersonalAccessTokenModel())->delete($token['id']);
        }

        return api_success('Logout successful');
    }

    private function publicUser(?array $user): array
    {
        if (! $user) {
            return [];
        }

        return [
            'id'     => (int) $user['id'],
            'name'   => $user['name'],
            'email'  => $user['email'],
            'role'   => $user['role'],
            'status' => $user['status'],
        ];
    }
}

