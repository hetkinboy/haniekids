# 05 - Thiết kế giao diện Angular + luồng thao tác người dùng

## 1. Mục tiêu giao diện

Giao diện Angular dùng để vận hành phần mềm tính chi phí TikTok Shop từ lúc tạo sản phẩm, cấu hình biến thể, sinh SKU, nhập tồn kho, nhập đơn hàng, đối soát doanh thu và xem báo cáo lợi nhuận.

Hệ thống cần dễ dùng cho shop nhỏ, ít sản phẩm nhưng có nhiều biến thể size/combo.

Nguyên tắc thiết kế:

- Không làm giao diện quá phức tạp.
- Ưu tiên thao tác nhanh.
- Người dùng phải hiểu rõ: sản phẩm gốc, sản phẩm kho, SKU TikTok, tồn kho theo size, combo bán ra.
- Tồn kho không nằm theo combo, tồn kho nằm theo size.
- Combo chỉ là cách bán ra và quy đổi về số bộ thật.

---

## 2. Cấu trúc menu Angular

Menu đề xuất:

```text
Dashboard

Sản phẩm
  - Sản phẩm gốc
  - Sản phẩm kho
  - SKU TikTok
  - Biến thể & Combo

Kho hàng
  - Tồn kho theo size
  - Nhập hàng
  - Điều chỉnh kho
  - Lịch sử kho

Đơn hàng
  - Danh sách đơn TikTok
  - Thêm đơn thủ công
  - Đơn hoàn / hủy

Tài chính
  - Chi phí vận hành
  - Đối soát doanh thu TikTok

Báo cáo
  - Tổng quan
  - Lợi nhuận theo sản phẩm
  - Lợi nhuận theo SKU
  - Báo cáo tồn kho
  - Báo cáo chi phí

Cấu hình
  - Loại chi phí
  - Người dùng
  - Vai trò / phân quyền
```

---

## 3. Phân biệt 3 lớp sản phẩm

Vì nghiệp vụ của shop cần rõ ràng, nên menu sản phẩm nên chia thành 3 lớp:

### 3.1. Sản phẩm gốc

Là mặt hàng thật ngoài đời.

Ví dụ:

```text
Bộ cotton bé gái mẫu A
Bộ bé trai mẫu B
Váy công chúa mẫu C
```

Sản phẩm gốc dùng để gom dữ liệu chung.

### 3.2. Sản phẩm kho

Là sản phẩm được quản lý tồn kho theo size.

Ví dụ:

```text
Bộ cotton bé gái mẫu A - Size 5
Bộ cotton bé gái mẫu A - Size 6
Bộ cotton bé gái mẫu A - Size 7
```

Tồn kho thật nằm ở lớp này.

### 3.3. SKU TikTok

Là biến thể cuối cùng dùng để bán trên TikTok.

Ví dụ:

```text
Size 5 - Combo 3
Size 6 - Combo 5
Size 7 - Combo 2
```

Khi bán SKU TikTok, hệ thống sẽ quy đổi về số lượng bộ thật để trừ kho.

---

## 4. Màn hình Dashboard

### Mục tiêu

Cho chủ shop nhìn nhanh tình hình bán hàng và lợi nhuận.

### Thành phần giao diện

Các thẻ thống kê:

- Doanh thu hôm nay.
- Tiền thực nhận.
- Giá vốn.
- Phí TikTok.
- Chi phí vận hành.
- Lợi nhuận tạm tính.
- Số đơn hôm nay.
- Số đơn hoàn/hủy.
- Số bộ đã bán.
- Giá trị tồn kho.

### Bộ lọc

- Hôm nay.
- 7 ngày.
- Tháng này.
- Khoảng ngày tùy chọn.

### Ghi chú

Dashboard chỉ hiển thị tổng quan, không xử lý nghiệp vụ phức tạp.

---

## 5. Màn hình Sản phẩm gốc

