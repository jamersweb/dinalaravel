<?php

/**
 * Content numbering scheme (exercises, and reference blocks for workouts/programs).
 *
 * Exercises share prefix "EX" with numeric bands by muscle / group.
 * Other families use their own prefix + local number range.
 *
 * Edit `tag_to_segment` to match your CMS tag names (case-insensitive).
 * Edit `type_to_segment` keys to match exact "Exercise Type" dropdown values in the CMS.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | EX — strength / hypertrophy by body region (single prefix, banded numbers)
    |--------------------------------------------------------------------------
    */
    'ex_segments' => [
        'chest' => ['min' => 0, 'max' => 99, 'label' => 'Chest'],
        'back' => ['min' => 100, 'max' => 199, 'label' => 'Back'],
        'shoulders' => ['min' => 200, 'max' => 249, 'label' => 'Shoulders'],
        'biceps' => ['min' => 250, 'max' => 299, 'label' => 'Biceps'],
        'triceps' => ['min' => 300, 'max' => 349, 'label' => 'Triceps'],
        'quads' => ['min' => 350, 'max' => 449, 'label' => 'Quads'],
        'glutes_hamstrings' => ['min' => 450, 'max' => 599, 'label' => 'Glutes & hamstrings'],
        'calves' => ['min' => 600, 'max' => 649, 'label' => 'Calves'],
        'abs' => ['min' => 650, 'max' => 799, 'label' => 'Abs'],
    ],

    'ex_prefix' => 'EX',
    'ex_number_pad' => 3,

    /*
    |--------------------------------------------------------------------------
    | Other exercise families (own prefix + range)
    |--------------------------------------------------------------------------
    */
    'prefix_segments' => [
        'combos' => ['prefix' => 'CO', 'min' => 0, 'max' => 150, 'label' => 'Combos', 'pad' => 3],
        'functional' => ['prefix' => 'FU', 'min' => 0, 'max' => 150, 'label' => 'Conditioning / functional', 'pad' => 3],
        'cardio' => ['prefix' => 'CA', 'min' => 0, 'max' => 100, 'label' => 'Cardio', 'pad' => 3],
        'hiit' => ['prefix' => 'HIIT', 'min' => 0, 'max' => 100, 'label' => 'HIIT', 'pad' => 3],
        'warmups' => ['prefix' => 'WA', 'min' => 0, 'max' => 150, 'label' => 'Warm-ups & activation', 'pad' => 3],
        'mobility' => ['prefix' => 'MO', 'min' => 0, 'max' => 150, 'label' => 'Mobility', 'pad' => 3],
        'stretches' => ['prefix' => 'ST', 'min' => 0, 'max' => 150, 'label' => 'Stretches', 'pad' => 3],
    ],

    /*
    |--------------------------------------------------------------------------
    | Map CMS tag names → segment key (ex_segments or prefix_segments).
    | Add every tag you use for muscle / modality. Substrings are NOT matched; exact name after trim/lowercase.
    |--------------------------------------------------------------------------
    */
    'tag_to_segment' => [
        // EX — adjust to your real tag labels in CMS
        'chest' => 'chest',
        'back' => 'back',
        'shoulders' => 'shoulders',
        'biceps' => 'biceps',
        'triceps' => 'triceps',
        'quads' => 'quads',
        'glutes' => 'glutes_hamstrings',
        'hamstrings' => 'glutes_hamstrings',
        'calves' => 'calves',
        'abs' => 'abs',
        'core' => 'abs',
        'combo' => 'combos',
        'combos' => 'combos',
        'functional' => 'functional',
        'conditioning' => 'functional',
        'cardio' => 'cardio',
        'hiit' => 'hiit',
        'warm up' => 'warmups',
        'warmup' => 'warmups',
        'activation' => 'warmups',
        'mobility' => 'mobility',
        'stretch' => 'stretches',
        'stretches' => 'stretches',
    ],

    /*
    |--------------------------------------------------------------------------
    | Map CMS "Exercise Type" (exact string) → segment when tags do not decide.
    | Keys must match resources/js/.../editExercise.vue option values exactly.
    |--------------------------------------------------------------------------
    */
    'type_to_segment' => [
        'Cardio' => 'cardio',
        'HIIT' => 'hiit',
        'Warm up before workout' => 'warmups',
        'Stretches' => 'stretches',
        'Mobility' => 'mobility',
        // Strength / endurance types usually need a body-part tag for EX-* band
        'Strength-weps and weights' => null,
        'Endurance' => null,
        'Timed(longer better ex planks)' => null,
        'Timed(faster better ex sprints)' => null,
        'Times strength seconds and weights' => null,
        'Topic (nutrition & fitness)' => null,
        '--select--' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Workouts & programs (Artisan: workouts:assign-content-codes, programs:assign-content-codes)
    |--------------------------------------------------------------------------
    */
    'workout_routines' => ['prefix' => 'WR', 'min' => 0, 'max' => 500, 'label' => 'Workout routines', 'pad' => 3],
    'programs' => ['prefix' => 'PR', 'min' => 0, 'max' => 200, 'label' => 'Programs', 'pad' => 3],
];
