<?php

namespace App\Services;

use App\Models\Exercise;
use Illuminate\Support\Facades\Config;

class ExerciseContentCodeAllocator
{
    /** @return array{prefix: string, min: int, max: int, pad: int}|null */
    public function definitionForSegment(string $segmentKey): ?array
    {
        $scheme = Config::get('content_code_scheme', []);
        $exPrefix = (string) ($scheme['ex_prefix'] ?? 'EX');
        $exSegments = $scheme['ex_segments'] ?? [];
        $padEx = (int) ($scheme['ex_number_pad'] ?? 3);

        if (isset($exSegments[$segmentKey])) {
            $band = $exSegments[$segmentKey];

            return [
                'prefix' => $exPrefix,
                'min' => (int) $band['min'],
                'max' => (int) $band['max'],
                'pad' => $padEx,
            ];
        }

        $prefixSegments = $scheme['prefix_segments'] ?? [];
        if (! isset($prefixSegments[$segmentKey])) {
            return null;
        }
        $p = $prefixSegments[$segmentKey];

        return [
            'prefix' => (string) $p['prefix'],
            'min' => (int) $p['min'],
            'max' => (int) $p['max'],
            'pad' => (int) ($p['pad'] ?? 3),
        ];
    }

    /**
     * Next free code in range, e.g. EX-042, CO-007. Returns null if range is full.
     *
     * @param  list<int>  $additionalUsed  Numbers already reserved this run (e.g. dry-run or batch without save).
     */
    public function allocateNext(string $segmentKey, array $additionalUsed = []): ?string
    {
        $def = $this->definitionForSegment($segmentKey);
        if ($def === null) {
            return null;
        }

        $used = array_unique(array_merge(
            $this->usedNumbersInBand($def['prefix'], $def['min'], $def['max']),
            $additionalUsed
        ));
        $next = null;
        for ($n = $def['min']; $n <= $def['max']; $n++) {
            if (! in_array($n, $used, true)) {
                $next = $n;
                break;
            }
        }
        if ($next === null) {
            return null;
        }

        $suffix = str_pad((string) $next, $def['pad'], '0', STR_PAD_LEFT);

        return $def['prefix'].'-'.$suffix;
    }

    /**
     * @return list<int>
     */
    public function usedNumbersInBand(string $prefix, int $min, int $max): array
    {
        $prefix = strtoupper($prefix);
        $pattern = '/^'.preg_quote($prefix, '/').'-(\d+)$/i';

        $nums = [];
        Exercise::query()
            ->whereNotNull('content_code')
            ->pluck('content_code')
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

    public function isCodeUnique(string $code): bool
    {
        return ! Exercise::query()->where('content_code', $code)->exists();
    }
}
