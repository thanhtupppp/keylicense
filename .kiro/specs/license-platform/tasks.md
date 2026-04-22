# Kế Hoạch Triển Khai — License Platform

## Tổng Quan

Triển khai hệ thống quản lý license key toàn diện trên Laravel, bao gồm: database migrations, Eloquent models, state machine, domain services, REST API v1, admin dashboard (Blade + Livewire), scheduler jobs, và property-based tests (Eris).

## Tasks

- [x]   1. Khởi tạo dự án Laravel và cấu hình môi trường
    - Cài đặt Laravel mới nhất, cấu hình `.env` với MySQL, Redis, queue driver
    - Cài đặt các package: `spatie/laravel-model-states`, `firebase/php-jwt` (hoặc `lcobucci/jwt`), `giorgiosironi/eris`, `livewire/livewire`
    - Tạo RSA keypair (RS256) cho JWT offline token; lưu private key vào `.env`, public key vào `storage/`
    - Cấu hình queue worker, scheduler, Redis connection
    - _Requirements: T6, T7, 9.7_

- [ ]   2. Database migrations và Eloquent models
    - [x] 2.1 Tạo migrations cho tất cả bảng
        - Migration `products`: id, name, slug (unique), description, logo_url, platforms (JSON), status (enum), offline_token_ttl_hours, api_key (unique), deleted_at, timestamps
        - Migration `licenses`: id, product_id (FK), key_hash (unique CHAR(64)), key_last4 (CHAR(4)), license_model (enum), status (enum, default inactive), max_seats, expiry_date, customer_name, customer_email, notes, deleted_at, timestamps
        - Migration `activations`: id, license_id (FK), device_fp_hash (CHAR(64) nullable), user_identifier (nullable), type (enum), activated_at, last_verified_at, is_active (bool), timestamps; unique(license_id, device_fp_hash), unique(license_id, user_identifier)
        - Migration `floating_seats`: id, license_id (FK), activation_id (FK), device_fp_hash (CHAR(64)), last_heartbeat_at, timestamps; unique(license_id, device_fp_hash)
        - Migration `offline_token_jti`: id, license_id (FK), jti (unique VARCHAR(36)), expires_at, is_revoked (bool), timestamps
        - Migration `audit_logs`: id, event_type, subject_type (enum nullable), subject_id (nullable), ip_address, payload (JSON), result (enum), severity (enum), created_at (no updated_at)
        - _Requirements: T4, T5, T8, T9, T10_

    - [x] 2.2 Tạo Eloquent models với relationships và soft deletes
        - `Product`: SoftDeletes, HasMany licenses; cast platforms as array; fillable fields
        - `License`: SoftDeletes, BelongsTo product, HasMany activations/floating_seats/offline_token_jti; cast expiry_date as date
        - `Activation`: BelongsTo license; HasMany floating_seats
        - `FloatingSeat`: BelongsTo license, activation
        - `OfflineTokenJti`: BelongsTo license
        - `AuditLog`: no updated_at; scopes by event_type, severity, subject
        - _Requirements: T8, 1.1, 2.1_

    - [x] 2.3 Viết property test cho hash storage (P15)
        - **Property 15: Hash storage round-trip**
        - Với bất kỳ license key plaintext K: `key_hash = SHA-256(K)`, `key_last4 = substr(K, -4)`
        - Với bất kỳ device fingerprint F: `device_fp_hash = SHA-256(F)`
        - **Validates: Requirements 13.1, 13.3**

