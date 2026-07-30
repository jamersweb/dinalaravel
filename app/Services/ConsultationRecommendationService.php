<?php

namespace App\Services;

use App\Models\BodyStats;
use App\Models\ConsultationForm;
use App\Models\ConsultationRecommendation;
use App\Models\Program;
use App\Models\User;
use App\Models\UserAnswer;
use App\Models\UserDetail;
use App\Models\UserSetting;
use App\Models\Workout;
use Carbon\Carbon;

class ConsultationRecommendationService
{
    public function recommendForUser(int $userId, array $overrides = []): ConsultationRecommendation
    {
        $user = User::findOrFail($userId);
        $detail = UserDetail::where('user_id', $userId)->first();
        $consultation = ConsultationForm::where('user_id', $userId)->latest('id')->first();
        $answers = UserAnswer::where('user_id', $userId)->with('question')->get();
        $bodyStats = BodyStats::where('user_id', $userId)->whereNotNull('weight')->latest('id')->first();
        $language = RoutineLibraryRules::normalizeLanguage(UserSetting::where('user_id', $userId)->value('language'));

        $text = $this->consultationText($consultation, $answers);
        $profile = $this->profile($detail, $bodyStats, $text);
        $goal = $this->goal($text);
        $equipment = $this->equipment($text);
        $level = $this->level($text);
        $injuries = $this->injuries($text);
        $conditions = $this->hormonalConditions($text);
        $calories = $this->calories($profile, $goal, $text);
        $frequency = $this->adaptedWeeklyFrequency($this->weeklyFrequency($text, $level, $injuries), $conditions, $injuries, $text);
        $duration = $this->adaptedPreferredDuration($this->preferredDuration($text, $level), $conditions, $injuries, $text);

        $overridePayload = $this->normalizeOverrides($overrides);
        $language = $overridePayload['language'] ?? $language;
        $equipment = $overridePayload['equipment_category'] ?? $equipment;
        $level = $overridePayload['training_level'] ?? $level;
        $frequency = $overridePayload['weekly_workout_frequency'] ?? $frequency;
        $duration = $overridePayload['preferred_duration_minutes'] ?? $duration;
        $methodology = $this->dinaMethodologyPayload($text, $conditions, $injuries, $goal);

        $routineResult = $this->routineIds($language, $equipment, $level, $injuries);
        $programResult = $this->programIds($language, $equipment, $level, $frequency, $duration, $injuries, $text);

        return ConsultationRecommendation::create([
            'user_id' => $user->id,
            'consultation_form_id' => optional($consultation)->id,
            'bmr' => $calories['bmr'],
            'tdee' => $calories['tdee'],
            'recommended_calories' => $calories['recommended_calories'],
            'training_level' => $level,
            'equipment_category' => $equipment,
            'weekly_workout_frequency' => $frequency,
            'injury_precautions' => $injuries,
            'missing_fields' => $calories['missing_fields'],
            'recommended_routine_ids' => $routineResult['ids'],
            'recommended_program_ids' => $programResult['ids'],
            'calculation_payload' => [
                'language' => $language,
                'goal' => $goal,
                'preferred_duration_minutes' => $duration,
                'activity_factor' => $calories['activity_factor'],
                'routine_source_status' => $routineResult['status'],
                'program_source_status' => $programResult['status'],
                'coach_overrides' => $overridePayload,
                'profile' => $profile,
                'dina_methodology' => $methodology,
                'notes' => array_values(array_merge($programResult['notes'], $routineResult['notes'], $methodology['coach_notes'])),
            ],
        ]);
    }

