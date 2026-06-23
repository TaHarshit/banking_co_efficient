<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Localization Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the localization feature.
    | You can add new languages by adding them to the available_locales array.
    |
    */

    // Available locales with their display names and flag icons
    'available_locales' => [
        'en' => [
            'name' => 'English',
            'native' => 'English',
            'flag' => 'en.svg',
        ],
        'fr' => [
            'name' => 'French',
            'native' => 'Français',
            'flag' => 'fr.svg',
        ],
    ],

    // Default locale
    'default' => 'en',

    // Flag images path (relative to public folder)
    'flags_path' => 'assets/img/flags/',
];
