# Tài Liệu Yêu Cầu — License Platform

## Giới Thiệu

License Platform là một hệ thống quản lý license key toàn diện được xây dựng bằng Laravel. Hệ thống cho phép admin tạo, quản lý và kiểm soát vòng đời của license key gắn với các sản phẩm phần mềm. Client application tích hợp qua REST API để xác thực và kích hoạt license. Hệ thống hỗ trợ ba mô hình cấp phép (per-device, per-user, floating), hai chế độ xác thực (online và offline hybrid), và cung cấp tài liệu hướng dẫn tích hợp cho các nền tảng phổ biến như React, Electron, Node.js, Python.

---

## Quyết Định Kỹ Thuật Cốt Lõi (Source of Truth)

Các quyết định dưới đây là **bất biến** — team backend, frontend, SDK, và QA phải bám theo, không tự diễn giải lại:

| #   | Quyết định                     | Chi tiết                                                                                                                                                                                                                                                                                                    |
| --- | ------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| T1  | **State machine**              | `License_Status` phải đi qua domain service/state machine (ví dụ: `LicenseService::revoke($license)`), không gọi `update(['status' => ...])` trực tiếp trong controller; mọi API/console command liên quan đến `License_Status` phải đi qua state machine — QA test case sẽ fail nếu phát hiện bypass       |
| T2  | **Transition chuẩn**           | `expired --renew--> suspended`; restore từ `suspended` luôn về `active` nếu chưa hết hạn tại thời điểm restore                                                                                                                                                                                              |
| T3  | **Offline enforcement**        | Suspend/revoke có hiệu lực ngay trên server; offline token có TTL ngắn (mặc định 24h, admin cấu hình được); SDK buộc refresh khi online; độ trễ tối đa = TTL còn lại                                                                                                                                        |
| T4  | **Lưu trữ License Key**        | Lưu `key_hash` (SHA-256) + `key_last4` (4 ký tự cuối plaintext); hiển thị plaintext 1 lần duy nhất khi tạo                                                                                                                                                                                                  |
| T5  | **Lưu trữ Device Fingerprint** | Lưu hash SHA-256, không lưu plaintext                                                                                                                                                                                                                                                                       |
| T6  | **JWT Offline Token**          | Ký RS256, verify phải chỉ định cứng `alg=RS256` (không tin `alg` trong header); claims bắt buộc: `exp`, `iss` (= `license-platform`), `aud` (= product slug), `nbf`, `iat`, `jti`; SDK phải từ chối token có `nbf` trong tương lai quá xa so với `iat`, hoặc `exp` vượt quá `Offline_Token_TTL` của product |
| T7  | **Rate limiting**              | Theo `X-API-Key`, dùng Redis làm limiter store để ổn định ở môi trường nhiều instance                                                                                                                                                                                                                       |
| T8  | **Soft delete**                | Product và License Key dùng soft delete; License Key không cho xóa vật lý, chỉ revoke hoặc soft delete                                                                                                                                                                                                      |
| T9  | **Timezone**                   | Toàn bộ timestamp lưu và trả về theo UTC                                                                                                                                                                                                                                                                    |
| T10 | **Concurrency**                | Activation và seat allocation dùng DB transaction + unique constraint để tránh race condition                                                                                                                                                                                                               |

---

## Bảng Chú Giải

- **License_Platform**: Hệ thống quản lý license key được xây dựng bằng Laravel.
- **Admin**: Người dùng duy nhất có quyền quản trị toàn bộ hệ thống.
- **License_Key**: Chuỗi ký tự định danh duy nhất đại diện cho một giấy phép sử dụng phần mềm.
- **Product**: Sản phẩm phần mềm được gắn với một hoặc nhiều License_Key.
- **Activation**: Hành động kích hoạt License_Key trên một thiết bị hoặc tài khoản cụ thể.
- **Device**: Máy tính hoặc thiết bị vật lý được định danh bằng Device_Fingerprint.
- **Device_Fingerprint**: Chuỗi định danh duy nhất của một thiết bị, được tạo từ thông tin phần cứng.
- **License_Model**: Mô hình cấp phép của License_Key, gồm ba loại: `per-device`, `per-user`, `floating`.
- **Per_Device_License**: License_Key chỉ được kích hoạt trên đúng một thiết bị duy nhất.
- **Per_User_License**: License_Key chỉ được kích hoạt cho đúng một tài khoản người dùng duy nhất.
- **Floating_License**: License_Key cho phép một số lượng thiết bị đồng thời giới hạn sử dụng cùng lúc.
- **Seat**: Một slot sử dụng đồng thời trong Floating_License.
- **Expiry_Date**: Ngày hết hạn của License_Key. Nếu không có, key có giá trị vĩnh viễn.
- **License_Status**: Trạng thái hiện tại của License_Key, gồm: `inactive`, `active`, `expired`, `revoked`, `suspended`.
- **Offline_Token**: Token mã hóa được cấp sau khi Activation thành công, dùng để xác thực cục bộ khi không có kết nối mạng.
- **Heartbeat**: Tín hiệu định kỳ mà client gửi lên server để duy trì trạng thái active của Floating_License.
- **API_Client**: Ứng dụng phần mềm tích hợp với License_Platform qua REST API.
- **SDK**: Thư viện tích hợp sẵn hướng dẫn API_Client kết nối với License_Platform.
- **Revoke**: Hành động vô hiệu hóa vĩnh viễn một License_Key.
- **Suspend**: Hành động tạm thời vô hiệu hóa một License_Key, có thể khôi phục.
- **Audit_Log**: Bản ghi lịch sử các hành động quan trọng trong hệ thống.
- **key_hash**: Giá trị SHA-256 của License_Key plaintext, dùng để tra cứu trong DB.
- **key_last4**: 4 ký tự cuối của License_Key plaintext, lưu để admin tìm kiếm/hỗ trợ.
- **Offline_Token_TTL**: Thời gian sống của Offline_Token, mặc định 24h, admin cấu hình được theo từng Product.
- **State_Machine**: Cơ chế kiểm soát chuyển đổi trạng thái hợp lệ của License_Key, triển khai bằng state classes trong Laravel.
- **Idempotency**: Tính chất của API endpoint — gọi nhiều lần với cùng tham số cho kết quả như nhau, không tạo bản ghi trùng lặp.

