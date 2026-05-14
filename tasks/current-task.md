# Current Task - Backend module TikTok/Don hang/Doi soat/Chi phi/Bao cao

## Trang thai

Da bo sung backend CodeIgniter 4 cho cac module con thieu: San pham TikTok, Don hang TikTok/thu cong, Doi soat tien, Chi phi van hanh va Bao cao.

## Da thuc hien

1. Them migration:
   - `tiktok_products`.
   - `tiktok_skus`.
   - `settlements`.
   - `settlement_items`.
2. Them models:
   - `TiktokProductModel`.
   - `TiktokSkuModel`.
   - `SettlementModel`.
   - `SettlementItemModel`.
3. Them API San pham TikTok:
   - CRUD `tiktok-products`.
   - Link SKU TikTok voi SKU kho.
4. Them API Don hang:
   - GET/POST/GET detail.
   - PUT status.
   - Tao order_items copy `cost_price`, `sale_price`, `combo_quantity` tai thoi diem ban.
   - Tru kho theo size voi cong thuc `quantity * combo_quantity`.
   - Cancel/return cong kho lai theo size.
5. Them API Chi phi van hanh:
   - CRUD `operating-costs`.
   - Loc theo cost_type/date range.
6. Them API Doi soat tien:
   - GET/POST/GET detail `settlements`.
   - Tao settlement_items, tinh expected/settled/difference.
7. Them API Bao cao:
   - `GET /api/reports/overview`.
   - `GET /api/reports/by-product`.
   - `GET /api/reports/by-sku`.
   - `GET /api/reports/stock`.
8. Giu dung nghiep vu:
   - Ton kho that theo size.
   - Khong tao ton kho theo combo.
   - Combo chi luu `combo_quantity` de quy doi khi ban.
   - Ban Combo 3 thi tru size tuong ung 3 bo.
   - Don hang ghi `stock_movements` type `sale`; cancel/return ghi type `return`.

## File chinh da cap nhat

```text
backend/app/Database/Migrations/2026-05-14-000003_CreateTiktokSettlementTables.php
backend/app/Models/TiktokProductModel.php
backend/app/Models/TiktokSkuModel.php
backend/app/Models/SettlementModel.php
backend/app/Models/SettlementItemModel.php
backend/app/Controllers/Api/TiktokProductsController.php
backend/app/Controllers/Api/OrdersController.php
backend/app/Controllers/Api/OperatingCostsController.php
backend/app/Controllers/Api/SettlementsController.php
backend/app/Controllers/Api/ReportsController.php
backend/app/Config/Routes.php
```

## Kiem tra

Da chay thanh cong:

```bash
php -l backend/app/Database/Migrations/2026-05-14-000003_CreateTiktokSettlementTables.php
php -l backend/app/Controllers/Api/TiktokProductsController.php
php -l backend/app/Controllers/Api/OrdersController.php
php -l backend/app/Controllers/Api/OperatingCostsController.php
php -l backend/app/Controllers/Api/SettlementsController.php
php -l backend/app/Controllers/Api/ReportsController.php
php spark migrate
php spark routes
```

## Cach chay

Backend:

```bash
cd backend
php spark serve
```

Frontend:

```bash
npm --prefix frontend start -- --host 127.0.0.1 --port 4200
```

Mo:

```text
http://127.0.0.1:8080
```

Tai khoan test:

```text
admin@example.com / Admin@123
staff@example.com / Staff@123
```

## Task tiep theo

Noi frontend Angular cac man hinh `Don hang TikTok`, `Doi soat tien`, `Chi phi van hanh`, `Bao cao` vao API that vua tao.

## Cap nhat frontend - San pham TikTok

Da thay man hinh khung bang UI that:

- Danh sach san pham TikTok.
- Tim kiem theo ID TikTok, ten, ma san pham kho.
- Loc trang thai.
- Tao/sua/xoa san pham TikTok.
- Lien ket san pham TikTok voi san pham kho.
- Chon mot san pham TikTok de xem SKU TikTok.
- Tao/sua/xoa SKU TikTok.
- Lien ket SKU TikTok voi SKU kho theo size/combo cua san pham kho da chon.

File da cap nhat:

```text
frontend/src/app/features/tiktok/tiktok-products.component.ts
frontend/src/app/core/api/api.models.ts
frontend/src/app/core/products/products.api.ts
frontend/src/app/app.routes.ts
```

## Cap nhat moi nhat - Don giao dien San pham kho/SKU kho

Da dieu chinh theo nghiep vu moi:

- Bo truong `SKU san pham chinh` khoi form va bang San pham kho.
- Bo cot/field `SKU TikTok` khoi bang SKU kho.
- `PUT /api/skus/{id}` khong cap nhat `tiktok_sku_id` nua.
- Product API khong tim kiem, nhan, tra ve `main_sku` nua.
- Lien ket SKU TikTok chi nam trong module San pham TikTok/SKU TikTok rieng.

