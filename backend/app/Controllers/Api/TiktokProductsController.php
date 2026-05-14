<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ProductModel;
use App\Models\ProductSkuModel;
use App\Models\TiktokProductModel;
use App\Models\TiktokSkuModel;
use CodeIgniter\HTTP\ResponseInterface;

class TiktokProductsController extends BaseController
{
    private TiktokProductModel $products;
    private TiktokSkuModel $skus;
    private ProductModel $warehouseProducts;
    private ProductSkuModel $warehouseSkus;

    public function __construct()
    {
        $this->products = new TiktokProductModel();
        $this->skus = new TiktokSkuModel();
        $this->warehouseProducts = new ProductModel();
        $this->warehouseSkus = new ProductSkuModel();
    }

    public function index(): ResponseInterface
    {
        $keyword = trim((string) $this->request->getGet('keyword'));
        $status = trim((string) $this->request->getGet('status'));
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $pageSize = min(100, max(1, (int) ($this->request->getGet('pageSize') ?? 20)));

        $builder = $this->products
            ->select('tiktok_products.*, products.product_code, products.name AS warehouse_product_name')
            ->join('products', 'products.id = tiktok_products.product_id', 'left');

        if ($keyword !== '') {
            $builder = $builder->groupStart()
                ->like('tiktok_products.name', $keyword)
                ->orLike('tiktok_products.tiktok_product_id', $keyword)
                ->orLike('products.product_code', $keyword)
                ->groupEnd();
        }

        if ($status !== '') {
            $builder = $builder->where('tiktok_products.status', $status);
        }

        $items = $builder->orderBy('tiktok_products.id', 'DESC')->paginate($pageSize, 'default', $page);

        return api_success('Success', [
            'items' => $items,
            'pager' => [
                'page' => $this->products->pager->getCurrentPage(),
                'pageSize' => $pageSize,
                'total' => $this->products->pager->getTotal(),
            ],
        ]);
    }

    public function show(int $id): ResponseInterface
    {
        $product = $this->products
            ->select('tiktok_products.*, products.product_code, products.name AS warehouse_product_name')
            ->join('products', 'products.id = tiktok_products.product_id', 'left')
            ->find($id);

        if (! $product) {
            return api_error('TikTok product not found', [], 404);
        }

        $product['skus'] = $this->linkedSkus($id);

        return api_success('Success', $product);
    }

    public function create(): ResponseInterface
    {
        $payload = $this->request->getJSON(true) ?? $this->request->getPost();

        if (! $this->validateProduct($payload)) {
            return api_error('Validation failed', $this->validator->getErrors(), 422);
        }

        $id = $this->products->insert([
            'product_id' => $payload['product_id'] ?? null,
            'tiktok_product_id' => $payload['tiktok_product_id'],
            'name' => $payload['name'],
            'shop_name' => $payload['shop_name'] ?? null,
            'status' => $payload['status'] ?? 'active',
        ], true);

        return api_success('TikTok product created', $this->products->find($id), 201);
    }

    public function update(int $id): ResponseInterface
    {
        if (! $this->products->find($id)) {
            return api_error('TikTok product not found', [], 404);
        }

        $payload = $this->request->getJSON(true) ?? $this->request->getRawInput();

        if (! $this->validateProduct($payload, $id)) {
            return api_error('Validation failed', $this->validator->getErrors(), 422);
        }

        $this->products->update($id, [
            'product_id' => $payload['product_id'] ?? null,
            'tiktok_product_id' => $payload['tiktok_product_id'],
            'name' => $payload['name'],
            'shop_name' => $payload['shop_name'] ?? null,
            'status' => $payload['status'] ?? 'active',
        ]);

        return api_success('TikTok product updated', $this->products->find($id));
    }

    public function delete(int $id): ResponseInterface
    {
        if (! $this->products->find($id)) {
            return api_error('TikTok product not found', [], 404);
        }

        $this->products->delete($id);

        return api_success('TikTok product deleted');
    }

    public function skus(int $id): ResponseInterface
    {
        if (! $this->products->find($id)) {
            return api_error('TikTok product not found', [], 404);
        }

        return api_success('Success', ['items' => $this->linkedSkus($id)]);
    }

