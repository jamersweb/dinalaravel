<?php

namespace App\Services;

use App\Models\Program;
use App\Models\Workout;
use Illuminate\Support\Collection;

class AiProgramDisplayService
{
    public function scheduleForProgram(int|Program $program, ?array $weekNumbers = null): ?array
    {
        $program = $program instanceof Program ? $program : Program::find($program);
        if (! $program) {
            return null;
        }

        $query = $program->aiWeekDays()
            ->with(['workout.workoutExercises' => function ($q) {
                $q->orderBy('id')->with('exerciseDetail.libraryTag');
            }]);

        if (is_array($weekNumbers) && $weekNumbers !== []) {
            $query->whereIn('week_no', array_map('intval', $weekNumbers));
        }

        $days = $query->get();
        if ($days->isEmpty()) {
            return null;
        }

        $weeks = $days
            ->groupBy('week_no')
            ->map(fn (Collection $weekDays, $weekNo) => [
                'week_no' => (int) $weekNo,
                'days' => $weekDays
                    ->sortBy('day_no')
                    ->values()
                    ->map(fn ($day) => $this->dayPayload($day))
                    ->all(),
            ])
            ->sortBy('week_no')
            ->values()
            ->all();

        return [
            'program_id' => $program->id,
            'content_code' => $program->content_code,
            'title' => $program->title,
            'language' => $program->language,
            'level' => $program->level,
            'type' => $program->type,
            'total_weeks' => count($weeks),
            'weeks' => $weeks,
        ];
    }

    private function dayPayload($day): array
    {
        $payload = [
            'id' => $day->id,
            'week_no' => $day->week_no,
            'day_no' => $day->day_no,
            'day_label' => 'Day ' . $day->day_no,
            'day_type' => $day->day_type,
            'day_type_label' => $this->label($day->day_type),
            'display_name' => $day->display_name,
            'estimated_minutes' => $day->estimated_minutes,
            'training_style' => $day->training_style,
            'muscle_groups' => $day->muscle_groups ?: [],
            'progression_notes' => $day->progression_notes ?: [],
            'recovery_guidance' => $day->recovery_guidance ?: [],
            'validation_errors' => $day->validation_errors ?: [],
            'workout' => null,
            'sections' => [],
        ];

        if ($day->workout) {
            $payload['workout'] = $this->workoutPayload($day->workout);
            $payload['sections'] = $this->sectionPayloads($day->workout);
        }

        return $payload;
    }

    private function workoutPayload(Workout $workout): array
    {
        return [
            'id' => $workout->id,
            'content_code' => $workout->content_code,
            'title' => $workout->title,
            'language' => $workout->language,
            'equipment_category' => $workout->equipment_category,
            'fitness_level' => $workout->fitness_level,
            'workout_type' => $workout->workout_type,
            'daily_summary' => $workout->daily_summary,
            'instructions' => $workout->instructions,
            'image' => $workout->image,
            'routine_sections' => is_array($workout->routine_sections) ? $workout->routine_sections : [],
        ];
    }

    private function sectionPayloads(Workout $workout): array
    {
        $rows = $workout->relationLoaded('workoutExercises')
            ? $workout->workoutExercises
            : $workout->workoutExercises()->with('exerciseDetail.libraryTag')->orderBy('id')->get();

        $sections = $rows->groupBy('category');
        $orderedKeys = array_values(array_unique(array_merge(
            RoutineLibraryRules::REQUIRED_WORKOUT_SECTIONS,
            RoutineLibraryRules::OPTIONAL_WORKOUT_SECTIONS,
            $sections->keys()->all()
        )));

        return collect($orderedKeys)
            ->filter(fn ($section) => $sections->has($section))
            ->map(fn ($section) => [
                'key' => $section,
                'label' => RoutineLibraryRules::WORKOUT_SECTION_LABELS[$section] ?? $this->label($section),
                'exercises' => $sections->get($section)
                    ->values()
                    ->map(fn ($row) => $this->exercisePayload($row))
                    ->all(),
            ])
            ->values()
            ->all();
    }

    private function exercisePayload($row): array
    {
        $exercise = $row->exerciseDetail;
        $tag = $exercise?->libraryTag;

        return [
            'workout_exercise_id' => $row->id,
            'exercise_id' => $row->exercise_id,
            'title' => $exercise?->title,
            'content_code' => $exercise?->content_code,
            'video_url' => $exercise?->video_url,
            'video_type' => $exercise?->video_type,
            'image' => $exercise?->image,
            'sets' => $row->sets,
            'reps' => $row->reps,
            'reps_type' => $row->reps_type,
            'time' => $row->time,
            'rest_period' => $row->rest_period,
            'description' => $row->description,
            'sets_rounds' => $row->sets_rounds,
            'group_id' => $row->group_id,
            'group_type' => $row->group_type,
            'group_label' => $row->group_label,
            'muscle_group' => $tag?->muscle_group,
            'secondary_muscle_groups' => $tag?->secondary_muscle_groups ?: [],
            'impact_level' => $tag?->impact_level,
            'intensity_level' => $tag?->intensity_level,
            'movement_patterns' => $tag?->movement_patterns ?: [],
            'safety_notes' => $tag?->safety_notes ?: [],
            'injury_cautions' => $tag?->injury_cautions ?: [],
            'contraindications' => $tag?->contraindications ?: [],
            'usage_flags' => $tag?->usage_flags ?: [],
        ];
    }

    private function label(?string $value): string
    {
        return ucwords(str_replace('_', ' ', (string) $value));
    }
}
