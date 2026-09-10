<?php

return [
    'name' => 'Form',

    /*
    |--------------------------------------------------------------------------
    | Support Models
    |--------------------------------------------------------------------------
    | Eloquent models that can serve as option sources for form fields
    | using the HelpModel option type. Map a key to the model FQCN.
    */
    'support_models' => [
        // 'countries' => \App\Models\Country::class,
        // 'specializations' => \App\Models\Specialization::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Support Configs
    |--------------------------------------------------------------------------
    | Config keys whose values can be used as option sources for form fields
    | using the HelpConfig option type.
    */
    'support_configs' => [
        // 'nationalities' => 'lookup.nationalities',
    ],

    /*
    |--------------------------------------------------------------------------
    | Support Enums
    |--------------------------------------------------------------------------
    | Enum classes that can serve as option sources for form fields
    | using the HelpEnum option type. Map a key to the enum FQCN.
    */
    'support_enums' => [
        // 'gender' => \App\Enum\Global\UserGenderEnum::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Internal URLs
    |--------------------------------------------------------------------------
    | Internal API endpoints that can serve as option sources for form fields
    | using the InternalUrl option type. Keys must match InternalUrlEnum cases.
    */
    'internal_urls' => [
        'countries' => '/api/countries',
        'specializations' => '/api/specializations',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Enum Namespace
    |--------------------------------------------------------------------------
    | Namespace searched by the FormReferenceResolver when a field reference
    | has no `module`, i.e. the host application's own enums.
    */
    'default_enum_namespace' => 'App\\Enum',
];