    public function createSku(int $id): ResponseInterface
    {
        if (! $this->products->find($id)) {
            return api_error('TikTok product not found', [], 404);
        }

        $payload = $this->request->getJSON(true) ?? $this->request->getPost();

        if (! $this->validateSku($payload)) {
            return api_error('Validation failed', $this->validator->getErrors(), 422);
        }

        $skuId = $this->skus->insert([
            'tiktok_product_id' => $id,
            'product_sku_id' => $this->findWarehouseSkuId($payload['seller_sku'] ?? null),
            'tiktok_sku_id' => $payload['tiktok_sku_id'],
            'seller_sku' => $payload['seller_sku'] ?? null,
            'name' => $payload['name'] ?? null,
            'status' => $payload['status'] ?? 'active',
        ], true);

        return api_success('TikTok SKU linked', $this->skus->find($skuId), 201);
    }

    public function updateSku(int $id): ResponseInterface
    {
        if (! $this->skus->find($id)) {
            return api_error('TikTok SKU not found', [], 404);
        }

        $payload = $this->request->getJSON(true) ?? $this->request->getRawInput();

        if (! $this->validateSku($payload, $id)) {
            return api_error('Validation failed', $this->validator->getErrors(), 422);
        }

        $this->skus->update($id, [
            'product_sku_id' => $this->findWarehouseSkuId($payload['seller_sku'] ?? null),
            'tiktok_sku_id' => $payload['tiktok_sku_id'],
            'seller_sku' => $payload['seller_sku'] ?? null,
            'name' => $payload['name'] ?? null,
            'status' => $payload['status'] ?? 'active',
        ]);

        return api_success('TikTok SKU updated', $this->skus->find($id));
    }

    public function deleteSku(int $id): ResponseInterface
    {
        if (! $this->skus->find($id)) {
            return api_error('TikTok SKU not found', [], 404);
        }

        $this->skus->delete($id);

        return api_success('TikTok SKU deleted');
    }

    private function validateProduct(array $payload, ?int $id = null): bool
    {
        $uniqueRule = $id === null
            ? 'is_unique[tiktok_products.tiktok_product_id]'
            : "is_unique[tiktok_products.tiktok_product_id,id,{$id}]";

        $valid = $this->validateData($payload, [
            'product_id' => 'permit_empty|integer',
            'tiktok_product_id' => "required|max_length[120]|{$uniqueRule}",
            'name' => 'required|max_length[255]',
            'shop_name' => 'permit_empty|max_length[255]',
            'status' => 'required|in_list[active,inactive]',
        ]);

        if (! $valid) {
            return false;
        }

        if (! empty($payload['product_id']) && ! $this->warehouseProducts->find((int) $payload['product_id'])) {
            $this->validator->setError('product_id', 'Warehouse product not found.');
            return false;
        }

        return true;
    }

    private function validateSku(array $payload, ?int $id = null): bool
    {
        $uniqueRule = $id === null
            ? 'is_unique[tiktok_skus.tiktok_sku_id]'
            : "is_unique[tiktok_skus.tiktok_sku_id,id,{$id}]";

        $valid = $this->validateData($payload, [
            'tiktok_sku_id' => "required|max_length[120]|{$uniqueRule}",
            'seller_sku' => 'required|max_length[120]',
            'name' => 'permit_empty|max_length[255]',
            'status' => 'required|in_list[active,inactive]',
        ]);

        if (! $valid) {
            return false;
        }

        return true;
    }

    private function findWarehouseSkuId(?string $sellerSku): ?int
    {
        $sellerSku = trim((string) $sellerSku);

        if ($sellerSku === '') {
            return null;
        }

        $sku = $this->warehouseSkus->where('sku_code', $sellerSku)->first();

        return $sku ? (int) $sku['id'] : null;
    }

    private function linkedSkus(int $tiktokProductId): array
    {
        return $this->skus
            ->select('tiktok_skus.*, product_skus.sku_code AS warehouse_sku_code, product_skus.display_name AS warehouse_sku_name')
            ->join('product_skus', 'product_skus.id = tiktok_skus.product_sku_id', 'left')
            ->where('tiktok_skus.tiktok_product_id', $tiktokProductId)
            ->orderBy('tiktok_skus.id', 'DESC')
            ->findAll();
    }
}
