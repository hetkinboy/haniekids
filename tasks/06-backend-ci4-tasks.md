# 06 - Chia task code Backend CodeIgniter 4

## 1. Mục tiêu backend

Xây dựng backend API bằng CodeIgniter 4 cho phần mềm tính chi phí vận hành TikTok Shop.

Backend cần xử lý:

- Auth/login.
- Phân quyền admin/member.
- Sản phẩm gốc.
- Biến thể text/combo.
- SKU cuối cùng.
- Tồn kho theo size.
- Nhập hàng.
- Đơn hàng.
- Chi phí vận hành.
- Đối soát doanh thu.
- Báo cáo lợi nhuận.

---

## 2. Quy ước code CI4

### Namespace

```php
App\Controllers\Api
App\Models
App\Entities
App\Filters
```

### Response chuẩn

```json
{
  "status": true,
  "message": "Success",
  "data": {}
}
```

Khi lỗi:

```json
{
  "status": false,
  "message": "Validation failed",
  "errors": {}
}
```

### HTTP status

```text
200 OK
201 Created
400 Bad Request
401 Unauthorized
403 Forbidden
404 Not Found
422 Validation Error
500 Server Error
```

---

## 3. Task Backend 01 - Khởi tạo project CI4

### Việc cần làm

- Tạo project CodeIgniter 4.
- Cấu hình database MySQL/MariaDB.
- Cấu hình CORS cho Angular.
- Tạo base API response helper.
- Tạo route group `/api`.

### Kết quả cần có

```text
GET /api/health
```

Response:

```json
{
  "status": true,
  "message": "API is running"
}
```

---

## 4. Task Backend 02 - Auth + phân quyền

### Bảng cần có

- users
- roles
- user_roles hoặc role_id trong users

### API

```http
POST /api/auth/login
GET /api/auth/me
POST /api/auth/logout
```

### Quyền cơ bản

```text
admin: toàn quyền
member: chỉ xem, thêm đơn nếu cho phép
```

### Middleware/Filter

- AuthFilter.
- RoleFilter.

### Ghi chú

Có thể dùng JWT đơn giản hoặc token lưu bảng personal_access_tokens.

---

## 5. Task Backend 03 - Migration database chính

Tạo migrations cho các bảng:

```text
products
variant_groups
variant_options
product_skus
stock_by_size
stock_movements
purchase_imports
purchase_import_items
orders
order_items
operating_costs
settlements
settlement_items
```

### Yêu cầu

- Có khóa chính id.
- Có created_at/updated_at.
- Những bảng quan trọng có deleted_at để soft delete.
- Index các cột hay query như product_id, sku_id, order_date, status.

---

## 6. Task Backend 04 - Models

Tạo model tương ứng:

```text
ProductModel
VariantGroupModel
VariantOptionModel
ProductSkuModel
StockBySizeModel
StockMovementModel
PurchaseImportModel
PurchaseImportItemModel
OrderModel
OrderItemModel
OperatingCostModel
SettlementModel
SettlementItemModel
```

### Yêu cầu

- Khai báo allowedFields đầy đủ.
- Khai báo useTimestamps.
- Khai báo useSoftDeletes nếu cần.
- Viết method query phổ biến trong model hoặc service.

---

## 7. Task Backend 05 - Module sản phẩm

### API

```http
GET /api/products
POST /api/products
GET /api/products/{id}
PUT /api/products/{id}
DELETE /api/products/{id}
```

### Validation

- name bắt buộc.
- product_code bắt buộc, không trùng.
- status thuộc active/inactive.

### Phân quyền

- Admin: thêm/sửa/xóa.
- Member: chỉ xem.

---

## 8. Task Backend 06 - Module biến thể

### API nhóm biến thể

```http
GET /api/products/{productId}/variant-groups
POST /api/products/{productId}/variant-groups
PUT /api/variant-groups/{id}
DELETE /api/variant-groups/{id}
```

### API option

```http
POST /api/variant-groups/{groupId}/options
PUT /api/variant-options/{id}
DELETE /api/variant-options/{id}
```

### Quy tắc

- type chỉ gồm text/combo.
- Combo option bắt buộc có combo_quantity.
- Size option có thể có base_cost.
- Không cho sửa combo_quantity nếu đã có đơn hàng.

---

## 9. Task Backend 07 - Module SKU

### API

```http
POST /api/products/{productId}/generate-skus
GET /api/products/{productId}/skus
PUT /api/skus/{id}
```

### Logic sinh SKU

- Lấy danh sách size option.
- Lấy danh sách combo option.
- Ghép từng size với từng combo.
- Tạo sku_code.
- Tạo display_name.
- Tính suggested_cost = size.base_cost x combo.combo_quantity.
- cost_price mặc định bằng suggested_cost.
- Combo 1 có thể mặc định is_sellable = false.