### Route Angular

```text
/products/base
```

### Chức năng

- Danh sách sản phẩm gốc.
- Thêm sản phẩm.
- Sửa sản phẩm.
- Ngưng bán.
- Xem nhanh số SKU và tồn kho.

### Bảng dữ liệu

| Cột | Mô tả |
|---|---|
| Mã sản phẩm | product_code |
| Tên sản phẩm | name |
| Danh mục | category |
| Tổng size | Số size đang có |
| Tổng SKU | Số SKU đã sinh |
| Tổng tồn | Tổng tồn theo size |
| Trạng thái | active/inactive |
| Thao tác | Sửa, biến thể, SKU, kho |

### Nút chính

```text
+ Thêm sản phẩm
Cấu hình biến thể
Xem SKU
Xem tồn kho
```

---

## 6. Màn hình thêm/sửa sản phẩm gốc

### Route Angular

```text
/products/base/create
/products/base/:id/edit
```

### Form

```text
Mã sản phẩm
Tên sản phẩm
Danh mục
Mô tả
Ảnh đại diện
Trạng thái
```

### Validation frontend

- Tên sản phẩm bắt buộc.
- Mã sản phẩm không được trống.
- Mã sản phẩm không được chứa khoảng trắng đặc biệt khó xử lý.

### Sau khi lưu

Điều hướng sang màn hình cấu hình biến thể.

---

## 7. Màn hình cấu hình biến thể & combo

### Route Angular

```text
/products/:productId/variants
```

### Mục tiêu

Cho phép cấu hình size và combo của sản phẩm.

### Bố cục đề xuất

```text
Tên sản phẩm: Bộ cotton bé gái mẫu A

[Khối Size]
- Tên nhóm: Size
- Loại: Text
- Quản lý tồn kho: Có

Danh sách size:
| Size | Cost 1 bộ | Thứ tự | Trạng thái | Thao tác |
| 5    | 38.000    | 1      | Active     | Sửa/Xóa  |
| 6    | 40.000    | 2      | Active     | Sửa/Xóa  |

[Thêm size]

[Khối Combo]
- Tên nhóm: Combo
- Loại: Combo

Danh sách combo:
| Tên combo | Số bộ quy đổi | Thứ tự | Có bán TikTok mặc định | Thao tác |
| Combo 1   | 1             | 1      | Không                 | Sửa/Xóa  |
| Combo 3   | 3             | 2      | Có                    | Sửa/Xóa  |
| Combo 5   | 5             | 3      | Có                    | Sửa/Xóa  |

[Nút Sinh SKU]
```

### Quy tắc giao diện

- Combo 1 luôn nên có trong hệ thống.
- Combo 1 có thể mặc định không bán TikTok.
- Size có cost 1 bộ.
- Combo có số bộ quy đổi.
- Không cho xóa size/combo nếu đã phát sinh đơn hàng.
- Nếu combo đã phát sinh đơn thì không cho sửa số bộ quy đổi.

---

## 8. Màn hình SKU TikTok

### Route Angular

```text
/products/:productId/skus
```

### Mục tiêu

Quản lý danh sách SKU cuối cùng sau khi ghép size và combo.

### Bảng SKU

| Cột | Mô tả |
|---|---|
| SKU nội bộ | sku_code |
| Tên SKU | display_name |
| Size | size_name |
| Combo | combo_name |
| Số bộ | combo_quantity |
| Cost gợi ý | suggested_cost |
| Cost thực tế | cost_price |
| Giá bán | sale_price |
| Mã SKU TikTok | tiktok_sku_id |
| Bán TikTok | is_sellable |
| Trạng thái | is_active |

### Chức năng

- Sinh SKU tự động.
- Sửa nhanh cost thực tế.
- Sửa giá bán.
- Bật/tắt bán TikTok.
- Nhập mã SKU TikTok.
- Lọc theo size.
- Lọc theo combo.

