<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\TiktokShopApiClient;
use App\Models\ProductSkuModel;
use App\Models\StockBySizeModel;
use App\Models\TiktokProductModel;
use App\Models\TiktokShopConnectionModel;
use App\Models\TiktokSkuModel;
use App\Models\TiktokWebhookEventModel;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Throwable;

class TiktokIntegrationController extends BaseController
{
    private TiktokShopConnectionModel $connections;
    private TiktokWebhookEventModel $webhookEvents;
    private ProductSkuModel $warehouseSkus;
    private StockBySizeModel $stock;
    private TiktokProductModel $tiktokProducts;
    private TiktokSkuModel $tiktokSkus;
    private TiktokShopApiClient $client;

    public function __construct()
    {
        $this->connections = new TiktokShopConnectionModel();
        $this->webhookEvents = new TiktokWebhookEventModel();
        $this->warehouseSkus = new ProductSkuModel();
        $this->stock = new StockBySizeModel();
        $this->tiktokProducts = new TiktokProductModel();
        $this->tiktokSkus = new TiktokSkuModel();
        $this->client = new TiktokShopApiClient();
    }

    public function connections(): ResponseInterface
    {
        $items = $this->connections->orderBy('id', 'DESC')->findAll();

        return api_success('Success', [
            'items' => array_map(fn (array $item): array => $this->maskConnection($item), $items),
        ]);
    }

    public function createConnection(): ResponseInterface
    {
        $payload = $this->payload();
        $errors = $this->validateConnection($payload);

        if ($errors !== []) {
            return api_error('Validation failed', $errors, 422);
        }

        $id = $this->connections->insert($this->connectionData($payload), true);

        return api_success('TikTok connection created', $this->maskConnection($this->connections->find($id)), 201);
    }

    public function updateConnection(int $id): ResponseInterface
    {
        $connection = $this->connections->find($id);

        if (! $connection) {
            return api_error('TikTok connection not found', [], 404);
        }

        $payload = $this->payload();
        $errors = $this->validateConnection($payload, $id);

        if ($errors !== []) {
            return api_error('Validation failed', $errors, 422);
        }

        $data = $this->connectionData($payload, false);
        $this->connections->update($id, $data);

        return api_success('TikTok connection updated', $this->maskConnection($this->connections->find($id)));
    }

    public function refreshToken(?int $id = null): ResponseInterface
    {
        try {
            return api_success('TikTok token refreshed', $this->client->refreshToken($id));
        } catch (Throwable $throwable) {
            return api_error($throwable->getMessage(), [], 500);
        }
    }

    public function authorizedShops(?int $id = null): ResponseInterface
    {
        try {
            return api_success('Success', $this->client->authorizedShops($id));
        } catch (Throwable $throwable) {
            return api_error($throwable->getMessage(), [], 500);
        }
    }

    public function productSearch(): ResponseInterface
    {
        $payload = $this->payload();

        try {
            return api_success('Success', $this->client->searchProducts($payload, $this->connectionId($payload)));
        } catch (Throwable $throwable) {
            return api_error($throwable->getMessage(), [], 500);
        }
    }

    public function importSearchResponse(): ResponseInterface
    {
        return $this->importSearchData($this->payload());
    }

    public function importSearchUrl(): ResponseInterface
    {
        $payload = $this->payload();
        $url = trim((string) ($payload['url'] ?? ''));

        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return api_error('Validation failed', ['url' => 'URL không hợp lệ.'], 422);
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (! in_array($scheme, ['http', 'https'], true)) {
            return api_error('Validation failed', ['url' => 'URL chỉ được dùng http hoặc https.'], 422);
        }

        try {
            $http = Services::curlrequest([
                'timeout' => 45,
                'http_errors' => false,
            ]);
            $response = $http->get($url, [
                'headers' => ['accept' => 'application/json'],
            ]);
            $body = $response->getBody();
            $json = json_decode($body, true);

            if (! is_array($json)) {
                return api_error('Import URL did not return valid JSON', [], 422);
            }

            return $this->importSearchData($json);
        } catch (Throwable $throwable) {
            return api_error($throwable->getMessage(), [], 500);
        }
    }