---

## Yêu Cầu

---

### Yêu Cầu 1: Quản Lý Sản Phẩm

**User Story:** Là Admin, tôi muốn tạo và quản lý danh sách sản phẩm, để có thể gắn License_Key với từng sản phẩm cụ thể.

#### Tiêu Chí Chấp Nhận

1. THE License_Platform SHALL cung cấp giao diện quản lý Product cho Admin bao gồm các thao tác tạo mới, xem chi tiết, chỉnh sửa và xóa.
2. WHEN Admin tạo mới Product, THE License_Platform SHALL yêu cầu các trường bắt buộc: tên sản phẩm (tối đa 255 ký tự), mã sản phẩm (slug duy nhất, chỉ chứa chữ thường, số và dấu gạch ngang).
3. WHEN Admin cung cấp mã sản phẩm đã tồn tại trong hệ thống, THE License_Platform SHALL trả về lỗi xác thực với thông báo mô tả rõ trường bị trùng.
4. WHEN Admin xóa một Product đang có License_Key liên kết, THE License_Platform SHALL từ chối thao tác xóa và trả về thông báo lỗi nêu rõ số lượng License_Key đang liên kết.
5. THE License_Platform SHALL cho phép Admin tìm kiếm Product theo tên hoặc mã sản phẩm.
6. THE License_Platform SHALL hiển thị danh sách Product kèm tổng số License_Key đang liên kết với mỗi Product.
7. WHEN Admin tạo mới Product, THE License_Platform SHALL cho phép Admin nhập thêm các trường tùy chọn: mô tả sản phẩm (text tự do, tối đa 1000 ký tự), URL logo/icon (URL hợp lệ), và danh sách platform hỗ trợ (chọn một hoặc nhiều từ: `Windows`, `macOS`, `Linux`, `Android`, `iOS`, `Web`).
8. THE License_Platform SHALL duy trì trạng thái Product gồm hai giá trị: `active` và `inactive`.
9. WHEN Admin chuyển trạng thái Product sang `inactive`, THE License_Platform SHALL chặn mọi yêu cầu Activation mới cho tất cả License_Key thuộc Product đó, nhưng các License_Key đang ở trạng thái `active` vẫn tiếp tục được xác thực bình thường.
10. WHEN Admin chuyển trạng thái Product từ `inactive` về `active`, THE License_Platform SHALL cho phép Activation mới trở lại cho tất cả License_Key thuộc Product đó.
11. IF API_Client gửi yêu cầu Activation với License_Key thuộc Product đang ở trạng thái `inactive`, THEN THE License_Platform SHALL từ chối yêu cầu và trả về mã lỗi `PRODUCT_INACTIVE`.

---

### Yêu Cầu 2: Tạo License Key

**User Story:** Là Admin, tôi muốn tạo License_Key với các tùy chọn linh hoạt, để cấp phép sử dụng phần mềm cho khách hàng theo nhiều mô hình khác nhau.

#### Tiêu Chí Chấp Nhận

1. WHEN Admin tạo License_Key, THE License_Platform SHALL yêu cầu Admin chỉ định: Product liên kết, License_Model (`per-device`, `per-user`, hoặc `floating`), và tùy chọn Expiry_Date.
2. WHEN Admin chọn License_Model là `floating`, THE License_Platform SHALL yêu cầu Admin nhập số lượng Seat tối đa (số nguyên dương, tối thiểu là 1).
3. WHEN Admin không cung cấp Expiry_Date, THE License_Platform SHALL tạo License_Key với giá trị vĩnh viễn (không có ngày hết hạn).
4. WHEN Admin tạo License_Key thành công, THE License_Platform SHALL sinh ra một chuỗi License_Key duy nhất theo định dạng `XXXX-XXXX-XXXX-XXXX` (mỗi nhóm gồm 4 ký tự chữ hoa và số), và gán trạng thái ban đầu là `inactive`.
5. THE License_Platform SHALL đảm bảo không có hai License_Key nào trong hệ thống có cùng giá trị chuỗi.
6. THE License_Platform SHALL cho phép Admin tạo nhiều License_Key cùng lúc (batch creation) với số lượng từ 1 đến 100 key trong một lần thao tác.
7. WHEN Admin tạo License_Key theo batch, THE License_Platform SHALL áp dụng cùng cấu hình (Product, License_Model, Expiry_Date, số Seat) cho tất cả key trong batch đó.
8. WHEN Admin tạo License_Key, THE License_Platform SHALL cho phép Admin nhập thêm thông tin khách hàng tùy chọn: tên khách hàng (tối đa 255 ký tự) và email khách hàng (định dạng email hợp lệ nếu được cung cấp).
9. WHEN Admin tạo License_Key, THE License_Platform SHALL cho phép Admin nhập ghi chú nội bộ (notes, tối đa 1000 ký tự) chỉ hiển thị trong giao diện Admin, không được trả về trong phản hồi API công khai.
10. WHEN Admin tạo License_Key theo batch, THE License_Platform SHALL áp dụng cùng thông tin khách hàng và ghi chú cho tất cả key trong batch đó.

