<?php

namespace App\Libraries;

use App\Models\TiktokProductModel;
use App\Models\TiktokShopConnectionModel;
use App\Models\TiktokSkuModel;
use CodeIgniter\HTTP\CURLRequest;
use Config\Services;
use RuntimeException;

class TiktokShopApiClient
{
    private TiktokShopConnectionModel $connections;
    private TiktokProductModel $products;
    private TiktokSkuModel $skus;
    private CURLRequest $http;
    private const TOKEN_REFRESH_BUFFER_SECONDS = 300;

    public function __construct()
    {
        $this->connections = new TiktokShopConnectionModel();
        $this->products = new TiktokProductModel();
        $this->skus = new TiktokSkuModel();
        $this->http = Services::curlrequest([
            'timeout' => 30,
            'http_errors' => false,
        ]);
    }

    public function connection(?int $connectionId = null): array
    {
        $connection = $this->connections->active($connectionId);

        if (! $connection) {
            throw new RuntimeException('TikTok shop connection is not configured.');
        }

        return $connection;
    }

    public function refreshToken(?int $connectionId = null): array
    {
        $connection = $this->connection($connectionId);

        if (empty($connection['refresh_token'])) {
            throw new RuntimeException('Refresh token is missing.');
        }

        $url = rtrim($connection['auth_base_url'], '/') . '/api/v2/token/refresh';
        $response = $this->http->get($url, [
            'query' => [
                'app_key'       => $connection['app_key'],
                'app_secret'    => $connection['app_secret'],
                'refresh_token' => $connection['refresh_token'],
                'grant_type'    => 'refresh_token',
            ],
        ]);

        $data = $this->decodeResponse($response->getBody());
        $tokenData = $data['data'] ?? [];

        if (! isset($tokenData['access_token'])) {
            $this->rememberError($connection, $data);
            throw new RuntimeException('TikTok refresh token failed.');
        }

        $this->connections->update($connection['id'], [
            'access_token'             => $tokenData['access_token'],
            'refresh_token'            => $tokenData['refresh_token'] ?? $connection['refresh_token'],
            'access_token_expires_at'  => $this->timestampToDate($tokenData['access_token_expire_in'] ?? null),
            'refresh_token_expires_at' => $this->timestampToDate($tokenData['refresh_token_expire_in'] ?? null),
            'last_error'               => null,
        ]);

        return $data;
    }

    public function authorizedShops(?int $connectionId = null): array
    {
        return $this->request('GET', '/authorization/202309/shops', [], null, $connectionId);
    }

