<?php

namespace App\Services;

class RoutineLibraryRules
{
    public const CONTENT_LANGUAGES = ['en', 'ar'];
    public const LANGUAGES = ['en', 'ar', 'no_audio'];
    public const LEVELS = ['beginner', 'intermediate', 'advanced'];
    public const EQUIPMENT_CATEGORIES = ['gym', 'full_gym', 'home_dumbbell', 'bodyweight'];
    public const STATUSES = ['draft', 'pending_review', 'approved', 'rejected', 'revision'];
    public const EXERCISE_TAG_REVIEW_STATUSES = ['pending_review', 'approved', 'rejected', 'needs_fix'];

    public const WORKOUT_TYPES = [
        'fbs' => 'Full Body Strength',
        'ubs' => 'Upper Body Strength',
        'lbs' => 'Lower Body Strength',
        'psh' => 'Push',
        'pul' => 'Pull',
        'leg' => 'Legs',
        'glu' => 'Glutes',
        'cor' => 'Core Focus',
        'hic' => 'HIIT Conditioning',
        'msc' => 'Mobility Strength Circuit',
        'cst' => 'Cardio Strength',
        'pbc' => 'Posture And Back Care',
        'fnc' => 'Functional Circuit',
    ];

    public const PROGRAM_DURATIONS_MINUTES = [15, 20, 30, 45, 60];
    public const PROGRAM_WEEKLY_FREQUENCIES = [3, 4, 5, 6];
    public const DEFAULT_PROGRAM_WEEKS = 12;
    public const IMPACT_LEVELS = ['low', 'moderate', 'high'];
    public const INTENSITY_LEVELS = ['low', 'moderate', 'high'];
    public const VIDEO_VARIANTS = ['explained', 'no_audio'];
    public const ROUTINE_SECTION_CONTRACT = 'master_ai_prompt_v1';
    public const LEGACY_ROUTINE_SECTION_CONTRACT = 'ai_program_builder_phase_3';
    public const EQUIPMENT_TAGS = ['bodyweight', 'dumbbells', 'machine', 'cable', 'barbell', 'cardio_machine', 'bench', 'mat', 'bands', 'kettlebell'];
    public const EXERCISE_TYPES = ['resistance', 'main', 'bodyweight', 'dumbbell', 'gym', 'cardio', 'cardio_warm_up', 'warm_up', 'mobility', 'stretching', 'activation', 'power_explosive', 'lower_back', 'abs', 'obliques'];
    public const TRAINING_STYLES = ['resistance_training', 'hypertrophy', 'muscular_endurance', 'conditioning', 'mobility', 'core', 'stretching', 'warm_up', 'circuit', 'hiit', 'steady_state_cardio'];
    public const MOVEMENT_DIRECTIONS = [
        'bilateral',
        'unilateral',
        'horizontal_push',
        'vertical_push',
        'horizontal_pull',
        'vertical_pull',
        'squat',
        'hinge',
        'lunge',
        'rotation',
        'anti_rotation',
        'locomotion',
        'static_hold',
        'loaded_carry',
        'lateral',
        'forward_backward',
        'multi_directional',
    ];
    public const STABILITY_DEMANDS = ['stable', 'supported', 'unsupported', 'unstable', 'single_leg', 'anti_rotation'];
    public const VARIATION_TYPES = ['base', 'regression', 'progression', 'alternative', 'advanced_variant', 'unilateral_variant', 'bilateral_variant', 'lateral_variant'];
    public const CONFIDENCE_BUCKETS = ['high', 'medium', 'low'];
    public const PRIMARY_CATEGORIES = [
        'resistance_training',
        'cardiovascular_training',
        'power_explosive_training',
        'mobility',
        'dynamic_warm_up',
        'muscle_activation',
        'flexibility_stretching',
        'balance_stability',
        'corrective_exercise',
        'recovery_breathing',
        'warm_up_cardio',
        'cool_down_cardio',
        'steady_state_cardio',
        'optional_additional_cardio',
        'hiit_cardio',
        'post_workout_stretching',
        'circuit_training',
    ];
    public const TRAINING_ADAPTATIONS = [
        'general_fitness',
        'strength',
        'hypertrophy',
        'muscular_endurance',
        'power',
        'explosiveness',
        'speed',
        'cardiovascular_endurance',
        'anaerobic_conditioning',
        'aerobic_conditioning',
        'mobility',
        'flexibility',
        'stability',
        'balance',
        'coordination',
        'muscle_activation',
        'movement_preparation',
        'rehabilitation_corrective',
        'recovery',
    ];
    public const PROGRAM_ROLES = [
        'warm_up',
        'warm_up_cardio',
        'dynamic_warm_up',
        'activation',
        'lower_back_core_preparation',
        'main_workout',
        'main_compound_exercise',
        'accessory_exercise',
        'isolation_exercise',
        'superset_exercise',
        'circuit_exercise',
        'hiit_interval',
        'cardio',
        'optional_cardio',
        'finisher',
        'core',
        'cool_down',
        'cool_down_stretching',
        'post_workout_stretching',
        'corrective',
        'recovery',
    ];
    public const BODY_REGIONS = [
        'full_body',
        'upper_body',
        'lower_body',
        'chest',
        'back',
        'shoulders',
        'arms',
        'glutes',
        'quadriceps',
        'hamstrings',
        'calves',
        'core',
        'abs',
        'obliques',
        'lower_back',
    ];
    public const MOVEMENT_PATTERNS = [
        'squat',
        'hinge',
        'lunge',
        'push',
        'pull',
        'carry',
        'rotation',
        'anti_rotation',
        'flexion',
        'extension',
        'abduction',
        'adduction',
        'stabilization',
        'locomotion',
        'jumping',
        'crawling',
        'mobility',
        'stretching',
    ];