    private function normalizeOverrides(array $overrides): array
    {
        $normalized = [];

        if (! empty($overrides['language'])) {
            $language = RoutineLibraryRules::normalizeLanguage($overrides['language']);
            if (in_array($language, RoutineLibraryRules::CONTENT_LANGUAGES, true)) {
                $normalized['language'] = $language;
            }
        }

        if (! empty($overrides['equipment_category'])) {
            $equipment = RoutineLibraryRules::normalizeEquipment($overrides['equipment_category']);
            if (in_array($equipment, RoutineLibraryRules::EQUIPMENT_CATEGORIES, true)) {
                $normalized['equipment_category'] = $equipment;
            }
        }

        if (! empty($overrides['training_level']) && in_array($overrides['training_level'], RoutineLibraryRules::LEVELS, true)) {
            $normalized['training_level'] = $overrides['training_level'];
        }

        if (! empty($overrides['weekly_workout_frequency'])) {
            $normalized['weekly_workout_frequency'] = max(3, min(6, (int) $overrides['weekly_workout_frequency']));
        }

        if (! empty($overrides['preferred_duration_minutes'])) {
            $duration = (int) $overrides['preferred_duration_minutes'];
            if (in_array($duration, [15, 20, 30, 45, 60], true)) {
                $normalized['preferred_duration_minutes'] = $duration;
            }
        }

        return $normalized;
    }

    private function consultationText(?ConsultationForm $consultation, $answers): string
    {
        $chunks = [];
        if ($consultation) {
            foreach ([
                'health_background',
                'injuries',
                'goals',
                'lifestyle_habits',
                'preferred_training_style',
                'fitness_level',
                'medical_concerns',
                'training_experience',
            ] as $field) {
                $chunks[] = (string) $consultation->{$field};
            }
        }

        foreach ($answers as $answer) {
            $question = optional($answer->question);
            $chunks[] = (string) $question->question_en;
            $chunks[] = (string) $question->question_ar;
            $chunks[] = $this->answerText($answer->answer);
        }

        return strtolower(implode(' ', $chunks));
    }

    private function answerText($answer): string
    {
        $decoded = json_decode((string) $answer, true);
        if (is_array($decoded)) {
            return implode(' ', array_map(fn ($item) => (string) $item, $decoded));
        }

        return (string) $answer;
    }

    private function profile(?UserDetail $detail, ?BodyStats $bodyStats, string $text): array
    {
        $weight = $bodyStats && $bodyStats->weight ? (float) $bodyStats->weight : $this->extractWeightKg($text);
        $unit = strtolower((string) optional($bodyStats)->weight_unit ?: (string) optional($bodyStats)->unit);
        if ($weight && in_array($unit, ['lb', 'lbs', 'pound', 'pounds'], true)) {
            $weight = round($weight * 0.453592, 2);
        }

        return [
            'gender' => $this->gender(strtolower((string) optional($detail)->gender), $text),
            'height_cm' => $this->heightCm(optional($detail)->height) ?: $this->extractHeightCm($text),
            'weight_kg' => $weight,
            'age' => $this->age(optional($detail)->DOB),
        ];
    }

    private function calories(array $profile, string $goal, string $text): array
    {
        $missing = [];
        foreach (['height_cm', 'weight_kg', 'age', 'gender'] as $field) {
            if (empty($profile[$field])) {
                $missing[] = $field;
            }
        }

        if ($missing !== []) {
            return [
                'bmr' => null,
                'tdee' => null,
                'recommended_calories' => null,
                'missing_fields' => $missing,
                'activity_factor' => null,
            ];
        }

        $isFemale = in_array($profile['gender'], ['female', 'f', 'انثى'], true);
        $bmr = (10 * $profile['weight_kg']) + (6.25 * $profile['height_cm']) - (5 * $profile['age']) + ($isFemale ? -161 : 5);
        $activityFactor = $this->activityFactor($text);
        $tdee = $bmr * $activityFactor;
        $adjusted = match ($goal) {
            'fat_loss' => $tdee - 500,
            'muscle_gain' => $tdee + 250,
            default => $tdee,
        };

        return [
            'bmr' => round($bmr, 2),
            'tdee' => round($tdee, 2),
            'recommended_calories' => max(1200, (int) round($adjusted)),
            'missing_fields' => [],
            'activity_factor' => $activityFactor,
        ];
    }

