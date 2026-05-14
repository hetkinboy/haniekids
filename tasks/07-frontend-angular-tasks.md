# 07 - Chia task code Frontend Angular

## 1. Mục tiêu frontend

Xây dựng giao diện Angular cho phần mềm quản lý chi phí vận hành TikTok Shop.

Frontend cần dễ dùng, ưu tiên thao tác nhanh cho chủ shop.

Stack đề xuất:

```text
Angular 18+ hoặc Angular 21
Standalone components
ng-zorro-antd
TailwindCSS
RxJS
Auth interceptor
Role guard
```

---

## 2. Cấu trúc thư mục đề xuất

```text
src/app/
  core/
    services/
    interceptors/
    guards/
    models/
  layout/
    admin-layout/
    auth-layout/
  features/
    auth/
    dashboard/
    products/
    variants/
    skus/
    stock/
    orders/
    finance/
    reports/
    settings/
  shared/
    components/
    pipes/
    directives/
```

---

## 3. Task Frontend 01 - Layout admin

### Việc cần làm

- Tạo layout chính.
- Sidebar menu.
- Header.
- Khu vực content.
- Responsive cơ bản.

### Menu

```text
Dashboard
Sản phẩm
Kho hàng
Đơn hàng
Tài chính
Báo cáo
Cấu hình
```

### Menu con sản phẩm

```text
Sản phẩm gốc
Sản phẩm kho
SKU TikTok
Biến thể & Combo
```

---

## 4. Task Frontend 02 - Auth UI

### Màn hình

```text
/auth/login
```

### Form

```text
Email/Username
Password
Button đăng nhập
```

### Logic

- Gọi API login.
- Lưu token.
- Lưu thông tin user.
- Điều hướng vào dashboard.
- AuthInterceptor tự gắn token.
- AuthGuard chặn route chưa login.

---

## 5. Task Frontend 03 - Product service + models

### Models

```ts
Product
VariantGroup
VariantOption
ProductSku
StockBySize
Order
OrderItem
OperatingCost
Settlement
```

### Services

```ts
ProductService
VariantService
SkuService
StockService
OrderService
FinanceService
ReportService
```

### Yêu cầu

- Tách rõ API service.
- Không viết logic HTTP trực tiếp trong component.
- Có interface request/response.

---

## 6. Task Frontend 04 - Danh sách sản phẩm gốc

### Route

```text
/products/base
```

### Giao diện

- Ô tìm kiếm.
- Lọc trạng thái.
- Nút thêm sản phẩm.
- Bảng sản phẩm.
- Pagination.

### Cột bảng

```text
Mã sản phẩm
Tên sản phẩm
Tổng SKU
Tổng tồn
Trạng thái
Thao tác
```

### Thao tác

```text
Sửa
Cấu hình biến thể
Xem SKU
Xem tồn kho
Ngưng bán
```

---

## 7. Task Frontend 05 - Form thêm/sửa sản phẩm

### Route

```text
/products/base/create
/products/base/:id/edit
```

### Form

```text
product_code
name
category_id
description
image
status
```

### Validation

- name required.
- product_code required.

### Sau khi lưu

- Nếu tạo mới: chuyển sang màn hình biến thể.
- Nếu sửa: quay lại danh sách hoặc giữ lại màn hình.

---

## 8. Task Frontend 06 - Màn hình cấu hình biến thể

### Route

```text
/products/:productId/variants
```

### Giao diện

Chia 2 card:

```text
Card Size
Card Combo
```

### Card Size

Bảng:

```text
Size
Cost 1 bộ
Thứ tự
Trạng thái
Thao tác
```

Nút:

```text
+ Thêm size
```

### Card Combo

Bảng:

```text
Tên combo
Số bộ quy đổi
Thứ tự
Mặc định bán TikTok
Thao tác
```

Nút:

```text
+ Thêm combo
```

### Nút chính

```text
Sinh SKU
Xem danh sách SKU
```

### Modal thêm size

```text
Size name
Base cost
Sort order
```

### Modal thêm combo

```text
Combo name
Combo quantity
Sort order
Default sellable
```

---

## 9. Task Frontend 07 - Màn hình SKU TikTok

### Route

```text
/products/:productId/skus
```

### Giao diện

Bảng SKU có chỉnh sửa nhanh.

### Cột

```text
SKU code
Tên SKU
Size
Combo
Số bộ
Cost gợi ý
Cost thực tế
Giá bán
Mã SKU TikTok
Bán TikTok
Trạng thái
```

### Chức năng

- Inline edit cost_price.
- Inline edit sale_price.
- Toggle is_sellable.
- Toggle is_active.
- Nhập tiktok_sku_id.
- Lọc size/combo.

### Cảnh báo

Hiển thị rõ:

```text
Combo 3 sẽ trừ 3 bộ tồn kho theo size.
```

---

## 10. Task Frontend 08 - Màn hình tồn kho theo size

### Route

```text
/stock/by-size
```

### Giao diện

- Bộ lọc sản phẩm.
- Bảng tồn kho.
- Nút nhập hàng.
- Nút điều chỉnh.
- Nút lịch sử.

### Cột

```text
Sản phẩm
Size
Tồn hiện tại
Đã giữ
Có thể bán
Cost TB
Giá trị tồn
```

### Cảnh báo