    public const LAUNCH_MATRIX_PROGRAMS = [
        [
            'number' => 1,
            'name' => 'Bodyweight Foundations',
            'level' => 'beginner',
            'equipment_category' => 'bodyweight',
            'days_per_week' => 3,
            'minutes' => '25-30',
        ],
        [
            'number' => 2,
            'name' => 'Bodyweight Express',
            'level' => 'beginner',
            'equipment_category' => 'bodyweight',
            'days_per_week' => 3,
            'minutes' => '15',
        ],
        [
            'number' => 3,
            'name' => 'Dumbbell Home Foundations',
            'level' => 'beginner',
            'equipment_category' => 'home_dumbbell',
            'days_per_week' => 3,
            'minutes' => '30',
        ],
        [
            'number' => 4,
            'name' => 'Dumbbell Express',
            'level' => 'beginner',
            'equipment_category' => 'home_dumbbell',
            'days_per_week' => 3,
            'minutes' => '15',
        ],
        [
            'number' => 5,
            'name' => 'Gym Foundations',
            'level' => 'beginner',
            'equipment_category' => 'gym',
            'days_per_week' => 3,
            'minutes' => '45',
        ],
        [
            'number' => 6,
            'name' => 'Bodyweight Strength & Conditioning',
            'level' => 'intermediate',
            'equipment_category' => 'bodyweight',
            'days_per_week' => 4,
            'minutes' => '30',
        ],
        [
            'number' => 7,
            'name' => 'Bodyweight Express',
            'level' => 'intermediate',
            'equipment_category' => 'bodyweight',
            'days_per_week' => 4,
            'minutes' => '15',
        ],
        [
            'number' => 8,
            'name' => 'Dumbbell Strength & Shape',
            'level' => 'intermediate',
            'equipment_category' => 'home_dumbbell',
            'days_per_week' => 4,
            'minutes' => '30-45',
        ],
        [
            'number' => 9,
            'name' => 'Dumbbell Express',
            'level' => 'intermediate',
            'equipment_category' => 'home_dumbbell',
            'days_per_week' => 4,
            'minutes' => '15',
        ],
        [
            'number' => 10,
            'name' => 'Gym Strength & Shape',
            'level' => 'intermediate',
            'equipment_category' => 'gym',
            'days_per_week' => 4,
            'minutes' => '45-60',
        ],
        [
            'number' => 11,
            'name' => 'Bodyweight Performance',
            'level' => 'advanced',
            'equipment_category' => 'bodyweight',
            'days_per_week' => 5,
            'minutes' => '30-45',
        ],
        [
            'number' => 12,
            'name' => 'Dumbbell Hypertrophy',
            'level' => 'advanced',
            'equipment_category' => 'home_dumbbell',
            'days_per_week' => 5,
            'minutes' => '45',
        ],
        [
            'number' => 13,
            'name' => 'Gym Strength & Hypertrophy',
            'level' => 'advanced',
            'equipment_category' => 'full_gym',
            'days_per_week' => '5-6',
            'minutes' => '60',
        ],
    ];

