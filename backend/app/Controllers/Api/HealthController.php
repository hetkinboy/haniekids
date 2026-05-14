<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class HealthController extends BaseController
{
    public function index(): ResponseInterface
    {
        return api_success('API is running');
    }
}