### Kiểm soát lỗi

- Sản phẩm chưa có size thì báo lỗi.
- Sản phẩm chưa có combo thì báo lỗi.
- Không tạo trùng SKU.

---

## 10. Task Backend 08 - Module tồn kho

### API

```http
GET /api/products/{productId}/stock
GET /api/stock/movements
POST /api/stock/adjust
```

### Logic

- Tồn kho nằm ở product_id + size_option_id.
- Không quản lý tồn kho theo combo.
- Mọi thay đổi kho phải ghi stock_movements.

### Điều chỉnh kho

Mode:

```text
increase
decrease
set
```

Validation:

- Không cho tồn âm.
- quantity > 0.

---

## 11. Task Backend 09 - Module nhập hàng

### API

```http
GET /api/purchase-imports
POST /api/purchase-imports
GET /api/purchase-imports/{id}
```

### Logic khi tạo phiếu nhập

Trong transaction:

1. Tạo purchase_imports.
2. Tạo purchase_import_items.
3. Tăng stock_by_size.
4. Tạo stock_movements type import.
5. Cập nhật total_quantity, total_amount.
6. Có thể cập nhật avg_cost.

### Validation

- items không rỗng.
- quantity > 0.
- unit_cost >= 0.
- size_option_id phải thuộc product_id.

---

## 12. Task Backend 10 - Module đơn hàng

### API

```http
GET /api/orders
POST /api/orders
GET /api/orders/{id}
PUT /api/orders/{id}/status
```

### Logic tạo đơn

Trong transaction:

1. Validate SKU.
2. Kiểm tra tồn kho nếu trạng thái cần trừ kho.
3. Copy thông tin SKU vào order_items.
4. Tính stock_quantity_deducted = quantity x combo_quantity.
5. Tính total_sale.
6. Tính total_cost.
7. Tạo order.
8. Tạo order_items.
9. Trừ stock_by_size nếu cần.
10. Tạo stock_movements type sale.
11. Tính total_profit.

### Trạng thái đơn

```text
pending
confirmed
shipped
completed
cancelled
returned
refunded
```

### Chống trừ kho 2 lần

Cần có field:

```text
orders.stock_deducted
```

Hoặc kiểm tra stock_movements theo reference.

---

## 13. Task Backend 11 - Module hoàn/hủy đơn

### API

```http
PUT /api/orders/{id}/status
```

### Request

```json
{
  "status": "returned",
  "return_to_stock": true,
  "return_fee": 15000,
  "note": "Khách hoàn, hàng còn bán được"
}
```

### Logic

- Nếu đơn hủy khi chưa trừ kho: chỉ đổi trạng thái.
- Nếu đơn đã trừ kho và return_to_stock = true: cộng lại kho.
- Nếu hàng lỗi: không cộng kho, có thể tạo movement damage.

---

## 14. Task Backend 12 - Module chi phí vận hành

### API

```http
GET /api/operating-costs
POST /api/operating-costs
PUT /api/operating-costs/{id}
DELETE /api/operating-costs/{id}
```

### Validation

- cost_date bắt buộc.
- cost_type bắt buộc.
- amount > 0.
- allocation_type hợp lệ.

### Phân quyền

- Admin được thêm/sửa/xóa.
- Member chỉ xem nếu cần.

---

## 15. Task Backend 13 - Module đối soát doanh thu

### API

```http
GET /api/settlements
POST /api/settlements
GET /api/settlements/{id}
GET /api/settlements/{id}/compare
```

### Logic

- Tạo phiên đối soát theo khoảng ngày.
- Gắn danh sách đơn.
- Lưu tiền thực nhận từ TikTok.
- So sánh với net_revenue hệ thống.

---

## 16. Task Backend 14 - Module báo cáo

### API

```http
GET /api/reports/overview
GET /api/reports/by-product
GET /api/reports/by-sku
GET /api/reports/stock
GET /api/reports/costs
```

### Báo cáo overview

Trả về:

```text
total_orders
gross_revenue
net_revenue
total_cost
platform_fee
operating_cost
profit
profit_margin
```

### Báo cáo by SKU

Cần tính:

```text
quantity_sold
stock_units_sold
gross_revenue
total_cost
profit
```

---

## 17. Task Backend 15 - Test API

### Việc cần làm

- Tạo Postman collection.
- Test CRUD sản phẩm.
- Test tạo size/combo.
- Test sinh SKU.
- Test nhập kho.
- Test tạo đơn combo 3 trừ đúng 3 bộ.
- Test hoàn hàng cộng kho.
- Test báo cáo lợi nhuận.

### Test case bắt buộc

```text
Size 5 tồn 10 bộ.
Bán SKU Size 5 - Combo 3, quantity = 2.
Hệ thống phải trừ 6 bộ.
Tồn còn 4 bộ.
```