    public const REQUIRED_WORKOUT_SECTIONS = [
        'dynamic_warm_up',
        'warm_up_cardio',
        'muscle_activation',
        'lower_back_core_superset',
        'main_workout',
        'post_workout_stretching',
    ];

    public const SECTION_MINIMUM_EXERCISES = [
        'dynamic_warm_up' => 5,
        'warm_up_cardio' => 1,
        'muscle_activation' => 1,
        'lower_back_core_superset' => 2,
        'main_workout' => 5,
        'post_workout_stretching' => 5,
    ];

    public const OPTIONAL_WORKOUT_SECTIONS = [
        'optional_additional_cardio',
    ];

    public const WORKOUT_SECTION_LABELS = [
        'dynamic_warm_up' => 'Dynamic Warm-Up',
        'warm_up_cardio' => 'Warm-Up Cardio',
        'mobility_dynamic_warm_up' => 'Mobility and Dynamic Warm-Up',
        'muscle_activation' => 'Muscle Activation',
        'lower_back_core_superset' => 'Lower-Back and Core Superset',
        'core_lower_back_preparation' => 'Core and Lower-Back Preparation',
        'main_workout' => 'Main Training Workout',
        'core_obliques' => 'Core and Obliques',
        'lower_back_strengthening' => 'Lower-Back Strengthening',
        'optional_additional_cardio' => 'Optional Additional Cardio',
        'post_workout_stretching' => 'Post-Workout Full-Body Stretching',
        'cool_down_stretching' => 'Cool-Down and Stretching',
    ];

    public const DINA_MANDATORY_USAGE = [
        'abs',
        'obliques',
        'lower_back_activation',
        'lower_back_strength',
        'stretching',
        'mobility',
    ];

    public const MOBILITY_FOCUS_ROTATION = [
        'hip_mobility',
        'thoracic_mobility',
        'shoulder_mobility',
        'ankle_mobility',
    ];

    public const DELOAD_WEEKS = [4, 8, 12];

    public const HORMONAL_CONDITION_RULES = [
        'hashimotos' => [
            'signals' => ['hashimoto', 'thyroid', 'hypothyroid'],
            'training_adjustments' => ['reduce excessive HIIT', 'prioritize strength with longer rest', 'add walking or low-intensity cardio', 'protect recovery'],
            'habit_suggestions' => ['daily steps', 'sleep routine', 'stress reduction', 'hydration'],
            'nutrition_suggestions' => ['consistent protein', 'anti-inflammatory meal choices', 'coach-reviewed calorie deficit only'],
        ],
        'pcos' => [
            'signals' => ['pcos', 'polycystic', 'insulin resistance', 'insulin resistant'],
            'training_adjustments' => ['prioritize strength training', 'use intervals sparingly', 'avoid chronic high-volume cardio', 'keep recovery predictable'],
            'habit_suggestions' => ['steps', 'water', 'protein habit', 'sleep consistency'],
            'nutrition_suggestions' => ['higher protein meals', 'fiber-rich carbohydrates', 'steady meal timing'],
        ],
        'endometriosis' => [
            'signals' => ['endometriosis', 'endo flare', 'pelvic pain'],
            'training_adjustments' => ['reduce intensity during flare-ups', 'use mobility and walking options', 'avoid painful bracing', 'extend warm-up and cooldown'],
            'habit_suggestions' => ['pain log', 'breathing', 'gentle walking', 'sleep support'],
            'nutrition_suggestions' => ['hydration', 'anti-inflammatory meal choices', 'coach-reviewed supplementation'],
        ],
        'menopause' => [
            'signals' => ['menopause', 'perimenopause', 'hot flash', 'hot flashes'],
            'training_adjustments' => ['prioritize progressive strength', 'manage impact', 'include balance and mobility', 'protect sleep recovery'],
            'habit_suggestions' => ['strength consistency', 'walking', 'sleep routine', 'stress reduction'],
            'nutrition_suggestions' => ['protein target', 'bone-supportive nutrition', 'hydration'],
        ],
        'high_stress' => [
            'signals' => ['adrenal fatigue', 'burnout', 'high stress', 'very stressed', 'poor sleep', 'insomnia'],
            'training_adjustments' => ['reduce density', 'avoid max-effort conditioning', 'add active recovery', 'use longer rest'],
            'habit_suggestions' => ['breathing', 'sleep routine', 'walks', 'screen cutoff'],
            'nutrition_suggestions' => ['regular meals', 'hydration', 'limit aggressive deficits'],
        ],
    ];