---

### Yêu Cầu 3: Quản Lý Vòng Đời License Key

**User Story:** Là Admin, tôi muốn kiểm soát trạng thái của License_Key trong suốt vòng đời của nó, để có thể xử lý các tình huống như thu hồi, tạm khóa hoặc gia hạn.

#### Tiêu Chí Chấp Nhận

1. THE License_Platform SHALL duy trì License_Status của mỗi License_Key theo các trạng thái: `inactive`, `active`, `expired`, `revoked`, `suspended`.
2. WHEN Admin thực hiện Revoke một License_Key, THE License_Platform SHALL chuyển License_Status sang `revoked`, hủy toàn bộ Activation đang hoạt động, và ghi Audit_Log với thông tin: admin thực hiện, thời điểm, lý do (nếu có).
3. WHEN Admin thực hiện Suspend một License_Key, THE License_Platform SHALL chuyển License_Status sang `suspended` và ngăn mọi yêu cầu xác thực mới từ License_Key đó cho đến khi được khôi phục.
4. WHEN Admin khôi phục một License_Key đang ở trạng thái `suspended`, THE License_Platform SHALL chuyển License_Status về `active` nếu Expiry_Date chưa qua tại thời điểm restore (bất kể path nào dẫn đến `suspended`); nếu Expiry_Date đã qua, THE License_Platform SHALL từ chối restore và trả về lỗi `LICENSE_EXPIRED`.
5. WHEN Expiry_Date của một License_Key đã qua, THE License_Platform SHALL tự động chuyển License_Status sang `expired` và từ chối mọi yêu cầu xác thực tiếp theo.
6. WHEN Admin gia hạn một License_Key đang ở trạng thái `expired` hoặc `active`, THE License_Platform SHALL cập nhật Expiry_Date mới và duy trì toàn bộ thông tin Activation hiện có.
7. IF Admin cố gắng thực hiện Revoke một License_Key đã ở trạng thái `revoked`, THEN THE License_Platform SHALL trả về thông báo lỗi nêu rõ License_Key đã bị thu hồi trước đó.
8. WHEN Admin thực hiện Suspend một License_Key đang ở trạng thái `active`, THE License_Platform SHALL vô hiệu hóa ngay lập tức toàn bộ Activation hiện tại trên server; các Offline_Token đã cấp sẽ bị từ chối khi SDK refresh online, với độ trễ tối đa bằng Offline_Token_TTL còn lại.
9. WHEN Admin thu hồi một Activation cụ thể của Per_Device_License hoặc Per_User_License, THE License_Platform SHALL vô hiệu hóa Activation đó và chuyển License_Status về `inactive` (reset hoàn toàn), cho phép kích hoạt lại trên thiết bị hoặc tài khoản khác.
10. WHEN Admin gia hạn Expiry_Date của một License_Key đang ở trạng thái `suspended`, THE License_Platform SHALL cập nhật Expiry_Date mới nhưng giữ nguyên License_Status là `suspended` cho đến khi Admin thực hiện thao tác restore thủ công.
11. WHEN Admin thực hiện un-revoke một License_Key đang ở trạng thái `revoked`, THE License_Platform SHALL chuyển License_Status về `inactive` (không phải `active`) và ghi Audit_Log với thông tin: Admin thực hiện, thời điểm, và lý do (nếu có).
12. IF Admin cố gắng thực hiện un-revoke một License_Key không ở trạng thái `revoked`, THEN THE License_Platform SHALL trả về thông báo lỗi nêu rõ trạng thái hiện tại của License_Key.

---

### Yêu Cầu 3b: Luồng Trạng Thái License Key

**User Story:** Là Admin, tôi muốn hiểu rõ các chuyển đổi trạng thái hợp lệ của License_Key, để thực hiện đúng các thao tác quản lý vòng đời license.

#### Sơ Đồ Trạng Thái

```
inactive ──(activate)──→ active ──(expire)──→ expired
                           │                     │
                        (suspend)             (renew)
                           ↓                     │
                        suspended ←──────────────┘
                           │
                        (restore)──→ active
                           │
                        (revoke)──→ revoked ──(un-revoke)──→ inactive

active ──(revoke)──→ revoked ──(un-revoke)──→ inactive
inactive ──(revoke)──→ revoked ──(un-revoke)──→ inactive
```

#### Mô Tả Chuyển Đổi Trạng Thái

| Trạng thái nguồn | Hành động | Trạng thái đích | Ghi chú                                                                |
| ---------------- | --------- | --------------- | ---------------------------------------------------------------------- |
| `inactive`       | activate  | `active`        | Kích hoạt lần đầu hoặc sau khi thu hồi Activation cụ thể               |
| `active`         | expire    | `expired`       | Tự động khi Expiry_Date đã qua                                         |
| `active`         | suspend   | `suspended`     | Vô hiệu hóa ngay lập tức toàn bộ Activation hiện tại                   |
| `active`         | revoke    | `revoked`       | Thu hồi vĩnh viễn, hủy toàn bộ Activation                              |
| `expired`        | renew     | `suspended`     | Gia hạn key đã hết hạn → chuyển sang `suspended`, cần restore thủ công |
| `suspended`      | restore   | `active`        | Admin khôi phục thủ công                                               |
| `suspended`      | revoke    | `revoked`       | Thu hồi vĩnh viễn từ trạng thái tạm khóa                               |
| `suspended`      | renew     | `suspended`     | Cập nhật Expiry_Date nhưng giữ nguyên trạng thái `suspended`           |
| `inactive`       | revoke    | `revoked`       | Thu hồi vĩnh viễn từ trạng thái chưa kích hoạt                         |
| `revoked`        | un-revoke | `inactive`      | Admin phục hồi, key về `inactive` (không phải `active`)                |

