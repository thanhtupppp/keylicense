Checklist missing items theo sprint
Sprint 1 — Infrastructure, Auth, Health, API Key Mgmt
Infrastructure

- [x] Chốt bộ migration nền tảng còn thiếu

- [x] Rà lại index/foreign key cho các bảng core

- [x] Đồng bộ .env.example, config, queue, cache theo chuẩn production

- [x] Chuẩn hóa error handler/logging structure
      Auth

- [x] Hoàn thiện flow admin login end-to-end

- [x] Đồng bộ MFA challenge/setup/disable trong controller + service

- [x] Bổ sung lockout/failed attempts nhất quán

- [x] Chuẩn hóa payload login/session giữa controller và service
      API Key Mgmt

- [x] Hoàn thiện issue/revoke/rotate API key

- [x] Thêm audit log cho mọi thao tác write

- [x] Rà lại authorization cho từng role
      Sprint 2 — Catalog, Entitlement, License, Activation
      Catalog

- [x] Hoàn thiện CRUD đầy đủ cho Product

- [x] Hoàn thiện CRUD đầy đủ cho Plan

- [x] Hoàn thiện ProductVersion

- [x] Hoàn thiện Feature / PlanFeature
      Entitlement

- [x] Hoàn thiện tạo entitlement từ order/customer

- [x] Rà lại trạng thái entitlement và rule chuyển trạng thái

- [x] Bổ sung query/filter/listing cho admin
      License

- [x] Hoàn thiện issue license từ entitlement

- [x] Hoàn thiện revoke/suspend/unsuspend/extend

- [x] Chuẩn hóa response structure và error codes
      Activation

- [x] Hoàn thiện activate flow

- [x] Hoàn thiện validate flow

- [x] Bổ sung heartbeat

- [x] Hoàn thiện deactivate

- [x] Rà lại state machine activation
      Sprint 3 — Admin Portal v1, SDK PHP v1
      Admin Portal v1

- [x] Hoàn thiện list/search/filter product

- [x] Hoàn thiện issue key từ portal

- [x] Hoàn thiện xem activation history

- [x] Đồng bộ quyền truy cập theo role
      SDK PHP v1

- [x] Hoàn thiện HTTP client layer

- [x] Bổ sung retry/timeout/error mapping

- [x] Hoàn thiện DTO response mapping

- [x] Thêm test cho các endpoint cốt lõi
      Sprint 4 — Foundation hardening / Admin portal completion
      Missing items

- [x] Rà toàn bộ route/admin permission mapping

- [ ] Rà lại audit log consistency

- [x] Bổ sung test coverage cho login/session/license core

- [x] Dọn cleanup các payload còn chưa nhất quán
      Sprint 5 — Activation, Notification, Email Verify, Notification Prefs
      Activation

- [x] Hoàn thiện heartbeat job/handler

- [x] Hoàn thiện deactivate endpoint

- [x] Rà lại trạng thái expired/revoked/suspended

- [x] Bổ sung edge cases cho activation state machine
      Notification

- [x] Tạo flow email gửi key sau issue

- [x] Tạo cảnh báo expiring soon

- [x] Tạo revoke notice email

- [x] Bổ sung retry/failure handling cho mail
      Email Verify

- [x] Hoàn thiện verify email flow

- [x] Hoàn thiện resend verification

- [x] Rà lại onboarding checklist
      Notification Prefs

- [x] Hoàn thiện opt-in/out

- [x] Hoàn thiện unsubscribe token

- [x] Đồng bộ controller/service/test
      Sprint 6 — Governance, License lifecycle
      Governance

- [x] Hoàn thiện RBAC đầy đủ

- [x] Bổ sung scoped access per product

- [x] Hoàn thiện policy/gate cho admin actions

- [x] Làm rõ role matrix và permission matrix
      License lifecycle

- [x] Hoàn thiện revoke

- [x] Hoàn thiện suspend

- [x] Hoàn thiện unsuspend

- [x] Hoàn thiện extend expiry

- [x] Bổ sung test cho từng state transition
      Sprint 7 — Update check, Environment, Admin Portal v2
      Update check

- [x] Tạo endpoint /updates/check

- [x] Link ProductVersion với entitlement

- [x] Bổ sung policy response cho update availability

- [x] Thêm test cho compatibility/version rule
      Environment

- [x] Hoàn thiện environments table

- [x] Tách staging/dev/prod key separation rõ ràng

- [x] Bổ sung rate limit multiplier theo environment
      Admin Portal v2

- [x] Hoàn thiện dashboard tổng quan

- [x] Hoàn thiện filter/search

- [x] Hoàn thiện revoke/suspend UI flow