    private function importSearchData(array $payload): ResponseInterface
    {
        $products = $this->extractSearchProducts($payload);

        if ($products === []) {
            return api_error('Validation failed', ['data' => 'TikTok search response must contain data products.'], 422);
        }

        $summary = [
            'products_created' => 0,
            'products_updated' => 0,
            'skus_created'     => 0,
            'skus_updated'     => 0,
            'skus_linked'      => 0,
            'skus_unmatched'   => 0,
            'items'            => [],
        ];

        foreach ($products as $product) {
            $result = $this->importSearchProduct($product);
            foreach ($summary as $key => $value) {
                if ($key !== 'items') {
                    $summary[$key] += $result[$key] ?? 0;
                }
            }
            $summary['items'][] = $result['item'];
        }

        return api_success('TikTok search response imported', $summary);
    }

    public function orderDetail(string $ids): ResponseInterface
    {
        $version = trim((string) ($this->request->getGet('version') ?? '202309'));
        $connectionId = $this->queryConnectionId();

        try {
            return api_success('Success', $this->client->orderDetail($ids, $version, $connectionId));
        } catch (Throwable $throwable) {
            return api_error($throwable->getMessage(), [], 500);
        }
    }

    private function extractSearchProducts(array $payload): array
    {
        if (isset($payload['response']) && is_array($payload['response'])) {
            $payload = $payload['response'];
        }

        if (isset($payload['data']['products']) && is_array($payload['data']['products'])) {
            return $payload['data']['products'];
        }

        if (isset($payload['data']) && is_array($payload['data'])) {
            return array_is_list($payload['data']) ? $payload['data'] : [];
        }

        if (isset($payload['products']) && is_array($payload['products'])) {
            return $payload['products'];
        }

        return array_is_list($payload) ? $payload : [];
    }

