<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ProductModel;
use App\Models\VariantGroupModel;
use App\Models\VariantOptionModel;
use CodeIgniter\HTTP\ResponseInterface;

class ProductsController extends BaseController
{
    private ProductModel $products;
    private VariantGroupModel $groups;
    private VariantOptionModel $options;

    public function __construct()
    {
        $this->products = new ProductModel();
        $this->groups = new VariantGroupModel();
        $this->options = new VariantOptionModel();
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

        $summary = $this->productSummary($keyword, $status);
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
            'summary' => $summary,
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

    public function copy(int $id): ResponseInterface
    {
        $source = $this->products->find($id);

        if (! $source) {
            return api_error('Product not found', [], 404);
        }

        $payload = $this->request->getJSON(true) ?? $this->request->getPost();
        $data = [
            'product_code' => $payload['product_code'] ?? '',
            'name'         => $payload['name'] ?? '',
            'category'     => array_key_exists('category', $payload) ? $payload['category'] : ($source['category'] ?? null),
            'description'  => array_key_exists('description', $payload) ? $payload['description'] : ($source['description'] ?? null),
            'image_url'    => array_key_exists('image_url', $payload) ? $payload['image_url'] : ($source['image_url'] ?? null),
            'status'       => $payload['status'] ?? 'active',
        ];

        if (! $this->validatePayload($data)) {
            return api_error('Validation failed', $this->validator->getErrors(), 422);
        }

        $this->products->db->transStart();

        $newProductId = (int) $this->products->insert($this->productData($data), true);
        $groupMap = [];
        $optionMap = [];

        $sourceGroups = $this->groups
            ->where('product_id', $id)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        foreach ($sourceGroups as $group) {
            $newGroupId = (int) $this->groups->insert([
                'product_id'      => $newProductId,
                'name'            => $group['name'],
                'type'            => $group['type'],
                'is_stock_group'  => (int) $group['is_stock_group'],
                'sort_order'      => (int) $group['sort_order'],
                'status'          => $group['status'],
            ], true);
            $groupMap[(int) $group['id']] = $newGroupId;

            $sourceOptions = $this->options
                ->where('variant_group_id', (int) $group['id'])
                ->orderBy('sort_order', 'ASC')
                ->orderBy('id', 'ASC')
                ->findAll();

            foreach ($sourceOptions as $option) {
                $newOptionId = (int) $this->options->insert([
                    'variant_group_id' => $newGroupId,
                    'name'             => $option['name'],
                    'option_code'      => $option['option_code'],
                    'base_cost'        => (float) $option['base_cost'],
                    'combo_quantity'   => $option['combo_quantity'],
                    'default_sellable' => (int) $option['default_sellable'],
                    'sort_order'       => (int) $option['sort_order'],
                    'status'           => $option['status'],
                ], true);
                $optionMap[(int) $option['id']] = $newOptionId;
            }
        }

        $this->products->db->transComplete();

        if ($this->products->db->transStatus() === false) {
            return api_error('Could not copy product', [], 500);
        }

        return api_success('Product copied', [
            'product'      => $this->formatProduct($this->products->find($newProductId)),
            'groups_copied'=> count($groupMap),
            'options_copied' => count($optionMap),
        ], 201);
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

    private function productSummary(string $keyword, string $status): array
    {
        $builder = $this->products->db->table('products')
            ->select("
                COUNT(*) AS total_products,
                COALESCE(SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END), 0) AS active_products,
                COALESCE(SUM(CASE WHEN status <> 'active' THEN 1 ELSE 0 END), 0) AS inactive_products
            ", false)
            ->where('deleted_at', null);

        if ($keyword !== '') {
            $builder->groupStart()
                ->like('product_code', $keyword)
                ->orLike('name', $keyword)
                ->groupEnd();
        }

        if ($status !== '') {
            $builder->where('status', $status);
        }

        $row = $builder->get()->getRowArray() ?? [];

        return [
            'total_products' => (int) ($row['total_products'] ?? 0),
            'active_products' => (int) ($row['active_products'] ?? 0),
            'inactive_products' => (int) ($row['inactive_products'] ?? 0),
        ];
    }

    private function emptyToNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
