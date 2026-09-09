# Export Reference

Inspect the current implementation before changing an export:

- `config/export.php`: namespaces, routes, controllers, storage, permissions, translation group, PDF settings, and chunking.
- `app/Exports/BaseExportStyle.php`: application-level shared presentation behavior.
- `app/Tools/Export/CauseExport.php`: list-filter parity, morph relation filters, and nested advanced-filter normalization.
- `app/Tools/Export/ProjectExport.php`: translatable columns and relation filtering.
- `Modules/Delegation/app/Tools/Export/DelegationExport.php`: module export, access/search filters, custom date scope, PDF title, enums, and custom relations.
- `lang/ar/export.php` and `lang/en/export.php`: global keys.
- `Modules/Delegation/lang/ar/export.php` and `lang/en/export.php`: module keys including `delegations_title`, columns, enums, dates, and `creator_name`.
- The owning module provider: confirm its export translations are merged into the group used by Export Builder.

Derive required keys from the installed builder and the concrete config rather than assuming that the PHP column name is always the final translation key.