#### Tiêu Chí Chấp Nhận

1. THE License_Platform SHALL chỉ cho phép các chuyển đổi trạng thái hợp lệ được định nghĩa trong sơ đồ trạng thái trên; mọi chuyển đổi không hợp lệ SHALL bị từ chối với thông báo lỗi mô tả rõ trạng thái hiện tại và hành động không được phép.
2. WHEN License_Key chuyển sang trạng thái `suspended`, THE License_Platform SHALL vô hiệu hóa ngay lập tức toàn bộ Activation đang hoạt động trên server; Offline_Token đã cấp sẽ bị từ chối khi SDK thực hiện refresh online, với độ trễ tối đa bằng Offline_Token_TTL còn lại.
3. WHEN Admin thu hồi một Activation cụ thể (per-device hoặc per-user), THE License_Platform SHALL chuyển License_Status về `inactive`, cho phép kích hoạt lại trên thiết bị hoặc tài khoản khác mà không cần tạo License_Key mới.
4. WHEN Admin gia hạn Expiry_Date của License_Key đang ở trạng thái `suspended`, THE License_Platform SHALL cập nhật Expiry_Date mới và duy trì License_Status là `suspended` cho đến khi Admin thực hiện restore thủ công.
5. WHEN Admin thực hiện un-revoke một License_Key, THE License_Platform SHALL chuyển License_Status về `inactive` (không phải `active`) và ghi Audit_Log bao gồm: Admin thực hiện, thời điểm, và lý do (nếu có).
6. WHEN Admin gia hạn một License_Key đang ở trạng thái `expired`, THE License_Platform SHALL chuyển License_Status sang `suspended` thay vì `active`, yêu cầu Admin thực hiện restore thủ công để kích hoạt lại.
7. WHEN Admin restore một License_Key đang ở trạng thái `suspended`, THE License_Platform SHALL kiểm tra Expiry_Date tại thời điểm restore: nếu chưa hết hạn thì chuyển về `active`; nếu đã hết hạn thì từ chối và trả về lỗi `LICENSE_EXPIRED`.

---

### Yêu Cầu 4: Kích Hoạt License Key (Activation)

**User Story:** Là API_Client, tôi muốn kích hoạt License_Key trên thiết bị hoặc tài khoản của người dùng, để bắt đầu sử dụng phần mềm được cấp phép.

#### Tiêu Chí Chấp Nhận

1. WHEN API_Client gửi yêu cầu Activation với License_Key hợp lệ và Device_Fingerprint, THE License_Platform SHALL xác thực License_Key và thực hiện Activation theo đúng License_Model tương ứng.
2. WHEN API_Client kích hoạt Per_Device_License trên một Device_Fingerprint mới, THE License_Platform SHALL ghi nhận Device_Fingerprint đó, chuyển License_Status sang `active`, và trả về Offline_Token.
3. IF API_Client cố gắng kích hoạt Per_Device_License trên một Device_Fingerprint khác với Device_Fingerprint đã đăng ký, THEN THE License_Platform SHALL từ chối yêu cầu và trả về mã lỗi `DEVICE_MISMATCH`.
4. WHEN API_Client kích hoạt Per_User_License với một `user_identifier` mới, THE License_Platform SHALL ghi nhận `user_identifier` đó, chuyển License_Status sang `active`, và trả về Offline_Token.
5. IF API_Client cố gắng kích hoạt Per_User_License với `user_identifier` khác với `user_identifier` đã đăng ký, THEN THE License_Platform SHALL từ chối yêu cầu và trả về mã lỗi `USER_MISMATCH`.
6. WHEN API_Client kích hoạt Floating_License và số lượng Seat đang sử dụng chưa đạt giới hạn tối đa, THE License_Platform SHALL cấp phát một Seat cho thiết bị đó và trả về Offline_Token.
7. IF API_Client cố gắng kích hoạt Floating_License khi tất cả Seat đã được sử dụng, THEN THE License_Platform SHALL từ chối yêu cầu và trả về mã lỗi `SEATS_EXHAUSTED` kèm thông tin số Seat tối đa.
8. IF API_Client gửi yêu cầu Activation với License_Key có License_Status là `revoked`, `suspended`, hoặc `expired`, THEN THE License_Platform SHALL từ chối yêu cầu và trả về mã lỗi tương ứng: `LICENSE_REVOKED`, `LICENSE_SUSPENDED`, `LICENSE_EXPIRED`.
9. WHEN Activation thành công, THE License_Platform SHALL ghi Audit_Log bao gồm: License_Key, Device_Fingerprint hoặc `user_identifier`, thời điểm kích hoạt, địa chỉ IP của API_Client.
10. IF API_Client gửi yêu cầu Activation cho Per_Device_License đã có Activation trên một thiết bị khác, THEN THE License_Platform SHALL từ chối yêu cầu và trả về mã lỗi `DEVICE_MISMATCH` kèm thông báo hướng dẫn liên hệ Admin để thu hồi Activation cũ trước khi kích hoạt trên thiết bị mới.

---

### Yêu Cầu 5: Xác Thực License Key Online

**User Story:** Là API_Client, tôi muốn xác thực License_Key qua API khi có kết nối mạng, để đảm bảo license vẫn còn hiệu lực trước khi cho phép người dùng sử dụng phần mềm.

