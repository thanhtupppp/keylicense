# Roadmap Missing Items Checklist

Tài liệu này dùng để theo dõi các hạng mục còn thiếu so với `docs/license-platform-technical-design.md`.

## Cột theo dõi

- **Status**: `done` | `partial` | `missing` | `unknown`
- **Owner**: người/nhóm phụ trách
- **Priority**: `P0` | `P1` | `P2` | `P3`
- **ETA**: ước lượng hoàn thành

## Sprint checklist

### Sprint 1 — Foundation

| Item                               | Missing work                                                      |  Status | Owner    | Priority | ETA      |
| ---------------------------------- | ----------------------------------------------------------------- | ------: | -------- | -------- | -------- |
| Repo / CI / env / logging baseline | Xác nhận toàn bộ pipeline, env mẫu, logging/error handler đồng bộ | partial | Platform | P0       | 1–2 tuần |
| Admin auth                         | Hoàn thiện luồng login, token/session, lockout, MFA handoff       | partial | Backend  | P0       | 1 tuần   |
| X-API-Key client auth              | Chuẩn hóa client key validation / rotation / middleware           | partial | Backend  | P0       | 1 tuần   |
| Health API                         | Hoàn tất smoke test cho `/health`, `/status`, `/version`          |    done | Backend  | P2       | done     |
| API key management                 | Bổ sung issue/revoke/rotate API key đầy đủ                        | partial | Backend  | P1       | 1–2 tuần |

### Sprint 2 — Catalog / Entitlement / License / Activation

| Item                           | Missing work                                               |  Status | Owner   | Priority | ETA      |
| ------------------------------ | ---------------------------------------------------------- | ------: | ------- | -------- | -------- |
| Product / Plan / Feature CRUD  | Đảm bảo CRUD đủ validation + policy + tests                | partial | Backend | P0       | 1–2 tuần |
| Customer / Order / Entitlement | Hoàn thiện create/update/revoke flow và liên kết dữ liệu   | partial | Backend | P0       | 1–2 tuần |
| License issue flow             | Hoàn tất issue key từ entitlement + audit + edge cases     | partial | Backend | P0       | 1 tuần   |
| Activation flow                | Hoàn thiện validate / heartbeat / deactivate state machine | partial | Backend | P0       | 1–2 tuần |
| Admin Portal v1                | Bổ sung list/search/issue activation admin endpoints       | partial | Backend | P1       | 1–2 tuần |
| SDK PHP v1                     | Đóng gói client activate/validate + error handling         | partial | SDK     | P1       | 1–2 tuần |

### Sprint 3 — Admin Portal v1 / SDK v1

| Item                    | Missing work                                   |  Status | Owner   | Priority | ETA      |
| ----------------------- | ---------------------------------------------- | ------: | ------- | -------- | -------- |
| Admin product list      | Filter/search/sort + pagination chuẩn          | partial | Backend | P1       | 1 tuần   |
| Issue key UI/API        | Đồng bộ response contract + audit              | partial | Backend | P1       | 1 tuần   |
| View activation history | Endpoint/filter cho activation history         | partial | Backend | P1       | 1 tuần   |
| SDK packaging           | Tách support layer thành package release-ready | partial | SDK     | P2       | 1–2 tuần |

### Sprint 5 — Activation / Notification / Email Verify / Notif Prefs

| Item               | Missing work                                           |  Status | Owner            | Priority | ETA      |
| ------------------ | ------------------------------------------------------ | ------: | ---------------- | -------- | -------- |
| Heartbeat          | Xác nhận heartbeat update last-active / cache / expiry | partial | Backend          | P0       | 1 tuần   |
| Deactivate         | Thêm endpoint/state cleanup đầy đủ                     | partial | Backend          | P0       | 1 tuần   |
| Notification email | Email issue/expiring/revoke còn thiếu luồng gửi đầy đủ | partial | Backend          | P1       | 1–2 tuần |
| Email verification | Hoàn thiện resend/verify/onboarding checklist          | partial | Customer Backend | P1       | 1–2 tuần |
| Notification prefs | Sync opt-in/out + unsubscribe token                    | partial | Customer Backend | P1       | 1 tuần   |

