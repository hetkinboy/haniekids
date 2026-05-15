<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->group('api', static function ($routes) {
    $routes->get('health', 'Api\HealthController::index');

    $routes->post('auth/login', 'Api\AuthController::login');
    $routes->get('auth/me', 'Api\AuthController::me', ['filter' => 'auth']);
    $routes->post('auth/logout', 'Api\AuthController::logout', ['filter' => 'auth']);

    $routes->get('products', 'Api\ProductsController::index', ['filter' => 'auth']);
    $routes->get('products/(:num)', 'Api\ProductsController::show/$1', ['filter' => 'auth']);
    $routes->post('products', 'Api\ProductsController::create', ['filter' => ['auth', 'role:admin']]);
    $routes->post('products/(:num)/copy', 'Api\ProductsController::copy/$1', ['filter' => ['auth', 'role:admin']]);
    $routes->put('products/(:num)', 'Api\ProductsController::update/$1', ['filter' => ['auth', 'role:admin']]);
    $routes->delete('products/(:num)', 'Api\ProductsController::delete/$1', ['filter' => ['auth', 'role:admin']]);

    $routes->get('products/(:num)/variant-groups', 'Api\VariantGroupsController::index/$1', ['filter' => 'auth']);
    $routes->post('products/(:num)/variant-groups', 'Api\VariantGroupsController::create/$1', ['filter' => ['auth', 'role:admin']]);
    $routes->put('variant-groups/(:num)', 'Api\VariantGroupsController::update/$1', ['filter' => ['auth', 'role:admin']]);
    $routes->delete('variant-groups/(:num)', 'Api\VariantGroupsController::delete/$1', ['filter' => ['auth', 'role:admin']]);

    $routes->post('variant-groups/(:num)/options', 'Api\VariantOptionsController::create/$1', ['filter' => ['auth', 'role:admin']]);
    $routes->put('variant-options/(:num)', 'Api\VariantOptionsController::update/$1', ['filter' => ['auth', 'role:admin']]);
    $routes->delete('variant-options/(:num)', 'Api\VariantOptionsController::delete/$1', ['filter' => ['auth', 'role:admin']]);

    $routes->post('products/(:num)/generate-skus', 'Api\SkusController::generate/$1', ['filter' => ['auth', 'role:admin']]);
    $routes->get('products/(:num)/skus', 'Api\SkusController::index/$1', ['filter' => 'auth']);
    $routes->put('skus/(:num)', 'Api\SkusController::update/$1', ['filter' => ['auth', 'role:admin']]);

    $routes->get('products/(:num)/stock', 'Api\StockController::productStock/$1', ['filter' => 'auth']);
    $routes->get('stock/movements', 'Api\StockController::movements', ['filter' => 'auth']);
    $routes->post('stock/adjust', 'Api\StockController::adjust', ['filter' => ['auth', 'role:admin']]);
    $routes->post('stock/import-legacy-1bo', 'Api\StockController::importLegacyOneBoStock', ['filter' => ['auth', 'role:admin']]);

    $routes->get('purchase-imports', 'Api\PurchaseImportsController::index', ['filter' => 'auth']);
    $routes->post('purchase-imports', 'Api\PurchaseImportsController::create', ['filter' => ['auth', 'role:admin']]);
    $routes->get('purchase-imports/(:num)', 'Api\PurchaseImportsController::show/$1', ['filter' => 'auth']);
    $routes->put('purchase-imports/(:num)', 'Api\PurchaseImportsController::update/$1', ['filter' => ['auth', 'role:admin']]);

    $routes->get('tiktok-products', 'Api\TiktokProductsController::index', ['filter' => 'auth']);
    $routes->post('tiktok-products', 'Api\TiktokProductsController::create', ['filter' => ['auth', 'role:admin']]);
    $routes->get('tiktok-products/(:num)', 'Api\TiktokProductsController::show/$1', ['filter' => 'auth']);
    $routes->put('tiktok-products/(:num)', 'Api\TiktokProductsController::update/$1', ['filter' => ['auth', 'role:admin']]);
    $routes->delete('tiktok-products/(:num)', 'Api\TiktokProductsController::delete/$1', ['filter' => ['auth', 'role:admin']]);
    $routes->get('tiktok-products/(:num)/skus', 'Api\TiktokProductsController::skus/$1', ['filter' => 'auth']);
    $routes->post('tiktok-products/(:num)/skus', 'Api\TiktokProductsController::createSku/$1', ['filter' => ['auth', 'role:admin']]);
    $routes->put('tiktok-skus/(:num)', 'Api\TiktokProductsController::updateSku/$1', ['filter' => ['auth', 'role:admin']]);
    $routes->delete('tiktok-skus/(:num)', 'Api\TiktokProductsController::deleteSku/$1', ['filter' => ['auth', 'role:admin']]);

    $routes->get('tiktok/connections', 'Api\TiktokIntegrationController::connections', ['filter' => 'auth']);
    $routes->post('tiktok/connections', 'Api\TiktokIntegrationController::createConnection', ['filter' => ['auth', 'role:admin']]);
    $routes->put('tiktok/connections/(:num)', 'Api\TiktokIntegrationController::updateConnection/$1', ['filter' => ['auth', 'role:admin']]);
    $routes->post('tiktok/refresh-token', 'Api\TiktokIntegrationController::refreshToken', ['filter' => ['auth', 'role:admin']]);
    $routes->post('tiktok/connections/(:num)/refresh-token', 'Api\TiktokIntegrationController::refreshToken/$1', ['filter' => ['auth', 'role:admin']]);
    $routes->get('tiktok/authorized-shops', 'Api\TiktokIntegrationController::authorizedShops', ['filter' => ['auth', 'role:admin']]);
    $routes->post('tiktok/product-search', 'Api\TiktokIntegrationController::productSearch', ['filter' => ['auth', 'role:admin']]);
    $routes->get('tiktok/read/product-search', 'Api\TiktokIntegrationController::productSearchPreview', ['filter' => ['auth', 'role:admin']]);
    $routes->post('tiktok/read/product-search', 'Api\TiktokIntegrationController::productSearchPreview', ['filter' => ['auth', 'role:admin']]);
    $routes->post('tiktok/read/product-search-url', 'Api\TiktokIntegrationController::previewSearchUrl', ['filter' => ['auth', 'role:admin']]);
    $routes->post('tiktok/read/product-search-response', 'Api\TiktokIntegrationController::previewSearchResponse', ['filter' => ['auth', 'role:admin']]);
    $routes->post('tiktok/import-search-url', 'Api\TiktokIntegrationController::importSearchUrl', ['filter' => ['auth', 'role:admin']]);
    $routes->post('tiktok/import-search-response', 'Api\TiktokIntegrationController::importSearchResponse', ['filter' => ['auth', 'role:admin']]);
    $routes->post('tiktok/import-legacy-revenue', 'Api\TiktokIntegrationController::importLegacyRevenue', ['filter' => ['auth', 'role:admin']]);
    $routes->get('tiktok/orders/(:segment)', 'Api\TiktokIntegrationController::orderDetail/$1', ['filter' => ['auth', 'role:admin']]);
    $routes->get('tiktok/orders-new/(:segment)', 'Api\TiktokIntegrationController::orderDetailNew/$1', ['filter' => ['auth', 'role:admin']]);
    $routes->post('tiktok/orders-new/(:segment)/import', 'Api\TiktokIntegrationController::importOrderDetailNew/$1', ['filter' => ['auth', 'role:admin']]);
    $routes->get('tiktok/products/(:segment)/detail', 'Api\TiktokIntegrationController::productDetail/$1', ['filter' => ['auth', 'role:admin']]);
    $routes->post('tiktok/products/(:segment)/sync-skus', 'Api\TiktokIntegrationController::syncProductSkus/$1', ['filter' => ['auth', 'role:admin']]);
    $routes->post('tiktok/inventory/update', 'Api\TiktokIntegrationController::updateInventory', ['filter' => ['auth', 'role:admin']]);
    $routes->post('tiktok/inventory/sync-size', 'Api\TiktokIntegrationController::syncInventoryBySize', ['filter' => ['auth', 'role:admin']]);
    $routes->post('tiktok/signature', 'Api\TiktokIntegrationController::sign', ['filter' => ['auth', 'role:admin']]);
    $routes->get('tiktok/webhook-events', 'Api\TiktokIntegrationController::webhookEvents', ['filter' => 'auth']);
    $routes->post('tiktok/webhook', 'Api\TiktokIntegrationController::webhook');

    $routes->get('orders', 'Api\OrdersController::index', ['filter' => 'auth']);
    $routes->post('orders', 'Api\OrdersController::create', ['filter' => ['auth', 'role:admin']]);
    $routes->get('orders/(:num)', 'Api\OrdersController::show/$1', ['filter' => 'auth']);
    $routes->put('orders/(:num)/status', 'Api\OrdersController::updateStatus/$1', ['filter' => ['auth', 'role:admin']]);

    $routes->get('operating-costs', 'Api\OperatingCostsController::index', ['filter' => 'auth']);
    $routes->get('operating-cost-settings', 'Api\OperatingCostsController::feeSettings', ['filter' => 'auth']);
    $routes->put('operating-cost-settings', 'Api\OperatingCostsController::saveFeeSettings', ['filter' => ['auth', 'role:admin']]);
    $routes->post('operating-costs', 'Api\OperatingCostsController::create', ['filter' => ['auth', 'role:admin']]);
    $routes->get('operating-costs/(:num)', 'Api\OperatingCostsController::show/$1', ['filter' => 'auth']);
    $routes->put('operating-costs/(:num)', 'Api\OperatingCostsController::update/$1', ['filter' => ['auth', 'role:admin']]);
    $routes->delete('operating-costs/(:num)', 'Api\OperatingCostsController::delete/$1', ['filter' => ['auth', 'role:admin']]);

    $routes->get('settlements', 'Api\SettlementsController::index', ['filter' => 'auth']);
    $routes->post('settlements', 'Api\SettlementsController::create', ['filter' => ['auth', 'role:admin']]);
    $routes->get('settlements/(:num)', 'Api\SettlementsController::show/$1', ['filter' => 'auth']);

    $routes->get('reports/overview', 'Api\ReportsController::overview', ['filter' => 'auth']);
    $routes->get('reports/by-product', 'Api\ReportsController::byProduct', ['filter' => 'auth']);
    $routes->get('reports/by-sku', 'Api\ReportsController::bySku', ['filter' => 'auth']);
    $routes->get('reports/stock', 'Api\ReportsController::stock', ['filter' => 'auth']);

    $routes->options('(:any)', static function () {
        return service('response')->setStatusCode(204);
    });
});