    private function routineIds(string $language, string $equipment, string $level, array $injuries): array
    {
        $approved = $this->routineQuery($language, $equipment, $level, $injuries, ['approved'])
            ->take(12)
            ->pluck('id')
            ->values()
            ->all();

        if (count($approved) >= 3) {
            return [
                'ids' => $approved,
                'status' => 'approved',
                'notes' => [],
            ];
        }

        $reviewReady = $this->routineQuery($language, $equipment, $level, $injuries, ['approved', 'pending_review'])
            ->take(12)
            ->pluck('id')
            ->values()
            ->all();

        if ($reviewReady !== []) {
            return [
                'ids' => $reviewReady,
                'status' => 'includes_pending_review',
                'notes' => ['Recommendation includes pending-review routines. Approve routines before assigning automatically.'],
            ];
        }

        if ($injuries !== []) {
            $coachReview = $this->routineQuery($language, $equipment, $level, [], ['approved', 'pending_review'])
                ->take(12)
                ->pluck('id')
                ->values()
                ->all();

            if ($coachReview !== []) {
                return [
                    'ids' => $coachReview,
                    'status' => 'needs_injury_review',
                    'notes' => ['Injury filtering removed all exact matches. Routines are suggested for coach review only.'],
                ];
            }
        }

        return [
            'ids' => [],
            'status' => 'missing_matching_routines',
            'notes' => ['No approved or pending-review routine matches the consultation filters yet.'],
        ];
    }

    private function routineQuery(string $language, string $equipment, string $level, array $injuries, array $statuses)
    {
        return Workout::query()
            ->whereIn('routine_status', $statuses)
            ->where('language', $language)
            ->where('equipment_category', $equipment)
            ->where('fitness_level', $level)
            ->with('workoutExercises.exerciseDetail.libraryTag')
            ->get()
            ->reject(function (Workout $workout) use ($injuries) {
                if ($injuries === []) {
                    return false;
                }

                foreach ($workout->workoutExercises as $row) {
                    $cautions = $row->exerciseDetail && $row->exerciseDetail->libraryTag
                        ? (array) $row->exerciseDetail->libraryTag->injury_cautions
                        : [];
                    foreach ($cautions as $caution) {
                        foreach ($injuries as $injury) {
                            if (str_contains(strtolower((string) $caution), $injury)) {
                                return true;
                            }
                        }
                    }
                }

                return false;
            })
            ->values();
    }

    private function programIds(string $language, string $equipment, string $level, int $frequency, int $duration, array $injuries, string $text): array
    {
        $programLanguage = $language === 'no_audio' ? 'en' : $language;
        $notes = [];
        if ($language === 'no_audio') {
            $notes[] = 'No-audio launch programs are not available yet; English programs are recommended for coach review.';
        }
        if ($injuries !== []) {
            $notes[] = 'Health or injury notes detected. Program recommendation should be reviewed by a coach before assignment.';
        }
        if ($this->needsMedicalClearance($text)) {
            $notes[] = 'Medical clearance may be required before starting this program.';
        }

        $candidates = collect(RoutineLibraryRules::LAUNCH_MATRIX_PROGRAMS)
            ->filter(fn (array $definition) => $definition['level'] === $level)
            ->map(function (array $definition) use ($programLanguage, $equipment, $frequency, $duration) {
                $contentCode = sprintf('AI-LAUNCH-%02d-%s-12w', $definition['number'], strtoupper($programLanguage));
                $program = Program::where('content_code', $contentCode)->first();
                if (! $program) {
                    return null;
                }

                return [
                    'program_id' => $program->id,
                    'score' => $this->programScore($definition, $equipment, $frequency, $duration),
                ];
            })
            ->filter()
            ->sortBy('score')
            ->take(3)
            ->values();

        if ($candidates->isEmpty() && $level !== 'beginner') {
            $fallback = $this->programIds($programLanguage, $equipment, 'beginner', 3, min($duration, 30), $injuries, $text);
            $fallback['status'] = 'fallback_beginner_program';
            $fallback['notes'][] = 'No exact level launch program was selected; beginner fallback suggested for coach review.';

            return $fallback;
        }

        return [
            'ids' => $candidates->pluck('program_id')->values()->all(),
            'status' => $candidates->isEmpty() ? 'missing_matching_programs' : 'matched_launch_programs',
            'notes' => $candidates->isEmpty()
                ? array_merge($notes, ['No matching launch program exists for the consultation filters.'])
                : $notes,
        ];
    }

