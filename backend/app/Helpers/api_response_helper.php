<?php

if (! function_exists('api_success')) {
    function api_success(string $message = 'Success', mixed $data = null, int $statusCode = 200): \CodeIgniter\HTTP\ResponseInterface
    {
        $payload = [
            'status'  => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $payload['data'] = $data;
        }

        return service('response')->setStatusCode($statusCode)->setJSON($payload);
    }
}

if (! function_exists('api_error')) {
    function api_error(string $message = 'Error', array $errors = [], int $statusCode = 400): \CodeIgniter\HTTP\ResponseInterface
    {
        $payload = [
            'status'  => false,
            'message' => $message,
        ];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return service('response')->setStatusCode($statusCode)->setJSON($payload);
    }
}