    public function searchProducts(array $filters = [], ?int $connectionId = null): array
    {
        $query = [
            'page_size' => $filters['page_size'] ?? 20,
        ];

        if (! empty($filters['page_token'])) {
            $query['page_token'] = $filters['page_token'];
        }

        $body = array_filter([
            'status'      => $filters['status'] ?? null,
            'seller_skus' => $filters['seller_skus'] ?? null,
            'keyword'     => $filters['keyword'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        return $this->request('POST', '/product/202312/products/search', $query, $body, $connectionId);
    }

    public function orderDetail(string $ids, string $version = '202309', ?int $connectionId = null): array
    {
        return $this->request('GET', "/order/{$version}/orders", ['ids' => $ids], null, $connectionId);
    }

    public function orderDetailNew(string $ids, ?int $connectionId = null): array
    {
        return $this->orderDetail($ids, '202507', $connectionId);
    }

    public function productDetail(string $productId, ?int $connectionId = null): array
    {
        return $this->request('GET', "/product/202309/products/{$productId}", [
            'return_under_review_version' => 'true',
        ], null, $connectionId);
    }

    public function syncProductSkus(string $productId, ?int $connectionId = null): array
    {
        $detail = $this->productDetail($productId, $connectionId);
        $product = $detail['data'] ?? [];
        $skus = $product['skus'] ?? [];
        $updated = 0;
        $items = [];

        foreach ($skus as $sku) {
            $sellerSku = $sku['seller_sku'] ?? null;
            $tiktokSkuId = $sku['id'] ?? null;

            if (! $sellerSku || ! $tiktokSkuId) {
                continue;
            }

            $linked = $this->skus->where('seller_sku', $sellerSku)->first();

            if ($linked) {
                $this->skus->update($linked['id'], [
                    'tiktok_sku_id' => $tiktokSkuId,
                    'status'        => 'active',
                ]);
                $updated++;
                $items[] = $this->skus->find($linked['id']);
            }
        }

        if (! empty($product['id'])) {
            $localProduct = $this->products->where('tiktok_product_id', (string) $product['id'])->first();

            if ($localProduct) {
                $this->products->update($localProduct['id'], [
                    'name'   => $product['title'] ?? $localProduct['name'],
                    'status' => 'active',
                ]);
            }
        }

        return [
            'product' => $product,
            'updated' => $updated,
            'items'   => $items,
        ];
    }

    public function updateInventory(string $productId, string $skuId, int $quantity, ?int $connectionId = null): array
    {
        $body = [
            'skus' => [
                [
                    'id' => $skuId,
                    'inventory' => [
                        ['quantity' => $quantity],
                    ],
                ],
            ],
        ];

        return $this->request('POST', "/product/202309/products/{$productId}/inventory/update", [], $body, $connectionId);
    }

    public function request(string $method, string $path, array $query = [], ?array $body = null, ?int $connectionId = null): array
    {
        $connection = $this->connection($connectionId);
        if ($this->shouldRefreshAccessToken($connection)) {
            $this->refreshToken((int) $connection['id']);
            $connection = $this->connection((int) $connection['id']);
        }

        $bodyJson = $body === null ? '' : json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($bodyJson === false) {
            throw new RuntimeException('Could not encode TikTok request body.');
        }

        $data = $this->sendSignedRequest($connection, $method, $path, $query, $bodyJson);

        if ($this->isAccessTokenExpiredResponse($data) && ! empty($connection['refresh_token'])) {
            $this->refreshToken((int) $connection['id']);
            $connection = $this->connection((int) $connection['id']);
            $data = $this->sendSignedRequest($connection, $method, $path, $query, $bodyJson);
        }

        return $data;
    }

    private function sendSignedRequest(array $connection, string $method, string $path, array $query, string $bodyJson): array
    {
        $method = strtoupper($method);

        $query = array_merge([
            'app_key'     => $connection['app_key'],
            'timestamp'   => time(),
            'shop_cipher' => $connection['shop_cipher'],
        ], $query);
        $query['sign'] = $this->signature($path, $query, $connection['app_secret'], $method, 'normal', $bodyJson);

        $response = $this->http->request($method, rtrim($connection['base_url'], '/') . $path, [
            'query' => $query,
            'body' => $bodyJson,
            'headers' => array_filter([
                'content-type'        => 'application/json',
                'x-tts-access-token'  => $connection['access_token'] ?? null,
            ]),
        ]);

        $data = $this->decodeResponse($response->getBody());
        $code = (string) ($data['code'] ?? '0');
        $message = (string) ($data['message'] ?? '');

        if ($response->getStatusCode() >= 400 || ($code !== '0' && $message !== 'Success')) {
            $this->rememberError($connection, $data);
        } else {
            $this->connections->update($connection['id'], [
                'last_synced_at' => date('Y-m-d H:i:s'),
                'last_error'     => null,
            ]);
        }

        return $data;
    }

    private function shouldRefreshAccessToken(array $connection): bool
    {
        if (empty($connection['refresh_token'])) {
            return false;
        }

        if (empty($connection['access_token']) || empty($connection['access_token_expires_at'])) {
            return true;
        }

        $expiresAt = strtotime((string) $connection['access_token_expires_at']);

        if ($expiresAt === false) {
            return true;
        }

        return $expiresAt <= time() + self::TOKEN_REFRESH_BUFFER_SECONDS;
    }

    private function isAccessTokenExpiredResponse(array $data): bool
    {
        $code = strtolower((string) ($data['code'] ?? ''));
        $message = strtolower((string) ($data['message'] ?? ''));
        $raw = strtolower(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');

        foreach (['access_token', 'token', 'expire', 'expired', 'invalid'] as $needle) {
            if (str_contains($code, $needle) || str_contains($message, $needle)) {
                return str_contains($raw, 'token') || str_contains($raw, 'access_token');
            }
        }

        return str_contains($raw, 'access_token') && (str_contains($raw, 'expire') || str_contains($raw, 'expired') || str_contains($raw, 'invalid'));
    }

    public function signature(string $path, array|string $params, string $appSecret, string $method = 'GET', string $requestType = 'normal', string $body = ''): string
    {
        if (! is_array($params)) {
            parse_str($params, $params);
        }

        unset($params['sign'], $params['access_token']);
        ksort($params);

        $input = '';
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                continue;
            }

            $input .= $key . $value;
        }

        if (strtoupper($method) !== 'GET' && $requestType !== 'multipart/form-data') {
            $input .= $body;
        }

        $input = $appSecret . $path . $input . $appSecret;

        return bin2hex(hash_hmac('sha256', $input, $appSecret, true));
    }

    private function decodeResponse(string $body): array
    {
        $data = json_decode($body, true);

        return is_array($data) ? $data : ['raw' => $body];
    }

    private function timestampToDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $timestamp = (int) $value;

        if ($timestamp <= 0) {
            return null;
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    private function rememberError(array $connection, array $data): void
    {
        $this->connections->update($connection['id'], [
            'last_error' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }
}
