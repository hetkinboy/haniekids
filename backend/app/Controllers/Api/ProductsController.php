<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ProductModel;
use CodeIgniter\HTTP\ResponseInterface;

class ProductsController extends BaseController
{
    private ProductModel $products;

    public function __construct()
    {
        $this->products = new ProductModel();
    }

    public function index(): ResponseInterface
    {
        $keyword = trim((string) $this->request->getGet('keyword'));
        $status = trim((string) $this->request->getGet('status'));
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $pageSize = $this->request->getGet('pageSize') ?? $this->request->getGet('per_page') ?? 20;
        $perPage = min(100, max(1, (int) $pageSize));

        $builder = $this->products;

        if ($keyword !== '') {
            $builder = $builder->groupStart()
                ->like('product_code', $keyword)
                ->orLike('name', $keyword)
                ->groupEnd();
        }

        if ($status !== '') {
            $builder = $builder->where('status', $status);
        }

        $items = $builder->orderBy('id', 'DESC')->paginate($perPage, 'default', $page);

        return api_success('Success', [
            'items' => array_map(fn (array $product): array => $this->formatProduct($product), $items),
            'pager' => [
                'current_page' => $this->products->pager->getCurrentPage(),
                'page_size'    => $perPage,
                'per_page'     => $perPage,
                'total'        => $this->products->pager->getTotal(),
                'last_page'    => $this->products->pager->getPageCount(),
            ],
        ]);
    }

    public function show(int $id): ResponseInterface
    {
        $product = $this->products->find($id);

        if (! $product) {
            return api_error('Product not found', [], 404);
        }

        return api_success('Success', $this->formatProduct($product));
    }

    public function create(): ResponseInterface
    {
        $payload = $this->request->getJSON(true) ?? $this->request->getPost();

        if (! $this->validatePayload($payload)) {
            return api_error('Validation failed', $this->validator->getErrors(), 422);
        }

        $id = $this->products->insert($this->productData($payload), true);

        return api_success('Product created', $this->formatProduct($this->products->find($id)), 201);
    }

    public function update(int $id): ResponseInterface
    {
        $product = $this->products->find($id);

        if (! $product) {
            return api_error('Product not found', [], 404);
        }

        $payload = $this->request->getJSON(true) ?? $this->request->getRawInput();

        if (! $this->validatePayload($payload, $id)) {
            return api_error('Validation failed', $this->validator->getErrors(), 422);
        }

        $this->products->update($id, $this->productData($payload));

        return api_success('Product updated', $this->formatProduct($this->products->find($id)));
    }

    public function delete(int $id): ResponseInterface
    {
        $product = $this->products->find($id);

        if (! $product) {
            return api_error('Product not found', [], 404);
        }

        $this->products->delete($id);

        return api_success('Product deleted');
    }

    private function validatePayload(array $payload, ?int $id = null): bool
    {
        $uniqueRule = $id === null
            ? 'is_unique[products.product_code]'
            : "is_unique[products.product_code,id,{$id}]";

        return $this->validateData($payload, [
            'product_code' => "required|max_length[80]|alpha_dash|{$uniqueRule}",
            'name'         => 'required|max_length[255]',
            'category'     => 'permit_empty|max_length[120]',
            'description'  => 'permit_empty',
            'image_url'    => 'permit_empty|max_length[500]',
            'status'       => 'required|in_list[active,inactive]',
        ]);
    }

    private function productData(array $payload): array
    {
        return [
            'product_code' => $payload['product_code'],
            'name'         => $payload['name'],
            'category'     => $payload['category'] ?? null,
            'description'  => $payload['description'] ?? null,
            'image_url'    => $payload['image_url'] ?? null,
            'status'       => $payload['status'] ?? 'active',
        ];
    }

    private function formatProduct(array $product): array
    {
        unset($product['main_sku']);

        return $product;
    }

    private function emptyToNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