#### Tiêu Chí Chấp Nhận

1. WHEN API_Client gửi yêu cầu xác thực online với License_Key và Device_Fingerprint hợp lệ, THE License_Platform SHALL kiểm tra License_Status, Expiry_Date, và tính hợp lệ của Device_Fingerprint rồi trả về kết quả xác thực trong vòng 500ms.
2. WHEN License_Key hợp lệ và còn hiệu lực, THE License_Platform SHALL trả về phản hồi thành công kèm thông tin: License_Status, Expiry_Date (nếu có), License_Model, và thời điểm xác thực.
3. IF License_Key không tồn tại trong hệ thống, THEN THE License_Platform SHALL trả về mã lỗi `LICENSE_NOT_FOUND`.
4. WHEN API_Client xác thực Floating_License, THE License_Platform SHALL kiểm tra xem Device_Fingerprint có đang giữ một Seat hợp lệ hay không và trả về kết quả tương ứng.
5. THE License_Platform SHALL ghi nhận thời điểm xác thực cuối cùng (`last_verified_at`) cho mỗi Activation sau mỗi lần xác thực thành công.
6. IF License_Key có License_Status là `suspended`, THEN THE License_Platform SHALL từ chối yêu cầu xác thực online và trả về mã lỗi `LICENSE_SUSPENDED`, bất kể trạng thái Activation hay Expiry_Date.

---

### Yêu Cầu 6: Xác Thực License Key Offline (Hybrid)

**User Story:** Là API_Client, tôi muốn xác thực License_Key cục bộ khi không có kết nối mạng, để phần mềm vẫn hoạt động được trong môi trường offline sau khi đã kích hoạt.

#### Tiêu Chí Chấp Nhận

1. WHEN Activation thành công, THE License_Platform SHALL tạo Offline_Token là một JWT được ký bằng RS256 với private key của hệ thống, chứa các claims bắt buộc: `iss` (issuer), `aud` (product slug), `sub` (key_hash), `jti` (unique token ID), `iat`, `nbf`, `exp` (= thời điểm cấp + Offline_Token_TTL), `device_fp_hash`, `license_model`, `license_expiry`.
2. THE License_Platform SHALL cung cấp public key (PEM format) qua endpoint `GET /api/v1/public-key` để SDK xác thực chữ ký Offline_Token mà không cần gọi API validate.
3. WHEN SDK xác thực Offline_Token cục bộ, THE SDK SHALL verify chữ ký với `alg=RS256` cố định (không tin `alg` trong header), kiểm tra `exp`, `nbf`, `iss` (= `license-platform`), `aud` (= product slug), và sự khớp giữa device fingerprint hiện tại với `device_fp_hash` trong token; SDK phải từ chối token có `nbf` trong tương lai quá xa so với `iat` (> 5 phút), hoặc `exp - iat` vượt quá `Offline_Token_TTL` tối đa của product.
4. IF Offline_Token có chữ ký không hợp lệ hoặc bất kỳ claim nào không hợp lệ, THEN THE SDK SHALL từ chối xác thực và trả về lỗi `INVALID_TOKEN`.
5. THE License_Platform SHALL cho phép Admin cấu hình `Offline_Token_TTL` theo từng Product (mặc định 24h, tối thiểu 1h, tối đa 7 ngày).
6. WHEN SDK có kết nối mạng, THE SDK SHALL tự động gọi API validate online để làm mới Offline_Token trước khi token hết hạn (khuyến nghị refresh khi còn 20% TTL); SDK phải validate token mỗi lần app start và trước mỗi action quan trọng.
7. WHEN License_Key bị suspend hoặc revoke trên server, THE License_Platform SHALL đánh dấu tất cả `jti` của Offline_Token đã cấp là vô hiệu; SDK sẽ nhận trạng thái này khi thực hiện refresh online tiếp theo; độ trễ tối đa bằng Offline_Token_TTL còn lại của token hiện tại.
8. THE License_Platform SHALL cung cấp public key qua `GET /api/v1/public-key`; SDK nên fetch và cache public key thay vì hardcode, để hỗ trợ key rotation trong tương lai.

---

### Yêu Cầu 7: Quản Lý Heartbeat cho Floating License

**User Story:** Là API_Client, tôi muốn duy trì trạng thái sử dụng Floating_License qua heartbeat, để hệ thống biết thiết bị nào đang thực sự sử dụng và giải phóng Seat khi không còn dùng.

#### Tiêu Chí Chấp Nhận

1. WHEN API_Client đang sử dụng Floating_License, THE SDK SHALL gửi Heartbeat lên License_Platform theo chu kỳ không quá 5 phút một lần.
2. WHEN License_Platform nhận được Heartbeat hợp lệ từ một Seat đang hoạt động, THE License_Platform SHALL cập nhật thời điểm heartbeat cuối cùng (`last_heartbeat_at`) cho Seat đó.
3. WHILE một Seat không gửi Heartbeat trong khoảng thời gian vượt quá 10 phút, THE License_Platform SHALL tự động giải phóng Seat đó và cập nhật số lượng Seat đang sử dụng.
4. WHEN API_Client chủ động đóng ứng dụng, THE SDK SHALL gửi yêu cầu giải phóng Seat (check-out) lên License_Platform trước khi thoát.
5. IF API_Client gửi Heartbeat với Device_Fingerprint không khớp với bất kỳ Seat nào đang hoạt động, THEN THE License_Platform SHALL trả về mã lỗi `SEAT_NOT_FOUND`.

---

