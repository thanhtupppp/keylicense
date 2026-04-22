# KeyLicense Admin UI Guideline

## 1. Principle

All admin screens must feel like one system, not a collection of unrelated pages.

## 2. Layout hierarchy

Use this order on every page:

1. `x-admin-layout`
2. optional `x-admin.page-header`
3. `x-ui.flash`
4. domain cards via `x-ui.card`
5. domain form controls via `x-ui.input`, `x-ui.textarea`, `x-ui.select`, `x-ui.checkbox`
6. empty states or skeletons when needed

## 3. Shell rules

- Sidebar and topbar are part of the shell, not page-specific UI.
- Keep content centered in a fixed rhythm container.
- Use consistent max width and spacing across all admin pages.
- Active navigation must be route-aware and visually clear.

## 4. Content rules

- Use `x-admin.page-header` for page titles, descriptions, and header actions.
- Use `x-ui.card` for major sections.
- Avoid raw Tailwind blocks when a reusable component exists.
- Keep each page aligned to a domain-specific component pattern.

## 5. Form rules

Use the standardized form controls:

- `x-ui.input`
- `x-ui.textarea`
- `x-ui.select`
- `x-ui.checkbox`

Rules:

- Every control must expose a clear label.
- Validation error state must be visible.
- Focus state must use the brand accent.
- Prefer `old()` compatibility and safe defaults.

## 6. Feedback rules

- Use `x-ui.alert` for local feedback blocks.
- Use `x-ui.flash` for session-based feedback.
- Success, warning, info, and error states must be visually distinct.

## 7. Empty state rules

- Every empty list/table should show a meaningful empty state.
- Empty states must explain why the screen is empty.
- Empty states should include a next step when possible.
- Use `x-ui.empty-state` with an icon, title, description, and optional CTA slot.

## 8. Loading rules

- Prefer skeletons over spinners for table-heavy pages.
- Use `x-ui.skeleton-table` for index pages and list loading states.
- Loading states should preserve layout height to reduce content shift.

## 9. Audit log rules

- Keep quick actions in the header.
- Keep detailed filters below the header.
- Make dangerous or suspicious events visually obvious.
- Provide an empty state that explains whether there are no logs or no matches.

## 10. Accessibility rules

- Every interactive element must be keyboard accessible.
- Labels must be associated with inputs.
- Buttons and links must have meaningful text.
- Color must never be the only indicator of state.

## 11. Implementation checklist

Before merging a new admin screen:

- [ ] Uses `x-admin-layout`
- [ ] Uses `x-admin.page-header` when needed
- [ ] Uses `x-ui.flash`
- [ ] Uses `x-ui.card`
- [ ] Uses standardized form controls
- [ ] Has a meaningful empty state
- [ ] Has a loading skeleton if the page is data-heavy
- [ ] Lint passes

## 12. Goal

The admin UI should feel consistent, calm, and deliberate. Every screen should look like it belongs to the same product family.
