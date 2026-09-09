# Dynamic Form Reference

Inspect these live files as one contract:

- `Modules/Form/Tools/Form/Builders/FormBuilder.php`: public facade boundary and operation routing.
- `Modules/Form/Tools/Form/Services/FormService.php`: form creation, nested sync, related targets, and version creation.
- `Modules/Form/Tools/Form/Services/FormSubmissionService.php`: submission persistence and dynamic rule composition.
- `Modules/Form/Tools/Form/Services/FormReferenceResolver.php`: inline, enum, and model option display resolution.
- `Modules/Form/app/Http/Requests/FormRequest.php`: nested schema and translatable validation.
- `Modules/Form/app/Http/Requests/FormSubmissionRequest.php`: fixed plus dynamic submission rules.
- `Modules/Form/app/Models/Form.php`, `FormStep.php`, `FormField.php`, `FormSubmission.php`, and `FormSubmissionValue.php`: ownership, translations, ordering, and relations.
- `Modules/Form/config/form_validations.php`: allowed input types and validation schema.
- `Modules/Form/app/Observers/FormObserver.php`: default-step behavior for forms without explicit steps.
- `Modules/Form/app/Http/Resources/Admin`: API representation consumed by the frontend.

Inspect frontend consumers before changing scheme keys, option/reference shape, version behavior, or submission payloads.
