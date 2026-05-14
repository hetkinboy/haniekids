# 08 - Hướng dẫn Codex đọc và triển khai project

## 1. Mục tiêu project

Xây dựng phần mềm quản lý chi phí vận hành TikTok Shop bằng:

```text
Backend: CodeIgniter 4
Frontend: Angular
Database: MySQL/MariaDB
```

Phần mềm dùng cho shop bán ít sản phẩm nhưng có nhiều biến thể size/combo.

Mục tiêu chính:

- Quản lý sản phẩm.
- Quản lý biến thể size/combo.
- Sinh SKU TikTok.
- Quản lý tồn kho thật theo size.
- Nhập hàng và cost.
- Nhập đơn hàng TikTok.
- Tính giá vốn.
- Tính phí vận hành.
- Tính lợi nhuận.
- Báo cáo doanh thu/lợi nhuận.

---

## 2. Tư duy nghiệp vụ quan trọng nhất

### Tồn kho nằm theo size, không nằm theo combo

Ví dụ:

```text
Size 5 còn 10 bộ.
```

Nếu bán:

```text
Size 5 - Combo 3 - Số lượng 2
```

Thì phải trừ:

```text
3 x 2 = 6 bộ
```

Tồn còn:

```text
10 - 6 = 4 bộ
```

Tuyệt đối không tạo tồn kho riêng cho combo 3 hoặc combo 5.

---

## 3. Combo là gì?

Combo là cách bán nhiều bộ trong một SKU.

```text
Combo 1 = 1 bộ
Combo 2 = 2 bộ
Combo 3 = 3 bộ
Combo 5 = 5 bộ
```

Combo 1 là đơn vị gốc. Có thể không bán trên TikTok nhưng vẫn nên tồn tại trong hệ thống để tính cost và tồn kho.

---

## 4. SKU cuối cùng là gì?

SKU cuối cùng được sinh từ size + combo.

Ví dụ:

```text
Size: 5, 6
Combo: 1, 3, 5
```

Sinh ra:

```text
Size 5 - Combo 1
Size 5 - Combo 3
Size 5 - Combo 5
Size 6 - Combo 1
Size 6 - Combo 3
Size 6 - Combo 5
```

Mỗi SKU có:

```text
sku_code
display_name
size_option_id
combo_option_id
combo_quantity
suggested_cost
cost_price
sale_price
is_sellable
```

---

## 5. Cách tính cost

Cost size là cost 1 bộ.

Ví dụ:

```text
Size 5 cost 38.000
Combo 3 = 3 bộ
```

Cost gợi ý:

```text
38.000 x 3 = 114.000
```

Nhưng hệ thống vẫn cho sửa cost thực tế từng SKU.

---

## 6. Cách tính đơn hàng

Khi tạo order item:

```text
stock_quantity_deducted = quantity x combo_quantity
total_sale = quantity x sale_price
total_cost = quantity x cost_price
profit = total_sale - total_cost - phí phân bổ
```

Cần copy cost_price, combo_quantity, size_name, combo_name vào order_items tại thời điểm bán để lịch sử không bị sai khi sau này sửa SKU.

---

## 7. Thứ tự Codex nên triển khai

Nên làm theo thứ tự:

```text
1. Backend CI4 base project + auth.
2. Migration database.
3. Models.
4. API sản phẩm.
5. API biến thể.
6. API sinh SKU.
7. API nhập kho.
8. API đơn hàng và trừ kho.
9. API chi phí vận hành.
10. API báo cáo.
11. Frontend Angular layout + auth.
12. Frontend sản phẩm.
13. Frontend biến thể/SKU.
14. Frontend kho hàng.
15. Frontend đơn hàng.
16. Frontend tài chính/báo cáo.
```

---

## 8. Các file Codex cần đọc

```text
docs/01-scope.md
docs/02-variant-logic.md
docs/03-database.md
docs/04-api.md
docs/05-ui-flow.md
tasks/06-backend-ci4-tasks.md
tasks/07-frontend-angular-tasks.md
tasks/current-task.md
tasks/task-list.md
```

---

## 9. Quy tắc code backend

- Dùng transaction cho nhập hàng và tạo đơn hàng.
- Không được để tồn kho âm.
- Không được trừ kho 2 lần cho cùng đơn.
- Không được sửa combo_quantity nếu đã có đơn hàng.
- Mọi thay đổi tồn kho phải ghi stock_movements.
- order_items phải copy dữ liệu SKU tại thời điểm bán.

---

## 10. Quy tắc code frontend

- Không gọi API trực tiếp trong component, phải qua service.
- Mỗi module có route riêng.
- Có loading state.
- Có message báo thành công/thất bại.
- Có confirm trước khi xóa.
- Ẩn nút theo quyền admin/member.
- Với combo, luôn hiển thị số bộ bị trừ kho để tránh nhầm.

---

## 11. Test case bắt buộc trước khi hoàn thành MVP

```text
Sản phẩm: Bộ cotton
Size 5 cost 38.000
Combo 3 = 3 bộ
Tồn size 5 = 10 bộ
SKU Size 5 - Combo 3 cost = 114.000
Tạo đơn bán 2 combo
Hệ thống phải trừ kho 6 bộ
Tồn còn 4 bộ
Order total_cost = 228.000
```

Nếu test case này sai thì nghiệp vụ chính đang sai.