- [ ]   3. State machine và LicenseService
    - [x] 3.1 Triển khai state classes và LicenseStateContract
        - Tạo interface `LicenseStateContract` với methods: `canActivate()`, `canSuspend()`, `canRevoke()`, `canRestore()`, `canRenew()`, `canUnrevoke()`
        - Tạo 5 state classes trong `App\States\License\`: `InactiveState`, `ActiveState`, `ExpiredState`, `SuspendedState`, `RevokedState`
        - Mỗi state implement `LicenseStateContract` trả về đúng boolean theo bảng transition
        - Đăng ký state machine trên model `License` qua `spatie/laravel-model-states`
        - _Requirements: T1, T2, 3.1, 3b.1_

    - [x] 3.2 Triển khai LicenseService với tất cả transition methods
        - Methods: `activate()`, `expire()`, `suspend()`, `revoke()`, `restore()`, `renew()`, `unrevoke()`
        - Mỗi method kiểm tra `canXxx()` trên state hiện tại; ném `InvalidTransitionException` nếu không hợp lệ
        - `onSuspend` hook: vô hiệu hóa tất cả activations (`is_active = false`), đánh dấu tất cả JTI `is_revoked = true`
        - `onRevoke` hook: hủy tất cả activations, đánh dấu JTI invalid, xóa floating seats
        - `onRestore` hook: kiểm tra `expiry_date`; nếu đã qua ném `LicenseExpiredException`
        - `onUnrevoke` hook: chuyển về `inactive`, ghi audit log
        - Tất cả transitions thực thi trong DB transaction
        - _Requirements: T1, T2, 3.2, 3.3, 3.4, 3.6, 3.8, 3.11, 3b.2_

    - [x] 3.3 Viết property test cho state machine transitions (P5)
        - **Property 5: State machine enforces valid transitions only**
        - Với mọi cặp (state, action): chỉ các transition trong bảng hợp lệ được phép; mọi transition khác ném `InvalidTransitionException`
        - **Validates: Requirements 3b.1, 3.1**

    - [x] 3.4 Viết property test cho restore kiểm tra expiry (P6)
        - **Property 6: Restore checks expiry date**
        - Với suspended license có `expiry_date` trong quá khứ: restore bị từ chối với `LICENSE_EXPIRED`
        - Với suspended license có `expiry_date` NULL hoặc tương lai: restore thành công → `active`
        - **Validates: Requirements 3b.7, 3.4**

    - [x] 3.5 Triển khai LicenseKeyGenerator service
        - Sinh license key theo format `XXXX-XXXX-XXXX-XXXX` (uppercase alphanumeric)
        - Đảm bảo uniqueness bằng cách kiểm tra `key_hash` trong DB trước khi lưu
        - Lưu `key_hash = SHA-256(plaintext)` và `key_last4 = substr(plaintext, -4)`
        - Hỗ trợ batch generation (1–100 keys)
        - _Requirements: T4, 2.4, 2.5, 2.6, 2.7_

    - [x] 3.6 Viết property test cho license key format và uniqueness (P4)
        - **Property 4: License key format and uniqueness**
        - Với bất kỳ batch size từ 1–100: mọi key khớp regex `^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$`
        - Không có hai key nào trùng nhau trong batch
        - **Validates: Requirements 2.4, 2.5**

- [x]   4. Checkpoint — Đảm bảo tất cả tests pass
    - Đảm bảo tất cả tests pass, hỏi người dùng nếu có thắc mắc.

- [-] 5. ActivationService và OfflineTokenService
    - [x] 5.1 Triển khai ActivationService
        - Method `activate(License $license, string $fingerprint, ?string $userIdentifier, string $ip)`: validate license state, thực thi trong DB transaction
        - Per-device: tạo/tìm Activation với `UNIQUE(license_id, device_fp_hash)`; xử lý `UniqueConstraintViolationException` idempotently
        - Per-user: tạo/tìm Activation với `UNIQUE(license_id, user_identifier)`
        - Floating: `lockForUpdate()` trên license, đếm active seats, ném `SeatsExhaustedException` nếu đầy, tạo `Activation` + `FloatingSeat`
        - Method `deactivate(License $license, string $fingerprint)`: xóa FloatingSeat (floating) hoặc đặt `is_active = false` + chuyển license về `inactive` (per-device/per-user)
        - _Requirements: T10, 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 8.2, 8.3, 9.8, 9.9_

    - [x] 5.2 Viết property test cho activation guard (P7)
        - **Property 7: Activation guard rejects invalid license states**
        - Với license ở trạng thái `revoked`/`suspended`/`expired`: activation bị từ chối với error code tương ứng
        - Per-device với fingerprint sai: `DEVICE_MISMATCH`; per-user với user_identifier sai: `USER_MISMATCH`
        - **Validates: Requirements 4.3, 4.5, 4.8**

    - [x] 5.3 Viết property test cho floating seat limit (P8)
        - **Property 8: Floating license seat limit is strictly enforced**
        - Với floating license `max_seats = N`: đúng N activation đầu thành công; activation thứ N+1 bị từ chối với `SEATS_EXHAUSTED`
        - **Validates: Requirements 4.6, 4.7, 9.9**

    - [x] 5.4 Viết property test cho activation idempotency (P14)
        - **Property 14: Activation idempotency**
        - Gửi cùng (license_key, device_fingerprint) nhiều lần: không tạo bản ghi activation mới; tổng số activation records cho cặp đó luôn là 1
        - **Validates: Requirements 9.8**

    - [x] 5.5 Triển khai OfflineTokenService
        - Method `issue(Activation $activation, Product $product)`: tạo JWT RS256 với đầy đủ claims: `iss`, `aud`, `sub`, `jti`, `iat`, `nbf`, `exp`, `device_fp_hash`, `license_model`, `license_expiry`
        - `exp = iat + product.offline_token_ttl_hours * 3600`
        - Lưu `jti` vào bảng `offline_token_jti` với `expires_at`
        - Method `verify(string $token, Product $product)`: verify chữ ký với `alg=RS256` hardcoded (không đọc từ header); kiểm tra `exp`, `nbf`, `iss`, `aud`, `jti` (not revoked); kiểm tra `exp - iat <= product.offline_token_ttl_hours * 3600`; kiểm tra `nbf - iat <= 300` giây
        - Method `revokeAllForLicense(License $license)`: đặt `is_revoked = true` cho tất cả JTI của license
        - _Requirements: T6, 6.1, 6.2, 6.3, 6.4, 6.5, 6.7_

    - [x] 5.6 Viết property test cho offline token claims và TTL (P10)
        - **Property 10: Offline token claims and TTL correctness**
        - Với bất kỳ `offline_token_ttl_hours = T`: `exp - iat = T * 3600`; `iss = "license-platform"`; `aud = product.slug`; `device_fp_hash = SHA-256(fingerprint)`; tất cả required claims có mặt
        - **Validates: Requirements 6.1, 6.5**

    - [x] 5.7 Viết property test cho token signature verification (P11)
        - **Property 11: Offline token signature verification rejects tampered tokens**
        - Token với chữ ký bị sửa: bị từ chối với `INVALID_TOKEN`
        - Token với `alg` header khác RS256: bị từ chối
        - Token với `nbf - iat > 300` giây: bị từ chối
        - Token với `exp - iat > max TTL`: bị từ chối
        - Token hợp lệ với public key đúng: luôn pass
        - **Validates: Requirements 6.3, 6.4**

- [x]   6. AuditLogger và HeartbeatService
    - [x] 6.1 Triển khai AuditLogger
        - Interface `AuditLoggerInterface` với method `log(string $eventType, array $payload, string $result, string $severity)`
        - Concrete implementation dispatch queue job `LogAuditEvent` để ghi bất đồng bộ
        - Ghi đầy đủ các sự kiện: `PRODUCT_CREATED/UPDATED/DELETED`, `LICENSE_CREATED/REVOKED/SUSPENDED/RESTORED/RENEWED/UNREVOKED`, `ACTIVATION_SUCCESS/FAILED`, `VALIDATION_FAILED`, `ADMIN_LOGIN/LOGIN_FAILED/LOCKED`, `ACTIVATION_REVOKED`
        - _Requirements: 3.2, 4.9, 8.4, 11.1_

    - [x] 6.2 Viết property test cho activation audit log (P9)
        - **Property 9: Successful activation always produces an audit log entry**
        - Với bất kỳ activation thành công: audit log chứa đúng 1 entry `ACTIVATION_SUCCESS` với license key reference, device_fp_hash/user_identifier, timestamp, IP address
        - **Validates: Requirements 4.9**

    - [x] 6.3 Triển khai HeartbeatService
        - Method `heartbeat(License $license, string $fingerprintHash)`: tìm FloatingSeat theo `(license_id, device_fp_hash)`; cập nhật `last_heartbeat_at = now()`; ném `SeatNotFoundException` nếu không tìm thấy
        - Method `releaseStaleSeats()`: xóa tất cả FloatingSeat có `last_heartbeat_at < now() - 10 minutes`
        - _Requirements: 7.2, 7.3, 7.4, 7.5_

    - [x] 6.4 Viết property test cho heartbeat timeout (P12)
        - **Property 12: Heartbeat timeout releases stale seats**
        - Với bất kỳ FloatingSeat có `last_heartbeat_at > 10 phút trước`: sau khi `releaseStaleSeats()` chạy, seat đó bị xóa và available seat count tăng 1
        - **Validates: Requirements 7.3**

- [x]   7. Checkpoint — Đảm bảo tất cả tests pass
    - Đảm bảo tất cả tests pass, hỏi người dùng nếu có thắc mắc.

- [x]   8. API Middleware và REST API v1
    - [x] 8.1 Triển khai middleware `auth:api_key`
        - Đọc `X-API-Key` header; tìm Product theo `api_key` (không bị soft delete)
        - Inject product vào request attributes: `$request->attributes->set('product', $product)`
        - Trả về 401 `UNAUTHORIZED` nếu key không hợp lệ hoặc thiếu
        - _Requirements: 9.2, 9.3_

    - [x] 8.2 Triển khai middleware rate limiting theo X-API-Key (Redis)
        - Dùng Laravel `RateLimiter` với Redis store; key = `api_key:{X-API-Key}`; limit = 60 req/60 giây
        - Trả về 429 với header `Retry-After` khi vượt giới hạn
        - _Requirements: T7, 9.5, 9.6_

    - [x] 8.3 Viết property test cho rate limiting (P13)
        - **Property 13: Rate limiting enforces per-API-key request quota**
        - Sau đúng 60 requests trong 60 giây: request thứ 61 bị từ chối với HTTP 429 và `Retry-After` header
        - Rate limit counter độc lập per API key
        - **Validates: Requirements 9.5, 9.6**

    - [x] 8.4 Triển khai middleware `json_response` và `X-Request-ID`
        - Đảm bảo mọi response có `Content-Type: application/json`
        - Thêm header `X-Request-ID: {uuid-v4}` vào mọi response
        - _Requirements: 9.4_

    - [x] 8.5 Triển khai LicenseController — activate endpoint
        - `POST /api/v1/licenses/activate`: validate request (license_key format, device_fingerprint required); hash license_key → tìm License; kiểm tra product inactive → `PRODUCT_INACTIVE`; gọi `ActivationService::activate()`; gọi `OfflineTokenService::issue()`; trả về offline_token
        - Xử lý idempotency: nếu activation đã tồn tại, trả về token hiện tại
        - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8, 4.9, 9.1, 9.8_

    - [x] 8.6 Triển khai LicenseController — validate, deactivate, info, transfer endpoints
        - `POST /api/v1/licenses/validate`: kiểm tra license status, expiry, device fingerprint; cập nhật `last_verified_at`; kiểm tra JTI revocation; trả về kết quả xác thực
        - `POST /api/v1/licenses/deactivate`: gọi `ActivationService::deactivate()`; xóa FloatingSeat nếu floating
        - `GET /api/v1/licenses/info`: trả về thông tin license (không cập nhật `last_verified_at`); không trả về `notes`
        - `POST /api/v1/licenses/transfer`: kiểm tra license ở trạng thái `inactive`; nếu không → `TRANSFER_NOT_ALLOWED`; thực hiện activation mới
        - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 9.1, 9.10_

    - [x] 8.7 Triển khai HeartbeatController và PublicKeyController
        - `POST /api/v1/licenses/heartbeat`: gọi `HeartbeatService::heartbeat()`; trả về 200 hoặc `SEAT_NOT_FOUND`
        - `GET /api/v1/public-key`: trả về public key PEM (không cần `X-API-Key`); không áp dụng rate limiting
        - _Requirements: 7.1, 7.2, 7.5, 6.2, 6.8, 9.1_

    - [x] 8.8 Viết property test cho inactive product blocks activation (P3)
        - **Property 3: Inactive product blocks new activations**
        - Với bất kỳ license thuộc product `inactive`: activation request luôn bị từ chối với `PRODUCT_INACTIVE`, bất kể license status hay device fingerprint
        - **Validates: Requirements 1.9, 1.11**

- [x]   9. Product validation và Admin Product management
    - [x] 9.1 Triển khai Product validation (FormRequest)
        - `StoreProductRequest`: validate `name` (max 255), `slug` (regex `^[a-z0-9][a-z0-9-]*[a-z0-9]$`, unique), `description` (max 1000), `logo_url` (url), `platforms` (array, in: Windows/macOS/Linux/Android/iOS/Web), `offline_token_ttl_hours` (int, 1–168)
        - `UpdateProductRequest`: tương tự, slug unique ignore current
        - _Requirements: 1.2, 1.3, 1.7, 1.8_

    - [x] 9.2 Viết property test cho product slug validation (P1)
        - **Property 1: Product slug validation**
        - Với bất kỳ string: chấp nhận nếu và chỉ nếu khớp `^[a-z0-9][a-z0-9-]*[a-z0-9]$`; từ chối với validation error nếu không khớp
        - **Validates: Requirements 1.2**

    - [x] 9.3 Viết property test cho product slug uniqueness (P2)
        - **Property 2: Product slug uniqueness**
        - Với hai product creation requests dùng cùng slug: request thứ hai luôn bị từ chối với validation error chỉ rõ slug đã tồn tại
        - **Validates: Requirements 1.3**

    - [x] 9.4 Triển khai Admin ProductController (web)
        - CRUD: index (list + search by name/slug), create, store, edit, update, destroy
        - `destroy`: kiểm tra có license liên kết → từ chối với thông báo số lượng license; nếu không → soft delete
        - Toggle status: `active` ↔ `inactive`
        - Hiển thị tổng số license liên kết trên list
        - _Requirements: 1.1, 1.4, 1.5, 1.6, 1.8, 1.9, 1.10_

- [x]   10. Admin License management
    - [x] 10.1 Triển khai Admin LicenseController (web) — list, search, filter, export
        - List với search (key string, product, model, status, date range) và filter
        - Batch create: form nhận số lượng (1–100), product, model, expiry_date, max_seats, customer info, notes; gọi `LicenseKeyGenerator::generateBatch()`; hiển thị plaintext keys 1 lần duy nhất sau khi tạo
        - Export CSV: fields = key (last4 + masked), product, model, status, expiry_date, created_at
        - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.6, 2.7, 2.8, 2.9, 2.10, 10.3, 10.4_

    - [x] 10.2 Triển khai License Detail page (Livewire)
        - Hiển thị thông tin license, danh sách activations (device_fp_hash masked, activated_at, last_verified_at, is_active)
        - Lifecycle actions: revoke, suspend, restore, renew (update expiry_date), un-revoke — mỗi action gọi `LicenseService` tương ứng
        - Thu hồi activation cụ thể: gọi `ActivationService::deactivate()` cho per-device/per-user; giải phóng seat cho floating
        - Hiển thị audit log của license
        - _Requirements: 3.2, 3.3, 3.4, 3.6, 3.7, 3.9, 3.10, 3.11, 3.12, 8.1, 8.2, 8.3, 8.4_

- [x]   11. Admin Authentication và Dashboard
    - [x] 11.1 Triển khai Admin authentication với login lockout
        - Login form với username/password; sử dụng Laravel Auth
        - Theo dõi failed attempts per username trong cache (Redis); sau 5 lần thất bại trong 15 phút → khóa 15 phút
        - Ghi audit log: `ADMIN_LOGIN`, `ADMIN_LOGIN_FAILED`, `ADMIN_LOCKED`
        - _Requirements: 10.1, 10.5_

    - [x] 11.2 Triển khai Admin Dashboard (Livewire)
        - Metrics: tổng license theo từng status, tổng product, activations trong 24h, validation failures trong 24h
        - Biểu đồ Chart.js: activations theo ngày/tuần/tháng (switchable), top 5 products theo activations trong 30 ngày
        - Filter theo product cụ thể hoặc toàn hệ thống
        - _Requirements: 10.2, 10.6, 10.7_

    - [x] 11.3 Triển khai Admin Audit Log page (Livewire)
        - List audit logs với filter: event_type, subject_type, severity, date range
        - Search theo ip_address, subject_id
        - _Requirements: 11.1, 11.2, 11.3_

- [x]   12. Checkpoint — Đảm bảo tất cả tests pass
    - Đảm bảo tất cả tests pass, hỏi người dùng nếu có thắc mắc.

- [x]   13. Laravel Scheduler jobs
    - [x] 13.1 Triển khai Expiry Check job
        - Command/job chạy hàng ngày (hoặc mỗi giờ): tìm tất cả license có `status = active` và `expiry_date < today()`; gọi `LicenseService::expire()` cho từng license
        - _Requirements: 3.5, 3b.1_

    - [x] 13.2 Triển khai Heartbeat Cleanup job
        - Command/job chạy mỗi phút: gọi `HeartbeatService::releaseStaleSeats()` để xóa FloatingSeat có `last_heartbeat_at < now() - 10 minutes`
        - _Requirements: 7.3_

    - [x] 13.3 Triển khai Audit Log Archive job
        - Command/job chạy hàng ngày: archive (hoặc xóa) audit_logs có `created_at < now() - 365 days`
        - _Requirements: 11.4_

    - [x] 13.4 Đăng ký tất cả jobs trong Laravel Scheduler (`app/Console/Kernel.php`)
        - Expiry check: `->daily()`
        - Heartbeat cleanup: `->everyMinute()`
        - Audit log archive: `->daily()`
        - _Requirements: 3.5, 7.3, 11.4_

- [] 14. Integration tests và wiring
    - [ ]\* 14.1 Viết integration tests cho full activation flow
        - Test end-to-end: POST /api/v1/licenses/activate → DB records → offline token response
        - Test per-device, per-user, floating models
        - Test idempotency với cùng request
        - _Requirements: 4.1, 4.2, 4.4, 4.6, 9.8_

    - [ ]\* 14.2 Viết integration tests cho concurrent floating seat allocation
        - Simulate concurrent requests với `max_seats = N`; verify đúng N seats được cấp, không có race condition
        - _Requirements: T10, 4.7, 9.9_

    - [ ]\* 14.3 Viết integration tests cho rate limiting với Redis thật
        - Gửi 61 requests với cùng API key trong 60 giây; verify request thứ 61 nhận 429 + `Retry-After`
        - Verify requests từ API key khác không bị ảnh hưởng
        - _Requirements: T7, 9.5, 9.6_

    - [ ]\* 14.4 Viết integration tests cho scheduler jobs
        - Test expiry check: tạo license với `expiry_date` trong quá khứ, chạy job, verify status = `expired`
        - Test heartbeat cleanup: tạo FloatingSeat với stale timestamp, chạy job, verify seat bị xóa
        - _Requirements: 3.5, 7.3_

- [ ]   15. Final checkpoint — Đảm bảo tất cả tests pass
    - Đảm bảo tất cả tests pass, hỏi người dùng nếu có thắc mắc.

## Notes

- Mỗi task tham chiếu đến requirements cụ thể để đảm bảo traceability
- Checkpoints đảm bảo validation tăng dần sau mỗi nhóm tính năng lớn
- Property tests dùng thư viện Eris, chạy tối thiểu 100 iterations; tag `@group property-based`
- Unit tests dùng SQLite in-memory; integration tests dùng MySQL thật + Redis thật
- Không bao giờ bypass state machine bằng cách gọi `$license->update(['status' => ...])` trực tiếp
- Private key JWT không được commit vào source code; chỉ lưu trong `.env`