    private function hormonalConditions(string $text): array
    {
        $conditions = [];
        foreach (RoutineLibraryRules::HORMONAL_CONDITION_RULES as $condition => $rule) {
            foreach ($rule['signals'] as $signal) {
                if (str_contains($text, $signal)) {
                    $conditions[] = $condition;
                    break;
                }
            }
        }

        return array_values(array_unique($conditions));
    }

    private function adaptedWeeklyFrequency(int $frequency, array $conditions, array $injuries, string $text): int
    {
        if ($injuries !== [] || $this->hasLowReadinessSignals($text)) {
            $frequency = min($frequency, 3);
        }

        if (array_intersect($conditions, ['hashimotos', 'endometriosis', 'high_stress'])) {
            $frequency = min($frequency, 3);
        }

        if (in_array('pcos', $conditions, true) && $injuries === [] && ! $this->hasLowReadinessSignals($text)) {
            $frequency = max($frequency, 4);
        }

        return max(3, min(6, $frequency));
    }

    private function adaptedPreferredDuration(int $duration, array $conditions, array $injuries, string $text): int
    {
        if ($injuries !== [] || $this->hasLowReadinessSignals($text)) {
            $duration = min($duration, 30);
        }

        if (array_intersect($conditions, ['hashimotos', 'endometriosis', 'high_stress'])) {
            $duration = min($duration, 30);
        }

        if (in_array('menopause', $conditions, true) && ! $this->hasLowReadinessSignals($text)) {
            $duration = max($duration, 30);
        }

        return in_array($duration, [15, 20, 30, 45, 60], true) ? $duration : 30;
    }

    private function dinaMethodologyPayload(string $text, array $conditions, array $injuries, string $goal): array
    {
        $trainingAdjustments = [
            'Every generated session must include abs, obliques, lower-back activation, lower-back strengthening, mobility, and stretching.',
            'Movement quality comes before load, speed, or density.',
        ];
        $habitSuggestions = ['hydration', 'steps', 'sleep routine'];
        $nutritionSuggestions = $goal === 'fat_loss'
            ? ['protein at each meal', 'hydration', 'coach-reviewed calorie target']
            : ['protein target', 'consistent meals', 'hydration'];
        $coachNotes = [];

        foreach ($conditions as $condition) {
            $rule = RoutineLibraryRules::HORMONAL_CONDITION_RULES[$condition] ?? null;
            if (! $rule) {
                continue;
            }
            $trainingAdjustments = array_merge($trainingAdjustments, $rule['training_adjustments']);
            $habitSuggestions = array_merge($habitSuggestions, $rule['habit_suggestions']);
            $nutritionSuggestions = array_merge($nutritionSuggestions, $rule['nutrition_suggestions']);
            $coachNotes[] = ucfirst(str_replace('_', ' ', $condition)) . ' signals detected; apply condition-specific training and recovery rules.';
        }

        if ($this->hasLowReadinessSignals($text)) {
            $trainingAdjustments[] = 'Use readiness-based reduction: fewer sets, lower intensity, longer rest, or active recovery.';
            $habitSuggestions[] = 'readiness check';
            $coachNotes[] = 'Low readiness signals detected from consultation text.';
        }

        return [
            'conditions' => $conditions,
            'training_adjustments' => array_values(array_unique($trainingAdjustments)),
            'habit_suggestions' => array_values(array_unique($habitSuggestions)),
            'nutrition_suggestions' => array_values(array_unique($nutritionSuggestions)),
            'substitution_policy' => $this->substitutionPolicy($injuries),
            'readiness_policy' => $this->readinessPolicy(),
            'coach_notes' => array_values(array_unique($coachNotes)),
        ];
    }

    private function substitutionPolicy(array $injuries): array
    {
        $policy = [];
        foreach ($injuries as $injury) {
            if (isset(RoutineLibraryRules::PAIN_SUBSTITUTION_RULES[$injury])) {
                $policy[$injury] = RoutineLibraryRules::PAIN_SUBSTITUTION_RULES[$injury];
            }
        }

        return $policy;
    }

    private function readinessPolicy(): array
    {
        return [
            'inputs' => ['energy_1_10', 'sleep_quality', 'stress_1_10', 'soreness_1_10', 'pain', 'illness', 'menstrual_symptoms'],
            'green' => 'Proceed as planned.',
            'yellow' => 'Reduce one set per strength section or skip optional cardio.',
            'red' => 'Use mobility, walking, breathing, or rest instead of full training.',
        ];
    }

