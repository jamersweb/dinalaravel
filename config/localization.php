<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Flutter template ARB (English UI strings)
    |--------------------------------------------------------------------------
    |
    | Used by the CMS export endpoint to produce JSON for translation workflows.
    | Default assumes the mobile repo sits next to this API: htdocs/fitnesswithdina_mobile/...
    |
    */
    'flutter_en_arb_path' => env(
        'FLUTTER_EN_ARB_PATH',
        base_path('../fitnesswithdina_mobile/lib/l10n/app_en.arb')
    ),

    /*
    | Directory containing app_<locale>.arb (for UI string editor source files).
    */
    'flutter_l10n_dir' => env(
        'FLUTTER_L10N_DIR',
        base_path('../fitnesswithdina_mobile/lib/l10n')
    ),

    /*
    | Top ~20 widely spoken locales that already exist in Flutter AppLocalizations.
    | Keys are BCP 47 / ARB filenames (app_<code>.arb). Values = native "language" label.
    */
    'top_spoken_arb_locales' => [
        'zh' => '中文',
        'hi' => 'हिन्दी',
        'es' => 'Español',
        'fr' => 'Français',
        'ar' => 'العربية',
        'pt' => 'Português',
        'ru' => 'Русский',
        'id' => 'Bahasa Indonesia',
        'de' => 'Deutsch',
        'ja' => '日本語',
        'tr' => 'Türkçe',
        'ko' => '한국어',
        'vi' => 'Tiếng Việt',
        'it' => 'Italiano',
        'pl' => 'Polski',
        'uk' => 'Українська',
        'th' => 'ไทย',
        'nl' => 'Nederlands',
        'he' => 'עברית',
        'sv' => 'Svenska',
    ],

];