### Ghi chú nghiệp vụ

Cost gợi ý:

```text
cost size x số bộ trong combo
```

Cost thực tế cho phép sửa riêng.

---

## 9. Màn hình tồn kho theo size

### Route Angular

```text
/stock/by-size
```

### Mục tiêu

Xem tồn kho thật theo sản phẩm và size.

### Bảng tồn kho

| Cột | Mô tả |
|---|---|
| Sản phẩm | product_name |
| Size | size_name |
| Tồn hiện tại | quantity_on_hand |
| Đã giữ | quantity_reserved |
| Có thể bán | quantity_available |
| Cost TB | avg_cost |
| Giá trị tồn | quantity_on_hand x avg_cost |

### Chức năng

- Lọc theo sản phẩm.
- Lọc size.
- Xem lịch sử kho.
- Điều chỉnh tồn.
- Nhập hàng nhanh.

---

## 10. Màn hình nhập hàng

### Route Angular

```text
/stock/imports
/stock/imports/create
```

### Form phiếu nhập

```text
Nhà cung cấp
Ngày nhập
Ghi chú
```

### Bảng dòng nhập

| Sản phẩm | Size | Số lượng | Cost 1 bộ | Thành tiền |
|---|---|---:|---:|---:|

### Luồng xử lý

1. Chọn sản phẩm.
2. Chọn size.
3. Nhập số lượng.
4. Nhập cost 1 bộ.
5. Lưu phiếu nhập.
6. Backend tăng tồn kho.
7. Backend ghi stock_movements.

### Ghi chú

Có thể có checkbox:

```text
Cập nhật cost size theo phiếu nhập này
```

Nếu bật, hệ thống cập nhật base_cost của size và gợi ý lại cost SKU.

---

## 11. Màn hình lịch sử kho

### Route Angular

```text
/stock/movements
```

### Bộ lọc

- Sản phẩm.
- Size.
- Loại phát sinh: import/sale/return/adjustment/damage.
- Từ ngày.
- Đến ngày.

### Bảng dữ liệu

| Ngày | Sản phẩm | Size | Loại | Số lượng | Cost | Tham chiếu | Ghi chú |
|---|---|---|---|---:|---:|---|---|

---

## 12. Màn hình đơn hàng TikTok

### Route Angular

```text
/orders
/orders/create
/orders/:id
```

### Danh sách đơn hàng

| Cột | Mô tả |
|---|---|
| Mã đơn | order_code/tiktok_order_id |
| Ngày đặt | order_date |
| Trạng thái | status |
| Doanh thu | gross_amount |
| Phí TikTok | platform_fee |
| Tiền thực nhận | net_revenue |
| Giá vốn | total_cost |
| Lợi nhuận | total_profit |
| Thao tác | Xem/Sửa trạng thái |

### Form tạo đơn thủ công

Thông tin đơn:

```text
Kênh bán
Mã đơn TikTok
Ngày đặt
Trạng thái
Tên khách
Ghi chú
```

Thông tin sản phẩm:

| SKU | Size | Combo | Số lượng combo | Giá bán | Cost | Trừ kho |
|---|---|---|---:|---:|---:|---:|

Thông tin phí:

```text
Doanh thu gộp
Giảm giá
Phí TikTok
Phí giao dịch
Phí vận chuyển shop chịu
Tiền COD/thu hộ
Tiền thực nhận
```

### Logic frontend cần hiển thị rõ

Khi chọn SKU:

```text
Size 5 - Combo 3
Số bộ trong combo: 3
Nếu bán số lượng 2 combo => trừ kho 6 bộ size 5
```

Frontend nên hiển thị cảnh báo nếu tồn không đủ.

---

## 13. Màn hình xử lý hoàn/hủy đơn

### Route Angular

```text
/orders/:id/status
```

### Tình huống

