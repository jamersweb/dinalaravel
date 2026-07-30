<?php

namespace App\Services;

use App\Models\AiProgramWeekDay;
use App\Models\Program;
use App\Models\ProgramPhase;
use App\Models\ProgramPhaseWorkout;
use App\Models\Workout;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AiLaunchProgramBuilderService
{
    public function __construct(
        private AiProgramSchedulePlannerService $planner,
        private AiProgramValidatorService $validator
    ) {
    }

    public function buildLaunchProgram(int $number, string $language = 'en', int $weeks = 12, bool $replace = false): array
    {
        $definition = collect(RoutineLibraryRules::LAUNCH_MATRIX_PROGRAMS)
            ->firstWhere('number', $number);

        if (! $definition) {
            throw new RuntimeException("Launch program {$number} is not defined.");
        }

        $language = RoutineLibraryRules::normalizeLanguage($language);
        $weeks = max(1, min(16, $weeks));
        $code = $this->programCode($definition, $language, $weeks);
        $existing = Program::where('content_code', $code)->first();
        if ($existing && ! $replace) {
            return [
                'program' => $existing->load(['programPhases', 'aiWeekDays']),
                'validation' => $this->validator->validateProgram($existing),
                'status' => 'existing',
            ];
        }

        $template = $this->planner->weekTemplate($definition['days_per_week']);
        $workoutSlotsPerWeek = collect($template)
            ->where('day_type', AiProgramWeekDay::TYPE_WORKOUT)
            ->count();
        $routines = $this->approvedRoutinesForDefinition($definition, $language, $workoutSlotsPerWeek);

        return DB::transaction(function () use ($definition, $language, $weeks, $template, $routines) {
            $program = $this->createProgram($definition, $language, $weeks);
            $phase = $this->createPhase($program, $definition, $weeks);

            $this->attachPhaseWorkouts($phase, $routines);
            $this->createWeekDays($program, $phase, $definition, $template, $routines, $weeks);

            $validation = $this->validator->validateProgram($program->fresh());

            return [
                'program' => $program->fresh(['programPhases', 'aiWeekDays']),
                'validation' => $validation,
                'status' => 'created',
            ];
        });
    }

    public function buildLaunchMatrix(array $languages = ['en', 'ar'], int $weeks = 12, bool $replace = false): array
    {
        $results = [];
        foreach (RoutineLibraryRules::LAUNCH_MATRIX_PROGRAMS as $definition) {
            foreach ($languages as $language) {
                try {
                    $result = $this->buildLaunchProgram((int) $definition['number'], (string) $language, $weeks, $replace);
                    $results[] = [
                        'number' => $definition['number'],
                        'name' => $definition['name'],
                        'language' => RoutineLibraryRules::normalizeLanguage($language),
                        'status' => $result['status'],
                        'program_id' => $result['program']->id,
                        'content_code' => $result['program']->content_code,
                        'valid' => $result['validation']['valid'],
                        'errors' => $result['validation']['errors'],
                    ];
                } catch (\Throwable $e) {
                    $results[] = [
                        'number' => $definition['number'],
                        'name' => $definition['name'],
                        'language' => RoutineLibraryRules::normalizeLanguage($language),
                        'status' => 'blocked',
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }

        return $results;
    }

    private function approvedRoutinesForDefinition(array $definition, string $language, int $minimum): \Illuminate\Support\Collection
    {
        $allowedEquipment = RoutineLibraryRules::allowedExerciseEquipment($definition['equipment_category']);
        $routines = Workout::query()
            ->where('routine_status', 'approved')
            ->where('routine_source', 'generated')
            ->where('language', $language)
            ->whereIn('equipment_category', $allowedEquipment)
            ->where('fitness_level', $definition['level'])
            ->orderByRaw('case when equipment_category = ? then 0 else 1 end', [$definition['equipment_category']])
            ->orderBy('workout_type')
            ->orderBy('id')
            ->get()
            ->filter(fn (Workout $routine) => $this->usesPhase3Contract($routine))
            ->filter(fn (Workout $routine) => $this->matchesProgramDuration($routine, (string) $definition['minutes']))
            ->values();

        $routines = $this->selectBestRoutineSpread($routines, $minimum);

        if ($routines->count() < $minimum) {
            throw new RuntimeException(
                "Need at least {$minimum} approved generated routines for {$definition['name']} ({$language}); found {$routines->count()}."
            );
        }

        return $routines->values();
    }

    private function selectBestRoutineSpread(\Illuminate\Support\Collection $routines, int $minimum): \Illuminate\Support\Collection
    {
        $selected = collect();
        $seenTypes = [];

        foreach ($routines as $routine) {
            $type = (string) $routine->workout_type;
            if (isset($seenTypes[$type])) {
                continue;
            }
            $seenTypes[$type] = true;
            $selected->push($routine);
        }

        if ($selected->count() >= $minimum) {
            return $selected->values();
        }

        $selectedIds = $selected->pluck('id')->all();
        foreach ($routines as $routine) {
            if (in_array($routine->id, $selectedIds, true)) {
                continue;
            }
            $selected->push($routine);
            $selectedIds[] = $routine->id;
            if ($selected->count() >= $minimum) {
                break;
            }
        }

        return $selected->values();
    }

    private function usesPhase3Contract(Workout $routine): bool
    {
        $routineSections = is_array($routine->routine_sections) ? $routine->routine_sections : [];
        if (($routineSections['_meta']['section_contract'] ?? null) !== 'ai_program_builder_phase_3') {
            return false;
        }

        $categories = $routine->workoutExercises()
            ->whereIn('category', RoutineLibraryRules::REQUIRED_WORKOUT_SECTIONS)
            ->distinct()
            ->pluck('category')
            ->all();

        return array_diff(RoutineLibraryRules::REQUIRED_WORKOUT_SECTIONS, $categories) === [];
    }

    private function matchesProgramDuration(Workout $routine, string $minutes): bool
    {
        $routineSections = is_array($routine->routine_sections) ? $routine->routine_sections : [];
        $targetMinutes = (int) ($routineSections['_meta']['target_minutes'] ?? 0);
        if ($targetMinutes <= 0) {
            return false;
        }

        $bounds = $this->durationBounds($minutes);
        if (! $bounds) {
            return true;
        }

        return $targetMinutes >= $bounds[0] && $targetMinutes <= $bounds[1];
    }

    /**
     * @return array{0:int,1:int}|null
     */
    private function durationBounds(string $minutes): ?array
    {
        if (! preg_match_all('/\d+/', $minutes, $matches) || $matches[0] === []) {
            return null;
        }

        $numbers = array_map('intval', $matches[0]);
        if (count($numbers) === 1) {
            return [$numbers[0], $numbers[0]];
        }

        return [min($numbers), max($numbers)];
    }

    private function createProgram(array $definition, string $language, int $weeks): Program
    {
        $code = $this->programCode($definition, $language, $weeks);
        Program::where('content_code', $code)->delete();

        $program = new Program();
        $program->content_code = $code;
        $program->title = $definition['name'];
        $program->type = 'ai_launch';
        $program->level = $definition['level'];
        $program->phases = 1;
        $program->language = $language;
        $program->discription = sprintf(
            '%s-week AI launch program. Equipment: %s. Days/week: %s. Minutes: %s.',
            $weeks,
            str_replace('_', ' ', $definition['equipment_category']),
            $definition['days_per_week'],
            $definition['minutes']
        );
        $program->save();

        return $program;
    }

    private function programCode(array $definition, string $language, int $weeks): string
    {
        return sprintf('AI-LAUNCH-%02d-%s-%dw', $definition['number'], strtoupper($language), $weeks);
    }

    private function createPhase(Program $program, array $definition, int $weeks): ProgramPhase
    {
        $phase = new ProgramPhase();
        $phase->program_id = $program->id;
        $phase->phase_no = 1;
        $phase->weeks = $weeks;
        $phase->name = $definition['name'] . ' Phase 1';
        $phase->summary = 'AI-generated launch phase with weekly progression and seven visible days per week.';
        $phase->save();

        return $phase;
    }

    private function attachPhaseWorkouts(ProgramPhase $phase, \Illuminate\Support\Collection $routines): void
    {
        foreach ($routines as $index => $routine) {
            ProgramPhaseWorkout::create([
                'program_phase_id' => $phase->id,
                'workout_id' => $routine->id,
                'display_name' => $routine->title,
                'section_tag' => 'workout_routine',
                'sort_order' => $index,
            ]);
        }
    }

    private function createWeekDays(
        Program $program,
        ProgramPhase $phase,
        array $definition,
        array $template,
        \Illuminate\Support\Collection $routines,
        int $weeks
    ): void {
        $routineIndex = 0;
        for ($weekNo = 1; $weekNo <= $weeks; $weekNo++) {
            $progression = $this->planner->progressionForWeek($weekNo);
            foreach ($template as $day) {
                $routine = null;
                if ($day['day_type'] === AiProgramWeekDay::TYPE_WORKOUT) {
                    $routine = $routines[$routineIndex % $routines->count()];
                    $routineIndex++;
                }

                AiProgramWeekDay::create([
                    'program_id' => $program->id,
                    'program_phase_id' => $phase->id,
                    'workout_id' => $routine?->id,
                    'week_no' => $weekNo,
                    'day_no' => $day['day_no'],
                    'day_type' => $day['day_type'],
                    'display_name' => $this->displayName($day, $routine),
                    'estimated_minutes' => $routine ? $this->adjustedEstimatedMinutes($routine, $definition, $progression) : null,
                    'training_style' => $day['training_focus'],
                    'muscle_groups' => $routine ? $this->routineMuscleGroups($routine) : [],
                    'progression_notes' => $progression,
                    'recovery_guidance' => $day['day_type'] === AiProgramWeekDay::TYPE_WORKOUT
                        ? $this->workoutRecoveryGuidance($progression)
                        : $this->recoveryGuidance($day['day_type']),
                ]);
            }
        }
    }

    private function displayName(array $day, ?Workout $routine): string
    {
        if ($routine) {
            return $routine->title;
        }

        return $day['day_type'] === AiProgramWeekDay::TYPE_ACTIVE_RECOVERY
            ? 'Active Recovery Day'
            : 'Rest Day';
    }

    private function adjustedEstimatedMinutes(Workout $routine, array $definition, array $progression): ?int
    {
        $minutes = $this->estimatedMinutes($routine, $definition);
        if ($minutes === null) {
            return null;
        }

        if (($progression['deload'] ?? false) === true) {
            return max(10, (int) round($minutes * 0.75));
        }

        return $minutes;
    }

    private function estimatedMinutes(Workout $routine, array $definition): ?int
    {
        $sections = is_array($routine->routine_sections) ? $routine->routine_sections : [];
        if (isset($sections['_meta']['estimated_minutes'])) {
            return (int) $sections['_meta']['estimated_minutes'];
        }

        $minutes = (string) $definition['minutes'];
        if (preg_match('/\d+/', $minutes, $matches)) {
            return (int) $matches[0];
        }

        return null;
    }

    private function routineMuscleGroups(Workout $routine): array
    {
        return $routine->workoutExercises()
            ->with('exerciseDetail.libraryTag')
            ->get()
            ->map(fn ($row) => optional(optional($row->exerciseDetail)->libraryTag)->muscle_group)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function recoveryGuidance(string $dayType): array
    {
        if ($dayType === AiProgramWeekDay::TYPE_ACTIVE_RECOVERY) {
            return [
                'Keep intensity low.',
                'Use walking, light cycling, mobility, stretching, or breathing work.',
                'Do not turn this into another full workout.',
            ];
        }

        return [
            'Prioritize sleep, hydration, and nutrition.',
            'Avoid intense training.',
            'Take a light walk only if comfortable.',
        ];
    }

    private function workoutRecoveryGuidance(array $progression): array
    {
        $guidance = [
            'Use readiness check before training: energy, sleep, soreness, stress, and pain.',
            'Reduce sets or skip optional cardio if readiness is low.',
        ];

        if (($progression['deload'] ?? false) === true) {
            $guidance[] = 'Deload week: reduce load, sets, or intensity and leave extra reps in reserve.';
            $guidance[] = 'Swap to mobility, walking, or active recovery if fatigue or symptoms are elevated.';
        }

        return $guidance;
    }
}
