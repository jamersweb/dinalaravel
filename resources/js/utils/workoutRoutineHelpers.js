export const ROUTINE_SECTION_TAGS = [
    { id: 'warm_up_cardio', label: 'Warm-up cardio' },
    { id: 'warm_up', label: 'Warm-up routine' },
    { id: 'workout_routine', label: 'Workout routine' },
    { id: 'cardio', label: 'Cardio' },
    { id: 'stretching', label: 'Stretching' },
    { id: 'custom', label: 'Custom' },
];

export function snakeToCapitalize(value) {
    return String(value)
        .replace(/^[-_]*(.)/, (_, c) => c.toUpperCase())
        .replace(/[-_]+(.)/g, (_, c) => ' ' + c.toUpperCase());
}

export function normalizeExerciseItem(item) {
    if (!item) {
        return null;
    }
    const detail = item.exercise_detail || {};
    return {
        exercise_id: item.exercise_id ?? null,
        sets: item.sets ?? 1,
        time: item.time ?? '5 sec',
        reps: item.reps ?? 1,
        reps_type: item.reps_type ?? 'text',
        rest_period: item.rest_period ?? 0,
        tempTitle: detail.title || item.tempTitle || (item.exercise_id ? 'Exercise' : 'Rest'),
        tempUrl: detail.image || item.tempUrl || '',
        description: item.description ?? null,
        exercise_detail: detail.title
            ? { title: detail.title, image: detail.image }
            : (item.exercise_detail || undefined),
    };
}

export function flattenWorkoutExercises(exs) {
    const items = [];
    (exs || []).forEach((block) => {
        if (block.type === 'simple' && block.item) {
            const normalized = normalizeExerciseItem(block.item);
            if (normalized) {
                items.push(normalized);
            }
            return;
        }
        if (Array.isArray(block.items)) {
            block.items.forEach((sub) => {
                const normalized = normalizeExerciseItem(sub);
                if (normalized) {
                    items.push(normalized);
                }
            });
        }
    });
    return items;
}

export function buildRoutineGroup({
    sectionTag,
    routineTitle,
    items,
    order,
    groupId,
    routineId = null,
}) {
    const tag = sectionTag || 'custom';
    const titleSuffix = routineTitle ? `: ${routineTitle}` : '';
    return {
        order,
        type: tag,
        sets_rounds: 1,
        items,
        type_name: `${snakeToCapitalize(tag)}${titleSuffix}`,
        group_id: groupId,
        source_routine_id: routineId,
    };
}