    private function importSearchProduct(array $product): array
    {
        $tiktokProductId = (string) ($product['id'] ?? '');
        $skus = $product['skus'] ?? [];
        $matchedProductIds = [];

        foreach ($skus as $sku) {
            $sellerSku = trim((string) ($sku['seller_sku'] ?? ''));
            if ($sellerSku === '') {
                continue;
            }

            $warehouseSku = $this->warehouseSkus->where('sku_code', $sellerSku)->first();
            if ($warehouseSku) {
                $matchedProductIds[(int) $warehouseSku['product_id']] = true;
            }
        }

        $warehouseProductId = count($matchedProductIds) === 1 ? (int) array_key_first($matchedProductIds) : null;
        $existingProduct = $this->tiktokProducts->where('tiktok_product_id', $tiktokProductId)->first();
        $productData = [
            'product_id'        => $warehouseProductId,
            'tiktok_product_id' => $tiktokProductId,
            'name'              => $product['title'] ?? $tiktokProductId,
            'shop_name'         => null,
            'status'            => ($product['status'] ?? '') === 'ACTIVATE' ? 'active' : 'inactive',
        ];

        if ($existingProduct) {
            $this->tiktokProducts->update($existingProduct['id'], array_filter($productData, static fn (mixed $value): bool => $value !== null));
            $localProductId = (int) $existingProduct['id'];
            $productCreated = 0;
            $productUpdated = 1;
        } else {
            $localProductId = (int) $this->tiktokProducts->insert($productData, true);
            $productCreated = 1;
            $productUpdated = 0;
        }

        $skuCreated = 0;
        $skuUpdated = 0;
        $skuLinked = 0;
        $skuUnmatched = 0;
        $skuItems = [];

        foreach ($skus as $sku) {
            $sellerSku = trim((string) ($sku['seller_sku'] ?? ''));
            $tiktokSkuId = (string) ($sku['id'] ?? '');

            if ($sellerSku === '' || $tiktokSkuId === '') {
                continue;
            }

            $warehouseSku = $this->warehouseSkus->where('sku_code', $sellerSku)->first();
            $inventory = $sku['inventory'][0] ?? [];
            $skuData = [
                'tiktok_product_id'          => $localProductId,
                'product_sku_id'             => $warehouseSku['id'] ?? null,
                'tiktok_sku_id'              => $tiktokSkuId,
                'seller_sku'                 => $sellerSku,
                'name'                       => $sellerSku,
                'tiktok_price'               => (float) ($sku['price']['tax_exclusive_price'] ?? 0),
                'tiktok_inventory_quantity'  => (int) ($inventory['quantity'] ?? 0),
                'tiktok_warehouse_id'        => $inventory['warehouse_id'] ?? null,
                'status'                     => 'active',
            ];

            $existingSku = $this->tiktokSkus->where('tiktok_sku_id', $tiktokSkuId)->first();

            if ($existingSku) {
                $this->tiktokSkus->update($existingSku['id'], $skuData);
                $localSkuId = (int) $existingSku['id'];
                $skuUpdated++;
            } else {
                $localSkuId = (int) $this->tiktokSkus->insert($skuData, true);
                $skuCreated++;
            }

            if ($warehouseSku) {
                $skuLinked++;
            } else {
                $skuUnmatched++;
            }

            $skuItems[] = [
                'id'                 => $localSkuId,
                'seller_sku'         => $sellerSku,
                'tiktok_sku_id'      => $tiktokSkuId,
                'product_sku_id'     => $warehouseSku['id'] ?? null,
                'matched'            => (bool) $warehouseSku,
                'tiktok_price'       => $skuData['tiktok_price'],
                'tiktok_quantity'    => $skuData['tiktok_inventory_quantity'],
                'tiktok_warehouse_id'=> $skuData['tiktok_warehouse_id'],
            ];
        }

        return [
            'products_created' => $productCreated,
            'products_updated' => $productUpdated,
            'skus_created'     => $skuCreated,
            'skus_updated'     => $skuUpdated,
            'skus_linked'      => $skuLinked,
            'skus_unmatched'   => $skuUnmatched,
            'item'             => [
                'id'                => $localProductId,
                'tiktok_product_id' => $tiktokProductId,
                'name'              => $productData['name'],
                'product_id'        => $warehouseProductId,
                'skus'              => $skuItems,
            ],
        ];
    }

    public function orderDetailNew(string $ids): ResponseInterface
    {
        try {
            return api_success('Success', $this->client->orderDetailNew($ids, $this->queryConnectionId()));
        } catch (Throwable $throwable) {
            return api_error($throwable->getMessage(), [], 500);
        }
    }

    public function productDetail(string $productId): ResponseInterface
    {
        try {
            return api_success('Success', $this->client->productDetail($productId, $this->queryConnectionId()));
        } catch (Throwable $throwable) {
            return api_error($throwable->getMessage(), [], 500);
        }
    }

    public function syncProductSkus(string $productId): ResponseInterface
    {
        $payload = $this->payload();

        try {
            return api_success('TikTok product SKUs synced', $this->client->syncProductSkus($productId, $this->connectionId($payload)));
        } catch (Throwable $throwable) {
            return api_error($throwable->getMessage(), [], 500);
        }
    }

    public function updateInventory(): ResponseInterface
    {
        $payload = $this->payload();
        $errors = [];

        foreach (['product_id', 'sku_id', 'quantity'] as $field) {
            if (! array_key_exists($field, $payload) || $payload[$field] === '') {
                $errors[$field] = 'This field is required.';
            }
        }

        if ((int) ($payload['quantity'] ?? -1) < 0) {
            $errors['quantity'] = 'Quantity must be greater than or equal to 0.';
        }

        if ($errors !== []) {
            return api_error('Validation failed', $errors, 422);
        }

        try {
            $data = $this->client->updateInventory(
                (string) $payload['product_id'],
                (string) $payload['sku_id'],
                (int) $payload['quantity'],
                $this->connectionId($payload),
            );

            return api_success('TikTok inventory updated', $data);
        } catch (Throwable $throwable) {
            return api_error($throwable->getMessage(), [], 500);
        }
    }

