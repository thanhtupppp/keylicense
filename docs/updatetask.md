# Cập nhật tiến độ task — License Platform MVP

## Mục tiêu

Ghi rõ trong mỗi task:

- Đã làm được gì
- File nào đã thay đổi
- Trạng thái hiện tại
- Bước tiếp theo

---

## Task 1 — Khảo sát project và xác định stack

**Trạng thái:** ✅ Hoàn thành

### Đã làm

- Xác nhận dự án đang dùng **Laravel 12 + PHP 8.2**.
- Kiểm tra cấu trúc khởi tạo app và routing hiện tại.

### File liên quan

- `composer.json`
- `bootstrap/app.php`

### Kết quả

- Chọn hướng triển khai backend API thuần theo mô hình Laravel.

---

## Task 2 — Thiết kế blueprint MVP theo technical design

**Trạng thái:** ✅ Hoàn thành (phiên bản triển khai đầu tiên)

### Đã làm

- Xác lập nhóm endpoint MVP theo OpenAPI:
    - Health: `health/status/version`
    - Admin auth + provisioning: `login/products/plans/entitlements/licenses/issue`
    - Client: `activate/validate`
- Chuẩn hoá response envelope: `success/data/error/meta`.

### File liên quan

- `docs/openapi-spec.md`
- `app/Support/ApiResponse.php`

### Kết quả

- Có khung API thống nhất để các module dùng chung.

---

## Task 3 — Scaffold module backend cốt lõi

**Trạng thái:** ✅ Hoàn thành (MVP core)

### Đã làm

- Tạo model cốt lõi:
    - `AdminUser`, `Product`, `Plan`, `Entitlement`, `LicenseKey`, `Activation`, `ApiKey`
- Tạo middleware xác thực:
    - `admin.auth` (Bearer token)
    - `client.api-key` (`X-API-Key`)
- Tạo controller theo nhóm endpoint:
    - Health, Admin Auth, Admin Catalog, Entitlement, Issue License, Client Activate/Validate
- Khai báo route API đầy đủ theo prefix `/v1`.

### File liên quan

- `app/Models/*.php`
- `app/Http/Controllers/Api/**`
- `app/Http/Middleware/AdminAuthMiddleware.php`
- `app/Http/Middleware/ClientApiKeyMiddleware.php`
- `routes/api.php`
- `bootstrap/app.php`

### Kết quả

- API MVP chạy end-to-end cho luồng cấp phép cơ bản.

---

## Task 4 — Database schema/migration + seed dữ liệu tối thiểu

**Trạng thái:** ✅ Hoàn thành (MVP schema)

### Đã làm

- Tạo migration cho bảng:
    - `admin_users`, `products`, `plans`, `entitlements`, `license_keys`, `activations`, `api_keys`
- Cấu hình UUID, index, FK và cột trạng thái cơ bản.
- Seed dữ liệu dev:
    - 1 admin account
    - 1 client API key

### File liên quan

- `database/migrations/2026_04_13_000100_create_admin_users_table.php`
- `database/migrations/2026_04_13_000110_create_license_platform_core_tables.php`
- `database/seeders/DatabaseSeeder.php`

### Kết quả

- `php artisan migrate:fresh --seed` chạy thành công.

---

## Task 5 — Expose API theo OpenAPI + kiểm tra

**Trạng thái:** ✅ Hoàn thành mức MVP

### Đã làm

- Expose toàn bộ endpoint MVP như OpenAPI draft.
- Validate dữ liệu request ở controller cho các endpoint chính.
- Áp business rule cơ bản:
    - kiểm tra license tồn tại/trạng thái/hết hạn
    - giới hạn activation
    - validate activation theo domain
- Chạy test framework mặc định thành công.

### File liên quan

- `routes/api.php`
- `app/Http/Controllers/Api/**`
- `docs/openapi-spec.md`

### Kết quả

- Luồng cơ bản hoạt động: `login -> create product/plan/entitlement -> issue -> activate -> validate`.

---

## Task 6 — Admin Portal login (Blade) + nối API

**Trạng thái:** ✅ Hoàn thành

### Đã làm

- Tạo trang login admin bằng Blade.
- Tạo dashboard admin cơ bản sau đăng nhập.
- Tạo middleware session auth cho portal.
- Refactor login để dùng `AdminLoginService`, bỏ gọi HTTP nội bộ gây lỗi SSL self-signed.

