<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\OrderProfitCalculator;
use App\Libraries\TiktokShopApiClient;
use App\Models\OrderItemModel;
use App\Models\OrderModel;
use App\Models\ProductSkuModel;
use App\Models\StockBySizeModel;
use App\Models\StockMovementModel;
use App\Models\TiktokProductModel;
use App\Models\TiktokShopConnectionModel;
use App\Models\TiktokSkuModel;
use App\Models\TiktokWebhookEventModel;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use RuntimeException;
use Throwable;

class TiktokIntegrationController extends BaseController
{
    private TiktokShopConnectionModel $connections;
    private TiktokWebhookEventModel $webhookEvents;
    private OrderModel $orders;
    private OrderItemModel $orderItems;
    private ProductSkuModel $warehouseSkus;
    private StockBySizeModel $stock;
    private StockMovementModel $movements;
    private TiktokProductModel $tiktokProducts;
    private TiktokSkuModel $tiktokSkus;
    private TiktokShopApiClient $client;
    private OrderProfitCalculator $profitCalculator;

    public function __construct()
    {
        $this->connections = new TiktokShopConnectionModel();
        $this->webhookEvents = new TiktokWebhookEventModel();
        $this->orders = new OrderModel();
        $this->orderItems = new OrderItemModel();
        $this->warehouseSkus = new ProductSkuModel();
        $this->stock = new StockBySizeModel();
        $this->movements = new StockMovementModel();
        $this->tiktokProducts = new TiktokProductModel();
        $this->tiktokSkus = new TiktokSkuModel();
        $this->client = new TiktokShopApiClient();
        $this->profitCalculator = new OrderProfitCalculator();
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

        $existing = $this->connections->orderBy('id', 'ASC')->first();

        if ($existing) {
            $this->connections->update((int) $existing['id'], $this->connectionData($payload));

            return api_success('TikTok connection updated', $this->maskConnection($this->connections->find((int) $existing['id'])));
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

    public function productSearchPreview(): ResponseInterface
    {
        $payload = $this->tiktokReadFilters();

        try {
            $response = $this->client->searchProducts($payload, $this->connectionId($payload));

            return api_success('Success', [
                'raw'     => $response,
                'preview' => $this->previewSearchData($response),
            ]);
        } catch (Throwable $throwable) {
            return api_error($throwable->getMessage(), [], 500);
        }
    }

    public function previewSearchResponse(): ResponseInterface
    {
        return api_success('Success', $this->previewSearchData($this->payload()));
    }

    public function previewSearchUrl(): ResponseInterface
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
                return api_error('URL did not return valid JSON', [], 422);
            }

            return api_success('Success', [
                'raw'     => $json,
                'preview' => $this->previewSearchData($json),
            ]);
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

    private function previewSearchData(array $payload): array
    {
        $products = $this->extractSearchProducts($payload);
        $items = [];

        foreach ($products as $product) {
            if (! is_array($product)) {
                continue;
            }

            $skus = is_array($product['skus'] ?? null) ? $product['skus'] : [];
            $skuItems = [];

            foreach ($skus as $sku) {
                if (! is_array($sku)) {
                    continue;
                }

                $sellerSku = trim((string) ($sku['seller_sku'] ?? ''));
                $inventory = $sku['inventory'][0] ?? [];
                $warehouseSku = $sellerSku === '' ? null : $this->warehouseSkus->where('sku_code', $sellerSku)->first();

                $skuItems[] = [
                    'tiktok_sku_id'     => (string) ($sku['id'] ?? ''),
                    'seller_sku'        => $sellerSku,
                    'matched_sku_id'    => $warehouseSku['id'] ?? null,
                    'matched_sku_code'  => $warehouseSku['sku_code'] ?? null,
                    'price'             => (float) ($sku['price']['tax_exclusive_price'] ?? 0),
                    'inventory_quantity'=> (int) ($inventory['quantity'] ?? 0),
                    'warehouse_id'      => $inventory['warehouse_id'] ?? null,
                ];
            }

            $items[] = [
                'tiktok_product_id' => (string) ($product['id'] ?? ''),
                'name'              => $product['title'] ?? $product['name'] ?? '',
                'status'            => $product['status'] ?? null,
                'sku_count'         => count($skuItems),
                'matched_sku_count' => count(array_filter($skuItems, static fn (array $sku): bool => $sku['matched_sku_id'] !== null)),
                'skus'              => $skuItems,
            ];
        }

        return [
            'mode'          => 'read_only',
            'will_write_db' => false,
            'product_count' => count($items),
            'sku_count'     => array_sum(array_map(static fn (array $item): int => $item['sku_count'], $items)),
            'items'         => $items,
        ];
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

    public function importOrderDetailNew(string $ids): ResponseInterface
    {
        try {
            $detail = $this->client->orderDetailNew($ids, $this->queryConnectionId());
            $imported = $this->importTiktokOrderDetail($detail);

            if (! $imported) {
                return api_error('Order detail response did not contain orders', ['order_id' => $ids], 422);
            }

            $orders = [];
            foreach (explode(',', $ids) as $orderId) {
                $orderId = trim($orderId);
                if ($orderId === '') {
                    continue;
                }

                $order = $this->orders->where('tiktok_order_id', $orderId)->first();
                if ($order) {
                    $orders[] = $order;
                }
            }

            return api_success('TikTok order imported', [
                'raw'    => $detail,
                'orders' => $orders,
            ]);
        } catch (Throwable $throwable) {
            return api_error($throwable->getMessage(), [], 500);
        }
    }

    public function importLegacyRevenue(): ResponseInterface
    {
        $payload = $this->payload();
        $url = trim((string) ($payload['url'] ?? 'https://shopapi.totdep.com/api/doanhthu'));
        $dryRun = filter_var($payload['dry_run'] ?? false, FILTER_VALIDATE_BOOLEAN);
        [$start, $end] = $this->legacyDateRange($payload['start'] ?? null, $payload['end'] ?? null);

        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return api_error('Validation failed', ['url' => 'URL không hợp lệ.'], 422);
        }

        try {
            $http = Services::curlrequest([
                'timeout' => 90,
                'http_errors' => false,
            ]);
            $response = $http->post($url, [
                'headers' => ['accept' => 'application/json'],
                'json' => [
                    'start' => $start,
                    'end' => $end,
                ],
            ]);
            $json = json_decode($response->getBody(), true);
        } catch (Throwable $throwable) {
            return api_error($throwable->getMessage(), [], 500);
        }

        if (! is_array($json)) {
            return api_error('URL did not return valid JSON', [], 422);
        }

        $rows = is_array($json['data']['list'] ?? null) ? $json['data']['list'] : [];
        $summary = [
            'dry_run' => $dryRun,
            'start' => $start,
            'end' => $end,
            'rows_seen' => count($rows),
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'items' => [],
        ];

        if (! $dryRun) {
            $this->orders->db->transStart();
        }

        foreach ($rows as $row) {
            if (! is_array($row) || trim((string) ($row['order_id'] ?? '')) === '') {
                $summary['skipped']++;
                continue;
            }

            $result = $this->upsertLegacyRevenueOrder($row, $dryRun);
            $summary[$result['action']]++;
            $summary['items'][] = $result;
        }

        if (! $dryRun) {
            $this->orders->db->transComplete();

            if ($this->orders->db->transStatus() === false) {
                return api_error('Could not import legacy TikTok revenue', [], 500);
            }
        }

        return api_success('Legacy TikTok revenue imported', $summary);
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
        $shopId = $this->extractWebhookShopId($payload);
        $connection = $this->resolveWebhookConnection($shopId);
        $eventType = $this->extractWebhookEventType($payload);
        $orderId = $this->extractWebhookOrderId($payload);
        $orderStatus = $this->extractWebhookOrderStatus($payload);
        $processStatus = $connection === null ? 'rejected' : 'received';
        $errorMessage = $connection === null ? 'Webhook shop_id is not configured or inactive.' : null;

        $eventId = $this->webhookEvents->insert([
            'connection_id'  => $connection['id'] ?? null,
            'shop_id'        => $shopId,
            'event_type'     => $eventType,
            'order_id'       => $orderId,
            'order_status'   => $orderStatus,
            'payload_json'   => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'process_status' => $processStatus,
            'error_message'  => $errorMessage,
            'received_at'    => date('Y-m-d H:i:s'),
        ], true);

        log_message('info', 'TikTok webhook received #' . $eventId . ': ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        if ($connection === null) {
            log_message('warning', 'TikTok webhook rejected #' . $eventId . ' for unknown shop_id: ' . ($shopId ?? '(missing)'));

            return api_success('Webhook rejected: unknown shop', ['id' => $eventId, 'process_status' => 'rejected']);
        }

        $importedOrder = false;
        $importError = null;

        if ($orderId !== null) {
            try {
                $detail = $this->client->orderDetailNew($orderId, (int) $connection['id']);
                $importedOrder = $this->importTiktokOrderDetail($detail);
            } catch (Throwable $throwable) {
                $importError = $throwable->getMessage();
            }
        }

        $updatedOrder = $importedOrder || $this->applyWebhookOrderStatus($orderId, $orderStatus);
        $this->webhookEvents->update($eventId, [
            'process_status' => $importError !== null ? 'failed' : ($updatedOrder ? 'processed' : 'received'),
            'error_message'  => $importError,
            'processed_at'   => $updatedOrder ? date('Y-m-d H:i:s') : null,
        ]);

        return api_success('Webhook received', [
            'id'             => $eventId,
            'connection_id'  => (int) $connection['id'],
            'shop_id'        => $shopId,
            'process_status' => $importError !== null ? 'failed' : ($updatedOrder ? 'processed' : 'received'),
            'order_updated'  => $updatedOrder,
            'error'          => $importError,
        ]);
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

    private function extractWebhookShopId(array $payload): ?string
    {
        return $this->emptyToNull($payload['shop_id'] ?? ($payload['data']['shop_id'] ?? null));
    }

    private function legacyDateRange(mixed $start, mixed $end): array
    {
        $startDate = $this->normalizeLegacyDate($start) ?? date('Y-m-d');
        $endDate = $this->normalizeLegacyDate($end) ?? date('Y-m-d');

        if (strtotime($startDate) > strtotime($endDate)) {
            return [$endDate, $startDate];
        }

        return [$startDate, $endDate];
    }

    private function normalizeLegacyDate(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }

    private function upsertLegacyRevenueOrder(array $row, bool $dryRun): array
    {
        $orderId = trim((string) $row['order_id']);
        $rawStatus = trim((string) ($row['order_status'] ?? ''));
        $status = $this->mapWebhookOrderStatus($rawStatus) ?? 'pending';
        $customerPaid = (float) ($row['total_amount'] ?? 0);
        $platformDiscount = (float) ($row['platform_discount'] ?? 0);
        $grossAmount = (float) ($row['sub_total'] ?? $customerPaid) + $platformDiscount;
        $netRevenue = (float) ($row['doanhthu'] ?? 0);
        $totalProfit = (float) ($row['loinhuan'] ?? 0);
        $totalCost = max(0, $netRevenue - $totalProfit);
        $totalFees = max(0, $grossAmount - $netRevenue);
        $rawJson = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $existing = $this->orders->where('tiktok_order_id', $orderId)->first();
        $data = [
            'order_code' => $orderId,
            'platform' => 'tiktok',
            'tiktok_order_id' => $orderId,
            'tiktok_status' => $this->emptyToNull($rawStatus),
            'tiktok_raw_json' => $rawJson === false ? null : $rawJson,
            'order_date' => $row['createAt'] ?? date('Y-m-d H:i:s'),
            'status' => $status,
            'gross_amount' => $grossAmount,
            'discount_amount' => $platformDiscount,
            'platform_fee' => $totalFees,
            'transaction_fee' => 0,
            'shipping_fee' => 0,
            'cod_amount' => $customerPaid,
            'net_revenue' => $netRevenue,
            'total_cost' => $totalCost,
            'total_profit' => $totalProfit,
            'return_fee' => 0,
            'note' => 'Import doanhthu legacy #' . ($row['id'] ?? ''),
        ];

        if (! $existing) {
            $data['stock_deducted'] = 0;
            $data['stock_returned'] = in_array($status, ['cancelled', 'returned'], true) ? 1 : 0;
        }

        if ($dryRun) {
            return [
                'action' => $existing ? 'updated' : 'created',
                'order_id' => $orderId,
                'status' => $status,
                'gross_amount' => $grossAmount,
                'net_revenue' => $netRevenue,
                'total_cost' => $totalCost,
                'total_profit' => $totalProfit,
            ];
        }

        if ($existing) {
            $this->orders->update((int) $existing['id'], $data);

            if (in_array($status, ['cancelled', 'returned'], true)) {
                $updatedOrder = $this->orders->find((int) $existing['id']);
                if ($updatedOrder && (int) ($updatedOrder['stock_deducted'] ?? 0) === 1 && (int) ($updatedOrder['stock_returned'] ?? 0) === 0) {
                    $this->applyOrderStockState((int) $existing['id'], $status);
                }
            }

            return [
                'action' => 'updated',
                'order_id' => $orderId,
                'local_order_id' => (int) $existing['id'],
                'status' => $status,
            ];
        }

        $newId = (int) $this->orders->insert($data, true);

        return [
            'action' => 'created',
            'order_id' => $orderId,
            'local_order_id' => $newId,
            'status' => $status,
        ];
    }

    private function extractWebhookEventType(array $payload): ?string
    {
        $eventType = $payload['event_type']
            ?? $payload['data']['event_type']
            ?? $payload['data']['reverse_event_type']
            ?? $payload['type']
            ?? null;

        return $eventType === null ? null : (string) $eventType;
    }

    private function extractWebhookOrderId(array $payload): ?string
    {
        return $this->emptyToNull(
            $payload['data']['order_id']
            ?? $payload['order_id']
            ?? $payload['data']['reverse_order_id']
            ?? null,
        );
    }

    private function extractWebhookOrderStatus(array $payload): ?string
    {
        $status = $payload['data']['order_status']
            ?? $payload['order_status']
            ?? $payload['data']['reverse_order_status']
            ?? $payload['data']['reverse_type']
            ?? null;

        return $status === null ? null : (string) $status;
    }

    private function resolveWebhookConnection(?string $shopId): ?array
    {
        if ($shopId === null) {
            return null;
        }

        return $this->connections
            ->where('shop_id', $shopId)
            ->where('status', 'active')
            ->orderBy('id', 'ASC')
            ->first();
    }

    private function applyWebhookOrderStatus(?string $orderId, ?string $rawStatus): bool
    {
        if ($orderId === null || $rawStatus === null) {
            return false;
        }

        $status = $this->mapWebhookOrderStatus($rawStatus);

        if ($status === null) {
            return false;
        }

        $order = $this->orders
            ->groupStart()
            ->where('tiktok_order_id', $orderId)
            ->orWhere('order_code', $orderId)
            ->groupEnd()
            ->first();

        if (! $order) {
            return false;
        }

        $this->orders->db->transStart();
        $this->applyOrderStockState((int) $order['id'], $status);
        $updated = $order['status'] !== $status
            ? (bool) $this->orders->update((int) $order['id'], ['status' => $status])
            : true;
        $this->orders->db->transComplete();

        if ($this->orders->db->transStatus() === false) {
            return false;
        }

        return $updated;
    }

    private function importTiktokOrderDetail(array $detail): bool
    {
        $orders = $this->extractOrdersFromDetail($detail);

        if (! is_array($orders) || $orders === []) {
            return false;
        }

        $changed = false;

        foreach ($orders as $order) {
            if (! is_array($order) || empty($order['id'])) {
                continue;
            }

            $this->upsertTiktokOrder($order);
            $changed = true;
        }

        return $changed;
    }

    private function upsertTiktokOrder(array $order): void
    {
        $tiktokOrderId = (string) $order['id'];
        $payment = is_array($order['payment'] ?? null) ? $order['payment'] : [];
        $recipient = is_array($order['recipient_address'] ?? null) ? $order['recipient_address'] : [];
        $lineItems = is_array($order['line_items'] ?? null) ? $order['line_items'] : [];
        $status = $this->mapWebhookOrderStatus((string) ($order['status'] ?? '')) ?? 'pending';
        $platformDiscount = (float) ($payment['platform_discount'] ?? 0);
        $subTotal = (float) ($payment['sub_total'] ?? $payment['total_amount'] ?? 0);
        $grossAmount = $subTotal + $platformDiscount;
        $customerPaid = (float) ($payment['total_amount'] ?? $payment['sub_total'] ?? 0);
        $discountAmount = $platformDiscount;
        $settlementAmount = $this->extractTiktokSettlementAmount($order, $payment);
        $orderDate = $this->timestampToDate($order['create_time'] ?? null) ?? date('Y-m-d H:i:s');
        $rawJson = json_encode($order, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $fees = $this->profitCalculator->calculateFees($grossAmount, $customerPaid);
        $profit = $this->profitCalculator->profit($grossAmount, $customerPaid, 0, $settlementAmount, $fees);

        $orderData = [
            'order_code'           => $tiktokOrderId,
            'platform'             => 'tiktok',
            'tiktok_order_id'      => $tiktokOrderId,
            'customer_name'        => $this->emptyToNull($recipient['name'] ?? null),
            'customer_phone'       => $this->emptyToNull($recipient['phone_number'] ?? null),
            'customer_address'     => $this->emptyToNull($recipient['full_address'] ?? $recipient['address_detail'] ?? null),
            'buyer_email'          => $this->emptyToNull($order['buyer_email'] ?? null),
            'shipping_provider'    => $this->emptyToNull($order['shipping_provider'] ?? ($lineItems[0]['shipping_provider_name'] ?? null)),
            'payment_method_name'  => $this->emptyToNull($order['payment_method_name'] ?? null),
            'delivery_option_name' => $this->emptyToNull($order['delivery_option_name'] ?? null),
            'tiktok_status'        => $this->emptyToNull($order['status'] ?? null),
            'tiktok_raw_json'      => $rawJson === false ? null : $rawJson,
            'order_date'           => $orderDate,
            'status'               => $status,
            'gross_amount'         => $grossAmount,
            'discount_amount'      => $discountAmount,
            'platform_fee'         => $profit['platform_fee'],
            'transaction_fee'      => $profit['transaction_fee'],
            'shipping_fee'         => $profit['shipping_fee'],
            'cod_amount'           => $customerPaid,
            'net_revenue'          => $profit['net_revenue'],
            'stock_deducted'       => 0,
            'stock_returned'       => 0,
            'return_fee'           => $profit['return_fee'],
            'note'                 => $this->buildTiktokOrderNote($order),
        ];

        $this->orders->db->transStart();

        $existing = $this->orders->where('tiktok_order_id', $tiktokOrderId)->first();

        if ($existing) {
            $localOrderId = (int) $existing['id'];
            if ((int) ($existing['stock_deducted'] ?? 0) === 1 && (int) ($existing['stock_returned'] ?? 0) === 0) {
                $this->returnImportedOrderStock($existing, 'resync');
            }

            $this->orders->update($localOrderId, $orderData);
        } else {
            $localOrderId = (int) $this->orders->insert($orderData, true);
        }

        $this->syncTiktokOrderItems($localOrderId, $lineItems);
        $this->recalculateImportedOrderTotals($localOrderId);
        $this->applyOrderStockState($localOrderId, $status);
        $this->orders->db->transComplete();

        if ($this->orders->db->transStatus() === false) {
            throw new RuntimeException('Could not import TikTok order ' . $tiktokOrderId);
        }
    }

    private function syncTiktokOrderItems(int $orderId, array $lineItems): void
    {
        $this->orderItems->where('order_id', $orderId)->delete();

        if ($lineItems === []) {
            return;
        }

        foreach ($lineItems as $line) {
            if (! is_array($line)) {
                continue;
            }

            $sellerSku = trim((string) ($line['seller_sku'] ?? ''));
            $sku = $sellerSku === '' ? null : $this->warehouseSkus->where('sku_code', $sellerSku)->first();

            if (! $sku) {
                continue;
            }

            $quantity = max(1, (int) ($line['quantity'] ?? 1));
            $salePrice = (float) ($line['sale_price'] ?? $line['original_price'] ?? $sku['sale_price'] ?? 0);
            $costPrice = (float) ($sku['suggested_cost'] ?? $sku['cost_price'] ?? 0);
            $totalSale = $salePrice * $quantity;
            $totalCost = $costPrice * $quantity;

            $this->orderItems->insert([
                'order_id'                => $orderId,
                'product_id'              => (int) $sku['product_id'],
                'sku_id'                  => (int) $sku['id'],
                'sku_code'                => $sku['sku_code'],
                'sku_display_name'        => $line['sku_name'] ?? $sku['display_name'],
                'size_option_id'          => (int) $sku['size_option_id'],
                'size_name'               => '',
                'combo_option_id'         => (int) $sku['combo_option_id'],
                'combo_name'              => '',
                'combo_quantity'          => (int) $sku['combo_quantity'],
                'quantity'                => $quantity,
                'stock_quantity_deducted' => 0,
                'sale_price'              => $salePrice,
                'cost_price'              => $costPrice,
                'total_sale'              => $totalSale,
                'total_cost'              => $totalCost,
                'allocated_fee'           => 0,
                'profit'                  => $totalSale - $totalCost,
            ]);
        }
    }

    private function extractOrdersFromDetail(array $detail): array
    {
        $data = is_array($detail['data'] ?? null) ? $detail['data'] : [];
        $orders = $data['orders']
            ?? $data['order_list']
            ?? $detail['orders']
            ?? $detail['order_list']
            ?? null;

        if (is_array($orders)) {
            return array_is_list($orders) ? $orders : [$orders];
        }

        if (is_array($data['order'] ?? null)) {
            return [$data['order']];
        }

        if (isset($data['id'])) {
            return [$data];
        }

        if (isset($detail['id'])) {
            return [$detail];
        }

        return isset($detail['data']) && is_array($detail['data']) && array_is_list($detail['data']) ? $detail['data'] : [];
    }

    private function applyOrderStockState(int $orderId, string $status): void
    {
        $order = $this->orders->find($orderId);
        if (! $order) {
            return;
        }

        if (in_array($status, ['cancelled', 'returned'], true)) {
            if ((int) ($order['stock_deducted'] ?? 0) === 1 && (int) ($order['stock_returned'] ?? 0) === 0) {
                $this->returnImportedOrderStock($order, 'status');
            }

            $this->orders->update($orderId, [
                'stock_deducted' => 0,
                'stock_returned' => 1,
            ]);

            return;
        }

        if ((int) ($order['stock_deducted'] ?? 0) === 0) {
            $this->deductImportedOrderStock($order);
        }
    }

    private function deductImportedOrderStock(array $order): void
    {
        $items = $this->orderItems->where('order_id', (int) $order['id'])->findAll();

        foreach ($items as $item) {
            $stockQty = (int) $item['quantity'] * max(1, (int) $item['combo_quantity']);
            if ($stockQty <= 0) {
                continue;
            }

            $stock = $this->findOrCreateStock((int) $item['product_id'], (int) $item['size_option_id'], (float) $item['cost_price']);
            $before = (int) $stock['quantity_on_hand'];
            $after = $before - $stockQty;

            $this->stock->update((int) $stock['id'], [
                'quantity_on_hand' => $after,
                'quantity_available' => max(0, $after - (int) ($stock['quantity_reserved'] ?? 0)),
            ]);

            $this->orderItems->update((int) $item['id'], ['stock_quantity_deducted' => $stockQty]);
            $this->movements->insert([
                'product_id' => (int) $item['product_id'],
                'size_option_id' => (int) $item['size_option_id'],
                'movement_type' => 'sale',
                'quantity' => -$stockQty,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'unit_cost' => (float) $item['cost_price'],
                'reference_type' => 'tiktok_order',
                'reference_id' => (int) $order['id'],
                'order_id' => (int) $order['id'],
                'order_item_id' => (int) $item['id'],
                'note' => 'Deduct stock from TikTok order ' . $order['order_code'],
            ]);

            $this->syncTiktokInventoryAfterStockChange((int) $item['product_id'], (int) $item['size_option_id'], $after, 'tiktok_order_sale');
        }

        $this->orders->update((int) $order['id'], [
            'stock_deducted' => 1,
            'stock_returned' => 0,
        ]);
    }

    private function returnImportedOrderStock(array $order, string $reason): void
    {
        $items = $this->orderItems->where('order_id', (int) $order['id'])->findAll();

        foreach ($items as $item) {
            $stockQty = (int) $item['stock_quantity_deducted'];
            if ($stockQty <= 0) {
                continue;
            }

            $stock = $this->findOrCreateStock((int) $item['product_id'], (int) $item['size_option_id'], (float) $item['cost_price']);
            $before = (int) $stock['quantity_on_hand'];
            $after = $before + $stockQty;

            $this->stock->update((int) $stock['id'], [
                'quantity_on_hand' => $after,
                'quantity_available' => max(0, $after - (int) ($stock['quantity_reserved'] ?? 0)),
            ]);

            $this->orderItems->update((int) $item['id'], ['stock_quantity_deducted' => 0]);
            $this->movements->insert([
                'product_id' => (int) $item['product_id'],
                'size_option_id' => (int) $item['size_option_id'],
                'movement_type' => 'return',
                'quantity' => $stockQty,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'unit_cost' => (float) $item['cost_price'],
                'reference_type' => 'tiktok_order_' . $reason,
                'reference_id' => (int) $order['id'],
                'order_id' => (int) $order['id'],
                'order_item_id' => (int) $item['id'],
                'note' => 'Return stock from TikTok order ' . $order['order_code'],
            ]);

            $this->syncTiktokInventoryAfterStockChange((int) $item['product_id'], (int) $item['size_option_id'], $after, 'tiktok_order_return');
        }
    }

    private function findOrCreateStock(int $productId, int $sizeOptionId, float $unitCost = 0): array
    {
        $stock = $this->stock->findByProductAndSize($productId, $sizeOptionId);
        if ($stock) {
            return $stock;
        }

        $id = (int) $this->stock->insert([
            'product_id' => $productId,
            'size_option_id' => $sizeOptionId,
            'quantity_on_hand' => 0,
            'quantity_reserved' => 0,
            'quantity_available' => 0,
            'avg_cost' => $unitCost,
        ], true);

        return $this->stock->find($id);
    }

    private function syncTiktokInventoryAfterStockChange(int $productId, int $sizeOptionId, int $quantityOnHand, string $reason): array
    {
        $linkedSkus = $this->warehouseSkus
            ->select('product_skus.id, product_skus.combo_quantity, tiktok_skus.id AS local_tiktok_sku_id, tiktok_skus.tiktok_sku_id, tiktok_products.tiktok_product_id')
            ->join('tiktok_skus', 'tiktok_skus.product_sku_id = product_skus.id')
            ->join('tiktok_products', 'tiktok_products.id = tiktok_skus.tiktok_product_id')
            ->where('product_skus.product_id', $productId)
            ->where('product_skus.size_option_id', $sizeOptionId)
            ->where('product_skus.is_active', 1)
            ->where('tiktok_skus.status', 'active')
            ->findAll();

        $writeEnabled = filter_var(env('TIKTOK_INVENTORY_WRITE_ENABLED') ?? 'false', FILTER_VALIDATE_BOOLEAN);
        $results = [];

        foreach ($linkedSkus as $sku) {
            $comboQuantity = max(1, (int) $sku['combo_quantity']);
            $tiktokQuantity = intdiv(max(0, $quantityOnHand), $comboQuantity);
            $result = [
                'product_sku_id' => (int) $sku['id'],
                'tiktok_product_id' => (string) $sku['tiktok_product_id'],
                'tiktok_sku_id' => (string) $sku['tiktok_sku_id'],
                'quantity' => $tiktokQuantity,
                'mode' => $writeEnabled ? 'real' : 'dry_run',
                'reason' => $reason,
            ];

            if ($writeEnabled) {
                try {
                    $result['response'] = $this->client->updateInventory(
                        (string) $sku['tiktok_product_id'],
                        (string) $sku['tiktok_sku_id'],
                        $tiktokQuantity,
                    );
                    $result['status'] = 'synced';
                } catch (Throwable $throwable) {
                    $result['status'] = 'failed';
                    $result['error'] = $throwable->getMessage();
                }
            } else {
                $result['status'] = 'skipped';
            }

            $this->tiktokSkus->update((int) $sku['local_tiktok_sku_id'], [
                'tiktok_inventory_quantity' => $tiktokQuantity,
            ]);
            $results[] = $result;
        }

        log_message('info', 'TikTok inventory sync ' . json_encode($results, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $results;
    }

    private function recalculateImportedOrderTotals(int $orderId): void
    {
        $items = $this->orderItems->where('order_id', $orderId)->findAll();
        $totalCost = 0.0;

        foreach ($items as $item) {
            $totalCost += (float) $item['total_cost'];
        }

        $order = $this->orders->find($orderId);
        if (! $order) {
            return;
        }

        $grossAmount = (float) $order['gross_amount'];
        $customerPaid = (float) $order['cod_amount'];
        $rawOrder = json_decode((string) ($order['tiktok_raw_json'] ?? ''), true);
        $payment = is_array($rawOrder) && is_array($rawOrder['payment'] ?? null) ? $rawOrder['payment'] : [];
        $settlementAmount = is_array($rawOrder) ? $this->extractTiktokSettlementAmount($rawOrder, $payment) : null;
        $profit = $this->profitCalculator->profit($grossAmount, $customerPaid, $totalCost, $settlementAmount);

        $this->orders->update($orderId, [
            'platform_fee'    => $profit['platform_fee'],
            'transaction_fee' => $profit['transaction_fee'],
            'shipping_fee'    => $profit['shipping_fee'],
            'return_fee'      => $profit['return_fee'],
            'net_revenue'     => $profit['net_revenue'],
            'total_cost'      => $totalCost,
            'total_profit'    => $profit['total_profit'],
        ]);
    }

    private function extractTiktokSettlementAmount(array $order, array $payment = []): ?float
    {
        $candidates = [
            'settlement_amount',
            'settlement_payable_amount',
            'settlement_total',
            'settlement_total_amount',
            'settlement_income',
            'total_settlement',
            'total_settlement_amount',
            'actual_settlement_amount',
            'seller_receivable_amount',
            'seller_income',
            'shop_income',
            'payout_amount',
            'payable_amount',
            'net_amount',
        ];

        foreach ([$payment, $order] as $source) {
            foreach ($candidates as $key) {
                $amount = $this->findMoneyValue($source, $key);
                if ($amount !== null) {
                    return $amount;
                }
            }
        }

        return null;
    }

    private function findMoneyValue(array $source, string $targetKey): ?float
    {
        foreach ($source as $key => $value) {
            if ((string) $key === $targetKey) {
                if (is_numeric($value)) {
                    return (float) $value;
                }

                if (is_string($value)) {
                    $normalized = preg_replace('/[^\d.-]/', '', $value);
                    if ($normalized !== '' && is_numeric($normalized)) {
                        return (float) $normalized;
                    }
                }
            }

            if (is_array($value)) {
                $nested = $this->findMoneyValue($value, $targetKey);
                if ($nested !== null) {
                    return $nested;
                }
            }
        }

        return null;
    }

    private function buildTiktokOrderNote(array $order): ?string
    {
        $lineItems = is_array($order['line_items'] ?? null) ? $order['line_items'] : [];
        $parts = [];

        foreach ($lineItems as $line) {
            if (! is_array($line)) {
                continue;
            }

            $parts[] = trim((string) ($line['seller_sku'] ?? '')) . ' - ' . trim((string) ($line['product_name'] ?? ''));
        }

        return $parts === [] ? null : implode("\n", array_filter($parts));
    }

    private function mapWebhookOrderStatus(string $rawStatus): ?string
    {
        $status = strtoupper(trim($rawStatus));
        $map = [
            'UNPAID'               => 'pending',
            'ON_HOLD'              => 'pending',
            'AWAITING_SHIPMENT'    => 'confirmed',
            'AWAITING_COLLECTION'  => 'confirmed',
            'PARTIALLY_SHIPPING'   => 'shipped',
            'IN_TRANSIT'           => 'shipped',
            'DELIVERED'            => 'completed',
            'COMPLETED'            => 'completed',
            'CANCELLED'            => 'cancelled',
            'CANCEL'               => 'cancelled',
            'RETURNED'             => 'returned',
            'REFUNDED'             => 'returned',
            '100'                  => null,
        ];

        return $map[$status] ?? null;
    }

    private function validateConnection(array $payload, ?int $id = null): array
    {
        $errors = [];

        foreach (['shop_id', 'shop_cipher', 'app_key'] as $field) {
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

    private function tiktokReadFilters(): array
    {
        $payload = $this->request->getMethod() === 'get' ? $this->request->getGet() : $this->payload();

        if (isset($payload['pagesize']) && ! isset($payload['page_size'])) {
            $payload['page_size'] = $payload['pagesize'];
        }

        if (isset($payload['seller_skus']) && is_string($payload['seller_skus'])) {
            $payload['seller_skus'] = array_values(array_filter(array_map('trim', explode(',', $payload['seller_skus']))));
        }

        return $payload;
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

    private function timestampToDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $timestamp = (int) $value;

        if ($timestamp <= 0) {
            return null;
        }

        if ($timestamp > 9999999999) {
            $timestamp = intdiv($timestamp, 1000);
        }

        return date('Y-m-d H:i:s', $timestamp);
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