Luu y: migration cu tao cot `main_sku` va `product_skus.tiktok_sku_id` van con trong DB de tranh rollback pha du lieu da migrate, nhung UI/API hien tai khong dung nua.

## Cap nhat moi nhat - Sinh SKU kho

Da bo sung cau hinh sinh SKU:

- Tien to SKU chinh, vi du `AT-01`.
- Tien to rieng cho bien the Size.
- Tien to rieng cho bien the Combo.
- Moi tien to bien the co lua chon hien thi truoc hoac sau gia tri.
- Cac phan SKU duoc noi voi nhau bang dau `-`.

Vi du:

- SKU prefix: `AT-01`
- Thu tu bien the: Combo truoc - Size sau
- Size prefix: de trong
- Combo prefix: `bo`
- Combo position: sau gia tri
- Size `5`, Combo quantity `2`

Ket qua SKU: `AT-01-2bo-5`.

## Cap nhat moi nhat - Giao dien San pham TikTok

Da sap xep lai man hinh San pham TikTok:

- Danh sach san pham TikTok thanh khoi bang rieng o tren.
- SKU TikTok cua san pham dang chon thanh khoi rieng o duoi.
- Them tom tat ID TikTok, san pham kho lien ket va so SKU.
- Nut thao tac ro hon: Xem SKU, Sua, Xoa.
- Giu lien ket SKU TikTok voi SKU kho trong module San pham TikTok, khong dua vao bang SKU kho.

## Cap nhat moi nhat - Sua tuy chon Size/Combo

Da bo sung thao tac `Sua` cho tung tuy chon trong man Bien the Size/Combo:

- Size: sua ten, gia von 1 bo, thu tu, trang thai.
- Combo: sua ten, so bo, thu tu, trang thai.
- Neu Combo da duoc dung sinh SKU, backend van chan doi `combo_quantity`; nguoi dung van sua duoc ten, thu tu, trang thai.

## Cap nhat moi nhat - Backend TikTok API Integration

Da tach logic TikTok API cu thanh module backend dung chung:

- Bang `tiktok_shop_connections`: luu shop cipher, app key/secret, access token, refresh token, base URL.
- Bang `tiktok_webhook_events`: nhan va luu webhook tho tu TikTok.
- Library `TiktokShopApiClient`: ky request `signature`, refresh token, product search, lay chi tiet order/product, sync SKU, update ton TikTok.
- Controller `TiktokIntegrationController`: expose API quan tri TikTok integration.

API da co:

```text
GET    /api/tiktok/connections
POST   /api/tiktok/connections
PUT    /api/tiktok/connections/{id}
POST   /api/tiktok/refresh-token
POST   /api/tiktok/connections/{id}/refresh-token
GET    /api/tiktok/authorized-shops
POST   /api/tiktok/product-search
POST   /api/tiktok/import-search-response
POST   /api/tiktok/import-search-url
GET    /api/tiktok/orders/{ids}
GET    /api/tiktok/orders-new/{ids}
GET    /api/tiktok/products/{productId}/detail
POST   /api/tiktok/products/{productId}/sync-skus
POST   /api/tiktok/inventory/update
POST   /api/tiktok/inventory/sync-size
POST   /api/tiktok/signature
POST   /api/tiktok/webhook
GET    /api/tiktok/webhook-events
```

Luu y bao mat: khong hard-code `app_secret`, `access_token`, `refresh_token` trong source. Tao connection qua API va luu trong DB.

Da chay:

```bash
php spark migrate
php spark routes
php -l backend/app/Libraries/TiktokShopApiClient.php
php -l backend/app/Controllers/Api/TiktokIntegrationController.php
```

## Cap nhat moi nhat - Import SKU TikTok tu response search

Da bo sung luong import dung voi cach van hanh hien tai:

- Kho va SKU kho duoc tao truoc.
- San pham/SKU tren TikTok da co san.
- Tai man `San pham TikTok`, bam `Import tu search`, nhap link API search TikTok.
- Vi du link: `https://shopapi.totdep.com/api/tiktok/productsearch`.
- Backend goi link, doc JSON response va import.
- Backend lay `product.id` lam `tiktok_product_id`.
- Backend lay tung `skus[].id` lam `tiktok_sku_id`.
- Backend lay `skus[].seller_sku` de tu dong match voi `product_skus.sku_code`.
- Luu them gia TikTok, ton TikTok va warehouse_id TikTok vao `tiktok_skus`.
- Neu seller_sku trung SKU kho thi tu dong lien ket `product_sku_id`.
- Da bo field chon thu cong `SKU kho lien ket` trong form SKU TikTok.
- Khi tao/sua SKU TikTok thu cong, backend tu dong map `product_sku_id` theo `seller_sku = product_skus.sku_code`.

Da them migration:

```text
backend/app/Database/Migrations/2026-05-14-000006_AddTiktokSkuImportFields.php
```

Da chay:

```bash
php spark migrate
npm --prefix frontend run build
```
