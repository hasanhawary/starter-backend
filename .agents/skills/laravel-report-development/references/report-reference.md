# Report Reference

Use the live implementation as the source of truth:

- `app/Tools/Report/BaseReport.php`: card icons, requested periods, qualified date columns, timeline resolution, and gap filling.
- `app/Tools/Report/CauseReport.php`: cards, distinct counts across joins, advanced filters, totals, and continuous charts.
- `Modules/Delegation/app/Tools/Report/DelegationReport.php`: module access/search filters, enum-complete buckets, and driver-aware date grouping.
- `Modules/Delegation/config/report.php`: module component registration.
- `Modules/Delegation/app/Providers/DelegationServiceProvider.php`: module report config and translation merging.
- `config/report.php`: global routes, defaults, component behavior, icons, pages, and types.
- `lang/ar/report.php`, `lang/en/report.php`, and module `lang/{locale}/report.php`: exact translation-key contracts.

Before copying a pattern, confirm the target uses the same table shape, date semantics, access rules, supported database driver, and frontend component contract.
