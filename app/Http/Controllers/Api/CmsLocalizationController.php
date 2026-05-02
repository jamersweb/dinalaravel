<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\BulkTranslateContentJob;
use App\Models\AppUiStringTranslation;
use App\Models\Exercise;
use App\Models\Language;
use App\Models\Meal;
use App\Models\Program;
use App\Models\Workout;
use App\Services\ContentBulkTranslationService;
use App\Services\FlutterArbExportService;
use App\Support\ContentLocaleResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use ZipArchive;

class CmsLocalizationController extends Controller
{
    public function index()
    {
        $languages = Language::query()->orderBy('sort_order')->get();

        return response()->json([
            'status' => true,
            'data' => $languages,
        ]);
    }

    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'code' => 'required|string|max:16|unique:languages,code',
            'label' => 'required|string|max:191',
            'native_label' => 'required|string|max:191',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);
        if ($v->fails()) {
            return response()->json(['status' => false, 'message' => $v->errors()->first()]);
        }

        $lang = Language::query()->create([
            'code' => strtolower(trim($request->code)),
            'label' => $request->label,
            'native_label' => $request->native_label,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) $request->input('sort_order', 99),
        ]);

        return response()->json(['status' => true, 'data' => $lang]);
    }

    public function update(Request $request, int $id)
    {
        $lang = Language::query()->find($id);
        if (! $lang) {
            return response()->json(['status' => false, 'message' => 'Language not found'], 404);
        }

        $v = Validator::make($request->all(), [
            'label' => 'sometimes|string|max:191',
            'native_label' => 'sometimes|string|max:191',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0',
        ]);
        if ($v->fails()) {
            return response()->json(['status' => false, 'message' => $v->errors()->first()]);
        }

        $lang->fill($request->only(['label', 'native_label', 'is_active', 'sort_order']));
        $lang->save();

        return response()->json(['status' => true, 'data' => $lang]);
    }

    public function bulkTranslate(Request $request, ContentBulkTranslationService $translationService)
    {
        $v = Validator::make($request->all(), [
            'target_locale' => 'nullable|string|max:16|exists:languages,code',
            'target_locales' => 'nullable|array',
            'target_locales.*' => 'string|max:16|exists:languages,code',
            'source_locale' => 'nullable|string|max:16',
            'scopes' => 'required|array',
            'scopes.*' => 'in:meals,exercises,programs,workouts',
            'sync' => 'sometimes|boolean',
        ]);
        if ($v->fails()) {
            return response()->json(['status' => false, 'message' => $v->errors()->first()]);
        }

        if (! $request->filled('target_locale') && ! $request->filled('target_locales')) {
            return response()->json(['status' => false, 'message' => 'Provide target_locale or target_locales.']);
        }

        $source = strtolower($request->input('source_locale', 'en'));
        $targets = [];
        if ($request->filled('target_locale')) {
            $targets[] = strtolower(trim($request->target_locale));
        }
        if (is_array($request->target_locales)) {
            foreach ($request->target_locales as $t) {
                if ($t !== null && $t !== '') {
                    $targets[] = strtolower(trim((string) $t));
                }
            }
        }
        $targets = array_values(array_unique($targets));
        $targets = array_values(array_filter($targets, fn ($t) => $t !== '' && $t !== $source));

        if ($targets === []) {
            return response()->json(['status' => false, 'message' => 'No target locales after removing the source language. Add at least one other language.']);
        }

        $scopes = $request->scopes;
        $runSync = $request->boolean('sync', false)
            || Config::get('queue.default') === 'sync';

        if ($runSync) {
            set_time_limit(0);
            $mealsTotal = 0;
            $exercisesTotal = 0;
            $programsTotal = 0;
            $workoutsTotal = 0;
            $perTarget = [];
            foreach ($targets as $target) {
                $counts = $translationService->translateForTarget($source, $target, $scopes);
                $perTarget[$target] = $counts;
                $mealsTotal += $counts['meals_updated'];
                $exercisesTotal += $counts['exercises_updated'];
                $programsTotal += $counts['programs_updated'];
                $workoutsTotal += $counts['workouts_updated'];
            }

            return response()->json([
                'status' => true,
                'queued' => false,
                'message' => 'Bulk translate finished (synchronous).',
                'target_locales' => $targets,
                'per_target' => $perTarget,
                'meals_updated' => $mealsTotal,
                'exercises_updated' => $exercisesTotal,
                'programs_updated' => $programsTotal,
                'workouts_updated' => $workoutsTotal,
            ]);
        }

        BulkTranslateContentJob::dispatch($source, $targets, $scopes);

        return response()->json([
            'status' => true,
            'queued' => true,
            'message' => 'Bulk translate queued for '.count($targets).' locale(s). Run `php artisan queue:work` (set QUEUE_CONNECTION=database or redis in .env). Check storage/logs for progress.',
            'target_locales' => $targets,
            'source_locale' => $source,
        ]);
    }

    public function updateMealTranslations(Request $request)
    {
        $v = Validator::make($request->all(), [
            'meal_id' => 'required|integer|exists:meals,id',
            'locale' => 'required|string|max:16|exists:languages,code',
            'translations' => 'required|array',
        ]);
        if ($v->fails()) {
            return response()->json(['status' => false, 'message' => $v->errors()->first()]);
        }

        $meal = Meal::query()->find($request->meal_id);
        $locale = strtolower($request->locale);
        $incoming = $request->translations;
        $allowed = array_flip(ContentLocaleResolver::MEAL_FIELDS);
        $filtered = array_intersect_key($incoming, $allowed);

        $map = $meal->locale_translations;
        if (! is_array($map)) {
            $map = [];
        }
        $map[$locale] = array_merge($map[$locale] ?? [], array_filter($filtered, fn ($t) => is_string($t)));

        $meal->locale_translations = $map;
        $meal->save();

        return response()->json(['status' => true, 'message' => 'Meal translations saved.', 'data' => $meal->fresh()]);
    }

    public function updateExerciseTranslations(Request $request)
    {
        $v = Validator::make($request->all(), [
            'exercise_id' => 'required|integer|exists:exercises,id',
            'locale' => 'required|string|max:16|exists:languages,code',
            'translations' => 'required|array',
        ]);
        if ($v->fails()) {
            return response()->json(['status' => false, 'message' => $v->errors()->first()]);
        }

        $ex = Exercise::query()->find($request->exercise_id);
        $locale = strtolower($request->locale);
        $incoming = $request->translations;
        $allowed = array_flip(ContentLocaleResolver::EXERCISE_FIELDS);
        $filtered = array_intersect_key($incoming, $allowed);

        $map = $ex->locale_translations;
        if (! is_array($map)) {
            $map = [];
        }
        $map[$locale] = array_merge($map[$locale] ?? [], array_filter($filtered, fn ($t) => is_string($t)));

        $ex->locale_translations = $map;
        $ex->save();

        return response()->json(['status' => true, 'message' => 'Exercise translations saved.', 'data' => $ex->fresh()]);
    }

    public function updateProgramTranslations(Request $request)
    {
        $v = Validator::make($request->all(), [
            'program_id' => 'required|integer|exists:programs,id',
            'locale' => 'required|string|max:16|exists:languages,code',
            'translations' => 'required|array',
        ]);
        if ($v->fails()) {
            return response()->json(['status' => false, 'message' => $v->errors()->first()]);
        }

        $row = Program::query()->find($request->program_id);
        $locale = strtolower($request->locale);
        $incoming = $request->translations;
        $allowed = array_flip(ContentLocaleResolver::PROGRAM_FIELDS);
        $filtered = array_intersect_key($incoming, $allowed);

        $map = $row->locale_translations;
        if (! is_array($map)) {
            $map = [];
        }
        $map[$locale] = array_merge($map[$locale] ?? [], array_filter($filtered, fn ($t) => is_string($t)));

        $row->locale_translations = $map;
        $row->save();

        return response()->json(['status' => true, 'message' => 'Program translations saved.', 'data' => $row->fresh()]);
    }

    public function updateWorkoutTranslations(Request $request)
    {
        $v = Validator::make($request->all(), [
            'workout_id' => 'required|integer|exists:workouts,id',
            'locale' => 'required|string|max:16|exists:languages,code',
            'translations' => 'required|array',
        ]);
        if ($v->fails()) {
            return response()->json(['status' => false, 'message' => $v->errors()->first()]);
        }

        $row = Workout::query()->find($request->workout_id);
        $locale = strtolower($request->locale);
        $incoming = $request->translations;
        $allowed = array_flip(ContentLocaleResolver::WORKOUT_FIELDS);
        $filtered = array_intersect_key($incoming, $allowed);

        $map = $row->locale_translations;
        if (! is_array($map)) {
            $map = [];
        }
        $map[$locale] = array_merge($map[$locale] ?? [], array_filter($filtered, fn ($t) => is_string($t)));

        $row->locale_translations = $map;
        $row->save();

        return response()->json(['status' => true, 'message' => 'Workout translations saved.', 'data' => $row->fresh()]);
    }

    /**
     * Export Flutter app_en.arb message keys as flat JSON for CAT / MT pipelines.
     */
    public function exportEnUiStrings()
    {
        $path = config('localization.flutter_en_arb_path');
        if (! is_string($path) || $path === '' || ! is_readable($path)) {
            return response()->json([
                'status' => false,
                'message' => 'English ARB not readable. Set FLUTTER_EN_ARB_PATH in .env to the full path of app_en.arb, or place fitnesswithdina_mobile next to this API (default: ../fitnesswithdina_mobile/lib/l10n/app_en.arb).',
                'configured_path' => $path,
            ], 404);
        }

        $raw = file_get_contents($path);
        $data = json_decode($raw, true);
        if (! is_array($data)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid JSON in ARB file.',
            ], 422);
        }

        $strings = [];
        foreach ($data as $key => $value) {
            if (! is_string($key) || $key === '' || Str::startsWith($key, '@')) {
                continue;
            }
            if (is_string($value)) {
                $strings[$key] = $value;
            }
        }

        return response()->json([
            'status' => true,
            'meta' => [
                'source_file' => basename($path),
                'absolute_path' => $path,
                'source_locale' => 'en',
                'message_count' => count($strings),
                'exported_at' => now()->toIso8601String(),
                'hint' => 'Translate values; merge into lib/l10n/app_<locale>.arb keeping the same keys (same format as app_en.arb).',
            ],
            'strings' => $strings,
        ]);
    }

    /**
     * Flutter UI strings editor: source from app_<locale>.arb, target from DB (+ optional on-disk ARB).
     */
    public function uiStringsEditorData(Request $request)
    {
        $v = Validator::make($request->all(), [
            'source_locale' => 'required|string|max:16',
            'target_locale' => 'required|string|max:16|exists:languages,code',
        ]);
        if ($v->fails()) {
            return response()->json(['status' => false, 'message' => $v->errors()->first()]);
        }

        $sourceLocale = strtolower(trim($request->source_locale));
        $targetLocale = strtolower(trim($request->target_locale));

        $sourcePath = $this->flutterArbPathForLocale($sourceLocale);
        if (! is_readable($sourcePath)) {
            return response()->json([
                'status' => false,
                'message' => 'Source ARB not found or not readable: app_'.$sourceLocale.'.arb (configure FLUTTER_L10N_DIR / FLUTTER_EN_ARB_PATH).',
                'path' => $sourcePath,
            ], 404);
        }

        $sourceStrings = $this->parseArbStringsFromPath($sourcePath);
        if ($sourceStrings === []) {
            return response()->json([
                'status' => false,
                'message' => 'No translatable strings in source ARB.',
            ], 422);
        }

        $dbRows = AppUiStringTranslation::query()
            ->where('locale', $targetLocale)
            ->whereIn('message_key', array_keys($sourceStrings))
            ->get(['message_key', 'value']);

        $saved = [];
        foreach ($dbRows as $row) {
            $saved[$row->message_key] = $row->value;
        }

        $fileHint = [];
        $targetPath = $this->flutterArbPathForLocale($targetLocale);
        if (is_readable($targetPath) && $targetLocale !== $sourceLocale) {
            $fileHint = $this->parseArbStringsFromPath($targetPath);
        }

        $rows = [];
        foreach ($sourceStrings as $key => $sourceText) {
            $targetText = $saved[$key] ?? ($fileHint[$key] ?? '');
            $rows[] = [
                'key' => $key,
                'source' => $sourceText,
                'target' => $targetText,
            ];
        }

        return response()->json([
            'status' => true,
            'meta' => [
                'source_locale' => $sourceLocale,
                'target_locale' => $targetLocale,
                'source_file' => basename($sourcePath),
                'count' => count($rows),
                'saved_in_db' => count($saved),
            ],
            'rows' => $rows,
        ]);
    }

    public function saveUiStrings(Request $request)
    {
        $v = Validator::make($request->all(), [
            'target_locale' => 'required|string|max:16|exists:languages,code',
            'strings' => 'required|array',
        ]);
        if ($v->fails()) {
            return response()->json(['status' => false, 'message' => $v->errors()->first()]);
        }

        $locale = strtolower(trim($request->target_locale));
        $incoming = $request->strings;
        if (! is_array($incoming)) {
            return response()->json(['status' => false, 'message' => 'strings must be an object map.']);
        }

        $updated = 0;
        $deleted = 0;

        foreach ($incoming as $key => $value) {
            if (! is_string($key) || $key === '' || Str::startsWith($key, '@')) {
                continue;
            }
            if ($value === null || (is_string($value) && trim($value) === '')) {
                $n = AppUiStringTranslation::query()
                    ->where('locale', $locale)
                    ->where('message_key', $key)
                    ->delete();
                $deleted += $n;

                continue;
            }
            if (! is_string($value)) {
                continue;
            }
            AppUiStringTranslation::query()->updateOrCreate(
                ['locale' => $locale, 'message_key' => $key],
                ['value' => $value]
            );
            $updated++;
        }

        return response()->json([
            'status' => true,
            'message' => 'Translations saved.',
            'upserted' => $updated,
            'removed_empty' => $deleted,
            'locale' => $locale,
        ]);
    }

    /**
     * Merge English keys with DB translations for target locale; download as .arb JSON.
     */
    public function exportUiStringsArb(Request $request)
    {
        $v = Validator::make($request->all(), [
            'target_locale' => 'required|string|max:16|exists:languages,code',
        ]);
        if ($v->fails()) {
            return response()->json(['status' => false, 'message' => $v->errors()->first()]);
        }

        $targetLocale = strtolower(trim($request->target_locale));
        $enPath = config('localization.flutter_en_arb_path');
        if (! is_string($enPath) || ! is_readable($enPath)) {
            return response()->json([
                'status' => false,
                'message' => 'English ARB not readable for export.',
            ], 404);
        }

        $sourceStrings = $this->parseArbStringsFromPath($enPath);
        $db = empty($sourceStrings) ? [] : AppUiStringTranslation::query()
            ->where('locale', $targetLocale)
            ->whereIn('message_key', array_keys($sourceStrings))
            ->pluck('value', 'message_key')
            ->all();

        $out = [];
        foreach ($sourceStrings as $key => $enValue) {
            $out[$key] = (isset($db[$key]) && $db[$key] !== '') ? $db[$key] : $enValue;
        }

        $json = json_encode((object) $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return response()->json(['status' => false, 'message' => 'Could not encode ARB.'], 500);
        }

        $filename = 'app_'.$targetLocale.'.arb';

        return response($json, 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Zip of app_<locale>.arb for top spoken locales (see config localization.top_spoken_arb_locales).
     */
    public function exportTopSpokenArbsZip(FlutterArbExportService $arb)
    {
        $locales = config('localization.top_spoken_arb_locales', []);
        if (! is_array($locales) || $locales === []) {
            return response()->json(['status' => false, 'message' => 'top_spoken_arb_locales is not configured.'], 422);
        }

        if ($arb->loadTemplateStrings() === []) {
            return response()->json([
                'status' => false,
                'message' => 'English template ARB not readable. Set FLUTTER_EN_ARB_PATH.',
            ], 404);
        }

        if (! class_exists(ZipArchive::class)) {
            return response()->json(['status' => false, 'message' => 'PHP zip extension (ZipArchive) is not enabled.'], 500);
        }

        $tmp = storage_path('app/tmp_arb_'.Str::lower(Str::random(12)));
        if (! is_dir($tmp) && ! @mkdir($tmp, 0755, true) && ! is_dir($tmp)) {
            return response()->json(['status' => false, 'message' => 'Could not create temp directory.'], 500);
        }

        try {
            foreach ($locales as $code => $label) {
                $code = strtolower(trim((string) $code));
                $merged = $arb->mergedForLocale($code, (string) $label);
                if ($merged === []) {
                    continue;
                }
                File::put($tmp.DIRECTORY_SEPARATOR.'app_'.$code.'.arb', $arb->encodeArb($merged));
            }

            $zipPath = storage_path('framework/l10n_top20_spoken.zip');
            if (is_file($zipPath)) {
                @unlink($zipPath);
            }
            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                return response()->json(['status' => false, 'message' => 'Could not create zip archive.'], 500);
            }

            foreach (glob($tmp.DIRECTORY_SEPARATOR.'*.arb') ?: [] as $full) {
                if (is_file($full)) {
                    $body = file_get_contents($full);
                    if ($body !== false) {
                        $zip->addFromString(basename($full), $body);
                    }
                }
            }
            $zip->close();
        } finally {
            foreach (glob($tmp.DIRECTORY_SEPARATOR.'*') ?: [] as $f) {
                if (is_file($f)) {
                    @unlink($f);
                }
            }
            @rmdir($tmp);
        }

        return response()->download($zipPath, 'l10n_top20_spoken.zip', [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(false);
    }

    private function flutterL10nDir(): string
    {
        $dir = config('localization.flutter_l10n_dir');
        if (is_string($dir) && $dir !== '') {
            return rtrim($dir, DIRECTORY_SEPARATOR);
        }
        $en = config('localization.flutter_en_arb_path');
        if (is_string($en) && $en !== '') {
            return rtrim(dirname($en), DIRECTORY_SEPARATOR);
        }

        return base_path('../fitnesswithdina_mobile/lib/l10n');
    }

    private function flutterArbPathForLocale(string $locale): string
    {
        return $this->flutterL10nDir().DIRECTORY_SEPARATOR.'app_'.$locale.'.arb';
    }

    /**
     * @return array<string, string>
     */
    private function parseArbStringsFromPath(string $path): array
    {
        if (! is_readable($path)) {
            return [];
        }
        $raw = file_get_contents($path);
        if ($raw === false) {
            return [];
        }
        $data = json_decode($raw, true);
        if (! is_array($data)) {
            return [];
        }
        $strings = [];
        foreach ($data as $key => $value) {
            if (! is_string($key) || $key === '' || Str::startsWith($key, '@')) {
                continue;
            }
            if (is_string($value)) {
                $strings[$key] = $value;
            }
        }

        return $strings;
    }
}
