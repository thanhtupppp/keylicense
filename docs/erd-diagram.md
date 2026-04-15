# License Platform — Entity Relationship Diagram
## Version: 1.0 | April 2026

Diagram dùng **Mermaid ERD syntax** — render trực tiếp trong GitHub, GitLab, Notion, hoặc [mermaid.live](https://mermaid.live).

---

## Domain 1 & 2: CATALOG + ENTITLEMENT

```mermaid
erDiagram
    products {
        uuid id PK
        varchar code UK
        varchar name
        varchar category
        varchar status
        jsonb metadata
        timestamptz created_at
        timestamptz updated_at
    }

    product_versions {
        uuid id PK
        uuid product_id FK
        varchar version
        boolean is_latest
        text download_url
        varchar checksum
        timestamptz released_at
    }

    plans {
        uuid id PK
        uuid product_id FK
        varchar code UK
        varchar billing_cycle
        integer price_cents
        integer max_activations
        integer max_sites
        integer trial_days
        boolean is_active
    }

    features {
        uuid id PK
        uuid product_id FK
        varchar code
        varchar feature_type
    }

    plan_features {
        uuid id PK
        uuid plan_id FK
        uuid feature_id FK
        varchar value
    }

    plan_pricing {
        uuid id PK
        uuid plan_id FK
        varchar currency
        integer price_cents
        boolean is_default
    }

    customers {
        uuid id PK
        varchar email UK
        varchar full_name
        timestamptz email_verified_at
        boolean mfa_enabled
        varchar preferred_language
        boolean onboarding_completed
    }

    organizations {
        uuid id PK
        varchar slug UK
        uuid owner_id FK
        varchar billing_email
    }

    organization_members {
        uuid id PK
        uuid org_id FK
        uuid customer_id FK
        varchar role
    }

    orders {
        uuid id PK
        varchar reference UK
        uuid customer_id FK
        uuid org_id FK
        varchar source
        integer total_cents
        varchar status
    }

    entitlements {
        uuid id PK
        uuid order_id FK
        uuid plan_id FK
        uuid customer_id FK
        uuid org_id FK
        varchar status
        timestamptz starts_at
        timestamptz expires_at
        integer max_activations
    }

    subscriptions {
        uuid id PK
        uuid entitlement_id FK
        uuid customer_id FK
        varchar external_id
        varchar source
        varchar status
        timestamptz current_period_end
        boolean cancel_at_period_end
    }

    coupons {
        uuid id PK
        varchar code UK
        varchar discount_type
        integer discount_value
        integer max_uses
        boolean is_active
    }

    coupon_usages {
        uuid id PK
        uuid coupon_id FK
        uuid customer_id FK
        uuid order_id FK
        integer discount_applied
    }

    trial_usages {
        uuid id PK
        uuid product_id FK
        uuid customer_id FK
        varchar email
        varchar domain
        uuid license_key_id FK
        timestamptz expires_at
        timestamptz converted_at
    }

    products ||--o{ product_versions : "has"
    products ||--o{ plans : "has"
    products ||--o{ features : "has"
    plans ||--o{ plan_features : "has"
    features ||--o{ plan_features : "in"
    plans ||--o{ plan_pricing : "priced_by"
    customers ||--o{ organization_members : "belongs_to"
    organizations ||--o{ organization_members : "has"
    customers ||--o{ orders : "places"
    orders ||--o{ entitlements : "creates"
    plans ||--o{ entitlements : "defines"
    entitlements ||--o{ subscriptions : "tracked_by"
    coupons ||--o{ coupon_usages : "used_in"
    customers ||--o{ coupon_usages : "uses"
    products ||--o{ trial_usages : "tracks"
```

---

## Domain 3 & 4: LICENSE + ACTIVATION

```mermaid
erDiagram
    license_keys {
        uuid id PK
        uuid entitlement_id FK
        varchar key_hash UK
        varchar key_display UK
        varchar key_prefix
        uuid product_id FK
        uuid plan_id FK
        uuid customer_id FK
        uuid bundle_id FK
        varchar status
        timestamptz expires_at
        timestamptz revoked_at
        boolean needs_activation_cleanup
    }

    license_policies {
        uuid id PK
        uuid license_key_id FK
        integer max_activations
        integer grace_period_days
        boolean offline_allowed
        integer offline_max_days
        jsonb features
    }

    license_tokens {
        uuid id PK
        uuid activation_id FK
        varchar token_hash
        timestamptz expires_at
        timestamptz revoked_at
    }

    license_transfers {
        uuid id PK
        uuid license_key_id FK
        uuid from_customer_id FK
        uuid to_customer_id FK
        varchar status
        timestamptz completed_at
    }

    license_ip_allowlists {
        uuid id PK
        uuid license_key_id FK
        cidr cidr
        varchar label
    }

    license_events {
        uuid id PK
        uuid license_key_id FK
        varchar event_type
        jsonb payload
        timestamptz occurred_at
    }

    device_fingerprints {
        uuid id PK
        varchar fingerprint_hash UK
        varchar hostname
        varchar domain
        inet ip_address
        varchar os_type
        varchar php_version
        varchar wp_version
    }

    activations {
        uuid id PK
        uuid license_key_id FK
        uuid fingerprint_id FK
        varchar domain
        varchar status
        timestamptz activated_at
        timestamptz last_heartbeat
        boolean is_offline
        timestamptz offline_expires_at
    }

    activation_events {
        uuid id PK
        uuid activation_id FK
        varchar event_type
        inet ip_address
        jsonb detail
        timestamptz occurred_at
    }

    heartbeat_logs {
        uuid id PK
        uuid activation_id FK
        varchar status
        inet ip_address
        timestamptz logged_at
    }

    usage_metrics {
        uuid id PK
        uuid product_id FK
        varchar code
        varchar unit
        varchar aggregation
        varchar reset_period
    }

    usage_records {
        uuid id PK
        uuid license_key_id FK
        uuid metric_id FK
        uuid activation_id FK
        bigint quantity
        varchar idempotency_key UK
        timestamptz period_start
        timestamptz period_end
    }

    usage_summaries {
        uuid id PK
        uuid license_key_id FK
        uuid metric_id FK
        timestamptz period_start
        bigint total_quantity
        bigint overage_quantity
        integer overage_cents
    }

    license_keys ||--|| license_policies : "has"
    license_keys ||--o{ license_tokens : "issues"
    license_keys ||--o{ activations : "has"
    license_keys ||--o{ license_transfers : "transferred_via"
    license_keys ||--o{ license_ip_allowlists : "restricted_by"
    license_keys ||--o{ license_events : "emits"
    license_keys ||--o{ usage_records : "tracks"
    license_keys ||--o{ usage_summaries : "summarized_in"
    activations ||--|| device_fingerprints : "identified_by"
    activations ||--o{ activation_events : "logs"
    activations ||--o{ heartbeat_logs : "pings"
    activations ||--o{ license_tokens : "owns"
```

---

## Domain 5: GOVERNANCE

```mermaid
erDiagram
    admin_users {
        uuid id PK
        varchar email UK
        varchar password_hash
        varchar status
        boolean mfa_enabled
        varchar mfa_secret
        timestamptz last_login_at
    }

    roles {
        uuid id PK
        varchar code UK
        varchar name
    }

    permissions {
        uuid id PK
        varchar code UK
        varchar resource
        varchar action
    }

    role_permissions {
        uuid role_id FK
        uuid permission_id FK
    }

    admin_roles {
        uuid admin_id FK
        uuid role_id FK
        uuid product_id FK
    }

    audit_logs {
        uuid id PK
        varchar actor_type
        uuid actor_id
        varchar actor_email
        varchar action
        varchar resource_type
        uuid resource_id
        inet ip_address
        jsonb before_state
        jsonb after_state
        timestamptz occurred_at
    }

    admin_sessions {
        uuid id PK
        uuid admin_id FK
        varchar token_hash UK
        inet ip_address
        varchar location
        timestamptz expires_at
        timestamptz last_active_at
        timestamptz revoked_at
    }

    admin_login_history {
        uuid id PK
        uuid admin_id FK
        inet ip_address
        varchar location
        boolean success
        varchar failure_reason
        timestamptz occurred_at
    }

    admin_mfa_backup_codes {
        uuid id PK
        uuid admin_id FK
        varchar code_hash
        timestamptz used_at
    }

    idempotency_keys {
        uuid id PK
        varchar key UK
        uuid actor_id
        varchar endpoint
        integer response_status
        jsonb response_body
        timestamptz expires_at
    }

    admin_users ||--o{ admin_roles : "assigned"
    roles ||--o{ admin_roles : "used_in"
    roles ||--o{ role_permissions : "has"
    permissions ||--o{ role_permissions : "granted_via"
    admin_users ||--o{ admin_sessions : "has"
    admin_users ||--o{ admin_login_history : "logs"
    admin_users ||--o{ admin_mfa_backup_codes : "has"
```

---

## Domain 6: NOTIFICATION

```mermaid
erDiagram
    notification_templates {
        uuid id PK
        varchar code
        varchar channel
        varchar locale
        boolean is_default
        text subject
        text body_template
        boolean is_active
    }

    notification_logs {
        uuid id PK
        uuid template_id FK
        varchar recipient_email
        varchar resource_type
        uuid resource_id
        varchar status
        timestamptz sent_at
    }

    notification_preferences {
        uuid id PK
        uuid customer_id FK
        varchar notification_code
        varchar channel
        boolean enabled
        varchar unsubscribe_token
    }

    webhook_configs {
        uuid id PK
        uuid product_id FK
        uuid org_id FK
        text url
        varchar secret
        text[] events
        boolean is_active
    }

    webhook_deliveries {
        uuid id PK
        uuid config_id FK
        varchar event_type
        jsonb payload
        integer status_code
        integer attempt_count
        timestamptz delivered_at
    }

    maintenance_windows {
        uuid id PK
        varchar title
        text[] affects
        timestamptz starts_at
        timestamptz ends_at
        boolean is_active
    }

    notification_templates ||--o{ notification_logs : "used_in"
    customers ||--o{ notification_preferences : "sets"
    webhook_configs ||--o{ webhook_deliveries : "tracks"
```

---

## Domain 7: CUSTOMER PORTAL

```mermaid
erDiagram
    customer_oauth_providers {
        uuid id PK
        uuid customer_id FK
        varchar provider
        varchar provider_uid UK
        timestamptz expires_at
    }

    customer_sessions {
        uuid id PK
        uuid customer_id FK
        varchar token_hash UK
        inet ip_address
        timestamptz expires_at
        timestamptz last_active_at
    }

    data_requests {
        uuid id PK
        uuid customer_id FK
        varchar request_type
        varchar status
        timestamptz requested_at
        timestamptz completed_at
        text export_url
    }

    data_retention_policies {
        uuid id PK
        varchar data_type UK
        integer retention_days
        boolean anonymize
    }

    customers ||--o{ customer_oauth_providers : "linked_to"
    customers ||--o{ customer_sessions : "has"
    customers ||--o{ data_requests : "submits"
```

---

## Domain 8: BILLING

```mermaid
erDiagram
    billing_addresses {
        uuid id PK
        uuid customer_id FK
        uuid org_id FK
        boolean is_default
        varchar full_name
        varchar company
        varchar country
        varchar tax_id
    }

    invoices {
        uuid id PK
        uuid order_id FK
        uuid customer_id FK
        varchar invoice_number UK
        varchar status
        integer subtotal_cents
        integer tax_cents
        integer total_cents
        varchar currency
        text pdf_url
        timestamptz issued_at
        timestamptz paid_at
    }

    invoice_items {
        uuid id PK
        uuid invoice_id FK
        text description
        integer quantity
        integer unit_price_cents
        integer total_cents
        uuid plan_id FK
    }

    refunds {
        uuid id PK
        uuid order_id FK
        uuid entitlement_id FK
        varchar refund_type
        integer amount_cents
        varchar reason
        varchar status
        boolean auto_revoke
        timestamptz processed_at
    }

    dunning_configs {
        uuid id PK
        uuid product_id FK
        integer step
        integer days_after_due
        varchar action
        varchar email_template_code
    }

    dunning_logs {
        uuid id PK
        uuid subscription_id FK
        integer step
        varchar action
        timestamptz executed_at
        varchar result
    }

    customers ||--o{ billing_addresses : "has"
    orders ||--o{ invoices : "generates"
    invoices ||--o{ invoice_items : "contains"
    orders ||--o{ refunds : "refunded_via"
    subscriptions ||--o{ dunning_logs : "tracked_by"
```

---

## Domain 9: PLATFORM & RESELLER

```mermaid
erDiagram
    api_keys {
        uuid id PK
        uuid product_id FK
        varchar name
        varchar key_hash UK
        varchar key_prefix
        varchar environment
        text[] scopes
        timestamptz last_used_at
        timestamptz expires_at
        timestamptz revoked_at
    }

    environments {
        uuid id PK
        uuid product_id FK
        varchar name
        varchar slug
        boolean is_production
        decimal rate_limit_multiplier
        integer heartbeat_interval_hours
    }

    platform_configs {
        uuid id PK
        varchar key UK
        text value
        varchar value_type
        boolean is_sensitive
        timestamptz updated_at
    }

    ip_blocklist {
        uuid id PK
        cidr cidr UK
        varchar reason
        timestamptz expires_at
    }

    plan_geo_restrictions {
        uuid id PK
        uuid plan_id FK
        varchar restriction_type
        varchar[] country_codes
    }

    resellers {
        uuid id PK
        varchar slug UK
        varchar contact_email
        varchar commission_type
        integer commission_value
        varchar status
    }

    reseller_plans {
        uuid reseller_id FK
        uuid plan_id FK
        integer custom_price_cents
        integer max_keys
    }

    reseller_key_pools {
        uuid id PK
        uuid reseller_id FK
        uuid plan_id FK
        integer total_keys
        integer used_keys
        timestamptz expires_at
    }

    reseller_users {
        uuid id PK
        uuid reseller_id FK
        varchar email UK
        varchar role
    }

    white_label_configs {
        uuid id PK
        uuid reseller_id FK
        varchar brand_name
        varchar custom_domain
        varchar support_email
    }

    affiliates {
        uuid id PK
        uuid customer_id FK
        varchar referral_code UK
        varchar commission_type
        integer commission_value
        varchar status
    }

    affiliate_referrals {
        uuid id PK
        uuid affiliate_id FK
        uuid referred_customer_id FK
        uuid order_id FK
        integer commission_cents
        varchar status
    }

    bundles {
        uuid id PK
        varchar code UK
        varchar name
        boolean is_active
    }

    bundle_products {
        uuid bundle_id FK
        uuid product_id FK
        uuid plan_id FK
    }

    products ||--o{ api_keys : "has"
    products ||--o{ environments : "has"
    plans ||--o{ plan_geo_restrictions : "restricted_by"
    resellers ||--o{ reseller_plans : "sells"
    resellers ||--o{ reseller_key_pools : "holds"
    resellers ||--o{ reseller_users : "has"
    resellers ||--|| white_label_configs : "branded_by"
    customers ||--o{ affiliates : "becomes"
    affiliates ||--o{ affiliate_referrals : "generates"
    bundles ||--o{ bundle_products : "contains"
    products ||--o{ bundle_products : "included_in"
```

---

## Full Entity Count

| Domain | Tables |
|---|---|
| CATALOG | products, product_versions, plans, features, plan_features, plan_pricing |
| ENTITLEMENT | customers, organizations, organization_members, orders, entitlements, subscriptions, coupons, coupon_usages, trial_usages |
| LICENSE | license_keys, license_policies, license_tokens, license_transfers, license_ip_allowlists, license_events |
| ACTIVATION | device_fingerprints, activations, activation_events, heartbeat_logs, usage_metrics, usage_records, usage_summaries |
| GOVERNANCE | admin_users, roles, permissions, role_permissions, admin_roles, audit_logs, admin_sessions, admin_login_history, admin_mfa_backup_codes, idempotency_keys |
| NOTIFICATION | notification_templates, notification_logs, notification_preferences, webhook_configs, webhook_deliveries, maintenance_windows |
| CUSTOMER PORTAL | customer_oauth_providers, customer_sessions, data_requests, data_retention_policies |
| BILLING | billing_addresses, invoices, invoice_items, refunds, dunning_configs, dunning_logs |
| PLATFORM | api_keys, environments, platform_configs, ip_blocklist, plan_geo_restrictions |
| RESELLER | resellers, reseller_plans, reseller_key_pools, reseller_users, white_label_configs |
| AFFILIATE | affiliates, affiliate_referrals |
| BUNDLE | bundles, bundle_products |
| **Total** | **~55 tables** |
