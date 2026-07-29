<?php

namespace App\Services;

use InvalidArgumentException;

class AiProgramSchedulePlannerService
{
    public function launchMatrix(): array
    {
        return RoutineLibraryRules::LAUNCH_MATRIX_PROGRAMS;
    }

    public function weekTemplate(int|string $daysPerWeek): array
    {
        $frequency = $this->normalizeFrequency($daysPerWeek);

        $templates = [
            3 => [
                ['day_no' => 1, 'day_type' => 'workout', 'training_focus' => 'Full body strength'],
                ['day_no' => 2, 'day_type' => 'rest', 'training_focus' => null],
                ['day_no' => 3, 'day_type' => 'workout', 'training_focus' => 'Full body conditioning'],
                ['day_no' => 4, 'day_type' => 'rest', 'training_focus' => null],
                ['day_no' => 5, 'day_type' => 'workout', 'training_focus' => 'Full body strength and core'],
                ['day_no' => 6, 'day_type' => 'active_recovery', 'training_focus' => 'Mobility and walking'],
                ['day_no' => 7, 'day_type' => 'rest', 'training_focus' => null],
            ],
            4 => [
                ['day_no' => 1, 'day_type' => 'workout', 'training_focus' => 'Upper body'],
                ['day_no' => 2, 'day_type' => 'workout', 'training_focus' => 'Lower body'],
                ['day_no' => 3, 'day_type' => 'rest', 'training_focus' => null],
                ['day_no' => 4, 'day_type' => 'workout', 'training_focus' => 'Upper body and core'],
                ['day_no' => 5, 'day_type' => 'workout', 'training_focus' => 'Lower body and conditioning'],
                ['day_no' => 6, 'day_type' => 'active_recovery', 'training_focus' => 'Mobility and stretching'],
                ['day_no' => 7, 'day_type' => 'rest', 'training_focus' => null],
            ],
            5 => [
                ['day_no' => 1, 'day_type' => 'workout', 'training_focus' => 'Lower body'],
                ['day_no' => 2, 'day_type' => 'workout', 'training_focus' => 'Upper body'],
                ['day_no' => 3, 'day_type' => 'rest', 'training_focus' => null],
                ['day_no' => 4, 'day_type' => 'workout', 'training_focus' => 'Lower body and glutes'],
                ['day_no' => 5, 'day_type' => 'workout', 'training_focus' => 'Upper body and core'],
                ['day_no' => 6, 'day_type' => 'workout', 'training_focus' => 'Full body conditioning'],
                ['day_no' => 7, 'day_type' => 'rest', 'training_focus' => null],
            ],
            6 => [
                ['day_no' => 1, 'day_type' => 'workout', 'training_focus' => 'Push'],
                ['day_no' => 2, 'day_type' => 'workout', 'training_focus' => 'Pull'],
                ['day_no' => 3, 'day_type' => 'workout', 'training_focus' => 'Legs'],
                ['day_no' => 4, 'day_type' => 'active_recovery', 'training_focus' => 'Mobility and low-intensity cardio'],
                ['day_no' => 5, 'day_type' => 'workout', 'training_focus' => 'Upper body'],
                ['day_no' => 6, 'day_type' => 'workout', 'training_focus' => 'Lower body'],
                ['day_no' => 7, 'day_type' => 'rest', 'training_focus' => null],
            ],
        ];

        return $templates[$frequency];
    }

    public function progressionForWeek(int $weekNo): array
    {
        if ($weekNo <= 2) {
            return [
                'focus' => 'Technique and base volume',
                'rules' => ['simple exercises', 'longer rest periods', 'controlled tempo'],
            ];
        }

        if ($weekNo <= 4) {
            return [
                'focus' => 'Volume build',
                'rules' => ['slightly more repetitions', 'small resistance increase where appropriate'],
            ];
        }

        if ($weekNo <= 8) {
            return [
                'focus' => 'Exercise progression',
                'rules' => ['harder variations', 'moderate rest reduction', 'accessory rotation'],
            ];
        }

        return [
            'focus' => 'Training density and performance',
            'rules' => ['higher resistance or density', 'advanced grouping only when level-appropriate'],
        ];
    }

    private function normalizeFrequency(int|string $daysPerWeek): int
    {
        if (is_string($daysPerWeek) && str_contains($daysPerWeek, '-')) {
            $parts = array_map('intval', explode('-', $daysPerWeek));

            return max($parts);
        }

        $frequency = (int) $daysPerWeek;
        if (! in_array($frequency, RoutineLibraryRules::PROGRAM_WEEKLY_FREQUENCIES, true)) {
            throw new InvalidArgumentException('Program frequency must be 3, 4, 5, or 6 days per week.');
        }

        return $frequency;
    }
}
