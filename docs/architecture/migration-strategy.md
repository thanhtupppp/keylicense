# Database Migration Strategy

## Principles

- Forward-only in production.
- Never edit a migration that has already run in production.
- Every schema change must be backward compatible during deploy.
- Prefer small, domain-focused migrations.

## Zero-downtime checklist

1. Add nullable columns first.
2. Backfill in batches.
3. Add indexes after data is stable.
4. Add NOT NULL / constraints only after backfill.
5. Deploy code that can read both old and new shapes.
6. Drop legacy columns in a separate release.

## Blue-green procedure

1. Run backward-compatible migration.
2. Deploy green release.
3. Verify health checks.
4. Shift traffic gradually.
5. Keep blue warm for rollback window.
6. Remove blue after stability window.

## Rollback rules

- Only rollback schema-only changes with no data loss.
- For data transformations, snapshot before migration.
- Prefer new corrective migration over reversing destructive changes.

## Operational checklist

- Migration tested on staging.
- Lock time measured.
- Backfill script validated.
- Rollback path documented.
- Related code deployed in correct order.