- [x] Rà lại API trả về cho frontend
      Sprint 8 — Reports, SDK PHP v2
      Reports

- [x] Hoàn thiện expiring report

- [x] Hoàn thiện activation report

- [x] Export CSV

- [x] Bổ sung filter by product/date/status
      SDK PHP v2

- [x] Thêm heartbeat client

- [x] Thêm deactivate client

- [x] Thêm update_check client

- [x] Hoàn thiện error handling chuẩn hoá
      Sprint 9 — Offline activation, Grace period
      Offline activation

- [x] Tạo /offline/request

- [x] Tạo /offline/confirm

- [x] Implement challenge-response Ed25519

- [x] One-time-use challenge

- [x] Expiry handling cho challenge
      Grace period

- [x] Tạo cron/job cập nhật grace → expired

- [x] Bổ sung tests cho chuyển trạng thái tự động
      Sprint 10 — Renewal, Billing integration, Refund & Chargeback, Coupon
      Renewal

- [ ] Hoàn thiện flow billing event → extend entitlement

- [ ] Tạo notify sau renewal

- [ ] Bổ sung idempotency cho event
      Billing integration

- [ ] Auto-create entitlement từ order.created

- [ ] Bổ sung webhook retry/error handling
      Refund & Chargeback

- [ ] Auto-revoke flow khi refund/chargeback

- [ ] Bổ sung audit log cho billing reversal
      Coupon & Discount

- [ ] Tạo coupons

- [ ] Tạo coupon_usages

- [ ] Tạo validate/apply API

- [ ] Bổ sung rule chống lạm dụng coupon
      Sprint 11 — Bulk ops, Multi-currency, Abuse detection, Webhook outbound
      Bulk operations

- [x] Tạo bulk_jobs

- [x] Issue/revoke/export/import hàng loạt

- [x] Async job tracking + progress + failure states
      Multi-currency

- [x] Hoàn thiện plan_pricing

- [x] Bổ sung currency resolution logic

- [x] Rà lại display/settlement currency
      Abuse detection

- [x] Rule engine nhiều IP

- [x] Rule engine nhiều quốc gia

- [x] Rule engine activation threshold

- [x] Alerting cho admin
      Webhook outbound

- [x] Config webhook theo product/org

- [x] Delivery retry strategy

- [x] Delivery status / dead-letter handling
      Sprint 12 — Analytics, Performance
      Analytics

- [ ] Usage dashboard

- [ ] Active activations

- [ ] Churn metrics

- [ ] Expiring pipeline

- [ ] Rà lại aggregation strategy
      Performance

- [ ] Cache entitlement/policy bằng Redis

- [ ] Tối ưu rate limiting

- [ ] Load test

- [ ] Review query/index bottlenecks
      Sprint 13 — Customer portal, Customer auth
      Customer portal

- [ ] Customer xem license

- [ ] Customer deactivate license

- [ ] Customer download key

- [ ] Route/controller cho portal permissions
      Customer auth

- [ ] Login

- [ ] Register

- [ ] OAuth

- [ ] MFA cho customer

- [ ] customer_sessions

- [ ] customer_oauth_providers
      Sprint 14 — Invoice & Billing, Reseller portal
      Invoice & Billing

- [ ] invoices / invoice_items

- [ ] billing_addresses

- [ ] PDF generation

- [ ] Billing history cho customer
      Reseller portal

- [ ] Bulk key operations

- [ ] Key distribution flow

- [ ] Activation tracking

- [ ] Reseller auth/permissions rõ ràng
      Sprint 15 — License Transfer, Metered licensing
      License Transfer

- [ ] license_transfers table

- [ ] Transfer flow

- [ ] Auto-revoke activations sau transfer

- [ ] Audit + rollback behavior
      Metered licensing

- [ ] Ghi nhận usage-based events

- [ ] Mapping API calls/seats → billing

- [ ] Bản tổng hợp usage theo kỳ
      Sprint 16 — Advanced RBAC

- [ ] Custom roles

- [ ] Cross-product admin scope

- [ ] Permission matrix động

- [ ] UI/API quản lý role

- [ ] Test quyền truy cập theo scope
      Sprint 17 — GDPR, Security hardening
      GDPR

- [ ] data_requests

- [ ] data_retention_policies

- [ ] Erasure flow

- [ ] Portability flow

- [ ] Anonymization strategy
      Security hardening

- [ ] Pentest checklist

- [ ] Audit log tamper detection

- [ ] Key rotation flow

- [ ] Security test coverage cho auth/session/license
      Sprint 18 — SLA & Observability

- [ ] Alerting chuẩn production

- [ ] Distributed tracing

- [ ] Runbook

- [ ] DR drill

- [ ] SLA/SLO metrics

- [ ] Incident response hooks