### Yêu Cầu 8: Quản Lý Activation

**User Story:** Là Admin, tôi muốn xem và quản lý danh sách Activation của từng License_Key, để theo dõi thiết bị nào đang sử dụng license và có thể thu hồi quyền truy cập của từng thiết bị.

#### Tiêu Chí Chấp Nhận

1. THE License_Platform SHALL hiển thị danh sách Activation của một License_Key bao gồm: Device_Fingerprint hoặc `user_identifier`, thời điểm kích hoạt, thời điểm xác thực cuối cùng, và trạng thái Activation.
2. WHEN Admin thu hồi một Activation cụ thể của Per_Device_License hoặc Per_User_License, THE License_Platform SHALL vô hiệu hóa Activation đó và chuyển License_Status về `inactive` để cho phép kích hoạt lại trên thiết bị hoặc tài khoản khác.
3. WHEN Admin thu hồi một Seat cụ thể của Floating_License, THE License_Platform SHALL giải phóng Seat đó và cập nhật số lượng Seat đang sử dụng.
4. THE License_Platform SHALL ghi Audit_Log cho mỗi thao tác thu hồi Activation, bao gồm: Admin thực hiện, License_Key liên quan, Device_Fingerprint hoặc `user_identifier`, và thời điểm thu hồi.

---

### Yêu Cầu 9: REST API cho Client Tích Hợp

**User Story:** Là API_Client, tôi muốn có các REST API endpoint rõ ràng và bảo mật, để tích hợp License_Platform vào ứng dụng của mình một cách dễ dàng.

#### Tiêu Chí Chấp Nhận

1. THE License_Platform SHALL cung cấp các REST API endpoint sau cho API_Client:
   - `POST /api/v1/licenses/activate` — kích hoạt License_Key
   - `POST /api/v1/licenses/validate` — xác thực License_Key online
   - `POST /api/v1/licenses/deactivate` — hủy kích hoạt (check-out) License_Key
   - `POST /api/v1/licenses/heartbeat` — gửi Heartbeat cho Floating_License
   - `GET /api/v1/licenses/info` — lấy thông tin License_Key, không cập nhật `last_verified_at`
   - `POST /api/v1/licenses/transfer` — kích hoạt lại trên thiết bị mới sau khi admin thu hồi activation cũ
   - `GET /api/v1/public-key` — lấy public key PEM để SDK verify Offline_Token cục bộ (không cần `X-API-Key`)
2. THE License_Platform SHALL xác thực mọi yêu cầu API (trừ `GET /api/v1/public-key`) bằng API key của Product, được truyền qua HTTP header `X-API-Key`.
3. IF API_Client gửi yêu cầu với API key không hợp lệ hoặc thiếu, THEN THE License_Platform SHALL trả về mã HTTP 401 và mã lỗi `UNAUTHORIZED`.
4. THE License_Platform SHALL trả về phản hồi API theo định dạng JSON chuẩn với cấu trúc: `{ "success": boolean, "data": object|null, "error": { "code": string, "message": string } | null }`.
5. THE License_Platform SHALL áp dụng rate limiting theo `X-API-Key` (không theo IP), dùng Redis làm limiter store, giới hạn tối đa 60 yêu cầu mỗi phút cho mỗi API key.
6. IF API_Client vượt quá giới hạn rate limiting, THEN THE License_Platform SHALL trả về mã HTTP 429 và thông tin thời gian chờ trong header `Retry-After`.
7. THE License_Platform SHALL hỗ trợ HTTPS cho tất cả API endpoint.
8. THE License_Platform SHALL đảm bảo idempotency cho endpoint `POST /api/v1/licenses/activate`: nếu cùng License_Key và Device_Fingerprint đã được activate thành công trước đó, SHALL trả về Offline_Token hiện tại thay vì tạo bản ghi mới.
9. THE License_Platform SHALL xử lý concurrency cho activation và seat allocation bằng DB transaction kết hợp unique constraint, đảm bảo không cấp trùng seat hoặc tạo activation race condition khi nhiều request đến đồng thời.
10. IF API_Client gọi `POST /api/v1/licenses/transfer` với License_Key không ở trạng thái `inactive`, THEN THE License_Platform SHALL từ chối yêu cầu và trả về mã lỗi `TRANSFER_NOT_ALLOWED` kèm thông báo yêu cầu Admin thu hồi Activation hiện tại trước.

---

### Yêu Cầu 10: Bảng Điều Khiển Admin

**User Story:** Là Admin, tôi muốn có một bảng điều khiển web trực quan, để quản lý toàn bộ hệ thống license mà không cần thao tác trực tiếp với database.

#### Tiêu Chí Chấp Nhận

1. THE License_Platform SHALL cung cấp giao diện web bảng điều khiển chỉ dành cho Admin, được bảo vệ bằng xác thực username và password.
2. THE License_Platform SHALL hiển thị trang tổng quan (dashboard) với các số liệu: tổng số License_Key theo từng trạng thái, tổng số Product, số lượng Activation trong 24 giờ qua, và số lượng xác thực thất bại trong 24 giờ qua.
3. THE License_Platform SHALL cho phép Admin tìm kiếm License_Key theo: chuỗi key, Product, License_Model, License_Status, và khoảng thời gian tạo.
4. THE License_Platform SHALL cho phép Admin xuất danh sách License_Key ra file CSV với các trường: chuỗi key, Product, License_Model, License_Status, Expiry_Date, ngày tạo.
5. WHEN Admin đăng nhập thất bại 5 lần liên tiếp trong vòng 15 phút, THE License_Platform SHALL khóa tài khoản Admin trong 15 phút và ghi Audit_Log sự kiện này.
6. THE License_Platform SHALL hiển thị trên trang dashboard các biểu đồ thống kê: biểu đồ số lượng Activation theo ngày/tuần/tháng (có thể chuyển đổi khoảng thời gian), và danh sách top 5 Product có nhiều Activation nhất trong 30 ngày gần nhất.
7. THE License_Platform SHALL cho phép Admin lọc dữ liệu biểu đồ theo Product cụ thể hoặc xem tổng hợp toàn hệ thống.

