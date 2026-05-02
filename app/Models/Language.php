<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\Rule;

class Language extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'label',
        'native_label',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public static function activeCodes(): array
    {
        $codes = static::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('code')
            ->map(fn ($code) => strtolower(trim((string) $code)))
            ->filter()
            ->values()
            ->all();

        return $codes !== [] ? $codes : ['en', 'ar'];
    }

    /** Validation rule: active language code for app user preference. */
    public static function activeCodeRule(): \Illuminate\Validation\Rules\In
    {
        $baseline = ['en', 'ar'];
        $fromDb = static::activeCodes();
        $allowed = array_values(array_unique(array_merge(
            $baseline,
            is_array($fromDb) ? $fromDb : []
        )));

        // Rule::in([]) rejects every value; never allow an empty allowlist.
        if ($allowed === []) {
            $allowed = $baseline;
        }

        return Rule::in($allowed);
    }
}