### Sprint 6 — Governance / License lifecycle

| Item                         | Missing work                                       |  Status | Owner   | Priority | ETA      |
| ---------------------------- | -------------------------------------------------- | ------: | ------- | -------- | -------- |
| RBAC đầy đủ                  | Custom roles/permissions/scoped access per product | partial | Backend | P0       | 2 tuần   |
| Revoke / suspend / unsuspend | Bổ sung state machine và guardrails                | partial | Backend | P0       | 1–2 tuần |
| Extend expiry                | Endpoint + audit + validation                      | partial | Backend | P1       | 1 tuần   |
| Audit trail access           | Quyền đọc audit logs theo scope                    | partial | Backend | P1       | 1 tuần   |

### Sprint 7 — Update check / Environment / Admin Portal v2

| Item                   | Missing work                                             |  Status | Owner         | Priority | ETA      |
| ---------------------- | -------------------------------------------------------- | ------: | ------------- | -------- | -------- |
| Update check           | Chưa thấy endpoint/flow đầy đủ                           | missing | SDK / Backend | P1       | 2 tuần   |
| Environment separation | Hoàn thiện environments table + policy + rate multiplier | partial | Backend       | P1       | 1–2 tuần |
| Admin dashboard v2     | Dashboard/search/filter/revoke/suspend UX                | missing | Frontend      | P2       | 2–3 tuần |

### Sprint 8 — Reports / SDK PHP v2

| Item              | Missing work                                      |  Status | Owner   | Priority | ETA      |
| ----------------- | ------------------------------------------------- | ------: | ------- | -------- | -------- |
| Expiring report   | Báo cáo keys sắp hết hạn                          | partial | Backend | P1       | 1 tuần   |
| Activation report | Báo cáo activation theo product/date/IP           | partial | Backend | P1       | 1 tuần   |
| CSV export        | Export CSV cho reports                            | missing | Backend | P2       | 1 tuần   |
| SDK PHP v2        | Heartbeat/deactivate/update_check + error mapping | partial | SDK     | P1       | 1–2 tuần |

### Sprint 9 — Offline activation / Grace period

| Item                    | Missing work                        |  Status | Owner         | Priority | ETA      |
| ----------------------- | ----------------------------------- | ------: | ------------- | -------- | -------- |
| Offline request/confirm | Tạo flow challenge-response Ed25519 | missing | Backend / SDK | P0       | 2–3 tuần |
| Grace period job        | Cron chuyển grace → expired tự động | missing | Backend       | P1       | 1 tuần   |

### Sprint 10 — Billing integration / Refund / Coupon / Renewal

| Item                   | Missing work                                |  Status | Owner           | Priority | ETA      |
| ---------------------- | ------------------------------------------- | ------: | --------------- | -------- | -------- |
| Renewal flow           | Billing event → extend entitlement → notify | partial | Billing Backend | P0       | 1–2 tuần |
| Stripe/Paddle webhooks | Chuẩn hóa adapter + retry/idempotency       | partial | Billing Backend | P0       | 1–2 tuần |
| Refund & chargeback    | Auto-revoke flow / webhook handling         | partial | Billing Backend | P1       | 1–2 tuần |
| Coupon / discount      | Coupon tables + validate/apply API          | missing | Billing Backend | P2       | 2 tuần   |

### Sprint 11 — Bulk operations / Multi-currency / Abuse / Outbound webhook

| Item              | Missing work                                  |  Status | Owner              | Priority | ETA      |
| ----------------- | --------------------------------------------- | ------: | ------------------ | -------- | -------- |
| Bulk jobs         | Issue/revoke/export/import async jobs         | missing | Backend            | P2       | 2–3 tuần |
| Multi-currency    | Hoàn thiện plan_pricing + currency resolution | partial | Billing Backend    | P1       | 1 tuần   |
| Abuse detection   | Rule engine + alerting                        | partial | Security / Backend | P1       | 1–2 tuần |
| Outbound webhooks | Delivery + retry + dead-letter tracking       | partial | Backend            | P1       | 1–2 tuần |

### Sprint 12 — Analytics / Performance