    private function hasLowReadinessSignals(string $text): bool
    {
        foreach (['poor sleep', 'bad sleep', 'insomnia', 'exhausted', 'fatigue', 'very tired', 'sore', 'illness', 'sick', 'flare', 'period pain', 'menstrual symptoms'] as $signal) {
            if (str_contains($text, $signal)) {
                return true;
            }
        }

        return false;
    }

    private function programScore(array $definition, string $equipment, int $frequency, int $duration): int
    {
        $score = 0;

        if ($definition['equipment_category'] !== $equipment) {
            $allowed = RoutineLibraryRules::allowedExerciseEquipment($definition['equipment_category']);
            $score += in_array($equipment, $allowed, true) ? 4 : 12;
        }

        $score += abs($this->daysPerWeekNumber($definition['days_per_week']) - $frequency) * 3;
        $score += abs($this->durationCenter((string) $definition['minutes']) - $duration);

        return $score;
    }

    private function daysPerWeekNumber($value): int
    {
        if (preg_match_all('/\d+/', (string) $value, $matches) && $matches[0] !== []) {
            $numbers = array_map('intval', $matches[0]);

            return (int) round(array_sum($numbers) / count($numbers));
        }

        return 3;
    }

    private function durationCenter(string $minutes): int
    {
        if (preg_match_all('/\d+/', $minutes, $matches) && $matches[0] !== []) {
            $numbers = array_map('intval', $matches[0]);

            return (int) round(array_sum($numbers) / count($numbers));
        }

        return 30;
    }

    private function heightCm($height): ?float
    {
        if ($height === null || $height === '') {
            return null;
        }
        $height = strtolower(trim((string) $height));
        if (preg_match('/(\d+(?:\.\d+)?)\s*cm/', $height, $matches)) {
            return (float) $matches[1];
        }
        if (preg_match('/(\d+)\s*(?:ft|feet|\')\s*(\d+)?/', $height, $matches)) {
            return round(((int) $matches[1] * 30.48) + ((int) ($matches[2] ?? 0) * 2.54), 2);
        }
        if (is_numeric($height)) {
            $value = (float) $height;
            return $value < 100 ? round($value * 2.54, 2) : $value;
        }

        return null;
    }

