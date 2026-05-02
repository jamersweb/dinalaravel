# Workout/Program Builder Regression Fixes

**Date:** March 3, 2025

## 1. Rest Period

### Changes
- **Storage:** `rest_period` is now `INT` (seconds, 0-240), default 0
- **Migration:** Converts existing string values ("5 min", "10 sec") to seconds; caps at 240
- **Dropdown:** 0 sec, 5 sec, 10 sec … 1 min, 1 min 30 sec, 2 min, 3 min, 4 min
- **Display:** Formatted as "15 sec", "2 min", "1 min 30 sec" in program view/cards

### Files
- `database/migrations/2025_03_03_200000_workout_rest_period_and_superset_groups.php`
- `app/Models/WorkoutExercise.php` – fillable, casts, `formatRestPeriod()`
- `resources/js/config.js` – `restPeriodOptions`
- `resources/js/components/master-libraries/workoutBuilder.vue` – rest dropdown
- `resources/js/components/master-libraries/editWorkout.vue` – rest dropdown
- `resources/js/components/master-libraries/workoutDetail.vue` – `formatRestPeriod()`, rest display
- `app/Http/Controllers/Api/WorkoutController.php` – `normalizeRestPeriod()`

---

## 2. Superset Grouping

### Changes
- **Columns:** `group_id` (uuid), `group_type`, `group_order` on `workout_exercises`
- **Logic:** Each superset block gets a unique `group_id`; grouping another pair creates a new block
- **Rendering:** Group by `(group_type, group_id)` so multiple supersets show separate headings
- **Ungroup:** Only ungroups the selected block

### Files
- Same migration as above
- `app/Http/Controllers/Api/WorkoutController.php` – save `group_id`, `organizeExercises()` groups by `group_id`
- `app/Http/Controllers/Api/ProgramSubTrackingController.php` – same `organizeExercises()` logic
- `resources/js/components/master-libraries/workoutBuilder.vue` – `uuidv4()`, `catSelected()` adds `group_id`
- `resources/js/components/master-libraries/editWorkout.vue` – same

---

## Manual Test Steps

### Rest Period
1. Create a new workout, add an exercise
2. Set Rest Period to "0 sec", "30 sec", "1 min 30 sec", "4 min"
3. Save and view workout detail – rest should display correctly
4. Edit workout – rest dropdown should show saved value
5. Run migration on DB with existing data – verify no errors, old "5 min" etc. converted

### Superset
1. Add Ex1, Ex2, Ex3, Ex4 (simple)
2. Select Ex1+Ex2, click Superset → one "Superset" block
3. Select Ex3+Ex4, click Superset → second "Superset" block
4. Save – both blocks should persist
5. View workout – two separate "Superset" sections
6. Ungroup first block – only Ex1, Ex2 become simple; second superset unchanged
7. Edit and re-save – structure preserved

### Backward Compatibility
- Existing workouts with `rest_period` as string are migrated to seconds
- Existing supersets without `group_id` will render as before (grouped by category only)
