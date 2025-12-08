<?php

/**
 * Language Configuration
 *
 * This file contains the configuration for the available languages in the application.
 * It includes:
 * - An array of available languages.
 * - The default language to be used.
 * - A list of required languages that the application must support.
 *
 * @return array
 */

return [

    // List of languages available in the application
    'available' => [
        'ar', // Arabic
        'en', // English
    ],

    // The default language of the application
    'default' => 'en',

    // List of languages that are required and must be supported by the application
    'required_languages' => [
        'ar', // Arabic
        'en', // English
    ],

    // validation lang
    'languages_validation' => [
        'ar' => 'required',
        'en' => 'sometimes',
    ],
];
