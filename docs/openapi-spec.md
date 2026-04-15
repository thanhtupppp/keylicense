# License Platform — OpenAPI Specification (MVP)

```yaml
openapi: 3.0.3
info:
    title: Internal Multi-Product License Platform API
    version: 1.0.0-mvp
    description: |
        MVP endpoints for License Platform.
        Scope: health/status/version, admin auth + core provisioning,
        client activation/validation.
servers:
    - url: https://license-api.internal
      description: Internal API Gateway

tags:
    - name: Health
    - name: Admin Auth
    - name: Admin Catalog
    - name: Admin Entitlement
    - name: Admin License
    - name: Client License

security:
    - BearerAuth: []

paths:
    /v1/health:
        get:
            tags: [Health]
            summary: Liveness check
            security: []
            responses:
                "200":
                    description: Service is alive
                    content:
                        application/json:
                            schema:
                                $ref: "#/components/schemas/HealthResponse"

    /v1/status:
        get:
            tags: [Health]
            summary: Platform status and dependencies
            security: []
            responses:
                "200":
                    description: Current status
                    content:
                        application/json:
                            schema:
                                $ref: "#/components/schemas/StatusResponse"

    /v1/version:
        get:
            tags: [Health]
            summary: API version info
            security: []
            responses:
                "200":
                    description: Version metadata
                    content:
                        application/json:
                            schema:
                                $ref: "#/components/schemas/VersionResponse"

    /v1/admin/auth/login:
        post:
            tags: [Admin Auth]
            summary: Admin login
            security: []
            requestBody:
                required: true
                content:
                    application/json:
                        schema:
                            type: object
                            required: [email, password]
                            properties:
                                email:
                                    type: string
                                    format: email
                                    example: admin@internal.local
                                password:
                                    type: string
                                    format: password
                                    example: secret-password
            responses:
                "200":
                    description: Login success
                    content:
                        application/json:
                            schema:
                                $ref: "#/components/schemas/AdminLoginResponse"
                "401":
                    description: Invalid credentials
                    content:
                        application/json:
                            schema:
                                $ref: "#/components/schemas/ErrorEnvelope"

    /v1/admin/products:
        post:
            tags: [Admin Catalog]
            summary: Create product
            security:
                - BearerAuth: []
            requestBody:
                required: true
                content:
                    application/json:
                        schema:
                            $ref: "#/components/schemas/CreateProductRequest"
            responses:
                "201":
                    description: Product created
                    content:
                        application/json:
                            schema:
                                $ref: "#/components/schemas/ProductResponse"
                "400":
                    $ref: "#/components/responses/BadRequest"
                "401":
                    $ref: "#/components/responses/Unauthorized"
                "403":
                    $ref: "#/components/responses/Forbidden"

    /v1/admin/plans:
        post:
            tags: [Admin Catalog]
            summary: Create plan
            security:
                - BearerAuth: []
            requestBody:
                required: true
                content:
                    application/json:
                        schema:
                            $ref: "#/components/schemas/CreatePlanRequest"
            responses:
                "201":
                    description: Plan created
                    content:
                        application/json:
                            schema:
                                $ref: "#/components/schemas/PlanResponse"
                "400":
                    $ref: "#/components/responses/BadRequest"
                "401":
                    $ref: "#/components/responses/Unauthorized"
                "403":
                    $ref: "#/components/responses/Forbidden"

    /v1/admin/entitlements:
        post:
            tags: [Admin Entitlement]
            summary: Create entitlement manually
            security:
                - BearerAuth: []
            requestBody:
                required: true
                content:
                    application/json:
                        schema:
                            $ref: "#/components/schemas/CreateEntitlementRequest"
            responses:
                "201":
                    description: Entitlement created
                    content:
                        application/json:
                            schema:
                                $ref: "#/components/schemas/EntitlementResponse"
                "400":
                    $ref: "#/components/responses/BadRequest"
                "401":
                    $ref: "#/components/responses/Unauthorized"
                "403":
                    $ref: "#/components/responses/Forbidden"

    /v1/admin/licenses/issue:
        post:
            tags: [Admin License]
            summary: Issue license key from entitlement
            security:
                - BearerAuth: []
            requestBody:
                required: true
                content:
                    application/json:
                        schema:
                            $ref: "#/components/schemas/IssueLicenseRequest"
            responses:
                "201":
                    description: License issued
                    content:
                        application/json:
                            schema:
                                $ref: "#/components/schemas/IssueLicenseResponse"
                "400":
                    $ref: "#/components/responses/BadRequest"
                "401":
                    $ref: "#/components/responses/Unauthorized"
                "403":
                    $ref: "#/components/responses/Forbidden"

    /v1/client/licenses/activate:
        post:
            tags: [Client License]
            summary: Activate a license for a device/domain
            security:
                - ApiKeyAuth: []
            requestBody:
                required: true
                content:
                    application/json:
                        schema:
                            $ref: "#/components/schemas/ActivateLicenseRequest"
            responses:
                "200":
                    description: Activation success
                    content:
                        application/json:
                            schema:
                                $ref: "#/components/schemas/ActivateLicenseResponse"
                "403":
                    description: Business rule denied (expired/revoked/suspended/limit exceeded)
                    content:
                        application/json:
                            schema:
                                $ref: "#/components/schemas/ErrorEnvelope"
                "422":
                    $ref: "#/components/responses/UnprocessableEntity"
                "429":
                    $ref: "#/components/responses/RateLimited"

    /v1/client/licenses/validate:
        post:
            tags: [Client License]
            summary: Validate an existing activation/license
            security:
                - ApiKeyAuth: []
            requestBody:
                required: true
                content:
                    application/json:
                        schema:
                            $ref: "#/components/schemas/ValidateLicenseRequest"
            responses:
                "200":
                    description: Validation result
                    content:
                        application/json:
                            schema:
                                $ref: "#/components/schemas/ValidateLicenseResponse"
                "403":
                    description: License not valid for use
                    content:
                        application/json:
                            schema:
                                $ref: "#/components/schemas/ErrorEnvelope"
                "422":
                    $ref: "#/components/responses/UnprocessableEntity"
                "429":
                    $ref: "#/components/responses/RateLimited"

components:
    securitySchemes:
        BearerAuth:
            type: http
            scheme: bearer
            bearerFormat: JWT
        ApiKeyAuth:
            type: apiKey
            in: header
            name: X-API-Key

    responses:
        BadRequest:
            description: Invalid request payload
            content:
                application/json:
                    schema:
                        $ref: "#/components/schemas/ErrorEnvelope"
        Unauthorized:
            description: Authentication required or invalid
            content:
                application/json:
                    schema:
                        $ref: "#/components/schemas/ErrorEnvelope"
        Forbidden:
            description: Permission denied
            content:
                application/json:
                    schema:
                        $ref: "#/components/schemas/ErrorEnvelope"
        UnprocessableEntity:
            description: Domain validation failed
            content:
                application/json:
                    schema:
                        $ref: "#/components/schemas/ErrorEnvelope"
        RateLimited:
            description: Too many requests
            content:
                application/json:
                    schema:
                        $ref: "#/components/schemas/ErrorEnvelope"

    schemas:
        Meta:
            type: object
            properties:
                request_id:
                    type: string
                    example: req_xxx
                timestamp:
                    type: string
                    format: date-time

        ErrorObject:
            type: object
            required: [code, message]
            properties:
                code:
                    type: string
                    example: LICENSE_EXPIRED
                message:
                    type: string
                    example: License key has expired.
                details:
                    type: object
                    additionalProperties: true

        ErrorEnvelope:
            type: object
            required: [success, data, error]
            properties:
                success:
                    type: boolean
                    enum: [false]
                data:
                    nullable: true
                error:
                    $ref: "#/components/schemas/ErrorObject"
                meta:
                    $ref: "#/components/schemas/Meta"

        HealthResponse:
            type: object
            properties:
                status:
                    type: string
                    example: ok

        StatusResponse:
            type: object
            properties:
                status:
                    type: string
                    example: operational
                components:
                    type: object
                    properties:
                        api: { type: string, example: operational }
                        database: { type: string, example: operational }
                        cache: { type: string, example: operational }
                        email: { type: string, example: operational }
                maintenance:
                    nullable: true
                    oneOf:
                        - type: "null"
                        - type: object
                          properties:
                              title: { type: string }
                              starts_at: { type: string, format: date-time }
                              ends_at: { type: string, format: date-time }

        VersionResponse:
            type: object
            properties:
                version:
                    type: string
                    example: v1
                release:
                    type: string
                    example: 2026.04.13

        AdminLoginResponse:
            type: object
            properties:
                success:
                    type: boolean
                    enum: [true]
                data:
                    type: object
                    properties:
                        token:
                            type: string
                            example: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
                        admin:
                            type: object
                            properties:
                                id: { type: string, format: uuid }
                                email: { type: string, format: email }
                                full_name: { type: string }
                        expires_in:
                            type: integer
                            example: 900
                error:
                    nullable: true
                    type: "null"
                meta:
                    $ref: "#/components/schemas/Meta"

        CreateProductRequest:
            type: object
            required: [code, name]
            properties:
                code:
                    type: string
                    maxLength: 64
                    example: PLUGIN_SEO
                name:
                    type: string
                    maxLength: 255
                    example: SEO Plugin Pro
                description:
                    type: string
                category:
                    type: string
                    example: plugin
                status:
                    type: string
                    example: active

        Product:
            type: object
            properties:
                id: { type: string, format: uuid }
                code: { type: string }
                name: { type: string }
                category: { type: string }
                status: { type: string }
                created_at: { type: string, format: date-time }

        ProductResponse:
            type: object
            properties:
                success: { type: boolean, enum: [true] }
                data:
                    type: object
                    properties:
                        product:
                            $ref: "#/components/schemas/Product"
                error:
                    nullable: true
                    type: "null"
                meta:
                    $ref: "#/components/schemas/Meta"

        CreatePlanRequest:
            type: object
            required: [product_id, code, name, billing_cycle, price_cents]
            properties:
                product_id: { type: string, format: uuid }
                code: { type: string, example: SEO_PRO_ANNUAL }
                name: { type: string, example: SEO Pro Annual }
                billing_cycle:
                    type: string
                    enum: [monthly, annual, lifetime, trial]
                price_cents: { type: integer, minimum: 0, example: 9900 }
                currency: { type: string, example: USD, default: USD }
                max_activations: { type: integer, nullable: true, example: 3 }
                max_sites: { type: integer, nullable: true, example: 3 }
                trial_days: { type: integer, minimum: 0, default: 0 }

        Plan:
            type: object
            properties:
                id: { type: string, format: uuid }
                product_id: { type: string, format: uuid }
                code: { type: string }
                name: { type: string }
                billing_cycle: { type: string }
                price_cents: { type: integer }
                currency: { type: string }
                max_activations: { type: integer, nullable: true }
                max_sites: { type: integer, nullable: true }

        PlanResponse:
            type: object
            properties:
                success: { type: boolean, enum: [true] }
                data:
                    type: object
                    properties:
                        plan:
                            $ref: "#/components/schemas/Plan"
                error:
                    nullable: true
                    type: "null"
                meta:
                    $ref: "#/components/schemas/Meta"

        CreateEntitlementRequest:
            type: object
            required: [plan_id, starts_at]
            properties:
                plan_id: { type: string, format: uuid }
                customer_id: { type: string, format: uuid, nullable: true }
                org_id: { type: string, format: uuid, nullable: true }
                starts_at: { type: string, format: date-time }
                expires_at: { type: string, format: date-time, nullable: true }
                auto_renew: { type: boolean, default: false }
                max_activations: { type: integer, nullable: true }
                max_sites: { type: integer, nullable: true }
            description: customer_id hoặc org_id phải có ít nhất một giá trị.

        Entitlement:
            type: object
            properties:
                id: { type: string, format: uuid }
                plan_id: { type: string, format: uuid }
                customer_id: { type: string, format: uuid, nullable: true }
                org_id: { type: string, format: uuid, nullable: true }
                status: { type: string, example: active }
                starts_at: { type: string, format: date-time }
                expires_at: { type: string, format: date-time, nullable: true }

        EntitlementResponse:
            type: object
            properties:
                success: { type: boolean, enum: [true] }
                data:
                    type: object
                    properties:
                        entitlement:
                            $ref: "#/components/schemas/Entitlement"
                error:
                    nullable: true
                    type: "null"
                meta:
                    $ref: "#/components/schemas/Meta"

        IssueLicenseRequest:
            type: object
            required: [entitlement_id, quantity]
            properties:
                entitlement_id: { type: string, format: uuid }
                quantity:
                    { type: integer, minimum: 1, maximum: 100, example: 1 }
                note: { type: string, nullable: true }

        IssueLicenseResponse:
            type: object
            properties:
                success: { type: boolean, enum: [true] }
                data:
                    type: object
                    properties:
                        licenses:
                            type: array
                            items:
                                type: object
                                properties:
                                    id: { type: string, format: uuid }
                                    key_display:
                                        {
                                            type: string,
                                            example: PROD1-****-****-IJKL4,
                                        }
                                    status: { type: string, example: issued }
                                    expires_at:
                                        {
                                            type: string,
                                            format: date-time,
                                            nullable: true,
                                        }
                error:
                    nullable: true
                    type: "null"
                meta:
                    $ref: "#/components/schemas/Meta"

        DeviceInfo:
            type: object
            properties:
                hostname: { type: string, example: example.com }
                ip: { type: string, example: 1.2.3.4 }
                os: { type: string, example: linux }
                php_version: { type: string, example: "8.2" }
                wp_version: { type: string, example: "6.4" }

        ActivateLicenseRequest:
            type: object
            required: [license_key, product_code, domain]
            properties:
                license_key: { type: string, example: PROD1-ABCD2-EFGH3-IJKL4 }
                product_code: { type: string, example: PLUGIN_SEO }
                domain: { type: string, example: example.com }
                app_version: { type: string, example: 2.1.0 }
                environment:
                    type: string
                    enum: [production, staging, development]
                    default: production
                device:
                    $ref: "#/components/schemas/DeviceInfo"

        ActivateLicenseResponse:
            type: object
            properties:
                success: { type: boolean, enum: [true] }
                data:
                    type: object
                    properties:
                        activation_id: { type: string, example: act_abc123 }
                        status: { type: string, example: active }
                        license:
                            type: object
                            properties:
                                key_display:
                                    {
                                        type: string,
                                        example: PROD1-****-****-IJKL4,
                                    }
                                product_code:
                                    { type: string, example: PLUGIN_SEO }
                                plan_code:
                                    { type: string, example: SEO_PRO_ANNUAL }
                                expires_at:
                                    {
                                        type: string,
                                        format: date-time,
                                        nullable: true,
                                    }
                                max_activations:
                                    { type: integer, nullable: true }
                                current_activations: { type: integer }
                        policy:
                            type: object
                            properties:
                                offline_allowed: { type: boolean }
                                grace_period_days: { type: integer }
                                features:
                                    type: object
                                    additionalProperties:
                                        type: string
                        token:
                            type: object
                            properties:
                                value: { type: string }
                                expires_at: { type: string, format: date-time }
                error:
                    nullable: true
                    type: "null"
                meta:
                    $ref: "#/components/schemas/Meta"

        ValidateLicenseRequest:
            type: object
            required: [license_key, product_code, activation_id, domain]
            properties:
                license_key: { type: string }
                product_code: { type: string }
                activation_id: { type: string }
                domain: { type: string }
                app_version: { type: string, nullable: true }

        ValidateLicenseResponse:
            type: object
            properties:
                success: { type: boolean, enum: [true] }
                data:
                    type: object
                    properties:
                        valid: { type: boolean, example: true }
                        status: { type: string, example: active }
                        expires_at:
                            { type: string, format: date-time, nullable: true }
                        features:
                            type: object
                            additionalProperties:
                                type: string
                        message:
                            type: string
                            nullable: true
                error:
                    nullable: true
                    type: "null"
                meta:
                    $ref: "#/components/schemas/Meta"
```

## MVP error codes

- `LICENSE_NOT_FOUND`
- `LICENSE_INVALID`
- `LICENSE_EXPIRED`
- `LICENSE_REVOKED`
- `LICENSE_SUSPENDED`
- `ACTIVATION_LIMIT_EXCEEDED`
- `ACTIVATION_NOT_FOUND`
- `PRODUCT_MISMATCH`
- `FINGERPRINT_MISMATCH`
- `RATE_LIMITED`
- `INTERNAL_ERROR`

## MVP rate limits (default)

- `POST /v1/client/licenses/activate`: 10 req/min per IP
- `POST /v1/client/licenses/validate`: 60 req/min per license key