### File liên quan

- `app/Http/Controllers/Web/AdminPortalAuthController.php`
- `app/Http/Middleware/AdminPortalSessionAuth.php`
- `resources/views/admin/login.blade.php`
- `resources/views/admin/dashboard.blade.php`
- `routes/web.php`
- `app/Services/Admin/AdminLoginService.php`

### Kết quả

- `/admin/login` chạy ổn định, không còn lỗi cURL SSL nội bộ.

---

## Task 7 — Session security nâng cao (remember/sliding/device limit)

**Trạng thái:** ✅ Hoàn thành

### Đã làm

- Thêm `remember me` ở login form.
- Thêm sliding session timeout.
- Thêm rotate token định kỳ theo activity window.
- Thêm giới hạn thiết bị đăng nhập đồng thời (`max_devices`).
- Thêm cảnh báo khi login mới làm kick phiên cũ do vượt giới hạn.
- Thêm trang quản lý phiên đăng nhập:
    - liệt kê device key
    - IP gần nhất
    - User-Agent gần nhất
    - last activity
    - expires at
    - revoke thủ công từng phiên
- Thêm thao tác **revoke all except current session**.
- Thêm audit log cho các event:
    - `login`
    - `kick`
    - `rotate`
    - `revoke`
    - `revoke_all_except_current`
    - `expired`
- Chuẩn hoá event code observability cho guard fail: `AUTH_TOKEN_GUARD_FAIL`.
- Hardening tránh lỗi runtime `stdClass::update()` bằng guard type + update theo query builder ở các luồng revoke/kick/rotate.
- Đã thêm biến env cấu hình session security vào `.env` và `.env.example`:
    - `ADMIN_MAX_DEVICES`
    - `ADMIN_ROTATE_AFTER_SECONDS`
    - `ADMIN_SESSION_SECONDS_DEFAULT`
    - `ADMIN_SESSION_SECONDS_REMEMBER`
- Đã chạy migration bổ sung:
    - `2026_04_14_000130_add_client_meta_to_admin_tokens_table`
    - `2026_04_14_000140_create_admin_token_audit_logs_table`

### File liên quan

- `config/admin_portal.php`
- `app/Models/AdminToken.php`
- `app/Models/AdminTokenAuditLog.php`
- `database/migrations/2026_04_14_000120_create_admin_tokens_table.php`
- `database/migrations/2026_04_14_000130_add_client_meta_to_admin_tokens_table.php`
- `database/migrations/2026_04_14_000140_create_admin_token_audit_logs_table.php`
- `app/Services/Admin/AdminLoginService.php`
- `app/Http/Middleware/AdminPortalSessionAuth.php`
- `app/Http/Controllers/Web/AdminPortalAuthController.php`
- `app/Http/Controllers/Api/Admin/AuthController.php`
- `resources/views/admin/dashboard.blade.php`
- `resources/views/admin/sessions.blade.php`
- `routes/web.php`

### Kết quả

- Quản lý phiên theo thiết bị đã hoạt động.
- Có thể revoke từng phiên hoặc revoke toàn bộ phiên khác.
- Có audit trail cho kick/revoke/rotate để truy vết bảo mật.
- Log guard đã có event code thống nhất `AUTH_TOKEN_GUARD_FAIL` để lọc dashboard observability.
- Luồng runtime đã được hardening để tránh lỗi `Call to unknown method: stdClass::update()`.

---

## Task 8 — Platform Configuration (theo mục 44 technical design)

**Trạng thái:** ✅ Hoàn thành (MVP API + schema)

### Đã làm

- Thêm bảng `platform_configs` theo đúng spec chính:
    - `key`, `value`, `value_type`, `description`, `is_sensitive`, `updated_by`, `updated_at`
- Thêm model `PlatformConfig` với typed value accessor:
    - `string`, `integer`, `boolean`, `json`
- Expose Admin API cho Platform Configuration:
    - `GET /v1/admin/platform/config`
    - `PATCH /v1/admin/platform/config`
    - `GET /v1/admin/platform/config/{key}`
- Áp dụng masking cho config nhạy cảm ở response:
    - `is_sensitive = true` trả về `"***"`

### File liên quan