- Tồn thấp.
- Tồn âm không được xảy ra.

---

## 11. Task Frontend 09 - Màn hình nhập hàng

### Route

```text
/stock/imports
/stock/imports/create
```

### Giao diện danh sách

```text
Mã phiếu
Ngày nhập
Nhà cung cấp
Tổng số lượng
Tổng tiền
Người tạo
```

### Giao diện tạo phiếu

Form header:

```text
supplier_name
import_date
note
```

Bảng items:

```text
Sản phẩm
Size
Số lượng
Cost 1 bộ
Thành tiền
```

### Chức năng

- Thêm dòng.
- Xóa dòng.
- Tự tính thành tiền.
- Tổng số lượng.
- Tổng tiền.

---

## 12. Task Frontend 10 - Lịch sử kho

### Route

```text
/stock/movements
```

### Bộ lọc

```text
Sản phẩm
Size
Loại phát sinh
Từ ngày
Đến ngày
```

### Bảng

```text
Ngày
Sản phẩm
Size
Loại
Số lượng
Cost
Tham chiếu
Ghi chú
```

---

## 13. Task Frontend 11 - Danh sách đơn hàng

### Route

```text
/orders
```

### Bộ lọc

```text
Keyword
Trạng thái
Từ ngày
Đến ngày
```

### Bảng

```text
Mã đơn
Mã TikTok
Ngày đặt
Trạng thái
Doanh thu
Phí TikTok
Tiền thực nhận
Giá vốn
Lợi nhuận
Thao tác
```

---

## 14. Task Frontend 12 - Form tạo đơn hàng

### Route

```text
/orders/create
```

### Form đơn hàng

```text
platform
tiktok_order_id
customer_name
order_date
status
note
```

### Bảng sản phẩm trong đơn

```text
SKU
Size
Combo
Số bộ/combo
Số lượng combo
Giá bán
Cost
Số bộ trừ kho
Thành tiền
```

### Logic khi chọn SKU

Frontend hiển thị:

```text
SKU: Size 5 - Combo 3
Số bộ/combo: 3
Số lượng mua: 2
Tổng trừ kho: 6 bộ size 5
```

### Form phí

```text
gross_amount
discount_amount
platform_fee
transaction_fee
shipping_fee
cod_amount
net_revenue
```

---

## 15. Task Frontend 13 - Cập nhật trạng thái hoàn/hủy

### Route

```text
/orders/:id
```

### Modal đổi trạng thái

```text
Trạng thái mới
Có cộng lại kho không
Phí hoàn hàng
Ghi chú
```

### Cảnh báo

Nếu return_to_stock = true:

```text
Hệ thống sẽ cộng lại số bộ đã trừ vào kho theo size.
```

---

## 16. Task Frontend 14 - Chi phí vận hành

### Route

```text
/finance/operating-costs
```

### Giao diện

- Danh sách chi phí.
- Thêm/sửa/xóa.
- Lọc ngày.
- Lọc loại chi phí.

### Form

```text
cost_date
cost_type
amount
allocation_type
product_id
order_id
note
```

---

## 17. Task Frontend 15 - Đối soát doanh thu

### Route

```text
/finance/settlements
```

### Chức năng

- Danh sách phiên đối soát.
- Tạo phiên.
- Xem chi tiết.
- So sánh chênh lệch.

### Màn hình compare

Bảng:

```text
Mã đơn
Hệ thống tính
TikTok trả
Chênh lệch
Ghi chú
```

---

## 18. Task Frontend 16 - Báo cáo

### Route

```text
/reports/overview
/reports/by-product
/reports/by-sku
/reports/stock
```

### Báo cáo tổng quan

Cards:

```text
Tổng đơn
Doanh thu gộp
Tiền thực nhận
Giá vốn
Chi phí vận hành
Lợi nhuận
Biên lợi nhuận
```

### Báo cáo theo sản phẩm

Bảng:

```text
Sản phẩm
Số combo bán
Số bộ bán
Doanh thu
Giá vốn
Lợi nhuận
```

### Báo cáo theo SKU

Bảng:

```text
SKU
Size
Combo
Số combo bán
Số bộ bán
Doanh thu
Giá vốn
Lợi nhuận
```

---

## 19. Task Frontend 17 - Guard phân quyền

### Quy tắc

Admin:

```text
Được thêm/sửa/xóa.
Được cấu hình biến thể.
Được điều chỉnh kho.
Được xem báo cáo.
```

Member:

```text
Chỉ xem sản phẩm/kho.
Có thể thêm đơn nếu được cấp quyền.
Không được xóa dữ liệu.
```

### Cần làm

- RoleGuard.
- Ẩn nút theo quyền.
- Chặn route theo quyền.

---

## 20. Task Frontend 18 - Test luồng MVP

### Test case chính

```text
1. Tạo sản phẩm Bộ cotton.
2. Thêm size 5, 6.
3. Thêm combo 1, 3, 5.
4. Sinh SKU.
5. Nhập kho size 5: 10 bộ.
6. Tạo đơn Size 5 - Combo 3, số lượng 2.
7. Kiểm tra tồn size 5 còn 4 bộ.
8. Kiểm tra lợi nhuận đơn hàng.
9. Nhập chi phí ads.
10. Xem báo cáo tổng quan.
```