- Đơn hủy.
- Đơn hoàn.
- Đơn hoàn nhưng hàng còn bán được.
- Đơn hoàn nhưng hàng lỗi/mất.

### Form cập nhật

```text
Trạng thái mới
Có cộng lại kho không?
Phí hoàn hàng
Ghi chú
```

### Quy tắc

- Đơn hủy trước khi trừ kho thì không cộng/trừ gì.
- Đơn đã trừ kho mà hoàn hàng dùng lại được thì cộng kho.
- Đơn đã trừ kho mà hàng lỗi thì không cộng kho, có thể ghi damage.

---

## 14. Màn hình chi phí vận hành

### Route Angular

```text
/finance/operating-costs
```

### Chức năng

- Thêm chi phí.
- Sửa chi phí.
- Xóa chi phí.
- Lọc theo ngày/loại chi phí.

### Form

```text
Ngày phát sinh
Loại chi phí
Số tiền
Cách phân bổ
Sản phẩm liên quan nếu có
Đơn hàng liên quan nếu có
Ghi chú
```

### Loại chi phí gợi ý

```text
ads
packing
shipping_extra
staff
software
warehouse
damage
other
```

---

## 15. Màn hình đối soát TikTok

### Route Angular

```text
/finance/settlements
/finance/settlements/create
/finance/settlements/:id/compare
```

### Mục tiêu

So sánh tiền hệ thống tính với tiền TikTok thực trả.

### Chức năng

- Tạo phiên đối soát.
- Chọn khoảng ngày.
- Gắn danh sách đơn.
- Nhập tiền TikTok trả.
- So sánh chênh lệch.

### Bảng compare

| Đơn | Hệ thống tính | TikTok trả | Chênh lệch | Ghi chú |
|---|---:|---:|---:|---|

---

## 16. Màn hình báo cáo

### Route Angular

```text
/reports/overview
/reports/by-product
/reports/by-sku
/reports/stock
```

### Báo cáo tổng quan

Chỉ số:

- Tổng đơn.
- Số bộ bán ra.
- Doanh thu gộp.
- Tiền thực nhận.
- Giá vốn.
- Phí TikTok.
- Chi phí vận hành.
- Lợi nhuận.
- Biên lợi nhuận.

### Báo cáo theo sản phẩm

| Sản phẩm | Số combo bán | Số bộ bán | Doanh thu | Giá vốn | Lợi nhuận |
|---|---:|---:|---:|---:|---:|

### Báo cáo theo SKU

| SKU | Size | Combo | Số combo bán | Số bộ trừ kho | Doanh thu | Giá vốn | Lợi nhuận |
|---|---|---|---:|---:|---:|---:|---:|

### Báo cáo tồn kho

| Sản phẩm | Size | Tồn | Cost TB | Giá trị tồn |
|---|---|---:|---:|---:|

---

## 17. Phân quyền giao diện

### Admin

- Thêm/sửa/xóa sản phẩm.
- Cấu hình biến thể.
- Sinh SKU.
- Nhập hàng.
- Điều chỉnh kho.
- Sửa đơn hàng.
- Xóa chi phí.
- Xem báo cáo.

### Member

- Xem sản phẩm.
- Xem tồn kho.
- Thêm đơn thủ công nếu được cấp quyền.
- Không được xóa/sửa dữ liệu quan trọng.

---

## 18. Luồng thao tác MVP chuẩn

```text
1. Admin tạo sản phẩm gốc.
2. Admin thêm size và cost 1 bộ.
3. Admin thêm combo 1, combo 3, combo 5.
4. Admin bấm sinh SKU.
5. Admin chỉnh cost thực tế và giá bán từng SKU.
6. Admin nhập hàng theo size.
7. Nhân viên nhập đơn TikTok hoặc import đơn sau này.
8. Hệ thống tự trừ kho theo size.
9. Admin nhập chi phí vận hành.
10. Admin xem báo cáo lợi nhuận.
```
