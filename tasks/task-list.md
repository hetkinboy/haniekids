# Task List - TikTok Shop Cost Management

## Phase 1 - MVP Backend

### 1. Khởi tạo backend CI4

- [x] Tạo project CI4.
- [x] Cấu hình database.
- [x] Cấu hình CORS.
- [x] Tạo response helper.
- [x] Tạo route `/api/health`.

### 2. Auth + role

- [x] Migration users.
- [x] Migration roles.
- [x] API login.
- [x] API me.
- [x] API logout.
- [x] AuthFilter.
- [x] RoleFilter admin/member.

### 3. Database nghiệp vụ

- [x] Migration products.
- [x] Migration variant_groups.
- [x] Migration variant_options.
- [x] Migration product_skus.
- [x] Migration stock_by_size.
- [x] Migration stock_movements.
- [x] Migration purchase_imports.
- [x] Migration purchase_import_items.
- [x] Migration orders.
- [x] Migration order_items.
- [x] Migration operating_costs.
- [x] Migration settlements.
- [x] Migration settlement_items.
- [x] Migration tiktok_products.
- [x] Migration tiktok_skus.

### 4. Models

- [x] ProductModel.
- [x] VariantGroupModel.
- [x] VariantOptionModel.
- [x] ProductSkuModel.
- [x] StockBySizeModel.
- [x] StockMovementModel.
- [x] PurchaseImportModel.
- [x] PurchaseImportItemModel.
- [x] OrderModel.
- [x] OrderItemModel.
- [x] OperatingCostModel.
- [x] SettlementModel.
- [x] SettlementItemModel.
- [x] TiktokProductModel.
- [x] TiktokSkuModel.

### 5. API sản phẩm

- [x] GET /api/products.
- [x] POST /api/products.
- [x] GET /api/products/{id}.
- [x] PUT /api/products/{id}.
- [x] DELETE /api/products/{id}.
- [x] Tìm kiếm theo keyword.
- [x] Lọc status.
- [x] Phân trang page/pageSize.
- [x] Soft delete.
- [x] Validate product_code không trùng.
- [x] Validate name bắt buộc.
- [x] Admin thêm/sửa/xóa, member chỉ xem.

### 6. API biến thể

- [x] GET /api/products/{productId}/variant-groups.
- [x] POST /api/products/{productId}/variant-groups.
- [x] PUT /api/variant-groups/{id}.
- [x] DELETE /api/variant-groups/{id}.
- [x] POST /api/variant-groups/{groupId}/options.
- [x] PUT /api/variant-options/{id}.
- [x] DELETE /api/variant-options/{id}.
- [x] Validate moi san pham chi co 1 group combo.
- [x] Validate moi san pham chi co 1 group quan ly ton kho.
- [x] Tu tao stock_by_size khi them size option.
- [x] Khong cho xoa option da dung trong SKU.
- [x] Khong cho sua combo_quantity neu da co SKU.

### 7. API SKU

- [x] POST /api/products/{productId}/generate-skus.
- [x] GET /api/products/{productId}/skus.
- [x] PUT /api/skus/{id}.
- [x] Sinh SKU theo size x combo.
- [x] Tinh suggested_cost = size.base_cost x combo_quantity.
- [x] Bo qua SKU da ton tai neu overwrite = false.
- [x] Cap nhat SKU da ton tai neu overwrite = true.
- [x] Khong ghi de cost_price tru khi force_update_cost = true.

### 8. API kho hàng

- [x] GET /api/products/{productId}/stock.
- [x] POST /api/stock/adjust.
- [x] GET /api/stock/movements.
- [x] Ton kho theo product_id + size_option_id.
- [x] Khong quan ly ton kho theo combo.
- [x] Moi thay doi kho ghi stock_movements.
- [x] Khong cho ton kho am.

### 9. API nhập hàng

- [x] GET /api/purchase-imports.
- [x] POST /api/purchase-imports.
- [x] GET /api/purchase-imports/{id}.
- [x] Tao phieu nhap trong transaction.
- [x] Tang stock_by_size.
- [x] Ghi stock_movements type import.
- [x] Cap nhat total_quantity, total_amount.
- [x] Cap nhat avg_cost theo weighted average.

### 10. API đơn hàng

- [x] GET /api/orders.
- [x] POST /api/orders.
- [x] GET /api/orders/{id}.
- [x] PUT /api/orders/{id}/status.
- [x] Tao order_items copy cost_price, sale_price, combo_quantity tai thoi diem ban.
- [x] Ban combo tru kho theo size = quantity x combo_quantity.
- [x] Cancel/return cong kho lai theo size.

### 11. API chi phí

- [x] GET /api/operating-costs.
- [x] POST /api/operating-costs.
- [x] GET /api/operating-costs/{id}.
- [x] PUT /api/operating-costs/{id}.
- [x] DELETE /api/operating-costs/{id}.

### 12. API báo cáo

- [x] GET /api/reports/overview.
- [x] GET /api/reports/by-product.
- [x] GET /api/reports/by-sku.
- [x] GET /api/reports/stock.

