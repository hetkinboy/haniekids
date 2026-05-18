<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\AuthContext;
use App\Models\PersonalAccessTokenModel;
use App\Models\RoleModel;
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
        $expiresAt = ($user['role'] ?? null) === 'admin'
            ? null
            : date('Y-m-d H:i:s', strtotime('+30 days'));

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

    public function users(): ResponseInterface
    {
        $users = (new UserModel())->listWithRole();

        return api_success('Success', [
            'items' => array_map(fn (array $user) => $this->publicUser($user), $users),
        ]);
    }

    public function createUser(): ResponseInterface
    {
        $payload = $this->request->getJSON(true) ?? $this->request->getPost();

        $rules = [
            'name'     => 'required|min_length[2]|max_length[150]',
            'email'    => 'required|valid_email|max_length[190]',
            'password' => 'required|min_length[8]',
            'role'     => 'required|in_list[admin,member]',
            'status'   => 'permit_empty|in_list[active,inactive]',
        ];

        if (! $this->validateData($payload, $rules)) {
            return api_error('Validation failed', $this->validator->getErrors(), 422);
        }

        $userModel = new UserModel();
        $email = strtolower(trim($payload['email']));

        if ($userModel->withDeleted()->where('email', $email)->first()) {
            return api_error('Email already exists', ['email' => 'Email already exists'], 422);
        }

        $role = (new RoleModel())->where('name', $payload['role'])->first();

        if (! $role) {
            return api_error('Role is invalid', ['role' => 'Role is invalid'], 422);
        }

        $id = $userModel->insert([
            'role_id'       => $role['id'],
            'name'          => trim($payload['name']),
            'email'         => $email,
            'password_hash' => password_hash($payload['password'], PASSWORD_DEFAULT),
            'status'        => $payload['status'] ?? 'active',
        ], true);

        return api_success('User created', [
            'user' => $this->publicUser($userModel->findAnyWithRole((int) $id)),
        ], 201);
    }

    public function updateUser(int $id): ResponseInterface
    {
        $payload = $this->request->getJSON(true) ?? $this->request->getRawInput();

        $rules = [
            'name'     => 'required|min_length[2]|max_length[150]',
            'email'    => 'required|valid_email|max_length[190]',
            'password' => 'permit_empty|min_length[8]',
            'role'     => 'required|in_list[admin,member]',
            'status'   => 'required|in_list[active,inactive]',
        ];

        if (! $this->validateData($payload, $rules)) {
            return api_error('Validation failed', $this->validator->getErrors(), 422);
        }

        $userModel = new UserModel();
        $user = $userModel->find($id);

        if (! $user) {
            return api_error('User not found', [], 404);
        }

        $email = strtolower(trim($payload['email']));
        $existing = $userModel->withDeleted()->where('email', $email)->where('id !=', $id)->first();

        if ($existing) {
            return api_error('Email already exists', ['email' => 'Email already exists'], 422);
        }

        $role = (new RoleModel())->where('name', $payload['role'])->first();

        if (! $role) {
            return api_error('Role is invalid', ['role' => 'Role is invalid'], 422);
        }

        $data = [
            'role_id' => $role['id'],
            'name'    => trim($payload['name']),
            'email'   => $email,
            'status'  => $payload['status'],
        ];

        if (! empty($payload['password'])) {
            $data['password_hash'] = password_hash($payload['password'], PASSWORD_DEFAULT);
        }

        $userModel->update($id, $data);

        if ($payload['status'] !== 'active' || ! empty($payload['password'])) {
            (new PersonalAccessTokenModel())->where('user_id', $id)->delete();
        }

        return api_success('User updated', [
            'user' => $this->publicUser($userModel->findAnyWithRole($id)),
        ]);
    }

    public function changePassword(): ResponseInterface
    {
        $payload = $this->request->getJSON(true) ?? $this->request->getPost();

        $rules = [
            'current_password' => 'required',
            'new_password'     => 'required|min_length[8]',
        ];

        if (! $this->validateData($payload, $rules)) {
            return api_error('Validation failed', $this->validator->getErrors(), 422);
        }

        if (($payload['current_password'] ?? '') === ($payload['new_password'] ?? '')) {
            return api_error('Validation failed', ['new_password' => 'New password must be different'], 422);
        }

        $authUser = AuthContext::user();

        if (! $authUser) {
            return api_error('Unauthenticated', [], 401);
        }

        $userModel = new UserModel();
        $user = $userModel->find((int) $authUser['id']);

        if (! $user || ! password_verify($payload['current_password'], $user['password_hash'])) {
            return api_error('Current password is incorrect', ['current_password' => 'Current password is incorrect'], 422);
        }

        $userModel->update($user['id'], [
            'password_hash' => password_hash($payload['new_password'], PASSWORD_DEFAULT),
        ]);

        $token = AuthContext::token();
        $tokenModel = new PersonalAccessTokenModel();
        $tokenBuilder = $tokenModel->where('user_id', $user['id']);

        if ($token) {
            $tokenBuilder->where('id !=', $token['id']);
        }

        $tokenBuilder->delete();

        return api_success('Password changed');
    }

    public function deleteUser(int $id): ResponseInterface
    {
        $authUser = AuthContext::user();

        if (! $authUser) {
            return api_error('Unauthenticated', [], 401);
        }

        if ((int) $authUser['id'] === $id) {
            return api_error('You cannot delete your own account', [], 422);
        }

        $userModel = new UserModel();
        $user = $userModel->find($id);

        if (! $user) {
            return api_error('User not found', [], 404);
        }

        (new PersonalAccessTokenModel())->where('user_id', $id)->delete();
        $userModel->delete($id);

        return api_success('User deleted');
    }

    private function publicUser(?array $user): array
    {
        if (! $user) {
            return [];
        }

        return [
            'id'            => (int) $user['id'],
            'name'          => $user['name'],
            'email'         => $user['email'],
            'role'          => $user['role'],
            'status'        => $user['status'],
            'last_login_at' => $user['last_login_at'] ?? null,
        ];
    }
}