    public const PAIN_SUBSTITUTION_RULES = [
        'shoulder' => [
            'avoid' => ['overhead press', 'upright row', 'dip', 'behind neck', 'kipping'],
            'substitute' => ['landmine press', 'incline press', 'neutral-grip row', 'scaption raise'],
        ],
        'knee' => [
            'avoid' => ['jump squat', 'deep lunge', 'high-impact plyometric', 'sissy squat'],
            'substitute' => ['box squat', 'glute bridge', 'step-up to comfortable height', 'hip hinge'],
        ],
        'lower back' => [
            'avoid' => ['heavy deadlift', 'loaded good morning', 'high-impact flexion', 'unsupported bent-over row'],
            'substitute' => ['bird dog', 'dead bug', 'hip thrust', 'supported row'],
        ],
        'neck' => [
            'avoid' => ['loaded shrug', 'behind neck press', 'neck strain core work'],
            'substitute' => ['chest-supported row', 'dead bug', 'wall slide'],
        ],
        'wrist' => [
            'avoid' => ['push-up on flat palms', 'front rack', 'loaded wrist extension'],
            'substitute' => ['neutral-grip dumbbell press', 'push-up handles', 'forearm plank'],
        ],
    ];

    public const REQUIRED_AUDIT_USAGE = [
        'cardio_warm_up' => 'Cardio warm-up',
        'warm_up' => 'Warm-up',
        'mobility' => 'Mobility',
        'muscle_activation' => 'Muscle activation',
        'lower_back_activation' => 'Lower-back activation',
        'main_workout' => 'Main workout',
        'abs' => 'Abs',
        'obliques' => 'Obliques',
        'lower_back_strength' => 'Lower-back strengthening',
        'stretching' => 'Stretching',
    ];

    public static function routineId(string $equipment, string $level, string $typeCode, string $language, int $variation): string
    {
        $equipmentCode = [
            'gym' => 'GW',
            'full_gym' => 'FGW',
            'home_dumbbell' => 'HDW',
            'bodyweight' => 'BWW',
        ][$equipment] ?? strtoupper(substr($equipment, 0, 3));

        $levelCode = [
            'beginner' => 'BEG',
            'intermediate' => 'INT',
            'advanced' => 'ADV',
        ][$level] ?? strtoupper(substr($level, 0, 3));

        return sprintf(
            '%s-%s-%s-%s-%02d',
            $equipmentCode,
            $levelCode,
            strtoupper($typeCode),
            strtoupper($language),
            $variation
        );
    }

    public static function allowedExerciseEquipment(string $routineEquipment): array
    {
        if ($routineEquipment === 'bodyweight') {
            return ['bodyweight'];
        }
        if ($routineEquipment === 'home_dumbbell') {
            return ['home_dumbbell', 'bodyweight'];
        }
        if ($routineEquipment === 'gym') {
            return ['gym', 'home_dumbbell', 'bodyweight'];
        }

        return ['full_gym', 'gym', 'home_dumbbell', 'bodyweight'];
    }

    public static function preferredExerciseEquipment(string $routineEquipment): array
    {
        return match ($routineEquipment) {
            'bodyweight' => ['bodyweight'],
            'home_dumbbell' => ['home_dumbbell'],
            'gym' => ['gym'],
            'full_gym' => ['full_gym', 'gym'],
            default => ['bodyweight'],
        };
    }