### 12.1 API TikTok/đối soát

- [x] GET /api/tiktok-products.
- [x] POST /api/tiktok-products.
- [x] GET /api/tiktok-products/{id}.
- [x] PUT /api/tiktok-products/{id}.
- [x] DELETE /api/tiktok-products/{id}.
- [x] GET /api/tiktok-products/{id}/skus.
- [x] POST /api/tiktok-products/{id}/skus.
- [x] PUT /api/tiktok-skus/{id}.
- [x] DELETE /api/tiktok-skus/{id}.
- [x] GET /api/settlements.
- [x] POST /api/settlements.
- [x] GET /api/settlements/{id}.
- [x] Migration tiktok_shop_connections.
- [x] Migration tiktok_webhook_events.
- [x] TikTok API client dùng chung: signature, refresh token, product search, order/product detail, update inventory.
- [x] Webhook endpoint lưu raw event.
- [x] API sync tồn TikTok theo product + size, quy đổi quantity theo combo_quantity.
- [x] API import response search TikTok, map seller_sku voi SKU kho.
- [x] API import tu URL search TikTok.
- [x] SKU TikTok tu dong map SKU kho theo seller_sku, khong can chon product_sku_id.

---

## Phase 2 - MVP Frontend Angular

### 13. Layout + auth

- [x] Admin layout.
- [x] Sidebar menu.
- [x] Login page.
- [x] AuthInterceptor.
- [x] AuthGuard.
- [ ] RoleGuard.

### 14. Frontend sản phẩm

- [x] Product list page.
- [x] Product create page.
- [x] Product edit page.
- [x] Product API service.
- [x] Login page co token auth.
- [x] Auth interceptor gan Bearer token.
- [x] Admin layout co sidebar/header.
- [x] NgRx store cho auth/product state.
- [x] Angular signals trong component.
- [x] Template dung `@if`, `@for`.

### 15. Frontend biến thể/SKU

- [x] Variant config page.
- [x] Add size form.
- [x] Add combo form.
- [x] Edit option Size/Combo, bao gom gia von Size.
- [x] Generate SKU button.
- [x] SKU list page.
- [x] Inline edit SKU cost/sale price.
- [x] Toggle is_sellable/is_active.
- [x] Filter SKU keyword/is_sellable/is_active.
- [x] Bo field SKU TikTok khoi man hinh SKU kho.

### 16. Frontend kho hàng

- [x] Stock by size page.
- [x] Purchase import list.
- [x] Purchase import create.
- [ ] Stock movements page.
- [x] Stock adjustment modal.
- [x] Responsive mobile/iPad cho layout va bang du lieu.

### 16.1 Frontend design system

- [x] Doc `design-skill-md`.
- [x] Doc `stitch_tiktok_shop_cost_manager`.
- [x] Chuan hoa app shell desktop-first.
- [x] Chuan hoa surface/filter/table/form utility.
- [x] Doi cac man hinh chinh sang OnPush.
- [x] Product list responsive card tren mobile.
- [x] Table sticky header va horizontal scroll.
- [x] Metric cards cho san pham, SKU, ton kho, nhap hang.
- [x] Tao dashboard theo phong cach Stitch.
- [x] Chuyen layout sang sidebar sang + primary teal.
- [x] Them route giao dien khung cho San pham TikTok, Don hang TikTok, Doi soat tien, Chi phi van hanh, Bao cao.

### 17. Frontend đơn hàng

- [x] Order list page khung theo Stitch, cho backend API.
- [ ] Order create page.
- [ ] Order detail page.
- [ ] Change status modal.
- [ ] Return/cancel handling UI.

### 18. Frontend tài chính/báo cáo

- [x] Operating costs page khung theo Stitch, cho backend API.
- [x] Settlement page khung theo Stitch, cho backend API.
- [x] Report overview khung theo Stitch, cho backend API.
- [ ] Report by product.
- [ ] Report by SKU.
- [ ] Report stock.

### 19. Frontend TikTok

- [x] Tiktok product list page.
- [x] Tiktok product create/edit/delete modal.
- [x] Link Tiktok product voi san pham kho.
- [x] Tiktok SKU list theo san pham TikTok.
- [x] Tiktok SKU create/edit/delete modal.
- [x] Link Tiktok SKU voi SKU kho.
- [x] Sap xep lai UI San pham TikTok thanh bang san pham va bang SKU rieng, responsive hon.
- [x] Nut Import tu search de nhap URL search TikTok va tao/cap nhat SKU TikTok.
- [x] Bo field chon SKU kho lien ket khoi form SKU TikTok.

---

## Phase 3 - Test MVP

- [ ] Test tạo sản phẩm.
- [x] Test thêm size.
- [x] Test thêm combo.
- [x] Test sinh SKU.
- [x] Test nhập kho.
- [ ] Test bán combo 3 trừ đúng 3 bộ.
- [ ] Test bán 2 combo 3 trừ đúng 6 bộ.
- [ ] Test hoàn hàng cộng kho.
- [ ] Test báo cáo lợi nhuận.
