<?php

namespace App\Filters;

use App\Libraries\AuthContext;
use App\Models\PersonalAccessTokenModel;
use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('api_response');

        $header = $request->getHeaderLine('Authorization');

        if (! preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return api_error('Unauthenticated', [], 401);
        }

        $tokenValue = trim($matches[1]);
        $tokenHash = hash('sha256', $tokenValue);
        $tokenModel = new PersonalAccessTokenModel();
        $token = $tokenModel->where('token_hash', $tokenHash)->first();

        if (! $token) {
            return api_error('Invalid token', [], 401);
        }

        if ($token['expires_at'] !== null && strtotime($token['expires_at']) < time()) {
            return api_error('Token expired', [], 401);
        }

        $user = (new UserModel())->findWithRole((int) $token['user_id']);

        if (! $user) {
            return api_error('User is inactive or not found', [], 401);
        }

        $tokenModel->update($token['id'], ['last_used_at' => date('Y-m-d H:i:s')]);
        AuthContext::set($user, $token);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        AuthContext::clear();
    }
}