- `database/migrations/2026_04_14_000150_create_platform_configs_table.php`
- `app/Models/PlatformConfig.php`
- `app/Http/Controllers/Api/Admin/PlatformConfigController.php`
- `routes/api.php`

### Kết quả

- Đã có module cấu hình nền tảng chạy được ở mức MVP theo đúng thứ tự trong technical design.
- Đã bảo vệ giá trị sensitive khi trả về API.

---

## Task 9 — Admin Session Management API (theo mục 43 technical design)

**Trạng thái:** ✅ Hoàn thành (API + schema cốt lõi)

### Đã làm

- Bổ sung schema security/session cho admin:
    - Thêm vào `admin_users`: `failed_login_attempts`, `locked_until`
    - Tạo bảng `admin_login_history`
- Nâng middleware `admin.auth` sang token session thật (`admin_tokens`) thay vì chỉ dựa vào `admin_users.api_token` legacy.
- Cập nhật login flow:
    - Ghi login history thành công/thất bại
    - Tăng failed attempts khi sai mật khẩu
    - Lock account 15 phút khi vượt ngưỡng (`admin_portal.max_login_attempts`, mặc định 5)
    - Reset attempts khi login thành công
- Thêm Admin Session API endpoints:
    - `GET /v1/admin/auth/sessions`
    - `DELETE /v1/admin/auth/sessions/{id}`
    - `DELETE /v1/admin/auth/sessions`
    - `GET /v1/admin/auth/login-history`
    - `GET /v1/admin/users/{id}/sessions` (super_admin)
    - `DELETE /v1/admin/users/{id}/sessions` (super_admin)
    - `POST /v1/admin/users/{id}/unlock` (super_admin)

### File liên quan

- `database/migrations/2026_04_14_000160_add_session_security_fields_to_admin_users_table.php`
- `database/migrations/2026_04_14_000170_create_admin_login_history_table.php`
- `app/Models/AdminLoginHistory.php`
- `app/Http/Middleware/AdminAuthMiddleware.php`
- `app/Services/Admin/AdminLoginService.php`
- `app/Http/Controllers/Api/Admin/AdminSessionController.php`
- `routes/api.php`

### Kết quả

- API admin đã có lớp session management đúng hướng theo thiết kế mục 43.
- Luồng auth API an toàn hơn (token/session thật, có lockout và login history).

---

## Task 10 — Dunning Management (theo mục 42 technical design)

**Trạng thái:** ✅ Hoàn thành (MVP API + schema cốt lõi)

### Đã làm

- Tạo schema dunning theo đúng spec chính:
    - `dunning_configs`
    - `dunning_logs`
- Tạo model:
    - `DunningConfig`
    - `DunningLog`
- Expose Admin API cho Dunning phần cấu hình + lịch sử:
    - `GET /v1/admin/dunning/configs`
    - `PUT /v1/admin/dunning/configs`
    - `GET /v1/admin/dunning/logs`
- Hỗ trợ lọc cơ bản:
    - configs theo `product_id`
    - logs theo `subscription_id`, `limit`
- Đã migrate thành công và xử lý thứ tự migration để đảm bảo FK `subscriptions` hoạt động đúng.

### File liên quan

- `database/migrations/2026_04_14_000190_create_dunning_tables.php`
- `app/Models/DunningConfig.php`
- `app/Models/DunningLog.php`
- `app/Http/Controllers/Api/Admin/DunningController.php`
- `routes/api.php`

### Kết quả

- Đã có module Dunning ở mức backend API cốt lõi theo mục 42 để khép kín phần admin API ưu tiên.
- Các endpoint cấu hình và lịch sử dunning đã hoạt động.

---

## Task 11 — Dunning automation nâng cao sau mục 42

**Trạng thái:** ✅ Hoàn thành (job nền + recovery + report chi tiết)

### Đã làm

- Tạo job nền dunning:
    - `RunDunningStepJob`
    - `RecoverDunningSubscriptionJob`
- Tạo scheduler service để dispatch job theo cấu hình step:
    - step 1/2/3/4 tương ứng `+1/+3/+7/+14` ngày
- Tạo billing webhook API để kích hoạt flow:
    - `POST /v1/admin/billing-webhooks/payment-failed`
    - `POST /v1/admin/billing-webhooks/payment-succeeded`
