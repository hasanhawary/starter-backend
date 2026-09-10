---
name: laravel-export-development
description: Create, change, debug, or review Export Builder classes and export endpoints in the configured Laravel backend. Use for export columns, relations, filters, permissions, queued/direct exports, PDF data, module export registration, or export translation keys. Do not use for dashboard reports.
---

# Laravel Export Development

Keep exported data, access, filters, and labels aligned with the source listing and with the current Export Builder contract.

## Authority and Reference

- Follow the root `AGENTS.md`.
- Read [the export implementation reference](references/export-reference.md), the backend-local `ai-pack/skills/export-pattern/SKILL.md`, the installed `hasanhawary/export-builder` API, `config/export.php`, and the nearest live export before editing.
- Use `.agents/skills/laravel-report-development/SKILL.md` for Report Builder components.

## Export Contract

- Reuse the current `BaseExport` or `BaseExportStyle` family and its filter payload; do not introduce parallel export infrastructure.
- Match the source endpoint's visibility scope, search, advanced filters, date semantics, soft-delete behavior, enum meaning, and relevant relations. An export permission must not widen data access.
- Keep columns and relation modes declarative. Use the builder's `one`, `many`, `count`, `list`, or `concat` conventions and inspect the installed package before inventing keys.
- Qualify relation filter columns and preserve morph constraints. Normalize complex filter payloads only where the corresponding listing has the same semantics.
- Preserve direct versus queued behavior, status/download responses, storage ownership, PDF metadata, and public page identifiers unless the task explicitly changes them.

## Translation-Key Completeness

- Determine the exact label key the installed builder derives for every exported column, custom relation, boolean, enum, and title.
- Add every required key to both `lang/ar/export.php` and `lang/en/export.php`, or to both module export files when the module owns the labels. Keys must have exact Arabic/English parity; do not rely on raw-key fallback.
- Include relation-derived keys such as `creator_name` and the expected page title key such as `{page}_title` when the builder requests them.
- For module exports, verify the provider merges module export translations into the group the builder actually reads. A translated file that is never merged is incomplete.
- Keep enum values stable and use their existing bilingual enum labels; do not translate stored values inside the export class.

## Verification

- Test permission and ownership boundaries, listing/export filter parity, column order and values, translated model attributes, enum/boolean/date formatting, relation columns, empty output, direct/queued behavior, and download response as applicable.
- Run a programmatic key-parity check for the affected Arabic and English export files and verify every configured output key resolves in both locales.
- Finish with focused tests, Pint on only authorized changed PHP files, and EA/PhpStorm inspections when available.