    public function syncInventoryBySize(): ResponseInterface
    {
        $payload = $this->payload();
        $productId = (int) ($payload['product_id'] ?? 0);
        $sizeOptionId = (int) ($payload['size_option_id'] ?? 0);
        $stock = $this->stock->findByProductAndSize($productId, $sizeOptionId);

        if (! $stock) {
            return api_error('Stock by size not found', [], 404);
        }

        $quantityOnHand = array_key_exists('quantity_on_hand', $payload)
            ? (int) $payload['quantity_on_hand']
            : (int) $stock['quantity_on_hand'];

        $linkedSkus = $this->warehouseSkus
            ->select('product_skus.id, product_skus.combo_quantity, tiktok_skus.tiktok_sku_id, tiktok_products.tiktok_product_id')
            ->join('tiktok_skus', 'tiktok_skus.product_sku_id = product_skus.id')
            ->join('tiktok_products', 'tiktok_products.id = tiktok_skus.tiktok_product_id')
            ->where('product_skus.product_id', $productId)
            ->where('product_skus.size_option_id', $sizeOptionId)
            ->where('product_skus.is_active', 1)
            ->where('tiktok_skus.status', 'active')
            ->findAll();

        $results = [];

        foreach ($linkedSkus as $sku) {
            $comboQuantity = max(1, (int) $sku['combo_quantity']);
            $tiktokQuantity = intdiv(max(0, $quantityOnHand), $comboQuantity);

            try {
                $response = $this->client->updateInventory(
                    (string) $sku['tiktok_product_id'],
                    (string) $sku['tiktok_sku_id'],
                    $tiktokQuantity,
                    $this->connectionId($payload),
                );
                $results[] = [
                    'product_sku_id'    => (int) $sku['id'],
                    'tiktok_product_id' => $sku['tiktok_product_id'],
                    'tiktok_sku_id'     => $sku['tiktok_sku_id'],
                    'combo_quantity'    => $comboQuantity,
                    'quantity'          => $tiktokQuantity,
                    'status'            => 'synced',
                    'response'          => $response,
                ];
            } catch (Throwable $throwable) {
                $results[] = [
                    'product_sku_id'    => (int) $sku['id'],
                    'tiktok_product_id' => $sku['tiktok_product_id'],
                    'tiktok_sku_id'     => $sku['tiktok_sku_id'],
                    'combo_quantity'    => $comboQuantity,
                    'quantity'          => $tiktokQuantity,
                    'status'            => 'failed',
                    'error'             => $throwable->getMessage(),
                ];
            }
        }

        return api_success('TikTok inventory sync completed', [
            'quantity_on_hand' => $quantityOnHand,
            'items'            => $results,
        ]);
    }

    public function sign(): ResponseInterface
    {
        $payload = $this->payload();
        $connection = $this->connections->find((int) ($payload['connection_id'] ?? 0));

        if (! $connection) {
            return api_error('TikTok connection not found', [], 404);
        }

        $body = $payload['body'] ?? '';
        $bodyJson = is_array($body) ? json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string) $body;