---

### Yêu Cầu 11: Audit Log và Giám Sát

**User Story:** Là Admin, tôi muốn xem lịch sử đầy đủ các hành động trong hệ thống, để kiểm tra và điều tra khi có sự cố.

#### Tiêu Chí Chấp Nhận

1. THE License_Platform SHALL ghi Audit_Log cho các sự kiện: tạo/sửa/xóa Product, tạo/thu hồi/tạm khóa/gia hạn License_Key, Activation thành công/thất bại, xác thực thất bại liên tiếp, và đăng nhập Admin.
2. THE License_Platform SHALL lưu trữ mỗi bản ghi Audit_Log với các thông tin: loại sự kiện, đối tượng liên quan (License_Key hoặc Product), địa chỉ IP, thời điểm xảy ra, và kết quả (thành công/thất bại).
3. THE License_Platform SHALL cho phép Admin lọc Audit_Log theo: loại sự kiện, khoảng thời gian, và địa chỉ IP.
4. THE License_Platform SHALL lưu trữ Audit_Log trong tối thiểu 365 ngày.
5. WHEN cùng một License_Key bị xác thực thất bại từ cùng một địa chỉ IP hơn 10 lần trong vòng 1 giờ, THE License_Platform SHALL ghi nhận sự kiện cảnh báo vào Audit_Log với mức độ `warning`.

---

### Yêu Cầu 12: SDK và Hướng Dẫn Tích Hợp

**User Story:** Là Developer, tôi muốn có SDK và tài liệu hướng dẫn tích hợp rõ ràng, để tích hợp License_Platform vào ứng dụng của mình trên các nền tảng khác nhau một cách nhanh chóng.

#### Tiêu Chí Chấp Nhận

1. THE License_Platform SHALL cung cấp tài liệu hướng dẫn tích hợp (integration guide) dạng Markdown cho từng nền tảng: Node.js, Electron, React, và Python.
2. Mỗi tài liệu hướng dẫn SHALL bao gồm ví dụ code hoàn chỉnh (copy-paste ready) cho từng bước trong flow: activate → validate → deactivate, và cho các endpoint bổ sung: heartbeat (Floating), info, transfer.
3. THE License_Platform SHALL cung cấp mô tả đầy đủ cho tất cả API endpoint bao gồm: URL, method, headers bắt buộc, request body schema, response schema, và danh sách mã lỗi có thể trả về.
4. THE License_Platform SHALL cung cấp hướng dẫn tạo Device_Fingerprint cho từng nền tảng: sử dụng thông tin phần cứng ổn định (CPU ID, MAC address, hostname) cho Node.js/Electron, và browser fingerprint cho React/Web; fingerprint phải dùng version schema (ví dụ: `fp_v1:sha256(os+cpu+mac_or_hostname)`) để tránh minor change làm mất license; không dùng các field thay đổi thường xuyên.
5. THE License_Platform SHALL cung cấp hướng dẫn xử lý offline mode bao gồm: nơi lưu trữ Offline_Token an toàn (encrypted keychain/secure storage cho Electron, file mã hóa cho Node.js), cách xác thực local, cách fetch và cache public key từ `GET /api/v1/public-key` thay vì hardcode, và cách xử lý khi token hết hạn hoặc bị revoke khi online trở lại.
6. THE License_Platform SHALL cung cấp API key riêng cho từng Product để API_Client sử dụng khi gọi API, tách biệt với thông tin đăng nhập Admin.

---

### Yêu Cầu 13: Bảo Mật Hệ Thống

**User Story:** Là Admin, tôi muốn hệ thống có các biện pháp bảo mật đầy đủ, để bảo vệ License_Key và dữ liệu khỏi bị giả mạo hoặc lạm dụng.

#### Tiêu Chí Chấp Nhận

1. THE License_Platform SHALL lưu trữ License_Key dưới dạng `key_hash` (SHA-256) và `key_last4` (4 ký tự cuối plaintext); chỉ hiển thị giá trị plaintext đầy đủ một lần duy nhất tại thời điểm tạo.
2. THE License_Platform SHALL ký Offline_Token bằng thuật toán RS256 với cặp khóa bất đối xứng; private key lưu an toàn trên server; public key phân phối qua API endpoint.
3. THE License_Platform SHALL lưu trữ Device_Fingerprint dưới dạng hash SHA-256 trong database, không lưu giá trị gốc.
4. THE License_Platform SHALL áp dụng HTTPS bắt buộc cho toàn bộ giao diện web và API endpoint.
5. IF một License_Key bị phát hiện đang được sử dụng từ nhiều hơn số lượng thiết bị cho phép theo License_Model, THEN THE License_Platform SHALL ghi nhận sự kiện cảnh báo vào Audit_Log; tùy cấu hình Product, hệ thống có thể: (a) chỉ flag để Admin xem và quyết định, hoặc (b) tự động suspend License_Key.
6. THE License_Platform SHALL bảo vệ giao diện Admin bằng CSRF token cho tất cả các form thao tác dữ liệu.
7. THE License_Platform SHALL áp dụng soft delete cho Product và License_Key; dữ liệu bị xóa vẫn được giữ trong DB và có thể khôi phục bởi Admin.

