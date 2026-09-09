---
name: laravel-report-development
description: Create, change, debug, or review Report Builder reports in the configured Laravel backend. Use for report cards, charts, timelines, report filters, permissions, report config, module report registration, or report translations. Do not use for file exports.
---

# Laravel Report Development

Build reports from the same authorization and filtering contract as the source feature, with correct aggregates and stable response keys.

## Authority and Reference

- Follow the root `AGENTS.md`.
- Read [the report implementation reference](references/report-reference.md), the installed `hasanhawary/report-builder` API, `config/report.php`, the nearest report, its list filters/scopes, and its frontend consumer before editing.
- Use `.agents/skills/laravel-export-development/SKILL.md` for exports; a report and an export are separate contracts even when they share filters.

## Query and Access Contract

- Extend the repository's `App\Tools\Report\BaseReport` unless the target module has an established compatible base.
- Apply the same visibility/ownership restrictions as the corresponding index endpoint before user-controlled filters. A report permission alone must not widen record access.
- Reuse existing domain scopes or filter collaborators when they operate on the report query shape. Do not duplicate access rules in raw SQL.
- Whitelist advanced filter keys and qualify joined columns. Never pass arbitrary request keys into `where`, `whereIn`, `orderBy`, `selectRaw`, or grouping expressions.
- Clone a stable base query for related components so filters remain aligned without leaking component-specific joins or conditions.

## Aggregation Correctness

- Use `COUNT(DISTINCT primary.id)` when joins can multiply the source rows. Verify totals and chart buckets against the unjoined source contract.
- Keep date columns qualified and apply one intentional time window. Fill missing timeline points using the shared BaseReport helpers when a continuous series is expected.
- Inspect the database drivers used by runtime and tests before copying `DATE_FORMAT`, `FORMAT`, JSON, or other driver-specific expressions. Add a proportionate compatible expression rather than assuming MySQL everywhere.
- Return numeric values as intentional integers/floats and preserve the builder's card/chart response envelopes, keys, selected period, totals, and icon behavior.
- Avoid N+1 queries and unbounded model hydration for aggregate reports; prefer query-builder aggregates and bounded lookup data.

## Registration and Localization

- Register only real report component methods in the applicable global or module report config.
- For module reports, verify provider config merging and translation merging without overwriting other modules' pages or types.
- Add every new report title, card key, series key, status/type label, and user-facing component label to both Arabic and English report translations. Preserve exact key parity and the builder's expected naming convention.
- Preserve frontend component keys and chart types unless a contract change is explicitly requested.

## Verification

- Test authorization and ownership scope, each filter, soft-deleted behavior, empty datasets, joins with multiple related rows, date boundaries, missing timeline points, totals versus buckets, response keys, and Arabic/English labels as applicable.
- Inspect generated queries for ambiguous columns and multiplication errors, and compare at least one aggregate to a direct source query.
- Finish with focused tests, Pint on only authorized changed PHP files, and EA/PhpStorm inspections when available.