        return api_success('Success', [
            'sign' => $this->client->signature(
                (string) ($payload['path'] ?? ''),
                $payload['params'] ?? [],
                $connection['app_secret'],
                (string) ($payload['method'] ?? 'GET'),
                (string) ($payload['request_type'] ?? 'normal'),
                $bodyJson ?: '',
            ),
        ]);
    }

    public function webhook(): ResponseInterface
    {
        $payload = $this->payload();
        $eventId = $this->webhookEvents->insert([
            'connection_id'  => $this->connectionId($payload),
            'event_type'     => $payload['type'] ?? $payload['event_type'] ?? null,
            'order_id'       => $payload['data']['order_id'] ?? $payload['order_id'] ?? null,
            'order_status'   => $payload['data']['order_status'] ?? $payload['order_status'] ?? null,
            'payload_json'   => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'process_status' => 'received',
            'received_at'    => date('Y-m-d H:i:s'),
        ], true);

        log_message('info', 'TikTok webhook received #' . $eventId . ': ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return api_success('Webhook received', ['id' => $eventId]);
    }

    public function webhookEvents(): ResponseInterface
    {
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $pageSize = min(100, max(1, (int) ($this->request->getGet('pageSize') ?? 20)));
        $status = trim((string) $this->request->getGet('process_status'));

        $builder = $this->webhookEvents;

        if ($status !== '') {
            $builder = $builder->where('process_status', $status);
        }

        $items = $builder->orderBy('id', 'DESC')->paginate($pageSize, 'default', $page);

        return api_success('Success', [
            'items' => $items,
            'pager' => [
                'page'     => $this->webhookEvents->pager->getCurrentPage(),
                'pageSize' => $pageSize,
                'total'    => $this->webhookEvents->pager->getTotal(),
            ],
        ]);
    }

    private function validateConnection(array $payload, ?int $id = null): array
    {
        $errors = [];

        foreach (['shop_cipher', 'app_key'] as $field) {
            if (trim((string) ($payload[$field] ?? '')) === '') {
                $errors[$field] = 'This field is required.';
            }
        }

        if ($id === null && trim((string) ($payload['app_secret'] ?? '')) === '') {
            $errors['app_secret'] = 'This field is required.';
        }

        return $errors;
    }

    private function connectionData(array $payload, bool $requireSecret = true): array
    {
        $data = [
            'shop_name'                => $this->emptyToNull($payload['shop_name'] ?? null),
            'shop_id'                  => $this->emptyToNull($payload['shop_id'] ?? null),
            'shop_cipher'              => trim((string) $payload['shop_cipher']),
            'app_key'                  => trim((string) $payload['app_key']),
            'base_url'                 => $this->emptyToNull($payload['base_url'] ?? null) ?? 'https://open-api.tiktokglobalshop.com',
            'auth_base_url'            => $this->emptyToNull($payload['auth_base_url'] ?? null) ?? 'https://auth.tiktok-shops.com',
            'status'                   => $payload['status'] ?? 'active',
        ];

        if ($requireSecret || trim((string) ($payload['app_secret'] ?? '')) !== '') {
            $data['app_secret'] = trim((string) $payload['app_secret']);
        }

        foreach (['access_token', 'refresh_token', 'access_token_expires_at', 'refresh_token_expires_at'] as $field) {
            if ($requireSecret || array_key_exists($field, $payload)) {
                $data[$field] = $this->emptyToNull($payload[$field] ?? null);
            }
        }

        return $data;
    }

    private function maskConnection(array $connection): array
    {
        if (isset($connection['app_secret'])) {
            $connection['app_secret'] = $this->mask($connection['app_secret']);
        }

        if (isset($connection['access_token'])) {
            $connection['access_token'] = $this->mask($connection['access_token']);
        }

        if (isset($connection['refresh_token'])) {
            $connection['refresh_token'] = $this->mask($connection['refresh_token']);
        }

        return $connection;
    }

    private function payload(): array
    {
        return $this->request->getJSON(true) ?? $this->request->getRawInput() ?? $this->request->getPost();
    }

    private function connectionId(array $payload): ?int
    {
        if (isset($payload['connection_id']) && $payload['connection_id'] !== '') {
            return (int) $payload['connection_id'];
        }

        return $this->queryConnectionId();
    }

    private function queryConnectionId(): ?int
    {
        $id = $this->request->getGet('connection_id');

        return $id === null || $id === '' ? null : (int) $id;
    }

    private function emptyToNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function mask(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return substr($value, 0, 6) . '...' . substr($value, -4);
    }
}
