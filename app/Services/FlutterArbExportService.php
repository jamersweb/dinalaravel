<?php

namespace App\Services;

use App\Models\AppUiStringTranslation;
use Illuminate\Support\Str;

class FlutterArbExportService
{
    /**
     * @return array<string, string> key order preserved from template
     */
    public function loadTemplateStrings(): array
    {
        $path = config('localization.flutter_en_arb_path');
        if (! is_string($path) || $path === '' || ! is_readable($path)) {
            return [];
        }
        $raw = file_get_contents($path);
        if ($raw === false) {
            return [];
        }
        $data = json_decode($raw, true);
        if (! is_array($data)) {
            return [];
        }
        $strings = [];
        foreach ($data as $key => $value) {
            if (! is_string($key) || $key === '' || Str::startsWith($key, '@')) {
                continue;
            }
            if (is_string($value)) {
                $strings[$key] = $value;
            }
        }

        return $strings;
    }

    /**
     * Merge English template with CMS DB overrides, then existing app_<locale>.arb on disk, then EN.
     *
     * @return array<string, string>
     */
    public function mergedForLocale(string $locale, string $languageDisplayName): array
    {
        $locale = strtolower(trim($locale));
        $template = $this->loadTemplateStrings();
        if ($template === []) {
            return [];
        }

        $keys = array_keys($template);
        $db = AppUiStringTranslation::query()
            ->where('locale', $locale)
            ->whereIn('message_key', $keys)
            ->pluck('value', 'message_key')
            ->all();

        $disk = [];
        $dir = config('localization.flutter_l10n_dir');
        if (is_string($dir) && $dir !== '') {
            $arbPath = rtrim($dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'app_'.$locale.'.arb';
            if (is_readable($arbPath)) {
                $disk = $this->stringsFromArbPath($arbPath);
            }
        }

        $out = [];
        foreach ($template as $key => $enValue) {
            if ($key === 'language') {
                $out[$key] = $languageDisplayName;

                continue;
            }
            if (isset($db[$key]) && $db[$key] !== '') {
                $out[$key] = (string) $db[$key];
            } elseif (isset($disk[$key]) && is_string($disk[$key]) && $disk[$key] !== '') {
                $out[$key] = $disk[$key];
            } else {
                $out[$key] = $enValue;
            }
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    public function stringsFromArbPath(string $path): array
    {
        if (! is_readable($path)) {
            return [];
        }
        $raw = file_get_contents($path);
        if ($raw === false) {
            return [];
        }
        $data = json_decode($raw, true);
        if (! is_array($data)) {
            return [];
        }
        $strings = [];
        foreach ($data as $key => $value) {
            if (! is_string($key) || $key === '' || Str::startsWith($key, '@')) {
                continue;
            }
            if (is_string($value)) {
                $strings[$key] = $value;
            }
        }

        return $strings;
    }

    public function encodeArb(array $strings): string
    {
        $json = json_encode((object) $strings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return ($json !== false ? $json : '{}')."\n";
    }
}