    public static function normalizeLanguage(?string $language): string
    {
        $language = strtolower(trim((string) $language));
        $language = str_replace('-', '_', $language);

        if (in_array($language, ['no', 'none', 'silent', 'noaudio'], true)) {
            return 'no_audio';
        }

        return in_array($language, self::LANGUAGES, true) ? $language : 'en';
    }

    public static function normalizeLevel(?string $level): string
    {
        $level = strtolower((string) $level);

        return in_array($level, self::LEVELS, true) ? $level : 'beginner';
    }

    public static function normalizeEquipment(?string $equipment): string
    {
        $equipment = strtolower((string) $equipment);

        return in_array($equipment, self::EQUIPMENT_CATEGORIES, true) ? $equipment : 'bodyweight';
    }

    public static function normalizeTaxonomyValue($value, array $allowed, string $fallback): string
    {
        if (is_array($value)) {
            $value = reset($value);
        }

        $value = strtolower(trim((string) $value));
        $value = str_replace(['&', '/', '-'], ' ', $value);
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? '';
        $value = trim($value, '_');
        if (in_array($value, $allowed, true)) {
            return $value;
        }

        $aliases = [
            'strength_training' => 'resistance_training',
            'resistance' => 'resistance_training',
            'strength' => 'strength',
            'cardio' => 'cardiovascular_training',
            'cardiovascular' => 'cardiovascular_training',
            'power_and_explosive_training' => 'power_explosive_training',
            'explosive_training' => 'power_explosive_training',
            'power_training' => 'power_explosive_training',
            'stretching' => 'flexibility_stretching',
            'flexibility_and_stretching' => 'flexibility_stretching',
            'warm_up' => 'dynamic_warm_up',
            'dynamic_warmup' => 'dynamic_warm_up',
            'rehabilitation_or_corrective' => 'rehabilitation_corrective',
            'rehab_corrective' => 'rehabilitation_corrective',
            'cool_down' => 'cool_down_stretching',
            'cooldown' => 'cool_down_stretching',
            'warmup_cardio' => 'warm_up_cardio',
            'cardio_warm_up' => 'warm_up_cardio',
            'warm_up_cardio_training' => 'warm_up_cardio',
            'cooldown_cardio' => 'cool_down_cardio',
            'cool_down_cardio_training' => 'cool_down_cardio',
            'steady_cardio' => 'steady_state_cardio',
            'steady_state' => 'steady_state_cardio',
            'hiit_training' => 'hiit_cardio',
            'post_workout_stretch' => 'post_workout_stretching',
            'post_workout_stretches' => 'post_workout_stretching',
            'flexibility_and_stretching_post_workout' => 'post_workout_stretching',
            'circuit' => 'circuit_training',
            'lower_back_and_core_preparation' => 'lower_back_core_preparation',
            'main_compound' => 'main_compound_exercise',
            'compound_exercise' => 'main_compound_exercise',
            'accessory' => 'accessory_exercise',
            'isolation' => 'isolation_exercise',
            'superset' => 'superset_exercise',
            'circuit_exercises' => 'circuit_exercise',
            'hiit_intervals' => 'hiit_interval',
            'optional_additional_cardio' => 'optional_cardio',
            'post_workout_stretch' => 'post_workout_stretching',
            'main' => 'main_workout',
        ];
        if ($value === 'hiit') {
            $value = in_array('hiit_cardio', $allowed, true) ? 'hiit_cardio' : 'hiit_interval';
        } elseif ($value === 'circuit') {
            $value = in_array('circuit_training', $allowed, true) ? 'circuit_training' : 'circuit_exercise';
        } elseif (isset($aliases[$value])) {
            $value = $aliases[$value];
        }

        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    public static function usageMatches(array $flags, string $usage): bool
    {
        return ($flags[$usage] ?? false) === true
            || ($flags[$usage] ?? null) === 1
            || ($flags[$usage] ?? null) === '1'
            || ($flags[$usage] ?? null) === 'true';
    }
}