    private function age($dob): ?int
    {
        if (! $dob) {
            return null;
        }

        try {
            return Carbon::parse($dob)->age;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function goal(string $text): string
    {
        if (
            str_contains($text, 'lose')
            || str_contains($text, 'fat loss')
            || str_contains($text, 'weight loss')
            || str_contains($text, 'slim')
            || str_contains($text, 'tone')
            || str_contains($text, 'tighten')
        ) {
            return 'fat_loss';
        }
        if (
            str_contains($text, 'muscle')
            || str_contains($text, 'gain')
            || str_contains($text, 'build')
            || str_contains($text, 'strength')
        ) {
            return 'muscle_gain';
        }

        return 'maintenance';
    }

    private function equipment(string $text): string
    {
        if (
            str_contains($text, 'gym')
            || str_contains($text, 'machine')
            || str_contains($text, 'cable')
            || str_contains($text, 'barbell')
        ) {
            return 'gym';
        }
        if (
            str_contains($text, 'dumbbell')
            || str_contains($text, 'dumbbells')
            || str_contains($text, 'resistance band')
            || str_contains($text, 'home')
        ) {
            return 'home_dumbbell';
        }

        return 'bodyweight';
    }

    private function level(string $text): string
    {
        $advancedSignals = ['advanced', 'athlete', '5 days', '6 days', 'heavy lifting', 'hypertrophy', 'performance'];
        foreach ($advancedSignals as $signal) {
            if (str_contains($text, $signal)) {
                return 'advanced';
            }
        }

        $beginnerSignals = ['new', 'beginner', 'inactive', 'sedentary', 'injury', 'injured', 'pain', 'low confidence', 'never'];
        foreach ($beginnerSignals as $signal) {
            if (str_contains($text, $signal)) {
                return 'beginner';
            }
        }

        $intermediateSignals = ['intermediate', 'consistent', 'advanced', '1 year', '2 year', '3 year', 'regularly', 'weight training'];
        foreach ($intermediateSignals as $signal) {
            if (str_contains($text, $signal)) {
                return 'intermediate';
            }
        }

        return 'beginner';
    }

    private function weeklyFrequency(string $text, string $level, array $injuries): int
    {
        if (preg_match('/\b([3-6])\s*(?:days|workouts|sessions)\b/', $text, $matches)) {
            return (int) $matches[1];
        }
        if ($injuries !== []) {
            return 3;
        }

        return match ($level) {
            'advanced' => 5,
            'intermediate' => 4,
            default => 3,
        };
    }

    private function preferredDuration(string $text, string $level): int
    {
        if (preg_match('/\b(15|20|30|45|60)\s*(?:min|minute|minutes)\b/', $text, $matches)) {
            return (int) $matches[1];
        }

        return match ($level) {
            'advanced' => 45,
            'intermediate' => 30,
            default => 30,
        };
    }

    private function needsMedicalClearance(string $text): bool
    {
        foreach (['pregnant', 'pregnancy', 'heart', 'cardiac', 'surgery', 'diabetes', 'hypertension', 'blood pressure', 'doctor'] as $signal) {
            if (str_contains($text, $signal)) {
                return true;
            }
        }

        return false;
    }

    private function injuries(string $text): array
    {
        $patterns = [
            'lower back' => '/\blower\s+back\b/',
            'back' => '/\bback\b/',
            'knee' => '/\bknee\b/',
            'shoulder' => '/\bshoulder\b/',
            'neck' => '/\bneck\b/',
            'hip' => '/\bhip\b/',
            'ankle' => '/\bankle\b/',
            'wrist' => '/\bwrist\b/',
            'elbow' => '/\belbow\b/',
        ];

        $injuries = [];
        foreach ($patterns as $injury => $pattern) {
            if (preg_match($pattern, $text)) {
                $injuries[] = $injury;
            }
        }

        if (in_array('lower back', $injuries, true) && in_array('back', $injuries, true)) {
            $injuries = array_values(array_diff($injuries, ['back']));
        }

        return $injuries;
    }

    private function activityFactor(string $text): float
    {
        if (str_contains($text, 'very active') || str_contains($text, 'athlete')) {
            return 1.725;
        }
        if (str_contains($text, 'active') || str_contains($text, '4 days') || str_contains($text, '5 days')) {
            return 1.55;
        }
        if (str_contains($text, 'light') || str_contains($text, '2 days') || str_contains($text, '3 days')) {
            return 1.375;
        }

        return 1.2;
    }

    private function gender(string $profileGender, string $text): ?string
    {
        if (in_array($profileGender, ['male', 'm', 'female', 'f'], true)) {
            return $profileGender;
        }
        if (str_contains($text, 'female') || str_contains($text, 'woman')) {
            return 'female';
        }
        if (str_contains($text, 'male') || str_contains($text, 'man')) {
            return 'male';
        }

        return $profileGender ?: null;
    }

    private function extractHeightCm(string $text): ?float
    {
        if (preg_match('/\bheight\b[^0-9]{0,20}(\d+(?:\.\d+)?)\s*cm\b/', $text, $matches)) {
            return (float) $matches[1];
        }
        if (preg_match('/\bheight\b[^0-9]{0,20}(\d+)\s*(?:ft|feet|\')\s*(\d+)?/', $text, $matches)) {
            return round(((int) $matches[1] * 30.48) + ((int) ($matches[2] ?? 0) * 2.54), 2);
        }

        return null;
    }

    private function extractWeightKg(string $text): ?float
    {
        if (preg_match('/\b(?:body\s+weight|current\s+weight|i\s+weigh|weight\s+is)\b[^0-9]{0,20}(\d+(?:\.\d+)?)\s*(kg|kgs|kilogram|kilograms|lb|lbs|pound|pounds)\b/', $text, $matches)) {
            $weight = (float) $matches[1];
            $unit = $matches[2];

            return in_array($unit, ['lb', 'lbs', 'pound', 'pounds'], true)
                ? round($weight * 0.453592, 2)
                : $weight;
        }

        return null;
    }
}
