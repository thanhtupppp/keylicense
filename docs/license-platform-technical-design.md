# Internal Multi-Product License Platform

## Technical Design Document — v2.0

**Document type:** Technical Design (Post-PRD)
**Version:** 2.1
**Status:** Draft
**Audience:** Engineering Lead, Backend, Frontend, DevOps, QA

---

## Table of Contents

1. [Capability-Based ERD](#1-capability-based-erd)
2. [State Machine — Entitlement, License, Activation](#2-state-machine)
3. [API Contract — Chi tiết](#3-api-contract)
4. [RBAC Matrix](#4-rbac-matrix)
5. [Integration Guideline cho Sản phẩm Nội bộ](#5-integration-guideline)
6. [Implementation Roadmap theo Module](#6-implementation-roadmap)
7. [Customer Authentication & Portal API](#7-customer-auth)
8. [API Key Management](#8-api-key-management)
9. [Coupon & Discount](#9-coupon-discount)
10. [Bulk Operations](#10-bulk-operations)
11. [License Transfer](#11-license-transfer)
12. [Refund & Chargeback](#12-refund-chargeback)
13. [Invoice & Billing History](#13-invoice-billing)
14. [Notification Preferences](#14-notification-preferences)
15. [Email Verification & Onboarding](#15-email-verification)
16. [Environment Separation](#16-environment-separation)
17. [Multi-currency Pricing](#17-multi-currency)
18. [Health & Status API](#18-health-status)
19. [GDPR & Data Retention](#19-gdpr-data-retention)
20. [Caching Strategy](#20-caching-strategy)
21. [Background Job & Queue Spec](#21-background-job--queue-spec)
22. [Database Migration Strategy](#22-database-migration-strategy)
23. [Webhook Signature Verification](#23-webhook-signature-verification)
24. [Metered / Usage-Based Licensing](#24-metered--usage-based-licensing)
25. [Reseller & Partner](#25-reseller--partner)
26. [IP Allowlist & Blocklist](#26-ip-allowlist--blocklist)
27. [SDK Specification](#27-sdk-specification)
28. [Admin MFA](#28-admin-mfa-two-factor-authentication)
29. [Metrics & Dashboard API](#29-metrics--dashboard-api)
30. [Notification Localization](#30-notification-localization)
31. [API Versioning & Deprecation Policy](#31-api-versioning--deprecation-policy)
32. [Event Stream API](#32-event-stream-api)
33. [Affiliate & Referral Program](#33-affiliate--referral-program)
34. [License Bundling](#34-license-bundling)
35. [White-label Support](#35-white-label-support)
36. [Compliance Export](#36-compliance-export)
37. [Observability Stack](#37-observability-stack)
38. [Security Headers & TLS Policy](#38-security-headers--tls-policy)
39. [Idempotency Keys — Admin API](#39-idempotency-keys--admin-api)
40. [Trial License Flow](#40-trial-license-flow)
41. [License Upgrade & Downgrade](#41-license-upgrade--downgrade)
42. [Dunning Management](#42-dunning-management)
43. [Admin Session Management](#43-admin-session-management)
44. [Platform Configuration](#44-platform-configuration)

---

## 1. Capability-Based ERD

### 1.1 Tổng quan Capability Domain

Hệ thống được tổ chức theo 6 capability domain:

```
┌──────────────────────────┬──────────────────────────┬──────────────────────────┐
│  CATALOG                 │  ENTITLEMENT             │  LICENSE                 │
│  ─────────────────────   │  ──────────────────────  │  ─────────────────────   │
│  Product                 │  Customer                │  LicenseKey              │
│  ProductVersion          │  Organization            │  LicensePolicy           │
│  Plan                    │  Order                   │  LicenseToken            │
│  Feature                 │  Entitlement             │                          │
│  PlanFeature             │  Subscription            │                          │
│  PlanPricing (multi-cur) │  Coupon / CouponUsage    │                          │
├──────────────────────────┼──────────────────────────┼──────────────────────────┤
│  ACTIVATION              │  GOVERNANCE              │  NOTIFICATION            │
│  ──────────────────────  │  ──────────────────────  │  ─────────────────────   │
│  Activation              │  AdminUser               │  NotificationTemplate    │
│  DeviceFingerprint       │  Role                    │  NotificationLog         │
│  ActivationEvent         │  Permission              │  WebhookConfig           │
│  HeartbeatLog            │  AuditLog                │  WebhookDelivery         │
├──────────────────────────┼──────────────────────────┼──────────────────────────┤
│  CUSTOMER PORTAL         │  BILLING                 │  PLATFORM                │
│  ──────────────────────  │  ──────────────────────  │  ─────────────────────   │
│  CustomerSession         │  Invoice                 │  ApiKey                  │
│  CustomerOAuthProvider   │  InvoiceItem             │  Environment             │
│  NotificationPreference  │  Refund                  │  MaintenanceWindow       │
│  LicenseTransfer         │  BillingAddress          │  DataRetentionPolicy     │
└──────────────────────────┴──────────────────────────┴──────────────────────────┘
```

---

### 1.2 Domain 1: CATALOG

```sql
-- Sản phẩm
CREATE TABLE products (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  code            VARCHAR(64) UNIQUE NOT NULL,   -- e.g. "PLUGIN_SEO"
  name            VARCHAR(255) NOT NULL,
  description     TEXT,
  category        VARCHAR(64),                    -- "plugin" | "saas" | "desktop"
  status          VARCHAR(32) DEFAULT 'active',  -- active | deprecated | archived
  metadata        JSONB DEFAULT '{}',
  created_at      TIMESTAMPTZ DEFAULT now(),
  updated_at      TIMESTAMPTZ DEFAULT now()
);

-- Phiên bản sản phẩm
CREATE TABLE product_versions (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  product_id      UUID REFERENCES products(id) ON DELETE CASCADE,
  version         VARCHAR(32) NOT NULL,           -- semver "1.2.3"
  release_notes   TEXT,
  min_php_version VARCHAR(16),
  is_latest       BOOLEAN DEFAULT false,
  download_url    TEXT,
  checksum        VARCHAR(128),
  released_at     TIMESTAMPTZ,
  created_at      TIMESTAMPTZ DEFAULT now()
);

-- Gói/Plan
CREATE TABLE plans (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  product_id      UUID REFERENCES products(id) ON DELETE CASCADE,
  code            VARCHAR(64) UNIQUE NOT NULL,    -- "SEO_PRO_ANNUAL"
  name            VARCHAR(255) NOT NULL,
  billing_cycle   VARCHAR(32),                    -- monthly | annual | lifetime | trial
  price_cents     INTEGER,
  currency        VARCHAR(8) DEFAULT 'USD',
  max_activations INTEGER DEFAULT 1,              -- NULL = unlimited
  max_sites       INTEGER DEFAULT 1,
  max_users       INTEGER,
  trial_days      INTEGER DEFAULT 0,
  is_active       BOOLEAN DEFAULT true,
  metadata        JSONB DEFAULT '{}',
  created_at      TIMESTAMPTZ DEFAULT now(),
  updated_at      TIMESTAMPTZ DEFAULT now()
);

-- Feature flags
CREATE TABLE features (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  product_id      UUID REFERENCES products(id) ON DELETE CASCADE,
  code            VARCHAR(128) NOT NULL,           -- "EXPORT_CSV"
  name            VARCHAR(255),
  description     TEXT,
  feature_type    VARCHAR(32) DEFAULT 'boolean',  -- boolean | numeric | string
  created_at      TIMESTAMPTZ DEFAULT now()
);

-- Plan <-> Feature (many-to-many)
CREATE TABLE plan_features (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  plan_id         UUID REFERENCES plans(id) ON DELETE CASCADE,
  feature_id      UUID REFERENCES features(id) ON DELETE CASCADE,
  value           VARCHAR(255) DEFAULT 'true',    -- "true" | "100" | "unlimited"
  UNIQUE(plan_id, feature_id)
);
```

---

### 1.3 Domain 2: ENTITLEMENT

```sql
-- Khách hàng cá nhân
CREATE TABLE customers (
  id                    UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  email                 VARCHAR(255) UNIQUE NOT NULL,
  full_name             VARCHAR(255),
  phone                 VARCHAR(32),
  country               VARCHAR(8),
  timezone              VARCHAR(64),
  email_verified_at     TIMESTAMPTZ,                   -- NULL = chưa verify
  verification_token    VARCHAR(128),                  -- one-time token, xóa sau verify
  password_hash         VARCHAR(256),                  -- NULL nếu chỉ dùng OAuth
  mfa_enabled           BOOLEAN DEFAULT false,
  mfa_secret            VARCHAR(128),                  -- TOTP secret, encrypted
  onboarding_completed  BOOLEAN DEFAULT false,
  preferred_language    VARCHAR(8) DEFAULT 'en',
  metadata              JSONB DEFAULT '{}',
  created_at            TIMESTAMPTZ DEFAULT now(),
  updated_at            TIMESTAMPTZ DEFAULT now()
);

-- Tổ chức / Team
CREATE TABLE organizations (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name            VARCHAR(255) NOT NULL,
  slug            VARCHAR(128) UNIQUE NOT NULL,
  owner_id        UUID REFERENCES customers(id),
  billing_email   VARCHAR(255),
  metadata        JSONB DEFAULT '{}',
  created_at      TIMESTAMPTZ DEFAULT now(),
  updated_at      TIMESTAMPTZ DEFAULT now()
);

-- Customer <-> Organization
CREATE TABLE organization_members (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  org_id          UUID REFERENCES organizations(id) ON DELETE CASCADE,
  customer_id     UUID REFERENCES customers(id) ON DELETE CASCADE,
  role            VARCHAR(32) DEFAULT 'member',   -- owner | admin | member
  joined_at       TIMESTAMPTZ DEFAULT now(),
  UNIQUE(org_id, customer_id)
);

-- Đơn hàng
CREATE TABLE orders (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  reference       VARCHAR(128) UNIQUE NOT NULL,   -- external order ID
  customer_id     UUID REFERENCES customers(id),
  org_id          UUID REFERENCES organizations(id),
  source          VARCHAR(64),                     -- "stripe" | "paddle" | "manual"
  total_cents     INTEGER,
  currency        VARCHAR(8) DEFAULT 'USD',
  status          VARCHAR(32) DEFAULT 'completed',
  purchased_at    TIMESTAMPTZ DEFAULT now(),
  metadata        JSONB DEFAULT '{}'
);

-- Entitlement (quyền sử dụng đã mua)
CREATE TABLE entitlements (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  order_id        UUID REFERENCES orders(id),
  plan_id         UUID REFERENCES plans(id),
  customer_id     UUID REFERENCES customers(id),
  org_id          UUID REFERENCES organizations(id),
  status          VARCHAR(32) DEFAULT 'active',
  -- Possible: active | suspended | expired | revoked | cancelled
  CONSTRAINT entitlement_owner_check CHECK (customer_id IS NOT NULL OR org_id IS NOT NULL),
  starts_at       TIMESTAMPTZ NOT NULL,
  expires_at      TIMESTAMPTZ,                     -- NULL = lifetime
  trial_ends_at   TIMESTAMPTZ,
  auto_renew      BOOLEAN DEFAULT false,
  max_activations INTEGER,                         -- override plan default
  max_sites       INTEGER,
  notes           TEXT,
  metadata        JSONB DEFAULT '{}',
  created_at      TIMESTAMPTZ DEFAULT now(),
  updated_at      TIMESTAMPTZ DEFAULT now()
);

-- Subscription (recurring billing state, liên kết với entitlement)
CREATE TABLE subscriptions (
  id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  entitlement_id    UUID REFERENCES entitlements(id) ON DELETE CASCADE,
  customer_id       UUID REFERENCES customers(id),
  org_id            UUID REFERENCES organizations(id),
  external_id       VARCHAR(128),                  -- Stripe/Paddle subscription ID
  source            VARCHAR(64),                   -- "stripe" | "paddle"
  status            VARCHAR(32) DEFAULT 'active',  -- active | past_due | cancelled | paused
  current_period_start TIMESTAMPTZ,
  current_period_end   TIMESTAMPTZ,
  cancel_at_period_end BOOLEAN DEFAULT false,
  metadata          JSONB DEFAULT '{}',
  created_at        TIMESTAMPTZ DEFAULT now(),
  updated_at        TIMESTAMPTZ DEFAULT now()
);
```

---

### 1.4 Domain 3: LICENSE

```sql
-- License Key
CREATE TABLE license_keys (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  entitlement_id  UUID REFERENCES entitlements(id) ON DELETE CASCADE,
  key_hash        VARCHAR(128) UNIQUE NOT NULL,   -- sha256 hash, không lưu plaintext
  key_display     VARCHAR(64) UNIQUE NOT NULL,    -- XXXX-XXXX-XXXX-XXXX (hiển thị)
  key_prefix      VARCHAR(16),                    -- 4 ký tự đầu, dùng cho fast lookup
  product_id      UUID REFERENCES products(id),
  plan_id         UUID REFERENCES plans(id),
  customer_id     UUID REFERENCES customers(id),
  status          VARCHAR(32) DEFAULT 'issued',
  -- Possible: issued | active | suspended | expired | revoked
  issued_at       TIMESTAMPTZ DEFAULT now(),
  expires_at      TIMESTAMPTZ,
  revoked_at      TIMESTAMPTZ,
  revoke_reason   TEXT,
  note            TEXT,
  metadata        JSONB DEFAULT '{}',
  created_at      TIMESTAMPTZ DEFAULT now(),
  updated_at      TIMESTAMPTZ DEFAULT now()
);

-- Policy snapshot tại thời điểm issue
CREATE TABLE license_policies (
  id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  license_key_id    UUID REFERENCES license_keys(id) ON DELETE CASCADE,
  max_activations   INTEGER DEFAULT 1,
  max_sites         INTEGER DEFAULT 1,
  max_users         INTEGER,
  grace_period_days INTEGER DEFAULT 7,
  offline_allowed   BOOLEAN DEFAULT false,
  offline_max_days  INTEGER DEFAULT 30,
  allow_trial       BOOLEAN DEFAULT false,
  features          JSONB DEFAULT '{}',           -- snapshot feature flags
  created_at        TIMESTAMPTZ DEFAULT now()
);

-- Signed token trả về client sau activation
CREATE TABLE license_tokens (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  activation_id   UUID REFERENCES activations(id) ON DELETE CASCADE,  -- [fix] thêm FK
  token_hash      VARCHAR(256),
  expires_at      TIMESTAMPTZ,
  revoked_at      TIMESTAMPTZ,
  created_at      TIMESTAMPTZ DEFAULT now()
);
```

---

### 1.5 Domain 4: ACTIVATION

```sql
-- Device fingerprint (normalized)
CREATE TABLE device_fingerprints (
  id               UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  fingerprint_hash VARCHAR(256) UNIQUE NOT NULL,
  hostname         VARCHAR(255),
  domain           VARCHAR(255),
  ip_address       INET,
  os_type          VARCHAR(64),
  hardware_id      VARCHAR(256),
  php_version      VARCHAR(32),
  wp_version       VARCHAR(32),
  raw_claims        JSONB DEFAULT '{}',
  first_seen_at    TIMESTAMPTZ DEFAULT now(),
  last_seen_at     TIMESTAMPTZ DEFAULT now()
);

-- Activation record
CREATE TABLE activations (
  id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  license_key_id      UUID REFERENCES license_keys(id) ON DELETE CASCADE,
  fingerprint_id      UUID REFERENCES device_fingerprints(id),
  domain              VARCHAR(255),
  ip_address          INET,
  app_version         VARCHAR(32),
  environment         VARCHAR(32) DEFAULT 'production',
  status              VARCHAR(32) DEFAULT 'active',
  -- Possible: active | revoked | replaced | expired | grace
  activated_at        TIMESTAMPTZ DEFAULT now(),
  last_heartbeat      TIMESTAMPTZ,
  deactivated_at      TIMESTAMPTZ,
  deactivate_reason   TEXT,
  is_offline          BOOLEAN DEFAULT false,
  offline_expires_at  TIMESTAMPTZ,
  metadata            JSONB DEFAULT '{}',
  created_at          TIMESTAMPTZ DEFAULT now(),
  updated_at          TIMESTAMPTZ DEFAULT now()
);

-- Event log
CREATE TABLE activation_events (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  activation_id   UUID REFERENCES activations(id) ON DELETE CASCADE,
  event_type      VARCHAR(64) NOT NULL,
  -- activated | heartbeat | validated | deactivated | revoked | grace_entered
  ip_address      INET,
  app_version     VARCHAR(32),
  detail          JSONB DEFAULT '{}',
  occurred_at     TIMESTAMPTZ DEFAULT now()
);

-- Heartbeat log (sampling)
CREATE TABLE heartbeat_logs (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  activation_id   UUID REFERENCES activations(id) ON DELETE CASCADE,
  status          VARCHAR(32),   -- ok | grace | expired | revoked
  ip_address      INET,
  app_version     VARCHAR(32),
  logged_at       TIMESTAMPTZ DEFAULT now()
);
```

---

### 1.6 Domain 5: GOVERNANCE

```sql
CREATE TABLE admin_users (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  email           VARCHAR(255) UNIQUE NOT NULL,
  full_name       VARCHAR(255),
  password_hash   VARCHAR(256),
  status          VARCHAR(32) DEFAULT 'active',
  last_login_at   TIMESTAMPTZ,
  mfa_enabled     BOOLEAN DEFAULT false,
  created_at      TIMESTAMPTZ DEFAULT now(),
  updated_at      TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE roles (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  code            VARCHAR(64) UNIQUE NOT NULL,
  name            VARCHAR(255),
  description     TEXT,
  created_at      TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE permissions (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  code            VARCHAR(128) UNIQUE NOT NULL, -- license:revoke | entitlement:create
  resource        VARCHAR(64),
  action          VARCHAR(64),
  description     TEXT
);

CREATE TABLE role_permissions (
  role_id         UUID REFERENCES roles(id) ON DELETE CASCADE,
  permission_id   UUID REFERENCES permissions(id) ON DELETE CASCADE,
  PRIMARY KEY (role_id, permission_id)
);

CREATE TABLE admin_roles (
  admin_id        UUID REFERENCES admin_users(id) ON DELETE CASCADE,
  role_id         UUID REFERENCES roles(id) ON DELETE CASCADE,
  product_id      UUID REFERENCES products(id), -- NULL = all products
  PRIMARY KEY (admin_id, role_id)
);

-- Audit log bất biến
CREATE TABLE audit_logs (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  actor_type      VARCHAR(32) NOT NULL,  -- admin | system | api_client
  actor_id        UUID,
  actor_email     VARCHAR(255),
  action          VARCHAR(128) NOT NULL, -- license.revoke | entitlement.create
  resource_type   VARCHAR(64),
  resource_id     UUID,
  ip_address      INET,
  user_agent      TEXT,
  before_state    JSONB,
  after_state     JSONB,
  metadata        JSONB DEFAULT '{}',
  occurred_at     TIMESTAMPTZ DEFAULT now()
);
```

---

### 1.7 Domain 6: NOTIFICATION

```sql
CREATE TABLE notification_templates (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  code            VARCHAR(128) UNIQUE NOT NULL,  -- "license_expiring_7d"
  channel         VARCHAR(32),                   -- email | webhook | in_app
  subject         TEXT,
  body_template   TEXT,                          -- Handlebars template
  is_active       BOOLEAN DEFAULT true,
  created_at      TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE notification_logs (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  template_id     UUID REFERENCES notification_templates(id),
  recipient_email VARCHAR(255),
  resource_type   VARCHAR(64),
  resource_id     UUID,
  status          VARCHAR(32),                   -- sent | failed | bounced
  sent_at         TIMESTAMPTZ DEFAULT now(),
  error_message   TEXT
);

CREATE TABLE webhook_configs (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  product_id      UUID REFERENCES products(id),
  org_id          UUID REFERENCES organizations(id),
  url             TEXT NOT NULL,
  secret          VARCHAR(256),                  -- lưu dạng hashed/encrypted, không plaintext
  events          TEXT[] DEFAULT '{}',
  is_active       BOOLEAN DEFAULT true,
  created_at      TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE webhook_deliveries (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  config_id       UUID REFERENCES webhook_configs(id) ON DELETE CASCADE,
  event_type      VARCHAR(128),
  payload         JSONB,
  status_code     INTEGER,
  response_body   TEXT,
  attempt_count   INTEGER DEFAULT 1,
  delivered_at    TIMESTAMPTZ DEFAULT now()
);
```

---

### 1.8 Index quan trọng

```sql
-- Hot path lookups
CREATE INDEX idx_license_keys_prefix     ON license_keys(key_prefix);
CREATE INDEX idx_license_keys_status     ON license_keys(status);
CREATE INDEX idx_license_keys_customer   ON license_keys(customer_id);
CREATE INDEX idx_license_keys_product    ON license_keys(product_id);
CREATE INDEX idx_activations_license     ON activations(license_key_id);
CREATE INDEX idx_activations_domain      ON activations(domain);
CREATE INDEX idx_activations_status      ON activations(status);
CREATE INDEX idx_activations_heartbeat   ON activations(last_heartbeat);
CREATE INDEX idx_entitlements_customer   ON entitlements(customer_id);
CREATE INDEX idx_entitlements_status     ON entitlements(status);
CREATE INDEX idx_entitlements_expires    ON entitlements(expires_at);
CREATE INDEX idx_audit_logs_actor        ON audit_logs(actor_id, occurred_at DESC);
CREATE INDEX idx_audit_logs_resource     ON audit_logs(resource_type, resource_id);
CREATE INDEX idx_fingerprints_hash       ON device_fingerprints(fingerprint_hash);
-- organization_members lookups
CREATE INDEX idx_org_members_customer    ON organization_members(customer_id);
CREATE INDEX idx_org_members_org         ON organization_members(org_id);
```

---

## 2. State Machine

### 2.1 Entitlement State Machine

```
         [created] ──auto──> [active] <──── renew
                                │
              ┌─────────────────┼──────────────────┐
           suspend           revoke           expires_at
              │                │                   │
              ▼                ▼                   ▼
         [suspended]       [revoked]           [expired]
              │                                    │
           unsuspend                          renewal.confirmed
              │                                    │
              ▼                                    ▼
           [active]                            [active]

         any state ──refund/cancellation──> [cancelled]  (terminal)
```

**Transition rules:**

| From      | To        | Trigger                  | Notes              |
| --------- | --------- | ------------------------ | ------------------ |
| created   | active    | order.confirmed          | Auto via event     |
| active    | suspended | admin.suspend            | Tạm khóa           |
| suspended | active    | admin.unsuspend          | Khôi phục          |
| active    | revoked   | admin.revoke / fraud     | Vĩnh viễn          |
| active    | expired   | cron: expires_at < now() | Scheduled job      |
| expired   | active    | renewal.confirmed        | Gia hạn thành công |
| any       | cancelled | refund / cancellation    | Từ billing         |

---

### 2.2 License Key State Machine

```
         [issued] ──first activation──> [active] <── re-activate (within policy)
                                           │
              ┌────────────────────────────┼──────────────────────┐
           suspend                      revoke              expires_at
              │                            │                      │
              ▼                            ▼                      ▼
         [suspended]                   [revoked]             [expired]
              │                                                    │
           unsuspend                                     entitlement renewed
              │                                                    │
              ▼                                                    ▼
           [active]                                            [active]
```

> **Renewal note:** Khi entitlement được renew, `license_keys.expires_at` được extend và status chuyển về `active` nếu đang ở `expired`. Không issue key mới trừ khi admin chủ động yêu cầu.

---

### 2.3 Activation State Machine

```
         [pending] ──offline confirm / online success──> [active] <── heartbeat OK
                                                             │
              ┌──────────────────────────────────────────────┼──────────────────┐
          heartbeat missed                            manual revoke       license expired
              │                                             │                   │
              ▼                                             ▼                   ▼
           [grace]  ──heartbeat OK──────────────────────> [active]         [expired]
     (7–30 days window)
              │
        grace_period exceeded
              │
              ▼
           [expired]
```

**Grace period logic:**

| Condition                              | Action                                  |
| -------------------------------------- | --------------------------------------- |
| `last_heartbeat` > `grace_period_days` | Chuyển sang `grace`, ghi event          |
| Trong grace                            | Sản phẩm vẫn chạy, platform cảnh báo    |
| Sau `offline_max_days`                 | Chuyển sang `expired`, sản phẩm bị khóa |
| Heartbeat thành công trong grace       | Trở về `active`                         |

---

## 3. API Contract — Chi tiết

### 3.1 Quy ước chung

```
Base URL  : https://license-api.internal/
Client auth: X-API-Key: <product_api_key>
Admin auth : Authorization: Bearer <jwt>
Content    : application/json
Version    : /v1/...
```

**Response envelope chuẩn:**

```json
{
    "success": true,
    "data": {},
    "meta": { "request_id": "req_xxx", "timestamp": "2026-04-13T00:00:00Z" },
    "error": null
}
```

**Error response:**

```json
{
    "success": false,
    "data": null,
    "error": {
        "code": "LICENSE_EXPIRED",
        "message": "License key has expired.",
        "details": {}
    }
}
```

**Error codes chuẩn:**

| Code                      | HTTP | Ý nghĩa                                       |
| ------------------------- | ---- | --------------------------------------------- |
| LICENSE_NOT_FOUND         | 404  | Key không tồn tại                             |
| LICENSE_INVALID           | 422  | Key format sai                                |
| LICENSE_EXPIRED           | 403  | Đã hết hạn                                    |
| LICENSE_REVOKED           | 403  | Đã bị thu hồi                                 |
| LICENSE_SUSPENDED         | 403  | Đang tạm khóa                                 |
| ACTIVATION_LIMIT_EXCEEDED | 403  | Vượt số lượng activation                      |
| ACTIVATION_NOT_FOUND      | 404  | Activation không tồn tại                      |
| PRODUCT_MISMATCH          | 422  | Key không thuộc product này                   |
| FINGERPRINT_MISMATCH      | 403  | Device không khớp                             |
| RATE_LIMITED              | 429  | Quá nhiều request                             |
| INTERNAL_ERROR            | 500  | Lỗi hệ thống                                  |
| CHALLENGE_ALREADY_USED    | 422  | Offline challenge đã được dùng (one-time-use) |
| CHALLENGE_EXPIRED         | 422  | Offline challenge đã hết hạn                  |

**Rate limit mặc định:**

| Endpoint         | Limit                                                  |
| ---------------- | ------------------------------------------------------ |
| /activate        | 10 req/min per IP                                      |
| /validate        | 60 req/min per license_key                             |
| /heartbeat       | 10 req/hour per activation_id                          |
| /offline/request | 5 req/hour per license_key                             |
| /offline/confirm | 3 req/hour per challenge_id (idempotent, one-time-use) |

---

### 3.2 Client API (Product Integration)

#### POST /v1/client/licenses/activate

**Request:**

```json
{
    "license_key": "PROD1-ABCD2-EFGH3-IJKL4",
    "product_code": "PLUGIN_SEO",
    "domain": "example.com",
    "app_version": "2.1.0",
    "environment": "production",
    "device": {
        "hostname": "example.com",
        "ip": "1.2.3.4",
        "os": "linux",
        "php_version": "8.2",
        "wp_version": "6.4"
    }
}
```

**Response 200:**

```json
{
    "success": true,
    "data": {
        "activation_id": "act_abc123",
        "status": "active",
        "license": {
            "key_display": "PROD1-****-****-IJKL4",
            "product_code": "PLUGIN_SEO",
            "plan_code": "SEO_PRO_ANNUAL",
            "expires_at": "2027-04-13T00:00:00Z",
            "max_activations": 3,
            "current_activations": 1
        },
        "policy": {
            "offline_allowed": false,
            "grace_period_days": 7,
            "features": {
                "EXPORT_CSV": "true",
                "MAX_KEYWORDS": "500"
            }
        },
        "token": {
            "value": "<signed_ed25519_token>",
            "expires_at": "2026-05-13T00:00:00Z"
        }
    }
}
```

---

#### POST /v1/client/licenses/validate

**Request:**

```json
{
    "license_key": "PROD1-ABCD2-EFGH3-IJKL4",
    "product_code": "PLUGIN_SEO",
    "activation_id": "act_abc123",
    "domain": "example.com",
    "app_version": "2.1.0"
}
```

**Response 200:**

```json
{
    "success": true,
    "data": {
        "valid": true,
        "status": "active",
        "expires_at": "2027-04-13T00:00:00Z",
        "features": {
            "EXPORT_CSV": "true",
            "MAX_KEYWORDS": "500"
        },
        "message": null
    }
}
```

---

#### POST /v1/client/licenses/heartbeat

**Request:**

```json
{
    "activation_id": "act_abc123",
    "license_key": "PROD1-ABCD2-EFGH3-IJKL4",
    "product_code": "PLUGIN_SEO",
    "app_version": "2.1.0",
    "domain": "example.com"
}
```

**Response 200:**

```json
{
    "success": true,
    "data": {
        "status": "active",
        "next_heartbeat_at": "2026-04-14T02:00:00Z",
        "expires_at": "2027-04-13T00:00:00Z",
        "policy_updated": false,
        "features": { "EXPORT_CSV": "true", "MAX_KEYWORDS": "500" }
    }
}
```

---

#### POST /v1/client/licenses/deactivate

> **Lưu ý:** Endpoint này dùng POST thay vì DELETE vì có request body. Một số HTTP clients/proxies không hỗ trợ DELETE với body.

**Request:**

```json
{
    "activation_id": "act_abc123",
    "license_key": "PROD1-ABCD2-EFGH3-IJKL4",
    "product_code": "PLUGIN_SEO",
    "reason": "user_requested"
}
```

**Response 200:**

```json
{
    "success": true,
    "data": {
        "deactivated": true,
        "remaining_activations": 2
    }
}
```

---

#### POST /v1/client/licenses/offline/request

**Request:**

```json
{
    "license_key": "PROD1-ABCD2-EFGH3-IJKL4",
    "product_code": "PLUGIN_SEO",
    "domain": "example.com",
    "nonce": "random_client_nonce_abc",
    "device": { "hostname": "...", "os": "linux" }
}
```

**Response 200:**

```json
{
    "success": true,
    "data": {
        "challenge_id": "chal_xyz789",
        "challenge_token": "base64_encoded_challenge",
        "expires_at": "2026-04-14T00:00:00Z",
        "instructions": "Take this challenge to an internet-connected device to complete offline activation."
    }
}
```

---

#### POST /v1/client/licenses/offline/confirm

> **Idempotency:** `challenge_id` là one-time-use. Gọi lần 2 với cùng `challenge_id` sẽ trả về lỗi `CHALLENGE_ALREADY_USED`. Challenge tự động expire sau thời gian trong `expires_at`.

**Request:**

```json
{
    "challenge_id": "chal_xyz789",
    "response_token": "base64_encoded_server_response"
}
```

**Response 200:**

```json
{
    "success": true,
    "data": {
        "activation_id": "act_offline_def456",
        "status": "active",
        "offline_expires_at": "2026-05-13T00:00:00Z",
        "license": {},
        "features": {}
    }
}
```

---

#### POST /v1/client/updates/check

> **Lưu ý:** Dùng POST thay vì GET để tránh `license_key` bị log trong server access log, proxy, và CDN.

**Request:**

```json
{
    "product_code": "PLUGIN_SEO",
    "current_version": "2.0.0",
    "license_key": "PROD1-ABCD2-EFGH3-IJKL4",
    "domain": "example.com"
}
```

**Response 200:**

```json
{
    "success": true,
    "data": {
        "has_update": true,
        "latest_version": "2.1.0",
        "allowed_to_download": true,
        "download_url": "https://cdn.internal/releases/plugin-seo-2.1.0.zip",
        "checksum": "sha256:abc...",
        "release_notes": "Bug fixes and performance improvements.",
        "requires": { "php": "8.0", "wp": "6.0" }
    }
}
```

---

### 3.3 Admin API

| Method | Endpoint                          | Mô tả                    |
| ------ | --------------------------------- | ------------------------ |
| GET    | /v1/admin/products                | Danh sách products       |
| POST   | /v1/admin/products                | Tạo product              |
| POST   | /v1/admin/plans                   | Tạo plan                 |
| POST   | /v1/admin/entitlements            | Tạo entitlement thủ công |
| POST   | /v1/admin/licenses/issue          | Issue license key        |
| GET    | /v1/admin/licenses                | Tìm kiếm license keys    |
| POST   | /v1/admin/licenses/{id}/revoke    | Thu hồi license          |
| POST   | /v1/admin/licenses/{id}/suspend   | Tạm khóa license         |
| POST   | /v1/admin/licenses/{id}/unsuspend | Khôi phục license        |
| POST   | /v1/admin/licenses/{id}/extend    | Gia hạn expiry           |
| GET    | /v1/admin/activations             | Xem activations          |
| POST   | /v1/admin/activations/{id}/reset  | Reset activation         |
| POST   | /v1/admin/activations/{id}/revoke | Revoke activation        |
| GET    | /v1/admin/reports/expiring        | Sắp hết hạn              |
| GET    | /v1/admin/reports/abuse           | Dấu hiệu abuse           |
| GET    | /v1/admin/audit-logs              | Audit trail              |

---

**POST /v1/admin/licenses/issue — Request:**

```json
{
    "entitlement_id": "ent_aaa111",
    "quantity": 1,
    "note": "Manual issue for VIP customer"
}
```

**POST /v1/admin/licenses/{id}/revoke — Request:**

```json
{
    "reason": "fraud_detected",
    "note": "Multiple IPs in 10 countries within 1 hour"
}
```

**POST /v1/admin/activations/{id}/reset — Request:**

```json
{
    "reason": "hardware_changed",
    "note": "Customer replaced server hardware"
}
```

---

## 4. RBAC Matrix

### 4.1 Danh sách Roles

| Role Code       | Tên           | Phạm vi       | Mô tả                               |
| --------------- | ------------- | ------------- | ----------------------------------- |
| `super_admin`   | Super Admin   | Platform-wide | Toàn quyền                          |
| `product_admin` | Product Admin | Per product   | Quản lý sản phẩm/plan/key được giao |
| `support`       | Support Agent | Per product   | Xem, reset activation, tìm key      |
| `billing_ops`   | Billing Ops   | Platform-wide | Tạo entitlement, issue key          |
| `viewer`        | Viewer        | Per product   | Chỉ đọc                             |
| `api_client`    | API Client    | Per product   | Product integration                 |

---

### 4.2 Permission Matrix

| Permission                 | super_admin | product_admin | support | billing_ops | viewer | api_client |
| -------------------------- | :---------: | :-----------: | :-----: | :---------: | :----: | :--------: |
| **CATALOG**                |             |               |         |             |        |            |
| product:create             |     ✅      |      ❌       |   ❌    |     ❌      |   ❌   |     ❌     |
| product:read               |     ✅      |      ✅       |   ✅    |     ✅      |   ✅   |     ✅     |
| product:update             |     ✅      |      ✅       |   ❌    |     ❌      |   ❌   |     ❌     |
| product:delete             |     ✅      |      ❌       |   ❌    |     ❌      |   ❌   |     ❌     |
| plan:create                |     ✅      |      ✅       |   ❌    |     ❌      |   ❌   |     ❌     |
| plan:update                |     ✅      |      ✅       |   ❌    |     ❌      |   ❌   |     ❌     |
| feature:manage             |     ✅      |      ✅       |   ❌    |     ❌      |   ❌   |     ❌     |
| **ENTITLEMENT**            |             |               |         |             |        |            |
| entitlement:create         |     ✅      |      ❌       |   ❌    |     ✅      |   ❌   |     ❌     |
| entitlement:read           |     ✅      |      ✅       |   ✅    |     ✅      |   ✅   |     ❌     |
| entitlement:update         |     ✅      |      ✅       |   ❌    |     ✅      |   ❌   |     ❌     |
| entitlement:revoke         |     ✅      |      ❌       |   ❌    |     ❌      |   ❌   |     ❌     |
| order:create               |     ✅      |      ❌       |   ❌    |     ✅      |   ❌   |     ❌     |
| customer:create            |     ✅      |      ❌       |   ❌    |     ✅      |   ❌   |     ❌     |
| customer:read              |     ✅      |      ✅       |   ✅    |     ✅      |   ✅   |     ❌     |
| **LICENSE**                |             |               |         |             |        |            |
| license:issue              |     ✅      |      ✅       |   ❌    |     ✅      |   ❌   |     ❌     |
| license:read               |     ✅      |      ✅       |   ✅    |     ✅      |   ✅   |     ✅     |
| license:revoke             |     ✅      |      ✅       |   ❌    |     ❌      |   ❌   |     ❌     |
| license:suspend            |     ✅      |      ✅       |   ❌    |     ❌      |   ❌   |     ❌     |
| license:extend             |     ✅      |      ✅       |   ❌    |     ✅      |   ❌   |     ❌     |
| **ACTIVATION**             |             |               |         |             |        |            |
| activation:read            |     ✅      |      ✅       |   ✅    |     ✅      |   ✅   |     ❌     |
| activation:create          |     ✅      |      ❌       |   ❌    |     ❌      |   ❌   |     ✅     |
| activation:validate        |     ✅      |      ❌       |   ❌    |     ❌      |   ❌   |     ✅     |
| activation:heartbeat       |     ✅      |      ❌       |   ❌    |     ❌      |   ❌   |     ✅     |
| activation:revoke          |     ✅      |      ✅       |   ❌    |     ❌      |   ❌   |     ❌     |
| activation:reset           |     ✅      |      ✅       |   ✅    |     ❌      |   ❌   |     ❌     |
| **GOVERNANCE**             |             |               |         |             |        |            |
| audit_log:read             |     ✅      |      ✅       |   ❌    |     ❌      |   ❌   |     ❌     |
| audit_log:read_own_product |     ✅      |      ✅       |   ✅    |     ❌      |   ❌   |     ❌     |
| admin_user:manage          |     ✅      |      ❌       |   ❌    |     ❌      |   ❌   |     ❌     |
| role:manage                |     ✅      |      ❌       |   ❌    |     ❌      |   ❌   |     ❌     |
| report:read                |     ✅      |      ✅       |   ✅    |     ✅      |   ✅   |     ❌     |
| report:export              |     ✅      |      ✅       |   ❌    |     ✅      |   ❌   |     ❌     |
| webhook:manage             |     ✅      |      ✅       |   ❌    |     ❌      |   ❌   |     ❌     |

---

### 4.3 Scope Rules

- `product_admin` và `viewer` chỉ thấy data thuộc product được assign trong `admin_roles.product_id`.
- `super_admin` không bị scope theo product.
- `api_client` chỉ được dùng activation/validate/heartbeat/update endpoint, phải kèm `X-API-Key` bound với `product_id`.
- `support` có thể `activation:reset` nhưng không thể `license:revoke`. `support` có `audit_log:read_own_product` để troubleshoot trong phạm vi product được assign.
- `billing_ops` phải có entitlement tồn tại trước khi issue key — không thể issue key orphan không có entitlement.
- Mọi action write đều bắt buộc ghi `audit_logs`.

---

## 5. Integration Guideline cho Sản phẩm Nội bộ

### 5.1 Mô hình tích hợp

Sản phẩm nội bộ không quản lý license logic. Platform là nguồn sự thật duy nhất. Sản phẩm chỉ là license consumer.

```
[Product A]  ─────┐
[Product B]  ──── > [License Platform API]
[Product C]  ─────┘         │
  (X-API-Key)           entitlement
                         license policy
                         activation state
```

---

### 5.2 Bước tích hợp chuẩn

**Bước 1: Đăng ký trên Platform**

- Admin tạo `Product` với `product_code` duy nhất.
- Tạo `Plan` và `Feature` matrix.
- Cấp `X-API-Key` cho product client.

**Bước 2: Cài SDK**

```php
// LICENSE_PLATFORM_KEY lấy từ env var hoặc encrypted config, không hardcode trong source
// Ví dụ: getenv('LICENSE_PLATFORM_KEY') hoặc wp_options encrypted
$client = new LicensePlatformClient([
    'base_url'     => 'https://license-api.internal/v1',
    'api_key'      => getenv('LICENSE_PLATFORM_KEY'),
    'product_code' => 'PLUGIN_SEO',
    'timeout'      => 10,
    'retry'        => 2,
]);
```

**Bước 3: Implement 4 flow chính**

| Flow         | Endpoint                        | Khi nào gọi             |
| ------------ | ------------------------------- | ----------------------- |
| Activation   | POST /client/licenses/activate  | Lần đầu nhập key        |
| Validation   | POST /client/licenses/validate  | Khi check quyền feature |
| Heartbeat    | POST /client/licenses/heartbeat | Định kỳ background      |
| Update check | POST /client/updates/check      | Trước khi tải update    |

**Bước 4: Lưu activation state local**

```php
// Lưu trong DB hoặc wp_options (encrypted)
// Cache hợp lệ trong 24 giờ. Nếu cached_at > 24h và không có mạng, áp dụng grace period.
// Grace period tính từ last_heartbeat, không phải từ token_expires.
[
  'activation_id'  => 'act_abc123',
  'license_key'    => '...',
  'status'         => 'active',
  'expires_at'     => '2027-04-13T00:00:00Z',
  'features'       => [...],
  'token'          => '...',
  'token_expires'  => '...',
  'last_heartbeat' => '...',
  'cached_at'      => '...',   // TTL: 24 giờ
]
```

**Bước 5: Feature gating**

```php
// Đọc từ cached state — KHÔNG hardcode business rule từ plan name
if ($licenseState->hasFeature('EXPORT_CSV')) {
    // allow
}
if ((int)$licenseState->getFeature('MAX_KEYWORDS') >= $requested) {
    // allow
}
```

---

### 5.3 Validation flow chuẩn

```
Request đến sản phẩm
       │
       ▼
Local token còn hạn?
  ├── YES → verify Ed25519 signature → return features (không gọi API)
  └── NO  → gọi POST /validate
                  │
                  ├── success → update cache → return features
                  └── fail (network/error) → check grace period
                                    │  (grace tính từ last_heartbeat, không phải token_expires)
                                    ├── trong grace → allow + flag warning
                                    └── hết grace   → block access
```

---

### 5.4 Heartbeat schedule

| Loại sản phẩm    | Interval          | Notes                         |
| ---------------- | ----------------- | ----------------------------- |
| WordPress plugin | 12 giờ / WP-Cron  | Không chạy mỗi request        |
| PHP web app      | 24 giờ / cron job | Background, không block       |
| SaaS backend     | 6 giờ / scheduler | Kết hợp health check          |
| Desktop app      | 24 giờ / on-start | Check khi khởi động + định kỳ |

---

### 5.5 Xử lý lỗi tại product

```php
try {
    $result = $client->activate($licenseKey, $domain, $deviceInfo);
} catch (LicenseExpiredException $e) {
    $this->showExpiredNotice(); // redirect đến trang mua
} catch (LicenseSuspendedException $e) {
    $this->showSuspendedNotice(); // số liên hệ support
} catch (ActivationLimitExceededException $e) {
    $this->showLimitNotice(); // hướng dẫn deactivate site cũ
} catch (NetworkException $e) {
    $this->allowWithGrace($e); // không fail cứng
} catch (LicensePlatformException $e) {
    $this->logger->error($e);
    $this->allowShortGrace(); // grace 1–3 ngày
}
```

---

### 5.6 Security rules

- Không log `license_key` đầy đủ — chỉ log `key_display` masked.
- Không expose `X-API-Key` trong frontend JavaScript hoặc HTML response.
- Cache token local phải encrypted, không lưu plaintext.
- Không bypass validate bằng cách hardcode `status = active` trong code.
- Mọi request đến platform phải qua HTTPS với TLS cert validation.

---

### 5.7 Checklist tích hợp

- [ ] `product_code` và `X-API-Key` đã được cấp từ platform admin
- [ ] SDK / HTTP client đã cài, không tự viết raw HTTP ở nhiều chỗ
- [ ] Activation flow hoạt động và lưu local state
- [ ] Validate flow có fallback grace khi mạng lỗi
- [ ] Heartbeat chạy định kỳ qua background job
- [ ] Feature gating đọc từ platform policy, không hardcode
- [ ] Update check tích hợp vào update checker của product
- [ ] Error handling đầy đủ theo chuẩn error code
- [ ] Không log key đầy đủ, không expose API Key ra ngoài
- [ ] Đã test offline / grace period scenario

---

### 5.8 Migration từ license logic cũ

**Nên làm:**

- Chọn 1 sản phẩm pilot ít critical nhất.
- Bọc logic cũ bằng adapter, cho chạy song song trong giai đoạn chuyển tiếp.
- Tách responsibility: bỏ logic sinh key, bỏ local rule expiry/concurrent.
- Chạy migration job để đẩy data cũ (customers, licenses, activations) lên platform.
- Khi ổn định mới migrate sản phẩm tiếp theo.

**Không nên làm:**

- Big-bang migrate tất cả cùng lúc.
- Mỗi sản phẩm tự giữ bản copy policy riêng.
- Frontend gọi trực tiếp platform bằng secret key.

---

## 6. Implementation Roadmap theo Module

### 6.1 Nguyên tắc phân kỳ

- Mỗi phase có deliverable độc lập, có thể ship và dùng được.
- Phase sau không block phase trước.
- MVP ưu tiên luồng quan trọng nhất: issue key → activate → validate.
- Sprint = 2 tuần.

---

### 6.2 Phase 1 — Foundation (Tuần 1–4) — MVP

**Mục tiêu:** Platform có thể issue key, activate và validate cơ bản.

| Sprint   | Module          | Deliverable                                                                                                                                            |
| -------- | --------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Sprint 1 | Infrastructure  | Repo, CI/CD, DB migration (Domain 1–3), env config, logging, error handler                                                                             |
| Sprint 1 | Auth            | Admin login (JWT), X-API-Key cho product client, RBAC cơ bản (super_admin + api_client roles), Ed25519 key pair setup (local/env-based, không cần KMS) |
| Sprint 1 | Health API      | GET /v1/health, GET /v1/status, GET /v1/version, MaintenanceWindow schema                                                                              |
| Sprint 1 | API Key Mgmt    | api_keys table, issue/revoke/rotate, thay thế hardcoded X-API-Key                                                                                      |
| Sprint 2 | Catalog         | CRUD Product, ProductVersion, Plan, Feature, PlanFeature                                                                                               |
| Sprint 2 | Entitlement     | CRUD Customer, Order, Entitlement (manual create)                                                                                                      |
| Sprint 3 | License         | Issue license key từ entitlement, state machine cơ bản                                                                                                 |
| Sprint 3 | Activation      | POST activate, POST validate, local token response                                                                                                     |
| Sprint 4 | Admin Portal v1 | Product list, issue key, xem activation                                                                                                                |
| Sprint 4 | SDK PHP v1      | HTTP client: activate, validate                                                                                                                        |

**Definition of Done:**

- Admin tạo product, plan, issue key thủ công.
- PHP product gọi activate và validate thành công.
- Audit log ghi nhận mọi action write.

---

### 6.3 Phase 2 — Core Operations (Tuần 5–8)

**Mục tiêu:** Vận hành đầy đủ lifecycle: heartbeat, revoke, suspend, renew, update check.

| Sprint   | Module          | Deliverable                                                            |
| -------- | --------------- | ---------------------------------------------------------------------- |
| Sprint 5 | Activation      | POST heartbeat, DELETE deactivate, state machine đầy đủ                |
| Sprint 5 | Notification    | Email gửi key sau issue, expiring soon (7 ngày), revoke notice         |
| Sprint 5 | Email Verify    | Email verification flow, onboarding checklist, resend verification     |
| Sprint 5 | Notif Prefs     | notification_preferences table, customer opt-in/out, unsubscribe token |
| Sprint 6 | Governance      | RBAC đầy đủ (tất cả roles, permissions, scoped access per product)     |
| Sprint 6 | License         | Revoke, suspend, unsuspend, extend expiry                              |
| Sprint 7 | Update check    | GET updates/check, link ProductVersion và entitlement                  |
| Sprint 7 | Environment     | environments table, staging/dev key separation, rate limit multiplier  |
| Sprint 7 | Admin Portal v2 | Dashboard tổng quan, filter/search, revoke/suspend UI                  |
| Sprint 8 | Reports         | Expiring report, activation report, export CSV                         |
| Sprint 8 | SDK PHP v2      | heartbeat, deactivate, update_check, error handling đầy đủ             |

**Definition of Done:**

- Toàn bộ lifecycle key hoạt động đúng state machine.
- Support team có thể tìm key, reset activation, xem history.
- Product nhận update đúng policy.

---

### 6.4 Phase 3 — Scale & Offline (Tuần 9–12)

**Mục tiêu:** Offline activation, billing integration, abuse detection.

| Sprint    | Module              | Deliverable                                                            |
| --------- | ------------------- | ---------------------------------------------------------------------- |
| Sprint 9  | Offline activation  | POST offline/request, POST offline/confirm, challenge-response Ed25519 |
| Sprint 9  | Grace period        | Cron job cập nhật grace → expired tự động                              |
| Sprint 10 | Renewal             | Renew flow: billing event → extend entitlement → notify                |
| Sprint 10 | Billing integration | Webhook nhận từ Stripe/Paddle: order.created → auto-create entitlement |
| Sprint 10 | Refund & Chargeback | refunds table, auto-revoke flow, billing webhook handler               |
| Sprint 10 | Coupon & Discount   | coupons, coupon_usages, validate API, apply khi tạo order              |
| Sprint 11 | Bulk operations     | bulk_jobs table, bulk issue/revoke/export/import, async job tracking   |
| Sprint 11 | Multi-currency      | plan_pricing table, currency resolution logic                          |
| Sprint 11 | Abuse detection     | Rule engine: nhiều IP, nhiều quốc gia, vượt activation ngưỡng → alert  |
| Sprint 11 | Webhook outbound    | Config per product/org, delivery + retry                               |
| Sprint 12 | Analytics           | Usage dashboard: active activations, churn, expiring pipeline          |

| Sprint 12 | Performance | Redis cache entitlement/policy, rate limiting, load test |

- Billing webhook tự động tạo entitlement và issue key.
- Abuse detection alert admin khi anomaly.

---

### 6.5 Phase 4 — Platform Maturity (Tuần 13–18)

**Mục tiêu:** Self-service portal, reseller, metered licensing, hardening.

| Sprint    | Module              | Deliverable                                                                          |
| --------- | ------------------- | ------------------------------------------------------------------------------------ |
| Sprint 13 | Customer portal     | Khách hàng tự xem license, deactivate, download key                                  |
| Sprint 13 | Customer Auth       | Login/register/OAuth/MFA cho customer, customer_sessions, customer_oauth_providers   |
| Sprint 14 | Invoice & Billing   | invoices, invoice_items, billing_addresses, PDF generation, customer billing history |
| Sprint 14 | Reseller portal     | Bulk key, phân phối, theo dõi activation                                             |
| Sprint 15 | License Transfer    | license_transfers table, transfer flow, auto-revoke activations                      |
| Sprint 15 | Metered licensing   | Usage-based: ghi nhận API calls/seats → bill theo usage                              |
| Sprint 16 | Advanced RBAC       | Custom roles, cross-product admin                                                    |
| Sprint 17 | GDPR                | data_requests, data_retention_policies, erasure/portability flow, anonymization      |
| Sprint 17 | Security hardening  | Pentest, audit log tamper detection, KMS key rotation                                |
| Sprint 18 | SLA & Observability | SLA 99.9%, alerting, tracing, runbook, DR drill                                      |

---

### 6.6 Timeline tổng quan

```
Q2 2026 (Tháng 4–6)
├── Phase 1 (T1–T4): Foundation — issue key, activate, validate, health API, API key mgmt
└── Phase 2 (T5–T8): Core Ops  — heartbeat, revoke, renew, reports, email verify, notif prefs, env separation

Q3 2026 (Tháng 7–9)
└── Phase 3 (T9–T12): Scale   — offline, billing, refund, coupon, bulk ops, multi-currency, abuse detection

Q4 2026 (Tháng 10–12)
└── Phase 4 (T13–T18): Maturity — customer auth, invoice, license transfer, self-service, reseller, GDPR, metered
```

---

### 6.7 Dependencies & Risks

| Dependency                             | Ảnh hưởng       | Mitigation                                                                   |
| -------------------------------------- | --------------- | ---------------------------------------------------------------------------- |
| Billing webhook format (Stripe/Paddle) | Phase 3         | Thiết kế adapter pattern sớm                                                 |
| Ed25519 KMS setup                      | Phase 3 offline | Key pair local/env từ Sprint 1; KMS rotation là Phase 4 concern              |
| Product teams sẵn sàng tích hợp        | Phase 1 end     | Pilot với 1 product duy nhất trước                                           |
| DB migration downtime                  | Mỗi phase       | Blue-green + migration script review kỹ                                      |
| Load test throughput target            | Phase 3         | Benchmark sớm ở Sprint 12                                                    |
| RBAC chưa đầy đủ ở Phase 1             | Phase 1         | Chỉ cấp account cho ≤2 người trong Sprint 1–4; full RBAC hoàn thiện Sprint 6 |
| Customer Auth complexity (OAuth/MFA)   | Phase 4         | Dùng thư viện chuẩn (Passport/OAuth2), không tự implement                    |
| GDPR erasure vs transaction integrity  | Phase 4         | Anonymize thay vì hard delete, giữ transaction records                       |
| PDF invoice generation                 | Phase 4         | Dùng headless Chrome hoặc dedicated PDF service                              |
| Multi-currency exchange rate           | Phase 3         | Giá cố định per currency, không tự động convert                              |

---

_Document version: 2.5 | Updated: April 2026 | Owner: Engineering Lead_

---

## 20. Caching Strategy

### 20.1 Cache Layers

```
Request
   │
   ▼
[L1] Local memory cache (product SDK, 5 min TTL)
   │ miss
   ▼
[L2] Redis cache (platform API, TTL per type)
   │ miss
   ▼
[L3] PostgreSQL (source of truth)
```

### 20.2 Cache Key Schema

| Key Pattern                  | TTL                  | Nội dung                          | Invalidate khi             |
| ---------------------------- | -------------------- | --------------------------------- | -------------------------- |
| `license:{key_hash}`         | 5 phút               | license status, expires_at        | revoke, suspend, extend    |
| `policy:{license_key_id}`    | 1 giờ                | feature flags, max_activations    | plan update, policy change |
| `activation:{activation_id}` | 5 phút               | activation status, last_heartbeat | heartbeat, revoke, reset   |
| `entitlement:{id}`           | 10 phút              | entitlement status, expires_at    | renew, revoke, suspend     |
| `product:{code}`             | 1 giờ                | product info, api_key validation  | product update             |
| `rate_limit:{ip}:{endpoint}` | 1 phút               | request count                     | auto-expire                |
| `session:{token_hash}`       | = session.expires_at | customer session                  | logout, revoke             |

### 20.3 Cache Invalidation

Invalidation được trigger qua **event bus** sau mỗi write operation:

```
Admin revoke license
       │
       ▼
DB update license_keys.status = 'revoked'
       │
       ▼
Publish event: license.revoked { license_key_id, key_hash }
       │
       ▼
Cache invalidator:
  - DEL license:{key_hash}
  - DEL policy:{license_key_id}
  - DEL activation:{activation_id} (tất cả activations của key)
```

### 20.4 Cache Stampede Protection

Dùng **probabilistic early expiration** hoặc **mutex lock** khi cache miss trên hot key:

```php
// Mutex lock pattern
$lock = $redis->set("lock:license:{hash}", 1, ['NX', 'EX' => 5]);
if ($lock) {
    $data = $db->fetchLicense($hash);
    $redis->setex("license:{hash}", 300, serialize($data));
    $redis->del("lock:license:{hash}");
} else {
    // Retry sau 100ms hoặc fallback về DB trực tiếp
    usleep(100000);
    $data = $redis->get("license:{hash}") ?? $db->fetchLicense($hash);
}
```

### 20.5 Redis Config

```yaml
# redis.conf
maxmemory: 2gb
maxmemory-policy: allkeys-lru # evict LRU khi đầy bộ nhớ
save: "" # disable RDB persistence cho cache
appendonly: no
```

> **Lưu ý:** Dùng Redis Cluster hoặc Redis Sentinel cho HA. Cache layer không phải source of truth — mất cache không mất data.

---

## 7. Customer Authentication & Portal API

### 7.1 Schema

```sql
-- OAuth provider liên kết với customer account
CREATE TABLE customer_oauth_providers (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  customer_id     UUID REFERENCES customers(id) ON DELETE CASCADE,
  provider        VARCHAR(32) NOT NULL,          -- "google" | "github" | "microsoft"
  provider_uid    VARCHAR(255) NOT NULL,
  access_token    TEXT,                           -- encrypted
  refresh_token   TEXT,                           -- encrypted
  expires_at      TIMESTAMPTZ,
  created_at      TIMESTAMPTZ DEFAULT now(),
  UNIQUE(provider, provider_uid)
);

-- Customer session (web portal)
CREATE TABLE customer_sessions (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  customer_id     UUID REFERENCES customers(id) ON DELETE CASCADE,
  token_hash      VARCHAR(256) UNIQUE NOT NULL,
  ip_address      INET,
  user_agent      TEXT,
  expires_at      TIMESTAMPTZ NOT NULL,
  created_at      TIMESTAMPTZ DEFAULT now(),
  last_active_at  TIMESTAMPTZ DEFAULT now()
);
```

### 7.2 Customer Auth API

```
POST /v1/customer/auth/register
POST /v1/customer/auth/login
POST /v1/customer/auth/logout
POST /v1/customer/auth/refresh
POST /v1/customer/auth/forgot-password
POST /v1/customer/auth/reset-password
POST /v1/customer/auth/verify-email
GET  /v1/customer/auth/oauth/{provider}
GET  /v1/customer/auth/oauth/{provider}/callback
POST /v1/customer/auth/mfa/enable
POST /v1/customer/auth/mfa/verify
```

### 7.3 Customer Portal API

```
GET    /v1/customer/me
PATCH  /v1/customer/me
GET    /v1/customer/licenses
GET    /v1/customer/licenses/{id}
GET    /v1/customer/licenses/{id}/activations
POST   /v1/customer/activations/{id}/deactivate
GET    /v1/customer/orders
GET    /v1/customer/invoices
GET    /v1/customer/invoices/{id}/download
GET    /v1/customer/subscriptions
POST   /v1/customer/subscriptions/{id}/cancel
```

**GET /v1/customer/licenses — Response 200:**

```json
{
    "success": true,
    "data": {
        "licenses": [
            {
                "key_display": "PROD1-****-****-IJKL4",
                "product_name": "SEO Plugin Pro",
                "plan_name": "Annual",
                "status": "active",
                "expires_at": "2027-04-13T00:00:00Z",
                "active_sites": 2,
                "max_sites": 3
            }
        ],
        "total": 1
    }
}
```

---

## 8. API Key Management

### 8.1 Schema

```sql
CREATE TABLE api_keys (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  product_id      UUID REFERENCES products(id) ON DELETE CASCADE,
  name            VARCHAR(128) NOT NULL,
  key_hash        VARCHAR(256) UNIQUE NOT NULL,
  key_prefix      VARCHAR(16) NOT NULL,
  environment     VARCHAR(32) DEFAULT 'production',
  scopes          TEXT[] DEFAULT '{}',
  last_used_at    TIMESTAMPTZ,
  expires_at      TIMESTAMPTZ,
  revoked_at      TIMESTAMPTZ,
  revoke_reason   TEXT,
  created_by      UUID REFERENCES admin_users(id),
  created_at      TIMESTAMPTZ DEFAULT now(),
  updated_at      TIMESTAMPTZ DEFAULT now()
);
```

### 8.2 API Key Lifecycle

```
[created] ──active──> [active] ──revoke──> [revoked]
                          │
                     expires_at
                          │
                          ▼
                      [expired]
```

### 8.3 Admin API

```
GET    /v1/admin/products/{id}/api-keys
POST   /v1/admin/products/{id}/api-keys        -- trả về plaintext 1 lần duy nhất
POST   /v1/admin/api-keys/{id}/rotate
POST   /v1/admin/api-keys/{id}/revoke
GET    /v1/admin/api-keys/{id}/usage
```

**POST /v1/admin/products/{id}/api-keys — Response 200:**

```json
{
    "success": true,
    "data": {
        "id": "key_xxx",
        "name": "Production Key",
        "key": "lp_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
        "key_prefix": "lp_live_",
        "environment": "production",
        "warning": "Store this key securely. It will not be shown again."
    }
}
```

---

## 9. Coupon & Discount

### 9.1 Schema

```sql
CREATE TABLE coupons (
  id                    UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  code                  VARCHAR(64) UNIQUE NOT NULL,
  name                  VARCHAR(255),
  discount_type         VARCHAR(32) NOT NULL,  -- "percent" | "fixed_amount" | "trial_extension" | "free_plan"
  discount_value        INTEGER NOT NULL,
  currency              VARCHAR(8),
  applies_to            VARCHAR(32) DEFAULT 'any',
  plan_id               UUID REFERENCES plans(id),
  product_id            UUID REFERENCES products(id),
  max_uses              INTEGER,
  max_uses_per_customer INTEGER DEFAULT 1,
  used_count            INTEGER DEFAULT 0,
  valid_from            TIMESTAMPTZ DEFAULT now(),
  valid_until           TIMESTAMPTZ,
  is_active             BOOLEAN DEFAULT true,
  created_by            UUID REFERENCES admin_users(id),
  created_at            TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE coupon_usages (
  id               UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  coupon_id        UUID REFERENCES coupons(id) ON DELETE CASCADE,
  customer_id      UUID REFERENCES customers(id),
  order_id         UUID REFERENCES orders(id),
  entitlement_id   UUID REFERENCES entitlements(id),
  discount_applied INTEGER,
  used_at          TIMESTAMPTZ DEFAULT now()
);
```

### 9.2 Admin & Client API

```
GET    /v1/admin/coupons
POST   /v1/admin/coupons
PATCH  /v1/admin/coupons/{id}
GET    /v1/admin/coupons/{id}/usages
POST   /v1/admin/coupons/{id}/deactivate
POST   /v1/client/coupons/validate
```

**POST /v1/client/coupons/validate — Request:**

```json
{ "coupon_code": "LAUNCH50", "plan_code": "SEO_PRO_ANNUAL" }
```

**Response 200:**

```json
{
    "success": true,
    "data": {
        "valid": true,
        "discount_type": "percent",
        "discount_value": 50,
        "description": "50% off first year",
        "expires_at": "2026-06-30T00:00:00Z"
    }
}
```

---

## 10. Bulk Operations

### 10.1 Schema

```sql
CREATE TABLE bulk_jobs (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  job_type        VARCHAR(64) NOT NULL,  -- "bulk_issue" | "bulk_revoke" | "bulk_import" | "bulk_export"
  status          VARCHAR(32) DEFAULT 'pending',
  created_by      UUID REFERENCES admin_users(id),
  total_items     INTEGER DEFAULT 0,
  processed_items INTEGER DEFAULT 0,
  failed_items    INTEGER DEFAULT 0,
  result_url      TEXT,
  error_log       JSONB DEFAULT '[]',
  started_at      TIMESTAMPTZ,
  completed_at    TIMESTAMPTZ,
  created_at      TIMESTAMPTZ DEFAULT now()
);
```

### 10.2 Admin API

```
POST /v1/admin/licenses/bulk-issue
POST /v1/admin/licenses/bulk-revoke
POST /v1/admin/licenses/bulk-export
POST /v1/admin/customers/bulk-import
GET  /v1/admin/bulk-jobs/{id}
GET  /v1/admin/bulk-jobs/{id}/download
```

**POST /v1/admin/licenses/bulk-issue — Response 202:**

```json
{
    "success": true,
    "data": {
        "job_id": "job_xxx",
        "status": "processing",
        "message": "50 keys are being generated. Check job status for progress."
    }
}
```

---

## 11. License Transfer

### 11.1 Schema

```sql
CREATE TABLE license_transfers (
  id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  license_key_id    UUID REFERENCES license_keys(id) ON DELETE CASCADE,
  from_customer_id  UUID REFERENCES customers(id),
  to_customer_id    UUID REFERENCES customers(id),
  from_org_id       UUID REFERENCES organizations(id),
  to_org_id         UUID REFERENCES organizations(id),
  reason            TEXT,
  status            VARCHAR(32) DEFAULT 'pending',
  initiated_by      UUID REFERENCES admin_users(id),
  completed_at      TIMESTAMPTZ,
  created_at        TIMESTAMPTZ DEFAULT now()
);
```

### 11.2 Transfer Rules

- Chỉ `super_admin` hoặc `billing_ops` được phép initiate transfer.
- Transfer tự động revoke tất cả activations hiện tại của key.
- Không thể transfer key đang ở trạng thái `revoked` hoặc `expired`.
- Ghi `audit_log` đầy đủ before/after state.

### 11.3 Admin API

```
POST /v1/admin/licenses/{id}/transfer
GET  /v1/admin/licenses/{id}/transfers
```

**POST /v1/admin/licenses/{id}/transfer — Request:**

```json
{
    "to_customer_id": "cust_bbb222",
    "reason": "Customer account merge",
    "revoke_activations": true
}
```

---

## 12. Refund & Chargeback

### 12.1 Schema

```sql
CREATE TABLE refunds (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  order_id        UUID REFERENCES orders(id) ON DELETE CASCADE,
  entitlement_id  UUID REFERENCES entitlements(id),
  external_id     VARCHAR(128),
  refund_type     VARCHAR(32) NOT NULL,  -- "full" | "partial"
  amount_cents    INTEGER NOT NULL,
  currency        VARCHAR(8) DEFAULT 'USD',
  reason          VARCHAR(64),           -- "customer_request" | "chargeback" | "fraud"
  status          VARCHAR(32) DEFAULT 'pending',
  auto_revoke     BOOLEAN DEFAULT true,
  initiated_by    VARCHAR(32),           -- "admin" | "billing_webhook" | "system"
  processed_at    TIMESTAMPTZ,
  notes           TEXT,
  created_at      TIMESTAMPTZ DEFAULT now()
);
```

### 12.2 Refund Flow

```
Refund event (manual / billing webhook)
       │
       ▼
Tạo refund record
       │
       ▼
auto_revoke = true?
  ├── YES → revoke entitlement → revoke license keys → revoke activations → notify customer
  └── NO  → ghi record, admin xử lý thủ công
       │
       ▼
Ghi audit_log
```

### 12.3 Admin API

```
POST /v1/admin/orders/{id}/refund
GET  /v1/admin/refunds
GET  /v1/admin/refunds/{id}
```

---

## 13. Invoice & Billing History

### 13.1 Schema

```sql
CREATE TABLE billing_addresses (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  customer_id     UUID REFERENCES customers(id) ON DELETE CASCADE,
  org_id          UUID REFERENCES organizations(id) ON DELETE CASCADE,
  is_default      BOOLEAN DEFAULT false,
  full_name       VARCHAR(255),
  company         VARCHAR(255),
  address_line1   TEXT,
  address_line2   TEXT,
  city            VARCHAR(128),
  state           VARCHAR(128),
  postal_code     VARCHAR(32),
  country         VARCHAR(8),
  tax_id          VARCHAR(64),
  created_at      TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE invoices (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  order_id        UUID REFERENCES orders(id),
  customer_id     UUID REFERENCES customers(id),
  org_id          UUID REFERENCES organizations(id),
  invoice_number  VARCHAR(64) UNIQUE NOT NULL,  -- "INV-2026-00001"
  status          VARCHAR(32) DEFAULT 'issued', -- issued | paid | void | refunded
  subtotal_cents  INTEGER NOT NULL,
  tax_cents       INTEGER DEFAULT 0,
  discount_cents  INTEGER DEFAULT 0,
  total_cents     INTEGER NOT NULL,
  currency        VARCHAR(8) DEFAULT 'USD',
  tax_rate        DECIMAL(5,2) DEFAULT 0,
  billing_address JSONB,
  pdf_url         TEXT,
  issued_at       TIMESTAMPTZ DEFAULT now(),
  due_at          TIMESTAMPTZ,
  paid_at         TIMESTAMPTZ
);

CREATE TABLE invoice_items (
  id               UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  invoice_id       UUID REFERENCES invoices(id) ON DELETE CASCADE,
  description      TEXT NOT NULL,
  quantity         INTEGER DEFAULT 1,
  unit_price_cents INTEGER NOT NULL,
  total_cents      INTEGER NOT NULL,
  plan_id          UUID REFERENCES plans(id)
);
```

### 13.2 API

```
GET  /v1/admin/invoices
GET  /v1/admin/invoices/{id}
POST /v1/admin/invoices/{id}/void
POST /v1/admin/orders/{id}/invoice
GET  /v1/customer/invoices
GET  /v1/customer/invoices/{id}
GET  /v1/customer/invoices/{id}/download
```

---

## 14. Notification Preferences

### 14.1 Schema

```sql
CREATE TABLE notification_preferences (
  id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  customer_id       UUID REFERENCES customers(id) ON DELETE CASCADE,
  notification_code VARCHAR(128) NOT NULL,
  channel           VARCHAR(32) NOT NULL,
  enabled           BOOLEAN DEFAULT true,
  unsubscribe_token VARCHAR(128),
  created_at        TIMESTAMPTZ DEFAULT now(),
  updated_at        TIMESTAMPTZ DEFAULT now(),
  UNIQUE(customer_id, notification_code, channel)
);
```

### 14.2 Notification Types

| Code                       | Mô tả                       | Có thể tắt    |
| -------------------------- | --------------------------- | ------------- |
| `license_expiring_30d`     | License sắp hết hạn 30 ngày | ✅            |
| `license_expiring_7d`      | License sắp hết hạn 7 ngày  | ✅            |
| `license_expired`          | License đã hết hạn          | ✅            |
| `license_revoked`          | License bị thu hồi          | ✅            |
| `activation_new`           | Có activation mới           | ✅            |
| `activation_limit_warning` | Gần đạt giới hạn activation | ✅            |
| `renewal_success`          | Gia hạn thành công          | ✅            |
| `renewal_failed`           | Gia hạn thất bại            | ✅            |
| `refund_processed`         | Refund đã xử lý             | ✅            |
| `security_alert`           | Đăng nhập từ IP lạ          | ❌ (bắt buộc) |

### 14.3 Customer API

```
GET   /v1/customer/notification-preferences
PATCH /v1/customer/notification-preferences
POST  /v1/customer/notification-preferences/unsubscribe
```

---

## 15. Email Verification & Onboarding

### 15.1 Verification Flow

```
POST /v1/customer/auth/register
       │
       ▼
Tạo customer (email_verified_at = NULL)
       │
       ▼
Gửi email verification (token, expires 24h)
       │
       ▼
Customer click link → POST /v1/customer/auth/verify-email
       │
       ▼
email_verified_at = now(), xóa verification_token
       │
       ▼
Trigger onboarding welcome email
```

### 15.2 Onboarding Steps

State lưu trong `customers.metadata.onboarding`:

| Step               | Mô tả                         |
| ------------------ | ----------------------------- |
| `verify_email`     | Xác thực email                |
| `view_dashboard`   | Xem dashboard lần đầu         |
| `activate_license` | Activate license key đầu tiên |
| `setup_billing`    | Thêm billing address          |
| `complete`         | Hoàn thành                    |

### 15.3 API

```
POST /v1/customer/auth/verify-email
POST /v1/customer/auth/resend-verification
GET  /v1/customer/onboarding
POST /v1/customer/onboarding/skip
```

---

## 16. Environment Separation

### 16.1 Schema

```sql
CREATE TABLE environments (
  id                       UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  product_id               UUID REFERENCES products(id) ON DELETE CASCADE,
  name                     VARCHAR(64) NOT NULL,  -- "production" | "staging" | "development"
  slug                     VARCHAR(64) NOT NULL,
  is_production            BOOLEAN DEFAULT false,
  rate_limit_multiplier    DECIMAL(3,2) DEFAULT 1.0,
  heartbeat_interval_hours INTEGER DEFAULT 12,
  grace_period_days        INTEGER DEFAULT 7,
  created_at               TIMESTAMPTZ DEFAULT now(),
  UNIQUE(product_id, slug)
);
```

### 16.2 Environment Rules

- `api_keys.environment` phải khớp với `activations.environment` khi gọi API.
- Staging keys không thể activate trên production domain và ngược lại.
- Rate limit cho staging/dev được nới lỏng (multiplier 10x mặc định).
- License keys có thể tag theo environment qua `license_keys.metadata.environment`.

### 16.3 Admin API

```
GET    /v1/admin/products/{id}/environments
POST   /v1/admin/products/{id}/environments
PATCH  /v1/admin/environments/{id}
```

---

## 17. Multi-currency Pricing

### 17.1 Schema

```sql
CREATE TABLE plan_pricing (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  plan_id       UUID REFERENCES plans(id) ON DELETE CASCADE,
  currency      VARCHAR(8) NOT NULL,
  price_cents   INTEGER NOT NULL,
  is_default    BOOLEAN DEFAULT false,
  valid_from    TIMESTAMPTZ DEFAULT now(),
  valid_until   TIMESTAMPTZ,
  created_at    TIMESTAMPTZ DEFAULT now(),
  UNIQUE(plan_id, currency)
);
```

### 17.2 Currency Resolution

```
Request với currency preference
       │
       ▼
Tìm plan_pricing với currency khớp
  ├── Found → dùng giá đó
  └── Not found → fallback về plan_pricing.is_default = true
```

### 17.3 Admin API

```
GET    /v1/admin/plans/{id}/pricing
POST   /v1/admin/plans/{id}/pricing
PATCH  /v1/admin/plans/{id}/pricing/{currency}
```

---

## 18. Health & Status API

### 18.1 Schema

```sql
CREATE TABLE maintenance_windows (
  id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  title       VARCHAR(255) NOT NULL,
  message     TEXT,
  affects     TEXT[] DEFAULT '{}',  -- ["activation", "validate", "all"]
  starts_at   TIMESTAMPTZ NOT NULL,
  ends_at     TIMESTAMPTZ NOT NULL,
  is_active   BOOLEAN DEFAULT false,
  created_by  UUID REFERENCES admin_users(id),
  created_at  TIMESTAMPTZ DEFAULT now()
);
```

### 18.2 Endpoints

```
GET /v1/health    -- Liveness check, không cần auth
GET /v1/status    -- Platform status + maintenance windows, không cần auth
GET /v1/version   -- API version info
```

**GET /v1/status — Response 200:**

```json
{
    "status": "operational",
    "components": {
        "api": "operational",
        "database": "operational",
        "cache": "operational",
        "email": "operational"
    },
    "maintenance": null
}
```

### 18.3 Maintenance Mode Behavior

- Khi `maintenance_windows.is_active = true` và `affects` chứa `"activation"`: `/activate` trả về `503` với `Retry-After` header.
- Product SDK phải handle `503` bằng cách fallback về local token cache.
- `validate` và `heartbeat` vẫn hoạt động trừ khi `affects = ["all"]`.

---

## 19. GDPR & Data Retention

### 19.1 Schema

```sql
CREATE TABLE data_requests (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  customer_id   UUID REFERENCES customers(id),
  request_type  VARCHAR(32) NOT NULL,  -- "erasure" | "portability" | "rectification"
  status        VARCHAR(32) DEFAULT 'pending',
  requested_at  TIMESTAMPTZ DEFAULT now(),
  completed_at  TIMESTAMPTZ,
  export_url    TEXT,
  notes         TEXT,
  processed_by  UUID REFERENCES admin_users(id)
);

CREATE TABLE data_retention_policies (
  id             UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  data_type      VARCHAR(64) UNIQUE NOT NULL,
  retention_days INTEGER NOT NULL,
  anonymize      BOOLEAN DEFAULT false,
  description    TEXT,
  updated_at     TIMESTAMPTZ DEFAULT now()
);
```

### 19.2 Default Retention Policies

| Data Type            | Retention           | Action     |
| -------------------- | ------------------- | ---------- |
| `audit_logs`         | 2555 ngày (7 năm)   | Giữ nguyên |
| `heartbeat_logs`     | 90 ngày             | Xóa        |
| `activation_events`  | 365 ngày            | Xóa        |
| `notification_logs`  | 180 ngày            | Xóa        |
| `webhook_deliveries` | 90 ngày             | Xóa        |
| `customer_sessions`  | 30 ngày sau expire  | Xóa        |
| `customer_pii`       | Sau erasure request | Anonymize  |

### 19.3 Erasure Logic

PII được anonymize thay vì xóa để giữ tính toàn vẹn của transaction history:

```sql
UPDATE customers SET
  email     = 'deleted_' || id || '@anonymized.invalid',
  full_name = '[DELETED]',
  phone     = NULL,
  metadata  = '{}'
WHERE id = :customer_id;

DELETE FROM customer_sessions        WHERE customer_id = :customer_id;
DELETE FROM customer_oauth_providers WHERE customer_id = :customer_id;
```

### 19.4 API

```
POST /v1/customer/data-requests/erasure
POST /v1/customer/data-requests/portability
GET  /v1/customer/data-requests
GET  /v1/admin/data-requests
POST /v1/admin/data-requests/{id}/process
POST /v1/admin/data-requests/{id}/reject
```

---

## 21. Background Job & Queue Spec

### 21.1 Queue Architecture

```
[API Server] ──publish──> [Message Queue] ──consume──> [Worker Pool]
                               │
                    ┌──────────┼──────────┐
                 queue:high  queue:default  queue:low
                 (critical)  (standard)    (bulk/export)
```

**Queue system:** Redis Queue (via BullMQ / Laravel Queue) hoặc AWS SQS tùy stack.

### 21.2 Job Types & Priority

| Job                        | Queue   | Priority | Retry               | Timeout |
| -------------------------- | ------- | -------- | ------------------- | ------- |
| `SendEmailJob`             | default | normal   | 3 lần               | 30s     |
| `WebhookDeliveryJob`       | default | normal   | 5 lần (exp backoff) | 15s     |
| `LicenseExpiryCheckJob`    | default | normal   | 1 lần               | 60s     |
| `GracePeriodTransitionJob` | high    | high     | 3 lần               | 30s     |
| `BillingWebhookProcessJob` | high    | high     | 5 lần               | 60s     |
| `BulkIssueJob`             | low     | low      | 1 lần               | 300s    |
| `BulkExportJob`            | low     | low      | 1 lần               | 300s    |
| `CustomerImportJob`        | low     | low      | 1 lần               | 300s    |
| `DataRetentionCleanupJob`  | low     | low      | 1 lần               | 600s    |
| `InvoicePdfGenerateJob`    | default | normal   | 3 lần               | 60s     |

### 21.3 Retry Policy

```
Attempt 1 → fail → wait 30s
Attempt 2 → fail → wait 2 min
Attempt 3 → fail → wait 10 min
Attempt 4 → fail → wait 30 min
Attempt 5 → fail → move to Dead Letter Queue (DLQ)
```

### 21.4 Dead Letter Queue (DLQ)

- Jobs thất bại sau max retry được chuyển vào DLQ.
- Admin có thể xem và retry thủ công qua Admin Portal.
- DLQ alert khi có > 10 jobs tồn đọng.

```
GET  /v1/admin/jobs/dlq          -- Danh sách failed jobs
POST /v1/admin/jobs/dlq/{id}/retry   -- Retry thủ công
POST /v1/admin/jobs/dlq/{id}/discard -- Xóa khỏi DLQ
```

### 21.5 Cron Schedule

| Job                        | Schedule       | Mô tả                              |
| -------------------------- | -------------- | ---------------------------------- |
| `LicenseExpiryCheckJob`    | `0 1 * * *`    | 1:00 AM daily — chuyển expired     |
| `GracePeriodTransitionJob` | `*/30 * * * *` | Mỗi 30 phút — check grace          |
| `HeartbeatTimeoutJob`      | `0 * * * *`    | Mỗi giờ — detect missed heartbeat  |
| `ExpiryNotificationJob`    | `0 9 * * *`    | 9:00 AM daily — gửi expiry warning |
| `DataRetentionCleanupJob`  | `0 3 * * 0`    | 3:00 AM Chủ nhật — cleanup cũ      |
| `SubscriptionSyncJob`      | `*/15 * * * *` | Mỗi 15 phút — sync Stripe/Paddle   |

---

## 22. Database Migration Strategy

### 22.1 Migration Tool

Dùng **Laravel migrations** cho codebase hiện tại, nhưng giữ quy ước tên migration theo hướng forward-only và có thứ tự thời gian rõ ràng.

**Naming convention:**

```
database/migrations/
  2026_04_13_000100_create_admin_users_table.php
  2026_04_13_000110_create_license_platform_core_tables.php
  2026_04_14_000180_create_subscriptions_table.php
  2026_04_15_000220_create_pricing_and_gdpr_tables.php
  2026_04_15_000230_create_billing_notification_email_tables.php
  2026_04_15_000240_create_customers_table.php
  2026_04_15_000250_create_inventory_onboarding_tables.php
  2026_04_15_000260_create_webhook_deliveries_table.php
```

**Rules:**

- Mỗi migration file là **forward-only** trong production.
- Không sửa migration đã chạy trên production — tạo migration mới để fix.
- Migration phải mô tả rõ 1 nhóm thay đổi logic liên quan, không trộn quá nhiều domain không liên quan.
- Các migration tạo bảng nên đi theo thứ tự dependency: core → billing → customer → GDPR → observability → ops.

### 22.2 Zero-Downtime Migration Pattern

Cho các bảng lớn (`activations`, `audit_logs`, `heartbeat_logs`, `webhook_deliveries`, `data_requests`):

**Thêm column mới:**

```sql
-- Bước 1: Thêm column nullable (không lock table)
ALTER TABLE activations ADD COLUMN new_field VARCHAR(64);

-- Bước 2: Backfill dữ liệu cũ theo batch
UPDATE activations
SET new_field = 'default'
WHERE id IN (
  SELECT id FROM activations
  WHERE new_field IS NULL
  LIMIT 1000
);
-- Chạy lặp lại đến hết

-- Bước 3: Thêm NOT NULL constraint sau khi backfill xong
ALTER TABLE activations ALTER COLUMN new_field SET NOT NULL;
```

**Index strategy:**

- Tạo index lớn bằng `CONCURRENTLY` khi DB hỗ trợ.
- Chỉ thêm index sau khi query pattern đã ổn định.
- Không combine đổi schema lớn với tạo index nặng trong cùng deploy.

**Đổi tên column:**

```
1. Thêm column mới song song với column cũ
2. Dual-write vào cả hai column trong code
3. Backfill column mới từ column cũ
4. Chuyển read sang column mới
5. Xóa dual-write, chỉ write vào column mới
6. Drop column cũ (migration riêng, sau 1 sprint)
```

### 22.3 Blue-Green Deployment

```
[Load Balancer]
      │
      ├── [Blue: v1.x] ── DB (current schema)
      └── [Green: v2.x] ── DB (new schema, backward compatible)

Deploy flow:
1. Run migration trên DB (backward compatible)
2. Deploy Green với code mới
3. Health check Green
4. Shift traffic 10% → 50% → 100% sang Green
5. Keep Blue warm 30 phút
6. Terminate Blue
```

### 22.4 Rollback Procedure

- Với codebase hiện tại, rollback ưu tiên bằng migration `down()` tương ứng và chỉ dùng cho thay đổi an toàn.
- Rollback chỉ áp dụng cho schema changes không có data loss.
- Nếu migration có data transformation: snapshot DB trước khi chạy.
- Các bảng vừa thêm trong kế hoạch này có rollback path rõ ràng: drop table mới hoặc revert column additions nếu chưa có data dependency chéo.

**Rollback path cho các bảng vừa thêm:**

- `plan_pricing`: drop bảng nếu chưa release dependent write-path.
- `data_requests`: drop bảng nếu request processing chưa được vận hành ở production.
- `data_retention_policies`: drop bảng nếu chưa có job runtime phụ thuộc.
- `environments`: drop bảng nếu chưa gắn vào activation gating.
- `maintenance_windows`: drop bảng nếu chưa có status/activation enforcement.
- `webhook_deliveries`: drop bảng nếu outbound delivery chưa dùng để audit.

```bash
# Rollback 1 version
php artisan migrate:rollback --step=1

# Kiểm tra migration status
php artisan migrate:status

# Chạy migrate lại sau khi fix
php artisan migrate
```

### 22.5 Migration Checklist

- [ ] Migration chạy được trên staging trước production
- [ ] Estimated lock time < 1 giây (dùng `CONCURRENTLY` cho index nếu DB hỗ trợ)
- [ ] Có rollback path rõ ràng trong `down()` hoặc migration kế tiếp
- [ ] Backfill script đã test với dataset lớn
- [ ] Không drop column/table trong cùng sprint với code change
- [ ] Deploy theo thứ tự: schema backward-compatible → code → cleanup → drop legacy fields
- [ ] Các bảng phụ trợ mới (`webhook_deliveries`, `data_requests`, `data_retention_policies`) không bị code production phụ thuộc cứng trước khi rollout hoàn tất

---

## 23. Webhook Signature Verification

### 23.1 Signing Algorithm

Platform dùng **HMAC-SHA256** để sign webhook payload. Secret lưu trong `webhook_configs.secret` dạng encrypted.

```
Signature = HMAC-SHA256(secret, timestamp + "." + payload_body)
```

### 23.2 Request Headers

```http
POST https://your-endpoint.com/webhook
Content-Type: application/json
X-LP-Signature-256: sha256=<hex_digest>
X-LP-Timestamp: 1713000000
X-LP-Event: license.revoked
X-LP-Delivery: del_abc123
```

### 23.3 Verification Flow (phía receiver)

```php
function verifyWebhook(string $payload, string $signature, string $timestamp, string $secret): bool {
    // 1. Kiểm tra timestamp không quá 5 phút (replay attack prevention)
    if (abs(time() - (int)$timestamp) > 300) {
        return false;
    }

    // 2. Tính expected signature
    $expected = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

    // 3. So sánh constant-time để tránh timing attack
    return hash_equals($expected, $signature);
}
```

### 23.4 Retry Policy (outbound)

| Attempt | Delay        | Condition                   |
| ------- | ------------ | --------------------------- |
| 1       | Ngay lập tức | HTTP 2xx = success          |
| 2       | 30 giây      | HTTP 4xx/5xx hoặc timeout   |
| 3       | 5 phút       | Tiếp tục fail               |
| 4       | 30 phút      | Tiếp tục fail               |
| 5       | 2 giờ        | Tiếp tục fail → mark failed |

- Timeout per attempt: 10 giây.
- HTTP 410 Gone: tự động disable webhook config.
- Delivery log lưu trong `webhook_deliveries` (status_code, response_body, attempt_count).

### 23.5 Webhook Event Catalog

| Event                    | Trigger                |
| ------------------------ | ---------------------- |
| `license.issued`         | License key được issue |
| `license.activated`      | Activation đầu tiên    |
| `license.revoked`        | License bị revoke      |
| `license.suspended`      | License bị suspend     |
| `license.expired`        | License hết hạn        |
| `license.renewed`        | License được gia hạn   |
| `activation.created`     | Activation mới         |
| `activation.deactivated` | Deactivation           |
| `entitlement.created`    | Entitlement mới        |
| `entitlement.cancelled`  | Entitlement bị cancel  |
| `refund.processed`       | Refund hoàn tất        |
| `subscription.cancelled` | Subscription bị hủy    |

### 23.6 Admin API

```
GET    /v1/admin/webhooks/{config_id}/deliveries     -- Lịch sử delivery
POST   /v1/admin/webhooks/{config_id}/test           -- Gửi test event
POST   /v1/admin/webhooks/deliveries/{id}/retry      -- Retry thủ công
```

---

## 24. Metered / Usage-Based Licensing

### 24.1 Schema

```sql
-- Định nghĩa metric cần đo per plan
CREATE TABLE usage_metrics (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  product_id      UUID REFERENCES products(id) ON DELETE CASCADE,
  code            VARCHAR(128) NOT NULL,          -- "api_calls" | "seats" | "storage_gb"
  name            VARCHAR(255),
  unit            VARCHAR(64),                    -- "calls" | "users" | "GB"
  aggregation     VARCHAR(32) DEFAULT 'sum',      -- "sum" | "max" | "last"
  reset_period    VARCHAR(32) DEFAULT 'monthly',  -- "daily" | "monthly" | "never"
  created_at      TIMESTAMPTZ DEFAULT now(),
  UNIQUE(product_id, code)
);

-- Giới hạn usage per plan
CREATE TABLE plan_usage_limits (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  plan_id         UUID REFERENCES plans(id) ON DELETE CASCADE,
  metric_id       UUID REFERENCES usage_metrics(id) ON DELETE CASCADE,
  soft_limit      BIGINT,                         -- cảnh báo khi vượt
  hard_limit      BIGINT,                         -- block khi vượt (NULL = unlimited)
  overage_price_cents INTEGER DEFAULT 0,          -- giá per unit khi vượt hard_limit
  UNIQUE(plan_id, metric_id)
);

-- Ghi nhận usage từ product
CREATE TABLE usage_records (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  license_key_id  UUID REFERENCES license_keys(id) ON DELETE CASCADE,
  metric_id       UUID REFERENCES usage_metrics(id),
  activation_id   UUID REFERENCES activations(id),
  quantity        BIGINT NOT NULL,
  recorded_at     TIMESTAMPTZ DEFAULT now(),
  period_start    TIMESTAMPTZ NOT NULL,
  period_end      TIMESTAMPTZ NOT NULL,
  idempotency_key VARCHAR(128) UNIQUE            -- tránh double-count
);

-- Tổng hợp usage theo period (materialized)
CREATE TABLE usage_summaries (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  license_key_id  UUID REFERENCES license_keys(id) ON DELETE CASCADE,
  metric_id       UUID REFERENCES usage_metrics(id),
  period_start    TIMESTAMPTZ NOT NULL,
  period_end      TIMESTAMPTZ NOT NULL,
  total_quantity  BIGINT DEFAULT 0,
  overage_quantity BIGINT DEFAULT 0,
  overage_cents   INTEGER DEFAULT 0,
  updated_at      TIMESTAMPTZ DEFAULT now(),
  UNIQUE(license_key_id, metric_id, period_start)
);
```

### 24.2 Usage Report Flow

```
Product gọi POST /v1/client/usage/record
       │
       ▼
Validate idempotency_key (tránh duplicate)
       │
       ▼
Insert usage_records
       │
       ▼
Update usage_summaries (upsert)
       │
       ▼
Check vs plan_usage_limits
  ├── < soft_limit  → OK
  ├── >= soft_limit → OK + warning flag trong response
  └── >= hard_limit → 403 USAGE_LIMIT_EXCEEDED (nếu overage_price = 0)
                      hoặc OK + ghi overage (nếu overage_price > 0)
```

### 24.3 Client API

```
POST /v1/client/usage/record          -- Ghi nhận usage
GET  /v1/client/usage/summary         -- Xem usage hiện tại của license
```

**POST /v1/client/usage/record — Request:**

```json
{
    "license_key": "PROD1-ABCD2-EFGH3-IJKL4",
    "product_code": "PLUGIN_SEO",
    "metric_code": "api_calls",
    "quantity": 150,
    "idempotency_key": "batch_20260413_001",
    "recorded_at": "2026-04-13T10:00:00Z"
}
```

**Response 200:**

```json
{
    "success": true,
    "data": {
        "accepted": true,
        "current_usage": 4500,
        "soft_limit": 8000,
        "hard_limit": 10000,
        "warning": null
    }
}
```

### 24.4 Admin API

```
GET /v1/admin/licenses/{id}/usage          -- Usage detail per license
GET /v1/admin/reports/usage                -- Usage report toàn platform
GET /v1/admin/reports/overage              -- Danh sách licenses vượt giới hạn
```

### 24.5 Error Code bổ sung

| Code                     | HTTP | Ý nghĩa                                   |
| ------------------------ | ---- | ----------------------------------------- |
| `USAGE_LIMIT_EXCEEDED`   | 403  | Vượt hard limit, không có overage pricing |
| `DUPLICATE_USAGE_RECORD` | 409  | idempotency_key đã tồn tại                |

---

## 25. Reseller & Partner

### 25.1 Schema

```sql
CREATE TABLE resellers (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name            VARCHAR(255) NOT NULL,
  slug            VARCHAR(128) UNIQUE NOT NULL,
  contact_email   VARCHAR(255) NOT NULL,
  commission_type VARCHAR(32) DEFAULT 'percent',  -- "percent" | "fixed_per_key"
  commission_value INTEGER DEFAULT 0,             -- 20 (%) hoặc 500 (cents per key)
  status          VARCHAR(32) DEFAULT 'active',   -- active | suspended | terminated
  metadata        JSONB DEFAULT '{}',
  created_at      TIMESTAMPTZ DEFAULT now(),
  updated_at      TIMESTAMPTZ DEFAULT now()
);

-- Reseller được phép bán plan nào
CREATE TABLE reseller_plans (
  reseller_id     UUID REFERENCES resellers(id) ON DELETE CASCADE,
  plan_id         UUID REFERENCES plans(id) ON DELETE CASCADE,
  custom_price_cents INTEGER,                     -- NULL = dùng giá gốc
  max_keys        INTEGER,                        -- NULL = unlimited
  PRIMARY KEY (reseller_id, plan_id)
);

-- Pool key được cấp cho reseller
CREATE TABLE reseller_key_pools (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  reseller_id     UUID REFERENCES resellers(id) ON DELETE CASCADE,
  plan_id         UUID REFERENCES plans(id),
  total_keys      INTEGER NOT NULL,
  used_keys       INTEGER DEFAULT 0,
  expires_at      TIMESTAMPTZ,
  created_by      UUID REFERENCES admin_users(id),
  created_at      TIMESTAMPTZ DEFAULT now()
);

-- Mapping key đã phân phối bởi reseller
CREATE TABLE reseller_key_assignments (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  pool_id         UUID REFERENCES reseller_key_pools(id) ON DELETE CASCADE,
  license_key_id  UUID REFERENCES license_keys(id),
  assigned_to_email VARCHAR(255),
  assigned_at     TIMESTAMPTZ DEFAULT now()
);

-- Reseller admin users
CREATE TABLE reseller_users (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  reseller_id     UUID REFERENCES resellers(id) ON DELETE CASCADE,
  email           VARCHAR(255) UNIQUE NOT NULL,
  full_name       VARCHAR(255),
  password_hash   VARCHAR(256),
  role            VARCHAR(32) DEFAULT 'member',   -- owner | member
  created_at      TIMESTAMPTZ DEFAULT now()
);
```

### 25.2 Reseller Portal API

```
-- Auth
POST /v1/reseller/auth/login
POST /v1/reseller/auth/logout

-- Key management
GET  /v1/reseller/pools                    -- Key pools được cấp
GET  /v1/reseller/pools/{id}/keys          -- Keys trong pool
POST /v1/reseller/pools/{id}/assign        -- Assign key cho end-customer
GET  /v1/reseller/assignments              -- Lịch sử phân phối

-- Reports
GET  /v1/reseller/reports/activations      -- Activation rate của keys đã phân phối
GET  /v1/reseller/reports/commissions      -- Hoa hồng tích lũy
```

### 25.3 Admin API

```
GET    /v1/admin/resellers
POST   /v1/admin/resellers
PATCH  /v1/admin/resellers/{id}
POST   /v1/admin/resellers/{id}/suspend
POST   /v1/admin/resellers/{id}/pools      -- Cấp key pool cho reseller
GET    /v1/admin/resellers/{id}/commissions
```

---

## 26. IP Allowlist & Blocklist

### 26.1 Schema

```sql
-- Per-license IP allowlist (chỉ cho phép activate từ IP/CIDR cụ thể)
CREATE TABLE license_ip_allowlists (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  license_key_id  UUID REFERENCES license_keys(id) ON DELETE CASCADE,
  cidr            CIDR NOT NULL,                  -- "1.2.3.4/32" hoặc "10.0.0.0/8"
  label           VARCHAR(128),                   -- "Office network", "Server DC1"
  created_by      UUID REFERENCES admin_users(id),
  created_at      TIMESTAMPTZ DEFAULT now()
);

-- Platform-wide IP blocklist (block activation/validate từ IP này)
CREATE TABLE ip_blocklist (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  cidr            CIDR NOT NULL UNIQUE,
  reason          VARCHAR(128),                   -- "fraud" | "abuse" | "chargeback"
  expires_at      TIMESTAMPTZ,                    -- NULL = permanent
  created_by      UUID REFERENCES admin_users(id),
  created_at      TIMESTAMPTZ DEFAULT now()
);

-- Geo-restriction per plan (block activation từ country cụ thể)
CREATE TABLE plan_geo_restrictions (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  plan_id         UUID REFERENCES plans(id) ON DELETE CASCADE,
  restriction_type VARCHAR(32) NOT NULL,          -- "allowlist" | "blocklist"
  country_codes   VARCHAR(8)[] NOT NULL,          -- ["US", "CA", "GB"]
  created_at      TIMESTAMPTZ DEFAULT now()
);
```

### 26.2 Enforcement Logic

```
Activation request đến
       │
       ▼
1. Check ip_blocklist → IP bị block? → 403 IP_BLOCKED
       │
       ▼
2. Check plan_geo_restrictions → Country bị restrict? → 403 GEO_RESTRICTED
       │
       ▼
3. Check license_ip_allowlists (nếu có) → IP không trong allowlist? → 403 IP_NOT_ALLOWED
       │
       ▼
4. Proceed với activation flow bình thường
```

### 26.3 Error Codes bổ sung

| Code             | HTTP | Ý nghĩa                             |
| ---------------- | ---- | ----------------------------------- |
| `IP_BLOCKED`     | 403  | IP nằm trong platform blocklist     |
| `IP_NOT_ALLOWED` | 403  | IP không trong license allowlist    |
| `GEO_RESTRICTED` | 403  | Country bị restrict bởi plan policy |

### 26.4 Admin API

```
-- IP Allowlist per license
GET    /v1/admin/licenses/{id}/ip-allowlist
POST   /v1/admin/licenses/{id}/ip-allowlist
DELETE /v1/admin/licenses/{id}/ip-allowlist/{entry_id}

-- Platform blocklist
GET    /v1/admin/ip-blocklist
POST   /v1/admin/ip-blocklist
DELETE /v1/admin/ip-blocklist/{id}

-- Geo restriction per plan
GET    /v1/admin/plans/{id}/geo-restrictions
PUT    /v1/admin/plans/{id}/geo-restrictions
```

---

## 27. SDK Specification

### 27.1 SDK Matrix

| Language        | Package                      | Target                          | Phase   |
| --------------- | ---------------------------- | ------------------------------- | ------- |
| PHP             | `license-platform/php-sdk`   | WordPress plugins, Laravel apps | Phase 1 |
| JavaScript/Node | `@license-platform/node-sdk` | SaaS backend, Next.js           | Phase 2 |
| Python          | `license-platform-sdk`       | Django, FastAPI                 | Phase 3 |
| .NET            | `LicensePlatform.SDK`        | Desktop apps, .NET services     | Phase 3 |

### 27.2 PHP SDK Interface

```php
interface LicensePlatformClientInterface {
    // Core flows
    public function activate(string $licenseKey, string $domain, array $device): ActivationResult;
    public function validate(string $licenseKey, string $activationId, string $domain): ValidationResult;
    public function heartbeat(string $activationId, string $licenseKey, string $domain): HeartbeatResult;
    public function deactivate(string $activationId, string $licenseKey, string $reason): bool;

    // Update check
    public function checkUpdate(string $licenseKey, string $currentVersion, string $domain): UpdateResult;

    // Offline
    public function requestOfflineChallenge(string $licenseKey, string $domain, array $device): ChallengeResult;
    public function confirmOfflineActivation(string $challengeId, string $responseToken): ActivationResult;

    // Usage (metered)
    public function recordUsage(string $licenseKey, string $metricCode, int $quantity, string $idempotencyKey): UsageResult;

    // Coupon
    public function validateCoupon(string $couponCode, string $planCode): CouponResult;
}
```

### 27.3 SDK Versioning Policy

- SDK version theo **SemVer**: `MAJOR.MINOR.PATCH`
- `MAJOR` bump khi breaking change trong API contract
- `MINOR` bump khi thêm feature mới, backward compatible
- `PATCH` bump khi bug fix
- SDK phải support tối thiểu **2 major API versions** cùng lúc
- Deprecation notice tối thiểu **6 tháng** trước khi drop support

### 27.4 SDK Configuration

```php
$client = new LicensePlatformClient([
    'base_url'        => getenv('LICENSE_PLATFORM_URL'),   // required
    'api_key'         => getenv('LICENSE_PLATFORM_KEY'),   // required
    'product_code'    => 'PLUGIN_SEO',                     // required
    'environment'     => 'production',                     // production | staging
    'timeout'         => 10,                               // seconds
    'retry'           => 2,                                // retry on network error
    'cache_driver'    => 'file',                           // file | redis | memory
    'cache_path'      => '/tmp/lp_cache',
    'cache_ttl'       => 86400,                            // 24 hours
    'log_channel'     => 'stderr',                         // stderr | file | null
]);
```

### 27.5 SDK Error Hierarchy

```
LicensePlatformException (base)
  ├── LicenseException
  │     ├── LicenseNotFoundException
  │     ├── LicenseExpiredException
  │     ├── LicenseRevokedException
  │     └── LicenseSuspendedException
  ├── ActivationException
  │     ├── ActivationLimitExceededException
  │     └── ActivationNotFoundException
  ├── NetworkException          -- timeout, connection refused
  ├── AuthException             -- invalid API key
  └── RateLimitException        -- 429 response
```

### 27.6 Changelog & Deprecation

- Mỗi SDK release có `CHANGELOG.md` theo format [Keep a Changelog](https://keepachangelog.com)
- Deprecated methods được đánh dấu `@deprecated` với version và replacement
- `Sunset` header trong API response báo hiệu endpoint sắp bị xóa

---

## 28. Admin MFA (Two-Factor Authentication)

### 28.1 Schema bổ sung

```sql
-- Backup codes cho admin (dùng khi mất thiết bị TOTP)
CREATE TABLE admin_mfa_backup_codes (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  admin_id        UUID REFERENCES admin_users(id) ON DELETE CASCADE,
  code_hash       VARCHAR(256) NOT NULL,          -- bcrypt hash
  used_at         TIMESTAMPTZ,                    -- NULL = chưa dùng
  created_at      TIMESTAMPTZ DEFAULT now()
);
```

`admin_users` đã có `mfa_enabled` và `mfa_secret` (TOTP secret, encrypted at rest).

### 28.2 MFA Setup Flow

```
Admin bật MFA
       │
       ▼
POST /v1/admin/auth/mfa/setup
  → Server generate TOTP secret
  → Trả về: secret (base32), qr_code_url
       │
       ▼
Admin scan QR bằng Authenticator app
       │
       ▼
POST /v1/admin/auth/mfa/verify-setup { totp_code }
  → Verify code hợp lệ
  → Lưu mfa_secret (encrypted), mfa_enabled = true
  → Generate 10 backup codes, trả về 1 lần duy nhất
  → Lưu backup code hashes vào admin_mfa_backup_codes
```

### 28.3 Login Flow với MFA

```
POST /v1/admin/auth/login { email, password }
       │
       ▼
Verify password
       │
       ▼
mfa_enabled = true?
  ├── NO  → Issue JWT → done
  └── YES → Trả về { mfa_required: true, mfa_token: "temp_token_5min" }
                  │
                  ▼
            POST /v1/admin/auth/mfa/challenge { mfa_token, totp_code }
                  │
                  ├── TOTP valid → Issue JWT
                  └── TOTP invalid → 401 (max 5 attempts, sau đó lock 15 phút)
```

### 28.4 Backup Code Flow

```
POST /v1/admin/auth/mfa/challenge { mfa_token, backup_code }
  → Hash backup_code, tìm trong admin_mfa_backup_codes
  → Nếu khớp và chưa dùng: mark used_at = now(), Issue JWT
  → Nếu đã dùng: 401 BACKUP_CODE_ALREADY_USED
```

### 28.5 Admin API

```
POST /v1/admin/auth/mfa/setup           -- Bắt đầu setup, trả về QR
POST /v1/admin/auth/mfa/verify-setup    -- Confirm setup với TOTP code
POST /v1/admin/auth/mfa/challenge       -- Submit TOTP hoặc backup code
POST /v1/admin/auth/mfa/disable         -- Tắt MFA (yêu cầu TOTP confirm)
POST /v1/admin/auth/mfa/regenerate-backup-codes  -- Tạo lại backup codes
```

### 28.6 Security Rules

- TOTP window: ±1 step (30 giây tolerance).
- Backup codes: 10 codes, mỗi code dùng 1 lần.
- Sau 5 lần nhập sai TOTP: lock account 15 phút, gửi email cảnh báo.
- `super_admin` bắt buộc bật MFA — không thể tắt.

---

## 29. Metrics & Dashboard API

### 29.1 Admin Dashboard Endpoints

```
GET /v1/admin/metrics/overview          -- KPI tổng quan
GET /v1/admin/metrics/licenses          -- License metrics
GET /v1/admin/metrics/activations       -- Activation metrics
GET /v1/admin/metrics/revenue           -- Revenue metrics (MRR, ARR, churn)
GET /v1/admin/metrics/usage             -- Usage metrics (metered)
```

**Query params chung:** `?product_id=&from=2026-01-01&to=2026-04-13&granularity=day`

### 29.2 GET /v1/admin/metrics/overview — Response

```json
{
    "success": true,
    "data": {
        "period": { "from": "2026-04-01", "to": "2026-04-13" },
        "licenses": {
            "total_active": 1240,
            "issued_this_period": 87,
            "expiring_30d": 43,
            "revoked_this_period": 5
        },
        "activations": {
            "total_active": 3102,
            "new_this_period": 210,
            "deactivated_this_period": 34
        },
        "revenue": {
            "mrr_cents": 4850000,
            "arr_cents": 58200000,
            "new_mrr_cents": 320000,
            "churned_mrr_cents": 45000
        },
        "health": {
            "grace_period_count": 12,
            "abuse_alerts": 2
        }
    }
}
```

### 29.3 GET /v1/admin/metrics/licenses — Response

```json
{
    "success": true,
    "data": {
        "by_status": {
            "active": 1240,
            "expired": 87,
            "revoked": 23,
            "suspended": 4
        },
        "by_product": [
            { "product_code": "PLUGIN_SEO", "active": 540 },
            { "product_code": "PLUGIN_FORMS", "active": 700 }
        ],
        "trend": [
            { "date": "2026-04-01", "issued": 12, "revoked": 1 },
            { "date": "2026-04-02", "issued": 8, "revoked": 0 }
        ]
    }
}
```

### 29.4 Revenue Metrics Definitions

| Metric          | Công thức                                                        |
| --------------- | ---------------------------------------------------------------- |
| MRR             | Tổng `plans.price_cents` của tất cả active monthly subscriptions |
| ARR             | MRR × 12                                                         |
| New MRR         | MRR từ entitlements mới trong period                             |
| Churned MRR     | MRR từ entitlements cancelled/expired trong period               |
| Net MRR Growth  | New MRR − Churned MRR                                            |
| Activation Rate | (Activated licenses / Issued licenses) × 100%                    |
| Churn Rate      | Churned licenses / Active licenses đầu period                    |

### 29.5 Product-scoped Metrics

`product_admin` chỉ thấy metrics của product được assign. Query tự động filter theo `admin_roles.product_id`.

---

## 30. Notification Localization

### 30.1 Schema bổ sung

```sql
-- Thêm locale vào notification_templates
ALTER TABLE notification_templates
  ADD COLUMN locale VARCHAR(8) DEFAULT 'en',     -- "en" | "vi" | "ja" | "fr"
  ADD COLUMN is_default BOOLEAN DEFAULT false;   -- fallback khi không có locale khớp

-- Unique per code + channel + locale
ALTER TABLE notification_templates
  ADD CONSTRAINT uq_template_locale UNIQUE (code, channel, locale);
```

### 30.2 Locale Resolution

```
Gửi notification cho customer
       │
       ▼
Lấy customer.preferred_language (e.g. "vi")
       │
       ▼
Tìm template với code + channel + locale = "vi"
  ├── Found → dùng template tiếng Việt
  └── Not found → fallback về template với is_default = true (thường là "en")
```

### 30.3 Template Variables

Template dùng **Handlebars** syntax, hỗ trợ các biến chuẩn:

```handlebars
Subject: [{{product_name}}] License của bạn sắp hết hạn Xin chào
{{customer_name}}, License key
{{key_display}}
cho sản phẩm **{{product_name}}** sẽ hết hạn vào ngày **{{expires_at}}**. Gia
hạn ngay tại:
{{renewal_url}}

Trân trọng,
{{platform_name}}
```

**Biến chuẩn có sẵn:**

| Biến                 | Mô tả                             |
| -------------------- | --------------------------------- |
| `{{customer_name}}`  | Tên customer                      |
| `{{customer_email}}` | Email customer                    |
| `{{product_name}}`   | Tên sản phẩm                      |
| `{{plan_name}}`      | Tên plan                          |
| `{{key_display}}`    | License key masked                |
| `{{expires_at}}`     | Ngày hết hạn (format theo locale) |
| `{{renewal_url}}`    | Link gia hạn                      |
| `{{platform_name}}`  | Tên platform                      |
| `{{support_email}}`  | Email support                     |

### 30.4 Admin API

```
GET    /v1/admin/notification-templates                    -- Danh sách templates
POST   /v1/admin/notification-templates                    -- Tạo template mới (kèm locale)
PATCH  /v1/admin/notification-templates/{id}               -- Cập nhật
POST   /v1/admin/notification-templates/{id}/preview       -- Preview render với data mẫu
GET    /v1/admin/notification-templates?code=license_expiring_7d  -- Filter theo code
```

---

## 31. API Versioning & Deprecation Policy

### 31.1 Versioning Strategy

- URL-based versioning: `/v1/`, `/v2/`, ...
- **Minor changes** (thêm field, thêm endpoint): không bump version, backward compatible.
- **Breaking changes** (xóa field, đổi response structure, đổi auth): bump major version.
- Tối thiểu **2 major versions** được support song song.

### 31.2 Breaking Change Definition

Những thay đổi sau được coi là **breaking**:

- Xóa hoặc đổi tên field trong response
- Thay đổi kiểu dữ liệu của field
- Xóa endpoint
- Thay đổi HTTP method của endpoint
- Thay đổi authentication scheme
- Thay đổi error code semantics

Những thay đổi **không breaking**:

- Thêm field mới vào response
- Thêm endpoint mới
- Thêm optional request parameter
- Thêm error code mới

### 31.3 Deprecation Timeline

```
T+0   : Announce deprecation, thêm Deprecation header vào response
T+3M  : Gửi email thông báo cho tất cả api_client đang dùng endpoint cũ
T+6M  : Endpoint trả về warning trong response body
T+9M  : Sunset — endpoint bị xóa, trả về 410 Gone
```

### 31.4 Response Headers

```http
# Endpoint đang bị deprecated
Deprecation: true
Sunset: Sat, 01 Jan 2027 00:00:00 GMT
Link: <https://docs.license-platform.internal/migration/v2>; rel="successor-version"

# Endpoint đã sunset
HTTP/1.1 410 Gone
Content-Type: application/json

{
  "success": false,
  "error": {
    "code": "ENDPOINT_SUNSET",
    "message": "This endpoint was removed on 2027-01-01. Please migrate to /v2/...",
    "migration_guide": "https://docs.license-platform.internal/migration/v2"
  }
}
```

### 31.5 Version Support Matrix

| API Version | Status             | Sunset Date |
| ----------- | ------------------ | ----------- |
| v1          | Active (current)   | TBD         |
| v2          | Planned (Phase 4+) | —           |

### 31.6 SDK Compatibility

- SDK major version = API major version nó support.
- SDK `1.x` → API `/v1/`
- SDK `2.x` → API `/v2/` (backward compat với `/v1/` trong 6 tháng)

---

## 32. Event Stream API

### 32.1 Mô hình

Product có thể nhận license change events theo 2 cách:

| Cách               | Endpoint                               | Use case                         |
| ------------------ | -------------------------------------- | -------------------------------- |
| Polling            | `GET /v1/client/licenses/{key}/events` | Simple, không cần infra          |
| Server-Sent Events | `GET /v1/client/licenses/{key}/stream` | Real-time, long-lived connection |

### 32.2 Schema

```sql
CREATE TABLE license_events (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  license_key_id  UUID REFERENCES license_keys(id) ON DELETE CASCADE,
  event_type      VARCHAR(64) NOT NULL,
  -- license.revoked | license.suspended | license.renewed | policy.updated | activation.revoked
  payload         JSONB DEFAULT '{}',
  occurred_at     TIMESTAMPTZ DEFAULT now()
);

CREATE INDEX idx_license_events_key_time ON license_events(license_key_id, occurred_at DESC);
```

### 32.3 Polling API

```
GET /v1/client/licenses/{key}/events?since=2026-04-13T00:00:00Z&limit=50
```

**Response 200:**

```json
{
    "success": true,
    "data": {
        "events": [
            {
                "id": "evt_xxx",
                "event_type": "policy.updated",
                "payload": { "features": { "MAX_KEYWORDS": "1000" } },
                "occurred_at": "2026-04-13T10:00:00Z"
            }
        ],
        "next_since": "2026-04-13T10:00:01Z"
    }
}
```

### 32.4 Server-Sent Events (SSE)

```
GET /v1/client/licenses/{key}/stream
Authorization: X-API-Key: <key>
Accept: text/event-stream
```

**Stream response:**

```
data: {"event_type":"policy.updated","payload":{"features":{"MAX_KEYWORDS":"1000"}},"occurred_at":"2026-04-13T10:00:00Z"}

data: {"event_type":"license.suspended","payload":{},"occurred_at":"2026-04-13T11:00:00Z"}
```

### 32.5 SDK Integration

```php
// Polling (recommended cho WordPress/PHP)
$events = $client->pollEvents($licenseKey, $lastSyncAt);
foreach ($events as $event) {
    match($event->type) {
        'policy.updated'   => $this->refreshPolicyCache($event->payload),
        'license.revoked'  => $this->blockAccess(),
        'license.suspended'=> $this->showSuspendedNotice(),
        default            => null,
    };
}
```

---

## 33. Affiliate & Referral Program

### 33.1 Schema

```sql
CREATE TABLE affiliates (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  customer_id     UUID REFERENCES customers(id),
  referral_code   VARCHAR(64) UNIQUE NOT NULL,
  commission_type VARCHAR(32) DEFAULT 'percent',  -- "percent" | "fixed"
  commission_value INTEGER DEFAULT 0,
  status          VARCHAR(32) DEFAULT 'active',
  total_referrals INTEGER DEFAULT 0,
  total_earned_cents INTEGER DEFAULT 0,
  created_at      TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE affiliate_referrals (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  affiliate_id    UUID REFERENCES affiliates(id) ON DELETE CASCADE,
  referred_customer_id UUID REFERENCES customers(id),
  order_id        UUID REFERENCES orders(id),
  commission_cents INTEGER DEFAULT 0,
  status          VARCHAR(32) DEFAULT 'pending',  -- pending | approved | paid | rejected
  created_at      TIMESTAMPTZ DEFAULT now()
);
```

### 33.2 API

```
-- Customer
GET  /v1/customer/affiliate              -- Xem referral code và stats
POST /v1/customer/affiliate/join         -- Đăng ký affiliate program

-- Admin
GET  /v1/admin/affiliates
GET  /v1/admin/affiliates/{id}/referrals
POST /v1/admin/affiliates/{id}/payout    -- Mark commission đã thanh toán
```

---

## 34. License Bundling

### 34.1 Schema

```sql
-- Bundle: 1 key unlock nhiều products
CREATE TABLE bundles (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  code            VARCHAR(64) UNIQUE NOT NULL,
  name            VARCHAR(255) NOT NULL,
  is_active       BOOLEAN DEFAULT true,
  created_at      TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE bundle_products (
  bundle_id       UUID REFERENCES bundles(id) ON DELETE CASCADE,
  product_id      UUID REFERENCES products(id) ON DELETE CASCADE,
  plan_id         UUID REFERENCES plans(id),      -- plan áp dụng trong bundle
  PRIMARY KEY (bundle_id, product_id)
);

-- License key có thể thuộc về bundle
ALTER TABLE license_keys ADD COLUMN bundle_id UUID REFERENCES bundles(id);
```

### 34.2 Bundle Activation Flow

Khi activate bundle key, platform tự động tạo activation record cho tất cả products trong bundle. Product validate bình thường bằng `product_code` — không cần biết về bundle.

### 34.3 Admin API

```
GET    /v1/admin/bundles
POST   /v1/admin/bundles
PATCH  /v1/admin/bundles/{id}
POST   /v1/admin/bundles/{id}/products    -- Thêm product vào bundle
DELETE /v1/admin/bundles/{id}/products/{product_id}
```

---

## 35. White-label Support

### 35.1 Schema

```sql
CREATE TABLE white_label_configs (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  reseller_id     UUID REFERENCES resellers(id) ON DELETE CASCADE UNIQUE,
  brand_name      VARCHAR(255),
  logo_url        TEXT,
  primary_color   VARCHAR(16),                    -- "#FF5733"
  custom_domain   VARCHAR(255),                   -- "licenses.partner.com"
  support_email   VARCHAR(255),
  from_email      VARCHAR(255),                   -- email gửi notification
  footer_text     TEXT,
  created_at      TIMESTAMPTZ DEFAULT now(),
  updated_at      TIMESTAMPTZ DEFAULT now()
);
```

### 35.2 White-label Behavior

- Reseller portal hiển thị theo `white_label_configs` của reseller đó.
- Email notifications gửi từ `from_email` với `brand_name` thay vì platform name.
- `custom_domain` trỏ về platform qua CNAME, platform serve đúng branding.
- API responses không thay đổi — chỉ UI và email bị white-label.

### 35.3 Admin API

```
GET    /v1/admin/resellers/{id}/white-label
PUT    /v1/admin/resellers/{id}/white-label
POST   /v1/admin/resellers/{id}/white-label/verify-domain  -- Verify CNAME
```

---

## 36. Compliance Export

### 36.1 Supported Formats

| Report               | Format     | Audience               |
| -------------------- | ---------- | ---------------------- |
| Audit Trail Export   | CSV / JSON | SOC2, ISO27001 auditor |
| License Inventory    | CSV        | Internal compliance    |
| Customer Data Export | JSON       | GDPR portability       |
| Access Log Export    | CSV        | Security audit         |
| Revenue Report       | CSV / PDF  | Finance / tax          |

### 36.2 Admin API

```
POST /v1/admin/compliance/export/audit-logs     -- Export audit logs theo date range
POST /v1/admin/compliance/export/licenses       -- Export license inventory
POST /v1/admin/compliance/export/access-logs    -- Export admin access logs
GET  /v1/admin/compliance/exports               -- Danh sách export jobs
GET  /v1/admin/compliance/exports/{id}/download -- Tải file
```

**POST /v1/admin/compliance/export/audit-logs — Request:**

```json
{
    "from": "2026-01-01T00:00:00Z",
    "to": "2026-04-13T00:00:00Z",
    "format": "csv",
    "filters": {
        "actor_type": "admin",
        "action_prefix": "license."
    }
}
```

### 36.3 Export Security

- Export file được encrypt (AES-256) và lưu tạm thời (24 giờ).
- Download URL có signed token, expire sau 1 giờ.
- Mọi export action được ghi vào `audit_logs`.
- Chỉ `super_admin` được export toàn platform; `product_admin` chỉ export data của product mình.

---

## 37. Observability Stack

### 37.1 Structured Logging

Mọi log phải là JSON, ghi vào stdout. Log aggregator (Loki / CloudWatch / ELK) collect từ stdout.

```json
{
    "timestamp": "2026-04-13T10:00:00.123Z",
    "level": "info",
    "service": "license-api",
    "trace_id": "abc123def456",
    "span_id": "789xyz",
    "request_id": "req_xxx",
    "actor_type": "api_client",
    "actor_id": "product_seo",
    "action": "license.activate",
    "resource_type": "license_key",
    "resource_id": "key_yyy",
    "duration_ms": 45,
    "status": "success",
    "message": "License activated successfully"
}
```

**Log levels:**

| Level   | Khi nào dùng                                         |
| ------- | ---------------------------------------------------- |
| `debug` | Dev/staging only — query params, cache hit/miss      |
| `info`  | Normal operations — activate, validate, heartbeat    |
| `warn`  | Grace period entered, rate limit approaching, retry  |
| `error` | Unhandled exception, DB error, external service fail |
| `fatal` | Service không thể start, critical dependency down    |

### 37.2 Distributed Tracing

`trace_id` được generate tại API Gateway và propagate qua toàn bộ request lifecycle:

```
[API Request] → trace_id: abc123
      │
      ├── [DB Query]        → trace_id: abc123, span_id: span_1
      ├── [Redis Cache]     → trace_id: abc123, span_id: span_2
      └── [Queue Publish]   → trace_id: abc123, span_id: span_3
                │
                └── [Worker Job] → trace_id: abc123, span_id: span_4
                        │
                        └── [Email Send] → trace_id: abc123, span_id: span_5
```

Header propagation: `X-Trace-ID`, `X-Span-ID` (OpenTelemetry compatible).

### 37.3 Prometheus Metrics

Endpoint: `GET /metrics` (internal only, không expose ra ngoài).

**Key metrics:**

```
# Request metrics
http_requests_total{method, endpoint, status_code}
http_request_duration_seconds{method, endpoint, quantile}

# License metrics
license_activations_total{product_code, status}
license_validations_total{product_code, result}
license_heartbeats_total{product_code, status}

# Queue metrics
queue_jobs_pending{queue_name}
queue_jobs_failed_total{job_type}
queue_job_duration_seconds{job_type, quantile}

# Cache metrics
cache_hits_total{cache_key_type}
cache_misses_total{cache_key_type}
cache_hit_ratio{cache_key_type}

# Business metrics
licenses_active_total{product_code}
licenses_grace_total{product_code}
licenses_expired_total{product_code}
```

### 37.4 Alerting Rules

| Alert               | Condition                                     | Severity | Action       |
| ------------------- | --------------------------------------------- | -------- | ------------ |
| High error rate     | `error_rate > 1%` trong 5 phút                | Critical | Page on-call |
| API latency         | `p99 > 2s` trong 5 phút                       | Warning  | Slack notify |
| Queue depth         | `queue_jobs_pending > 1000`                   | Warning  | Slack notify |
| DLQ spike           | `dlq_size > 10`                               | Critical | Page on-call |
| Cache hit ratio     | `cache_hit_ratio < 0.7`                       | Warning  | Slack notify |
| DB connection pool  | `pool_usage > 80%`                            | Warning  | Slack notify |
| License grace spike | `licenses_grace_total tăng > 20%` trong 1 giờ | Warning  | Slack notify |

### 37.5 SLA Targets

| Endpoint         | P50     | P99     | Availability |
| ---------------- | ------- | ------- | ------------ |
| /activate        | < 100ms | < 500ms | 99.9%        |
| /validate        | < 50ms  | < 200ms | 99.9%        |
| /heartbeat       | < 50ms  | < 200ms | 99.9%        |
| Admin API        | < 200ms | < 1s    | 99.5%        |
| Webhook delivery | —       | < 10s   | 99%          |

---

## 38. Security Headers & TLS Policy

### 38.1 Required HTTP Response Headers

Áp dụng cho tất cả API responses và portal pages:

```http
# Prevent clickjacking
X-Frame-Options: DENY

# Prevent MIME sniffing
X-Content-Type-Options: nosniff

# Force HTTPS
Strict-Transport-Security: max-age=31536000; includeSubDomains; preload

# Referrer policy
Referrer-Policy: strict-origin-when-cross-origin

# Permissions policy
Permissions-Policy: geolocation=(), microphone=(), camera=()

# Content Security Policy (Admin Portal)
Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; connect-src 'self' https://license-api.internal

# Remove server fingerprint
Server: (empty hoặc generic)
X-Powered-By: (remove)
```

### 38.2 TLS Policy

```
Minimum TLS version : TLS 1.2
Preferred           : TLS 1.3
Cipher suites (1.2) : TLS_ECDHE_RSA_WITH_AES_256_GCM_SHA384
                      TLS_ECDHE_RSA_WITH_AES_128_GCM_SHA256
Disabled            : SSLv3, TLS 1.0, TLS 1.1, RC4, DES, 3DES
Certificate         : RSA 2048+ hoặc ECDSA P-256
OCSP Stapling       : enabled
```

### 38.3 SDK Certificate Pinning Guidance

```php
// PHP SDK — verify TLS cert, không disable verification
$client = new LicensePlatformClient([
    'verify_ssl' => true,           // KHÔNG đặt false
    'ca_bundle'  => '/path/to/cacert.pem',  // optional custom CA
]);

// KHÔNG làm thế này
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // ❌
```

### 38.4 CORS Policy

```
# Client API (gọi từ server-side, không cần CORS)
Access-Control-Allow-Origin: (không set)

# Admin Portal API
Access-Control-Allow-Origin: https://admin.license-platform.internal
Access-Control-Allow-Methods: GET, POST, PATCH, DELETE
Access-Control-Allow-Headers: Authorization, Content-Type, X-Request-ID
Access-Control-Max-Age: 86400

# Customer Portal API
Access-Control-Allow-Origin: https://portal.license-platform.internal
```

### 38.5 API Key Transmission Security

- `X-API-Key` chỉ được gửi qua HTTPS.
- Không log `X-API-Key` header — middleware phải mask trước khi log.
- Không trả về `X-API-Key` trong response body (chỉ trả về 1 lần khi tạo).
- Rotate key nếu bị expose: `POST /v1/admin/api-keys/{id}/rotate`.

---

## 39. Idempotency Keys — Admin API

### 39.1 Vấn đề

Admin API write operations (issue, revoke, extend, refund) có thể bị gọi 2 lần do:

- Network timeout → client retry
- Double-click trên UI
- Billing webhook delivered twice

Không có idempotency key → duplicate license keys, double refund, double revoke.

### 39.2 Cơ chế

Client gửi `Idempotency-Key` header với mỗi write request. Server lưu kết quả và trả về response giống hệt nếu cùng key được gọi lại.

```http
POST /v1/admin/licenses/issue
Idempotency-Key: ik_20260413_ent_aaa111_issue_001
Content-Type: application/json

{ "entitlement_id": "ent_aaa111", "quantity": 1 }
```

### 39.3 Schema

```sql
CREATE TABLE idempotency_keys (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  key             VARCHAR(255) UNIQUE NOT NULL,
  actor_id        UUID,
  endpoint        VARCHAR(128),
  request_hash    VARCHAR(64),                    -- sha256 của request body
  response_status INTEGER,
  response_body   JSONB,
  expires_at      TIMESTAMPTZ NOT NULL,           -- 24 giờ sau khi tạo
  created_at      TIMESTAMPTZ DEFAULT now()
);

CREATE INDEX idx_idempotency_keys_key ON idempotency_keys(key);
CREATE INDEX idx_idempotency_keys_expires ON idempotency_keys(expires_at);
```

### 39.4 Server Logic

```
Request đến với Idempotency-Key: ik_xxx
       │
       ▼
Tìm trong idempotency_keys WHERE key = 'ik_xxx'
  ├── Found + response_status NOT NULL
  │     → Trả về cached response (không xử lý lại)
  │     → Header: Idempotent-Replayed: true
  │
  ├── Found + response_status IS NULL (đang xử lý)
  │     → 409 Conflict: "Request is being processed"
  │
  └── Not found
        → Insert record (response_status = NULL)
        → Xử lý request
        → Update record với response
        → Trả về response bình thường
```

### 39.5 Endpoints áp dụng

| Endpoint                         | Bắt buộc    | Ghi chú                     |
| -------------------------------- | ----------- | --------------------------- |
| POST /admin/licenses/issue       | ✅          | Tránh duplicate key         |
| POST /admin/licenses/{id}/revoke | ✅          | Tránh double revoke         |
| POST /admin/licenses/{id}/extend | ✅          | Tránh double extend         |
| POST /admin/orders/{id}/refund   | ✅          | Tránh double refund         |
| POST /admin/entitlements         | ✅          | Tránh duplicate entitlement |
| POST /admin/licenses/bulk-issue  | ✅          | Tránh duplicate bulk job    |
| POST /client/licenses/activate   | Recommended | SDK tự generate             |

### 39.6 Error Codes bổ sung

| Code                       | HTTP | Ý nghĩa                             |
| -------------------------- | ---- | ----------------------------------- |
| `IDEMPOTENCY_KEY_CONFLICT` | 409  | Request đang được xử lý với key này |
| `IDEMPOTENCY_KEY_MISMATCH` | 422  | Cùng key nhưng request body khác    |

---

## 40. Trial License Flow

### 40.1 Schema bổ sung

```sql
-- Tracking trial usage per customer/domain để prevent abuse
CREATE TABLE trial_usages (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  product_id      UUID REFERENCES products(id) ON DELETE CASCADE,
  customer_id     UUID REFERENCES customers(id),
  email           VARCHAR(255),                   -- dùng khi chưa có account
  domain          VARCHAR(255),                   -- domain đã dùng trial
  license_key_id  UUID REFERENCES license_keys(id),
  started_at      TIMESTAMPTZ DEFAULT now(),
  expires_at      TIMESTAMPTZ NOT NULL,
  converted_at    TIMESTAMPTZ,                    -- NULL = chưa convert sang paid
  UNIQUE(product_id, email),
  UNIQUE(product_id, domain)
);
```

### 40.2 Trial Creation Flow

```
POST /v1/admin/licenses/trial  (hoặc tự động từ billing webhook)
       │
       ▼
Check trial_usages: email + product_id đã dùng trial chưa?
  ├── Đã dùng → 403 TRIAL_ALREADY_USED
  └── Chưa dùng
        │
        ▼
Tạo entitlement (status=active, expires_at = now() + trial_days)
        │
        ▼
Issue license_key (status=issued, allow_trial=true trong policy)
        │
        ▼
Insert trial_usages record
        │
        ▼
Gửi email welcome + trial key
```

### 40.3 Trial → Paid Conversion

```
Billing webhook: order.created (paid)
       │
       ▼
Tìm trial_usages với email + product_id
  ├── Found → extend entitlement expires_at theo plan mới
  │           update trial_usages.converted_at = now()
  │           giữ nguyên license_key (không issue mới)
  └── Not found → tạo entitlement + issue key mới bình thường
```

### 40.4 Trial Abuse Prevention

| Rule                                       | Action                       |
| ------------------------------------------ | ---------------------------- |
| Cùng email đã dùng trial                   | Block — `TRIAL_ALREADY_USED` |
| Cùng domain đã dùng trial                  | Block — `TRIAL_DOMAIN_USED`  |
| Email domain disposable (mailinator, etc.) | Block — `DISPOSABLE_EMAIL`   |
| Cùng IP đã tạo > 3 trials trong 24h        | Block — `TRIAL_RATE_LIMITED` |

### 40.5 API

```
-- Admin
POST /v1/admin/licenses/trial              -- Tạo trial key thủ công
GET  /v1/admin/reports/trials              -- Trial conversion report

-- Client (self-service trial)
POST /v1/client/licenses/trial             -- Customer tự lấy trial key
```

**POST /v1/client/licenses/trial — Request:**

```json
{
    "product_code": "PLUGIN_SEO",
    "email": "[email]",
    "domain": "example.com"
}
```

**Response 200:**

```json
{
    "success": true,
    "data": {
        "license_key": "TRIAL-ABCD-EFGH-IJKL",
        "expires_at": "2026-05-13T00:00:00Z",
        "trial_days": 30,
        "features": { "EXPORT_CSV": "true", "MAX_KEYWORDS": "100" }
    }
}
```

### 40.6 Error Codes bổ sung

| Code                 | HTTP | Ý nghĩa                             |
| -------------------- | ---- | ----------------------------------- |
| `TRIAL_ALREADY_USED` | 403  | Email đã dùng trial cho product này |
| `TRIAL_DOMAIN_USED`  | 403  | Domain đã dùng trial                |
| `DISPOSABLE_EMAIL`   | 422  | Email domain không được chấp nhận   |

---

## 41. License Upgrade & Downgrade

### 41.1 Upgrade Flow

```
Billing webhook: subscription.upgraded (new_plan_id)
       │
       ▼
Tìm entitlement hiện tại
       │
       ▼
Update entitlement:
  - plan_id = new_plan_id
  - max_activations = new plan value (hoặc giữ override nếu có)
  - expires_at = extend nếu cần
       │
       ▼
Update license_policies snapshot với features mới
       │
       ▼
Invalidate cache: policy:{license_key_id}
       │
       ▼
Publish event: license.upgraded → product nhận qua webhook/event stream
       │
       ▼
Gửi notification: "Your plan has been upgraded to {plan_name}"
```

### 41.2 Downgrade Flow

```
Billing webhook: subscription.downgraded (new_plan_id)
       │
       ▼
Tìm entitlement + license_key hiện tại
       │
       ▼
Tính toán conflict:
  current_activations > new_plan.max_activations?
  ├── YES → Flag license: needs_activation_cleanup = true
  │         Gửi email: "Bạn cần deactivate X sites trước {date}"
  │         Grace period: 7 ngày để tự deactivate
  └── NO  → Proceed ngay
       │
       ▼
Update entitlement + license_policies với plan mới
       │
       ▼
Invalidate cache
       │
       ▼
Publish event: license.downgraded
```

### 41.3 Schema bổ sung

```sql
-- Track pending downgrade conflicts
ALTER TABLE license_keys
  ADD COLUMN needs_activation_cleanup BOOLEAN DEFAULT false,
  ADD COLUMN cleanup_deadline         TIMESTAMPTZ;
```

### 41.4 Downgrade Conflict Resolution

Sau grace period 7 ngày nếu customer chưa tự deactivate:

- Cron job tự động revoke các activations cũ nhất (giữ lại N activations theo plan mới).
- Gửi notification cho từng domain bị revoke.

### 41.5 Admin API

```
POST /v1/admin/licenses/{id}/change-plan   -- Manual upgrade/downgrade
GET  /v1/admin/licenses/{id}/plan-history  -- Lịch sử thay đổi plan
```

**POST /v1/admin/licenses/{id}/change-plan — Request:**

```json
{
  "new_plan_id": "plan_pro_annual",
  "effective": "immediate",           -- "immediate" | "next_billing_cycle"
  "prorate": true
}
```

### 41.6 Error Codes bổ sung

| Code                   | HTTP | Ý nghĩa                                           |
| ---------------------- | ---- | ------------------------------------------------- |
| `PLAN_CHANGE_CONFLICT` | 409  | Downgrade conflict, cần cleanup activations trước |
| `PLAN_NOT_COMPATIBLE`  | 422  | Plan mới không thuộc cùng product                 |

---

## 42. Dunning Management

### 42.1 Schema

```sql
CREATE TABLE dunning_configs (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  product_id      UUID REFERENCES products(id),   -- NULL = platform default
  step            INTEGER NOT NULL,               -- 1, 2, 3, 4
  days_after_due  INTEGER NOT NULL,               -- 1, 3, 7, 14
  action          VARCHAR(32) NOT NULL,           -- "email" | "suspend" | "cancel"
  email_template_code VARCHAR(128),
  created_at      TIMESTAMPTZ DEFAULT now(),
  UNIQUE(product_id, step)
);

CREATE TABLE dunning_logs (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  subscription_id UUID REFERENCES subscriptions(id) ON DELETE CASCADE,
  step            INTEGER NOT NULL,
  action          VARCHAR(32) NOT NULL,
  executed_at     TIMESTAMPTZ DEFAULT now(),
  result          VARCHAR(32),                    -- "sent" | "suspended" | "cancelled" | "recovered"
  notes           TEXT
);
```

### 42.2 Default Dunning Sequence

| Step | Ngày sau past_due | Action          | Mô tả                                   |
| ---- | ----------------- | --------------- | --------------------------------------- |
| 1    | +1 ngày           | email           | Nhắc thanh toán lần 1                   |
| 2    | +3 ngày           | email           | Nhắc lần 2, cảnh báo sắp bị suspend     |
| 3    | +7 ngày           | email + suspend | Suspend license, nhắc lần 3             |
| 4    | +14 ngày          | cancel          | Cancel subscription, revoke entitlement |

### 42.3 Dunning Flow

```
Billing webhook: subscription.payment_failed
       │
       ▼
Update subscriptions.status = 'past_due'
       │
       ▼
Schedule dunning jobs theo dunning_configs
       │
       ▼
DunningStep1Job (T+1 ngày)
  → Gửi email "payment_failed_reminder_1"
  → Insert dunning_logs
       │
DunningStep2Job (T+3 ngày)
  → Gửi email "payment_failed_reminder_2"
       │
DunningStep3Job (T+7 ngày)
  → Suspend license_key
  → Gửi email "license_suspended_payment"
       │
DunningStep4Job (T+14 ngày)
  → Cancel subscription
  → Revoke entitlement
  → Gửi email "subscription_cancelled"

Nếu payment_succeeded bất kỳ lúc nào:
  → Cancel tất cả pending dunning jobs
  → Unsuspend license nếu đang suspended
  → Update subscriptions.status = 'active'
  → Insert dunning_logs { result: 'recovered' }
```

### 42.4 Admin API

```
GET    /v1/admin/dunning/configs           -- Xem cấu hình dunning
PUT    /v1/admin/dunning/configs           -- Cập nhật sequence
GET    /v1/admin/dunning/logs              -- Lịch sử dunning actions
GET    /v1/admin/reports/dunning           -- Dunning recovery rate report
POST   /v1/admin/subscriptions/{id}/retry-payment  -- Trigger retry thủ công
```

---

## 43. Admin Session Management

### 43.1 Schema

```sql
CREATE TABLE admin_sessions (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  admin_id        UUID REFERENCES admin_users(id) ON DELETE CASCADE,
  token_hash      VARCHAR(256) UNIQUE NOT NULL,
  ip_address      INET,
  user_agent      TEXT,
  location        VARCHAR(128),                   -- "Hanoi, VN" (từ IP geolocation)
  expires_at      TIMESTAMPTZ NOT NULL,
  last_active_at  TIMESTAMPTZ DEFAULT now(),
  revoked_at      TIMESTAMPTZ,
  revoke_reason   VARCHAR(64),                    -- "logout" | "admin_revoke" | "suspicious"
  created_at      TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE admin_login_history (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  admin_id        UUID REFERENCES admin_users(id) ON DELETE CASCADE,
  ip_address      INET,
  user_agent      TEXT,
  location        VARCHAR(128),
  success         BOOLEAN NOT NULL,
  failure_reason  VARCHAR(64),                    -- "wrong_password" | "mfa_failed" | "account_locked"
  occurred_at     TIMESTAMPTZ DEFAULT now()
);
```

### 43.2 Session Policy

- JWT access token: TTL 15 phút.
- Refresh token (stored in `admin_sessions`): TTL 8 giờ (hoặc 30 ngày nếu "remember me").
- Max concurrent sessions per admin: 5.
- Session tự động expire sau 30 phút không hoạt động (idle timeout).

### 43.3 Suspicious Activity Detection

```
Mỗi lần login thành công:
       │
       ▼
So sánh IP/location với login gần nhất
  ├── Cùng country → OK
  └── Khác country
        │
        ▼
  Gửi email cảnh báo: "New login from {location}"
  Ghi audit_log: admin.login.new_location
```

**Trigger lock account:**

- 5 lần nhập sai password liên tiếp → lock 15 phút.
- 3 lần nhập sai MFA → lock 15 phút.
- Login từ > 3 quốc gia khác nhau trong 1 giờ → lock + alert `super_admin`.

### 43.4 Admin API

```
GET    /v1/admin/auth/sessions              -- Danh sách active sessions của mình
DELETE /v1/admin/auth/sessions/{id}         -- Revoke session cụ thể
DELETE /v1/admin/auth/sessions              -- Revoke tất cả sessions (trừ current)
GET    /v1/admin/auth/login-history         -- Lịch sử đăng nhập

-- Super admin only
GET    /v1/admin/users/{id}/sessions        -- Xem sessions của admin khác
DELETE /v1/admin/users/{id}/sessions        -- Force logout admin khác
POST   /v1/admin/users/{id}/unlock          -- Mở khóa account bị lock
```

**GET /v1/admin/auth/sessions — Response 200:**

```json
{
    "success": true,
    "data": {
        "sessions": [
            {
                "id": "sess_xxx",
                "ip_address": "1.2.3.4",
                "location": "Hanoi, VN",
                "user_agent": "Chrome/124 on Windows",
                "last_active_at": "2026-04-13T10:00:00Z",
                "is_current": true
            }
        ]
    }
}
```

---

## 44. Platform Configuration

### 44.1 Schema

```sql
CREATE TABLE platform_configs (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  key             VARCHAR(128) UNIQUE NOT NULL,
  value           TEXT NOT NULL,
  value_type      VARCHAR(32) DEFAULT 'string',   -- "string" | "integer" | "boolean" | "json"
  description     TEXT,
  is_sensitive    BOOLEAN DEFAULT false,           -- mask trong logs và API response
  updated_by      UUID REFERENCES admin_users(id),
  updated_at      TIMESTAMPTZ DEFAULT now()
);
```

### 44.2 Default Config Keys

| Key                                | Type    | Default            | Mô tả                                  |
| ---------------------------------- | ------- | ------------------ | -------------------------------------- |
| `default_grace_period_days`        | integer | `7`                | Grace period mặc định cho activation   |
| `default_trial_days`               | integer | `14`               | Trial duration mặc định                |
| `default_heartbeat_interval_hours` | integer | `12`               | Heartbeat interval mặc định            |
| `max_activations_per_license`      | integer | `5`                | Hard cap toàn platform                 |
| `email_sender_name`                | string  | `License Platform` | Tên hiển thị khi gửi email             |
| `email_sender_address`             | string  | `noreply@...`      | Email gửi đi                           |
| `support_email`                    | string  | `support@...`      | Email support hiển thị trong template  |
| `platform_name`                    | string  | `License Platform` | Tên platform trong UI và email         |
| `platform_url`                     | string  | `https://...`      | Base URL của portal                    |
| `trial_abuse_max_per_ip`           | integer | `3`                | Max trial per IP trong 24h             |
| `admin_session_idle_timeout_min`   | integer | `30`               | Idle timeout cho admin session         |
| `admin_max_login_attempts`         | integer | `5`                | Max failed login trước khi lock        |
| `webhook_timeout_seconds`          | integer | `10`               | Timeout per webhook delivery           |
| `webhook_max_retries`              | integer | `5`                | Max retry cho webhook                  |
| `bulk_job_max_items`               | integer | `1000`             | Max items per bulk job                 |
| `maintenance_mode`                 | boolean | `false`            | Bật/tắt maintenance mode toàn platform |
| `feature_flag_metered_licensing`   | boolean | `false`            | Bật/tắt metered licensing feature      |
| `feature_flag_reseller_portal`     | boolean | `false`            | Bật/tắt reseller portal                |
| `feature_flag_affiliate_program`   | boolean | `false`            | Bật/tắt affiliate program              |

### 44.3 Feature Flags

Config keys có prefix `feature_flag_` được dùng để bật/tắt tính năng theo environment:

```php
// Kiểm tra feature flag trước khi xử lý
if (!$config->get('feature_flag_metered_licensing')) {
    throw new FeatureNotEnabledException('Metered licensing is not enabled.');
}
```

### 44.4 Admin API

```
GET    /v1/admin/platform/config           -- Xem tất cả config (sensitive values masked)
PATCH  /v1/admin/platform/config           -- Cập nhật một hoặc nhiều keys
GET    /v1/admin/platform/config/{key}     -- Xem giá trị cụ thể
```

**PATCH /v1/admin/platform/config — Request:**

```json
{
    "configs": [
        { "key": "default_grace_period_days", "value": "14" },
        { "key": "feature_flag_metered_licensing", "value": "true" }
    ]
}
```

> **Lưu ý:** Thay đổi config được ghi vào `audit_logs`. Config sensitive (`is_sensitive = true`) không được trả về plaintext trong API response — chỉ hiển thị `"***"`.

### 44.5 Config Cache

Platform config được cache trong Redis với key `platform_config:{key}`, TTL 5 phút. Khi update config, invalidate cache ngay lập tức.
