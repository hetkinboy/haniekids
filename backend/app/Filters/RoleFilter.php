<?php

namespace App\Filters;

use App\Libraries\AuthContext;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('api_response');

        $user = AuthContext::user();

        if (! $user) {
            return api_error('Unauthenticated', [], 401);
        }

        $allowedRoles = $arguments ?? [];

        if ($allowedRoles !== [] && ! in_array($user['role'], $allowedRoles, true)) {
            return api_error('Forbidden', [], 403);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}