- Khi payment success:
    - tự recover subscription về `active`
    - unsuspend entitlement/license nếu cần
    - ghi log recovery
- Mở rộng report dunning theo chiều:
    - tổng hợp chung
    - theo `product_code`
    - theo `subscription_id`
    - có product code và trạng thái gần nhất trong log
- Nối report/retry flow với `DunningService` để tái sử dụng logic recovery

### File liên quan

- `app/Jobs/RunDunningStepJob.php`
- `app/Jobs/RecoverDunningSubscriptionJob.php`
- `app/Services/Billing/DunningScheduler.php`
- `app/Services/Billing/DunningService.php`
- `app/Http/Controllers/Api/Admin/BillingWebhookController.php`
- `app/Http/Controllers/Api/Admin/DunningController.php`
- `app/Models/DunningLog.php`
- `routes/api.php`

### Kết quả

- Flow dunning đã đi xa hơn mức schema/API: có job nền, webhook recover, và report theo product/subscription.
- `php -l` cho các file backend mới/sửa đều qua.

---

## Task 12 — Rà soát phần còn thiếu theo technical design (mục 12–56)

**Trạng thái:** 🔄 Đang làm

### Đã làm

- Đọc lại phạm vi design từ mục `12` đến `56` trong `docs/license-platform-technical-design.md`.
- Đối chiếu nhanh với trạng thái hiện tại của project sau các task 1–11.
- Xác định đây là bước rà soát gap trước khi bắt tay bổ sung từng module còn thiếu.

### File liên quan

- `docs/license-platform-technical-design.md`
- `docs/updatetask.md`

### Kết quả

- Có điểm bắt đầu rõ ràng cho phase rà soát gap.
- Sẵn sàng bóc tách từng mục chưa làm theo thứ tự ưu tiên của technical design.
- Đã triển khai nhanh 2 gap nhỏ ưu tiên cao:
    - `18. Health & Status API`
    - `16. Environment Separation`

### Đã triển khai ở mục 17

- `plan_pricing` migration
- `PlanPricing` model
- `PricingService` để resolve giá theo currency và fallback default
- `PlanPricingController` cho admin API

### Đã triển khai ở mục 19

- `data_requests` migration
- `data_retention_policies` migration
- `DataRequest` model
- `DataRetentionPolicy` model
- `DataRequestController` cho customer API
- `DataRetentionService` cho retention cleanup

### Đã triển khai ở mục 18

- `GET /api/v1/health`
- `GET /api/v1/status`
- `GET /api/v1/version`
- `maintenance_windows` migration
- `MaintenanceWindow` model
- status endpoint đọc maintenance window đang active và trả `degraded` khi có maintenance

### Đã triển khai ở mục 16

- `environments` migration
- `Environment` model
- `ClientEnvironmentController`
- `GET /api/v1/client/environment`
- test đảm bảo trả `ok` hoặc `not_found`

### Hướng xử lý tiếp theo

1. So khớp từng mục 12–56 với code hiện có.
2. Ghi lại module nào đã đủ, module nào còn thiếu, module nào làm dở.
3. Ưu tiên các mục ảnh hưởng trực tiếp tới contract/API và background jobs.
4. Tiếp tục ngay mục 12–19, ưu tiên các endpoint/public contract có thể hoàn thiện nhanh và giảm gap lớn nhất.

---

## Các hạng mục chưa làm (đề xuất Task tiếp theo)

**Trạng thái:** ⏳ Chưa bắt đầu

1. Pest Feature Tests cho luồng admin portal + session/device management.
2. Form Request classes tách validation khỏi controllers.
3. RBAC chuẩn (role/permission chi tiết).
4. Sanctum/JWT chính thức cho admin auth.
5. Audit log cho revoke/kick/rotate session.
6. Error code map đầy đủ đồng bộ 1-1 với OpenAPI.

---

## Cách cập nhật sau mỗi lần làm việc

Khi hoàn thành một phần mới, thêm vào đúng task theo format:

- **Đã làm:** mô tả ngắn, có thể kiểm chứng
- **File liên quan:** liệt kê path file
- **Kết quả:** trạng thái chạy được/chưa, command test
- **Trạng thái:** `✅ Hoàn thành` | `🔄 Đang làm` | `⏳ Chưa làm`
