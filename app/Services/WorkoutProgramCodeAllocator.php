<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class WorkoutProgramCodeAllocator
{
    /**
     * Use a top-level scheme block: `workout_routines` or `programs` in content_code_scheme.php.
     *
     * @param  class-string<Model>  $modelClass
     * @param  list<int>  $additionalUsed
     */
    public function allocateNext(string $schemeKey, string $modelClass, string $codeColumn = 'content_code', array $additionalUsed = []): ?string
    {
        $scheme = Config::get('content_code_scheme', []);
        $block = $scheme[$schemeKey] ?? null;
        if (! is_array($block)) {
            return null;
        }
        $prefix = (string) ($block['prefix'] ?? '');
        $min = (int) ($block['min'] ?? 0);
        $max = (int) ($block['max'] ?? 0);
        $pad = (int) ($block['pad'] ?? 3);
        if ($prefix === '' || $max < $min) {
            return null;
        }

        $used = array_unique(array_merge(
            $this->usedNumbersInBand($modelClass, $codeColumn, $prefix, $min, $max),
            $additionalUsed
        ));

        $next = null;
        for ($n = $min; $n <= $max; $n++) {
            if (! in_array($n, $used, true)) {
                $next = $n;
                break;
            }
        }
        if ($next === null) {
            return null;
        }

        $suffix = str_pad((string) $next, $pad, '0', STR_PAD_LEFT);

        return $prefix.'-'.$suffix;
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return list<int>
     */
    public function usedNumbersInBand(string $modelClass, string $codeColumn, string $prefix, int $min, int $max): array
    {
        $prefixUpper = strtoupper($prefix);
        $pattern = '/^'.preg_quote($prefixUpper, '/').'-(\d+)$/i';

        $nums = [];
        $modelClass::query()
            ->whereNotNull($codeColumn)
            ->pluck($codeColumn)
            ->each(function ($code) use ($pattern, $min, $max, &$nums) {
                $code = trim((string) $code);
                if ($code === '' || ! preg_match($pattern, $code, $m)) {
                    return;
                }
                $n = (int) $m[1];
                if ($n >= $min && $n <= $max) {
                    $nums[] = $n;
                }
            });

        return array_values(array_unique($nums));
    }
}