---

## Yêu Cầu Phi Chức Năng

### NFR-1: Hiệu Năng

- API validate (`POST /api/v1/licenses/validate`) phải trả về kết quả trong vòng 500ms ở điều kiện tải bình thường (≤ 100 req/s, DB có index đầy đủ, Redis cache hoạt động).
- Admin dashboard phải tải trang chính trong vòng 2 giây ở điều kiện bình thường.

### NFR-2: Độ Tin Cậy

- Hệ thống phải xử lý đúng concurrent activation bằng DB transaction + unique constraint, không để race condition tạo trùng seat hoặc activation.
- Per-device/per-user activation: unique composite index `(license_id, device_fp_hash)` và `(license_id, user_identifier)`; khi catch duplicate key error thì select lại và trả activation hiện tại (idempotency).
- Floating seat: unique `(license_id, device_fp_hash)` trên bảng `floating_seats`; dùng `SELECT FOR UPDATE` hoặc count trong transaction trước khi insert.
- Heartbeat timeout (10 phút) phải được xử lý bằng scheduled job, không phụ thuộc vào request của client.

### NFR-3: Lưu Trữ & Backup

- Audit_Log lưu tối thiểu 365 ngày; sau 365 ngày có thể archive sang cold storage (S3/Glacier hoặc tương đương).
- Soft delete áp dụng cho Product và License_Key; không hard delete dữ liệu production.
- Toàn bộ timestamp lưu và trả về theo UTC.

### NFR-4: Bảo Mật

- Tất cả secret (private key, API key) không được commit vào source code, phải dùng environment variable.
- Admin login bị khóa 15 phút sau 5 lần thất bại liên tiếp trong 15 phút.
- Rate limiting theo `X-API-Key` dùng Redis store, ổn định ở môi trường nhiều instance.

### NFR-5: Khả Năng Bảo Trì

- License_Status transition phải đi qua State Machine (state classes, ví dụ: `spatie/laravel-model-states`), không update DB trực tiếp trong controller; mỗi transition hook phải: cập nhật activation/seat liên quan, ghi Audit_Log.
- DB phải có index trên: `licenses.key_hash`, `licenses.status`, `activations.license_id`, `activations.device_fp_hash`, `floating_seats.license_id + last_heartbeat_at`.

### NFR-6: Observability

- Hệ thống phải ghi log/metrics cho các sự kiện: `RATE_LIMIT_EXCEEDED`, `LICENSE_EXPIRED`, `LICENSE_SUSPENDED`, `LICENSE_REVOKED`, activation thất bại — để hỗ trợ monitoring (Prometheus/Grafana hoặc tool tương đương).
- Mỗi API response phải bao gồm request ID trong header `X-Request-ID` để trace lỗi.

---

## Error Catalog

Danh sách mã lỗi chuẩn hóa cho toàn bộ hệ thống:

| Mã lỗi                   | HTTP | Mô tả                                                                    |
| ------------------------ | ---- | ------------------------------------------------------------------------ |
| `UNAUTHORIZED`           | 401  | API key thiếu hoặc không hợp lệ                                          |
| `RATE_LIMIT_EXCEEDED`    | 429  | Vượt quá giới hạn request (kèm header `Retry-After`)                     |
| `LICENSE_NOT_FOUND`      | 404  | License Key không tồn tại                                                |
| `LICENSE_INACTIVE`       | 422  | License Key chưa được kích hoạt                                          |
| `LICENSE_EXPIRED`        | 422  | License Key đã hết hạn                                                   |
| `LICENSE_REVOKED`        | 422  | License Key đã bị thu hồi vĩnh viễn                                      |
| `LICENSE_SUSPENDED`      | 422  | License Key đang bị tạm khóa                                             |
| `PRODUCT_INACTIVE`       | 422  | Sản phẩm đang bị tắt, không nhận activation mới                          |
| `DEVICE_MISMATCH`        | 422  | Device Fingerprint không khớp với activation đã đăng ký                  |
| `USER_MISMATCH`          | 422  | User identifier không khớp với activation đã đăng ký                     |
| `SEATS_EXHAUSTED`        | 422  | Floating license đã hết slot đồng thời                                   |
| `SEAT_NOT_FOUND`         | 404  | Không tìm thấy seat đang hoạt động cho device này                        |
| `TRANSFER_NOT_ALLOWED`   | 422  | License Key không ở trạng thái `inactive`, không thể transfer            |
| `INVALID_TOKEN`          | 422  | Offline Token không hợp lệ (chữ ký sai, claim lỗi, hết hạn)              |
| `TRANSITION_NOT_ALLOWED` | 422  | Chuyển đổi trạng thái không hợp lệ theo state machine                    |
| `EXCEEDED_USAGE`         | 422  | License Key đang được dùng vượt quá giới hạn thiết bị cho phép           |
| `VALIDATION_ERROR`       | 422  | Dữ liệu đầu vào không hợp lệ; kèm `error.details: { field: [messages] }` |
| `INTERNAL_ERROR`         | 500  | Lỗi server nội bộ                                                        |

**Cấu trúc response lỗi chuẩn:**

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Dữ liệu đầu vào không hợp lệ",
    "details": {
      "email": ["Email không đúng định dạng"],
      "license_model": [
        "Giá trị không hợp lệ, phải là per-device, per-user, hoặc floating"
      ]
    }
  }
}
```

`error.details` chỉ có mặt khi `code = VALIDATION_ERROR`; các lỗi khác không có `details`.