| Item               | Missing work                                   |  Status | Owner       | Priority | ETA      |
| ------------------ | ---------------------------------------------- | ------: | ----------- | -------- | -------- |
| Usage dashboard    | Churn / active activations / expiring pipeline | partial | Backend     | P1       | 1–2 tuần |
| Redis cache policy | Cache hot data + invalidate theo event         | partial | Backend     | P1       | 1 tuần   |
| Load test          | Benchmark và chốt throughput target            | missing | QA / DevOps | P2       | 1 tuần   |

### Sprint 13 — Customer portal / Customer Auth

| Item            | Missing work                             |  Status | Owner    | Priority | ETA      |
| --------------- | ---------------------------------------- | ------: | -------- | -------- | -------- |
| Customer portal | Self-service xem key/deactivate/download | missing | Frontend | P1       | 2–3 tuần |
| Customer auth   | Login/register/OAuth/MFA + sessions      | partial | Backend  | P0       | 2 tuần   |

### Sprint 14 — Invoice & Billing / Reseller portal

| Item            | Missing work                              |  Status | Owner           | Priority | ETA      |
| --------------- | ----------------------------------------- | ------: | --------------- | -------- | -------- |
| Invoice PDF     | PDF generation + download history         | partial | Billing Backend | P1       | 1–2 tuần |
| Billing history | Customer invoice history endpoint/UI      | partial | Billing Backend | P1       | 1 tuần   |
| Reseller portal | Bulk key/distribution/activation tracking | partial | Backend         | P1       | 2 tuần   |

### Sprint 15 — License Transfer / Metered licensing

| Item              | Missing work                             |  Status | Owner           | Priority | ETA    |
| ----------------- | ---------------------------------------- | ------: | --------------- | -------- | ------ |
| License transfer  | transfer flow + auto-revoke activations  | missing | Backend         | P1       | 2 tuần |
| Metered licensing | Usage-based billable model + aggregation | partial | Billing Backend | P1       | 2 tuần |

### Sprint 16 — Advanced RBAC

| Item                | Missing work                      |  Status | Owner   | Priority | ETA      |
| ------------------- | --------------------------------- | ------: | ------- | -------- | -------- |
| Custom roles        | Dynamic roles / permissions model | missing | Backend | P2       | 2–3 tuần |
| Cross-product admin | Scope theo product/org            | missing | Backend | P2       | 2 tuần   |

### Sprint 17 — GDPR / Security hardening

| Item                   | Missing work                    |  Status | Owner             | Priority | ETA      |
| ---------------------- | ------------------------------- | ------: | ----------------- | -------- | -------- |
| Data requests          | Export/erasure/portability flow | missing | Backend           | P1       | 2 tuần   |
| Retention policies     | Data retention automation       | missing | Backend           | P1       | 1–2 tuần |
| Audit tamper detection | Detect/log tampering            | partial | Security          | P1       | 1–2 tuần |
| KMS key rotation       | Rotation workflow & runbook     | missing | DevOps / Security | P2       | 2 tuần   |

### Sprint 18 — SLA & Observability

| Item               | Missing work                  |  Status | Owner  | Priority | ETA      |
| ------------------ | ----------------------------- | ------: | ------ | -------- | -------- |
| Alerting / tracing | SLO, traces, alerts, runbooks | partial | DevOps | P1       | 1–2 tuần |
| DR drill           | Disaster recovery test        | missing | DevOps | P2       | 1 tuần   |

## High-priority next actions

1. Hoàn thiện `AdminSessionController` / `MfaController` response contract và test tương ứng.
2. Chốt luồng activation/deactivate/heartbeat để ổn định Phase 2.
3. Bổ sung offline activation và grace-period job cho Phase 3.
4. Mở rộng customer portal/auth sau khi admin flows đã ổn.

## Ghi chú trạng thái hiện tại

- `done`: chỉ áp dụng khi endpoint/flow đã có, response ổn, và test cover tối thiểu.
- `partial`: đã có khung hoặc một phần behavior, nhưng chưa khép kín.
- `missing`: chưa thấy evidence đủ rõ trong repo hiện tại.
- `unknown`: chưa kiểm chứng hết, cần đối chiếu sâu hơn bằng test/code search.

---

_Cập nhật lần cuối: 2026-04-16_
