<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientRoutineAssignment;
use App\Models\AiExerciseTagProposal;
use App\Models\ConsultationRecommendation;
use App\Models\Exercise;
use App\Models\ExerciseLibraryTag;
use App\Models\Program;
use App\Models\ProgramPhase;
use App\Models\ProgramPhaseWorkout;
use App\Models\ProgramSub;
use App\Models\RoutineGenerationBatch;
use App\Models\WeekWiseProgram;
use App\Models\Workout;
use App\Services\AiLaunchProgramBuilderService;
use App\Services\AiProgramValidatorService;
use App\Services\ConsultationRecommendationService;
use App\Services\ClientRoutineProgramService;
use App\Services\RoutineContentAuditService;
use App\Services\RoutineExerciseAutoTaggerService;
use App\Services\RoutineGeneratorService;
use App\Services\RoutineLibraryRules;
use App\Services\RoutineValidatorService;
use App\Services\OllamaExerciseTaggerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RoutineLibraryController extends Controller
{
    public function aiVideoTagProposals(Request $request)
    {
        $query = AiExerciseTagProposal::query()
            ->with(['exercise' => function ($q) {
                $q->select([
                    'id',
                    'title',
                    'type',
                    'language',
                    'video_type',
                    'video_url',
                    'image',
                    'custom_thumbnail',
                    'content_code',
                    'tags',
                ]);
            }])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->language, function ($q, $language) {
                $q->whereHas('exercise', fn ($exerciseQuery) => $exerciseQuery->where('language', $language));
            })
            ->when($request->search, function ($q, $search) {
                $q->whereHas('exercise', function ($exerciseQuery) use ($search) {
                    $exerciseQuery->where('title', 'like', '%' . $search . '%')
                        ->orWhere('content_code', 'like', '%' . $search . '%');
                });
            });

        return response()->json([
            'status' => true,
            'data' => $query
                ->latest('created_at')
                ->paginate(max(10, min(100, (int) $request->get('per_page', 20)))),
            'summary' => [
                'total' => AiExerciseTagProposal::count(),
                'queued' => AiExerciseTagProposal::where('status', 'queued')->count(),
                'processing' => AiExerciseTagProposal::where('status', 'processing')->count(),
                'proposed' => AiExerciseTagProposal::where('status', 'proposed')->count(),
                'applied' => AiExerciseTagProposal::where('status', 'applied')->count(),
                'rejected' => AiExerciseTagProposal::where('status', 'rejected')->count(),
                'failed' => AiExerciseTagProposal::where('status', 'failed')->count(),
            ],
            'options' => [
                'languages' => RoutineLibraryRules::CONTENT_LANGUAGES,
                'equipment_categories' => RoutineLibraryRules::EQUIPMENT_CATEGORIES,
                'levels' => RoutineLibraryRules::LEVELS,
                'proposal_statuses' => ['queued', 'processing', 'proposed', 'applied', 'rejected', 'failed'],
                'default_model' => config('services.ollama.model', 'qwen2.5vl:7b'),
            ],
        ]);
    }

    public function generateAiVideoTagProposals(Request $request, OllamaExerciseTaggerService $service)
    {
        $validator = Validator::make($request->all(), [
            'language' => ['nullable', Rule::in(RoutineLibraryRules::CONTENT_LANGUAGES)],
            'equipment_category' => ['nullable', Rule::in(RoutineLibraryRules::EQUIPMENT_CATEGORIES)],
            'scope' => ['nullable', Rule::in(['all', 'tagged', 'untagged'])],
            'search' => 'nullable|string|max:255',
            'limit' => 'nullable|integer|min:1|max:50',
            'model' => 'nullable|string|max:128',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $summary = $service->generateProposals($validator->validated());

            return response()->json([
                'status' => true,
                'message' => sprintf(
                    'AI video tag proposals queued. Queued %d video(s). Keep the queue worker running; results will appear automatically.',
                    $summary['queued'] ?? 0
                ),
                'data' => $summary,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function applyAiVideoTagProposal(Request $request, int $id, OllamaExerciseTaggerService $service)
    {
        $proposal = AiExerciseTagProposal::find($id);
        if (! $proposal) {
            return response()->json([
                'status' => false,
                'message' => 'AI tag proposal not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'approve' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $tag = $service->applyProposal($proposal, $request->boolean('approve'));

            return response()->json([
                'status' => true,
                'message' => $request->boolean('approve')
                    ? 'AI proposal applied and approved for generation.'
                    : 'AI proposal applied for manual review.',
                'data' => $tag,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function rejectAiVideoTagProposal(int $id, OllamaExerciseTaggerService $service)
    {
        $proposal = AiExerciseTagProposal::find($id);
        if (! $proposal) {
            return response()->json([
                'status' => false,
                'message' => 'AI tag proposal not found.',
            ], 404);
        }

        try {
            $service->rejectProposal($proposal);

            return response()->json([
                'status' => true,
                'message' => 'AI proposal removed. Generate again to add a fresh proposal for this video.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function clearRejectedAiVideoTagProposals(OllamaExerciseTaggerService $service)
    {
        try {
            $deleted = $service->clearRejectedProposals();

            return response()->json([
                'status' => true,
                'message' => "Removed {$deleted} rejected/failed AI proposal(s). Generate again to add fresh proposals.",
                'data' => ['deleted' => $deleted],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function audit(Request $request, RoutineContentAuditService $auditService)
    {
        return response()->json([
            'status' => true,
            'data' => $auditService->audit($request->only(['language', 'equipment_category'])),
        ]);
    }

    public function syncExerciseTags(Request $request, RoutineExerciseAutoTaggerService $tagger)
    {
        $validator = Validator::make($request->all(), [
            'approve' => 'sometimes|boolean',
            'replace' => 'sometimes|boolean',
            'include_no_audio' => 'sometimes|boolean',
            'preserve_review_status' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $summary = $tagger->tag([
            'approve' => $request->boolean('approve'),
            'replace' => $request->boolean('replace'),
            'include_no_audio' => $request->boolean('include_no_audio'),
            'preserve_review_status' => $request->boolean('preserve_review_status', true),
        ]);

        return response()->json([
            'status' => true,
            'message' => sprintf(
                'Exercise sync complete. Scanned %d, tagged %d, skipped %d.',
                $summary['scanned'] ?? 0,
                $summary['tagged'] ?? 0,
                $summary['skipped'] ?? 0
            ),
            'data' => [
                'summary' => $summary,
                'report' => $tagger->report(),
            ],
        ]);
    }

    public function launchMatrixDashboard(Request $request, RoutineContentAuditService $auditService, AiProgramValidatorService $validatorService)
    {
        $weeks = max(1, min(16, (int) $request->get('weeks', 12)));
        $audit = $auditService->audit([]);
        $readiness = collect($audit['launch_matrix_readiness'] ?? [])->keyBy('number');
        $languages = RoutineLibraryRules::CONTENT_LANGUAGES;

        $programs = collect(RoutineLibraryRules::LAUNCH_MATRIX_PROGRAMS)
            ->map(function (array $definition) use ($readiness, $languages, $weeks, $validatorService) {
                $languageStates = [];
                foreach ($languages as $language) {
                    $contentCode = sprintf('AI-LAUNCH-%02d-%s-%dw', $definition['number'], strtoupper($language), $weeks);
                    $program = Program::where('content_code', $contentCode)
                        ->withCount('aiWeekDays')
                        ->first();
                    $validation = $program ? $validatorService->validateProgram($program) : null;
                    $phaseIds = $program ? ProgramPhase::where('program_id', $program->id)->pluck('id') : collect();

                    $languageReadiness = $readiness->get($definition['number'])['languages'][$language] ?? null;
                    $routineReadiness = $this->launchRoutineReadiness($definition, $language);
                    $sourceStatus = $languageReadiness['status'] ?? 'blocked';
                    $buildStatus = $sourceStatus;
                    if ($sourceStatus === 'ready') {
                        if ($routineReadiness['approved_count'] >= $routineReadiness['minimum_required']) {
                            $buildStatus = 'ready_to_build';
                        } elseif (($routineReadiness['approved_count'] + $routineReadiness['pending_review_count']) >= $routineReadiness['minimum_required']) {
                            $buildStatus = 'needs_routine_review';
                        } else {
                            $buildStatus = 'needs_routines';
                        }
                    }

                    $languageStates[$language] = [
                        'language' => $language,
                        'content_code' => $contentCode,
                        'status' => $program
                            ? ($validation && $validation['valid'] ? 'built_valid' : 'built_invalid')
                            : $buildStatus,
                        'program_id' => $program?->id,
                        'weeks' => $program ? (int) $program->aiWeekDays()->distinct('week_no')->count('week_no') : 0,
                        'days' => $program ? (int) $program->ai_week_days_count : 0,
                        'phase_routines' => $phaseIds->isNotEmpty()
                            ? ProgramPhaseWorkout::whereIn('program_phase_id', $phaseIds)->count()
                            : 0,
                        'validation' => $validation,
                        'readiness' => $languageReadiness,
                        'routine_readiness' => $routineReadiness,
                    ];
                }

                return [
                    'number' => $definition['number'],
                    'name' => $definition['name'],
                    'level' => $definition['level'],
                    'equipment_category' => $definition['equipment_category'],
                    'days_per_week' => $definition['days_per_week'],
                    'minutes' => $definition['minutes'],
                    'readiness' => $readiness->get($definition['number']),
                    'languages' => $languageStates,
                ];
            })
            ->values()
            ->all();

        $states = collect($programs)->flatMap(fn ($program) => array_values($program['languages']));

        return response()->json([
            'status' => true,
            'data' => [
                'weeks' => $weeks,
                'languages' => $languages,
                'summary' => [
                    'programs' => count($programs),
                    'built_valid' => $states->where('status', 'built_valid')->count(),
                    'built_invalid' => $states->where('status', 'built_invalid')->count(),
                    'ready_to_build' => $states->where('status', 'ready_to_build')->count(),
                    'blocked' => $states->where('status', 'blocked')->count(),
                    'needs_review' => $states->where('status', 'needs_review')->count(),
                    'needs_routines' => $states->where('status', 'needs_routines')->count(),
                    'needs_routine_review' => $states->where('status', 'needs_routine_review')->count(),
                ],
                'programs' => $programs,
            ],
        ]);
    }

    private function launchRoutineReadiness(array $definition, string $language): array
    {
        $minimum = $this->launchWorkoutSlots($definition['days_per_week']);
        $routines = Workout::query()
            ->where('routine_source', 'generated')
            ->where('language', $language)
            ->where('equipment_category', $definition['equipment_category'])
            ->where('fitness_level', $definition['level'])
            ->get()
            ->filter(fn (Workout $routine) => $this->routineUsesPhase3Contract($routine))
            ->filter(fn (Workout $routine) => $this->routineMatchesProgramDuration($routine, (string) $definition['minutes']));

        return [
            'minimum_required' => $minimum,
            'approved_count' => $routines->where('routine_status', 'approved')->count(),
            'pending_review_count' => $routines->where('routine_status', 'pending_review')->count(),
            'revision_count' => $routines->where('routine_status', 'revision')->count(),
            'generate_payload' => [
                'language' => $language,
                'equipment_category' => $definition['equipment_category'],
                'fitness_level' => $definition['level'],
                'target_minutes' => $this->targetMinutesForLaunch((string) $definition['minutes']),
                'limit' => max(10, $minimum * 3),
                'variations_per_type' => 1,
            ],
        ];
    }

    private function launchWorkoutSlots(int|string $daysPerWeek): int
    {
        if (is_string($daysPerWeek) && str_contains($daysPerWeek, '-')) {
            return max(array_map('intval', explode('-', $daysPerWeek)));
        }

        return max(1, (int) $daysPerWeek);
    }

    private function targetMinutesForLaunch(string $minutes): int
    {
        preg_match_all('/\d+/', $minutes, $matches);
        $numbers = array_map('intval', $matches[0] ?? []);
        $target = $numbers === [] ? 30 : max($numbers);
        $allowed = collect(RoutineLibraryRules::PROGRAM_DURATIONS_MINUTES);

        return (int) $allowed
            ->sortBy(fn (int $allowedMinutes) => abs($allowedMinutes - $target))
            ->first();
    }

    private function routineUsesPhase3Contract(Workout $routine): bool
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

    private function routineMatchesProgramDuration(Workout $routine, string $minutes): bool
    {
        $routineSections = is_array($routine->routine_sections) ? $routine->routine_sections : [];
        $targetMinutes = (int) ($routineSections['_meta']['target_minutes'] ?? 0);
        if ($targetMinutes <= 0) {
            return false;
        }

        preg_match_all('/\d+/', $minutes, $matches);
        $numbers = array_map('intval', $matches[0] ?? []);
        if ($numbers === []) {
            return true;
        }

        return $targetMinutes >= min($numbers) && $targetMinutes <= max($numbers);
    }

    public function buildLaunchMatrix(Request $request, AiLaunchProgramBuilderService $builder)
    {
        $validator = Validator::make($request->all(), [
            'number' => 'nullable|integer|min:1|max:13',
            'language' => ['nullable', Rule::in(RoutineLibraryRules::CONTENT_LANGUAGES)],
            'weeks' => 'nullable|integer|min:1|max:16',
            'replace' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $weeks = max(1, min(16, (int) $request->get('weeks', 12)));
        $replace = $request->boolean('replace');

        try {
            if ($request->filled('number')) {
                $language = $request->get('language', 'en');
                $result = $builder->buildLaunchProgram((int) $request->number, $language, $weeks, $replace);
                $program = $result['program'];

                return response()->json([
                    'status' => $result['validation']['valid'],
                    'message' => $result['validation']['valid'] ? 'Launch program ready.' : 'Launch program built but validation failed.',
                    'data' => [
                        'mode' => 'single',
                        'program_id' => $program->id,
                        'content_code' => $program->content_code,
                        'title' => $program->title,
                        'status' => $result['status'],
                        'validation' => $result['validation'],
                    ],
                ], $result['validation']['valid'] ? 200 : 422);
            }

            $languages = $request->filled('language') ? [$request->language] : RoutineLibraryRules::CONTENT_LANGUAGES;
            $results = $builder->buildLaunchMatrix($languages, $weeks, $replace);
            $blocked = collect($results)->where('status', 'blocked')->count();
            $invalid = collect($results)->filter(fn ($result) => array_key_exists('valid', $result) && $result['valid'] === false)->count();

            return response()->json([
                'status' => $blocked === 0 && $invalid === 0,
                'message' => $blocked === 0 && $invalid === 0 ? 'Launch matrix ready.' : 'Launch matrix has blocked or invalid programs.',
                'data' => [
                    'mode' => 'matrix',
                    'weeks' => $weeks,
                    'replace' => $replace,
                    'summary' => [
                        'created' => collect($results)->where('status', 'created')->count(),
                        'existing' => collect($results)->where('status', 'existing')->count(),
                        'blocked' => $blocked,
                        'invalid' => $invalid,
                    ],
                    'results' => $results,
                ],
            ], $blocked === 0 && $invalid === 0 ? 200 : 422);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function tagExercise(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exercise_id' => 'required|exists:exercises,id',
            'language' => ['required', Rule::in(RoutineLibraryRules::LANGUAGES)],
            'equipment_category' => ['required', Rule::in(RoutineLibraryRules::EQUIPMENT_CATEGORIES)],
            'equipment_tags' => 'nullable|array',
            'muscle_group' => 'nullable|string|max:64',
            'secondary_muscle_groups' => 'nullable|array',
            'exercise_type' => 'required|string|max:64',
            'movement_patterns' => 'nullable|array',
            'training_styles' => 'nullable|array',
            'workout_sections' => 'nullable|array',
            'impact_level' => ['nullable', Rule::in(RoutineLibraryRules::IMPACT_LEVELS)],
            'intensity_level' => ['nullable', Rule::in(RoutineLibraryRules::INTENSITY_LEVELS)],
            'video_variant' => ['nullable', Rule::in(RoutineLibraryRules::VIDEO_VARIANTS)],
            'recommended_duration_seconds' => 'nullable|integer|min:0|max:3600',
            'recommended_repetitions' => 'nullable|string|max:64',
            'recommended_sets' => 'nullable|string|max:64',
            'recommended_rest_seconds' => 'nullable|integer|min:0|max:600',
            'safety_notes' => 'nullable|array',
            'contraindications' => 'nullable|array',
            'difficulty' => ['required', Rule::in(RoutineLibraryRules::LEVELS)],
            'injury_cautions' => 'nullable|array',
            'goal_fit' => 'nullable|array',
            'usage_flags' => 'nullable|array',
            'approved_for_generation' => 'boolean',
            'review_status' => ['nullable', Rule::in(RoutineLibraryRules::EXERCISE_TAG_REVIEW_STATUSES)],
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $payload = $validator->validated();
        if (! isset($payload['review_status'])) {
            $payload['review_status'] = ($payload['approved_for_generation'] ?? false) ? 'approved' : 'pending_review';
        }
        if (($payload['review_status'] ?? null) !== 'approved') {
            $payload['approved_for_generation'] = false;
        }

        $tag = ExerciseLibraryTag::updateOrCreate(
            ['exercise_id' => $request->exercise_id],
            $payload
        );

        return response()->json([
            'status' => true,
            'data' => $tag,
        ]);
    }

    public function exerciseTags(Request $request)
    {
        $query = ExerciseLibraryTag::query()
            ->with(['exercise' => function ($q) {
                $q->select([
                    'id',
                    'title',
                    'type',
                    'language',
                    'video_type',
                    'video_url',
                    'image',
                    'custom_thumbnail',
                    'content_code',
                    'tags',
                ]);
            }])
            ->when($request->language, fn ($q, $language) => $q->where('language', $language))
            ->when($request->equipment_category, fn ($q, $equipment) => $q->where('equipment_category', $equipment))
            ->when($request->difficulty, fn ($q, $difficulty) => $q->where('difficulty', $difficulty))
            ->when($request->exercise_type, fn ($q, $type) => $q->where('exercise_type', $type))
            ->when($request->impact_level, fn ($q, $impact) => $q->where('impact_level', $impact))
            ->when($request->intensity_level, fn ($q, $intensity) => $q->where('intensity_level', $intensity))
            ->when($request->video_variant, fn ($q, $variant) => $q->where('video_variant', $variant))
            ->when($request->review_status, fn ($q, $status) => $q->where('review_status', $status))
            ->when($request->search, function ($q, $search) {
                $q->whereHas('exercise', function ($exerciseQuery) use ($search) {
                    $exerciseQuery->where('title', 'like', '%' . $search . '%')
                        ->orWhere('content_code', 'like', '%' . $search . '%');
                });
            });

        $summary = [
            'total' => ExerciseLibraryTag::count(),
            'pending_review' => ExerciseLibraryTag::where('review_status', 'pending_review')->count(),
            'approved' => ExerciseLibraryTag::where('review_status', 'approved')->count(),
            'needs_fix' => ExerciseLibraryTag::where('review_status', 'needs_fix')->count(),
            'rejected' => ExerciseLibraryTag::where('review_status', 'rejected')->count(),
            'available_for_generation' => ExerciseLibraryTag::where('approved_for_generation', true)->count(),
            'untagged' => Exercise::doesntHave('libraryTag')->count(),
        ];

        $tags = $query
            ->orderByRaw("case review_status when 'pending_review' then 0 when 'needs_fix' then 1 when 'approved' then 2 else 3 end")
            ->latest('updated_at')
            ->paginate((int) $request->get('per_page', 20));

        return response()->json([
            'status' => true,
            'data' => $tags,
            'summary' => $summary,
            'options' => [
                'languages' => RoutineLibraryRules::LANGUAGES,
                'equipment_categories' => RoutineLibraryRules::EQUIPMENT_CATEGORIES,
                'levels' => RoutineLibraryRules::LEVELS,
                'impact_levels' => RoutineLibraryRules::IMPACT_LEVELS,
                'intensity_levels' => RoutineLibraryRules::INTENSITY_LEVELS,
                'video_variants' => RoutineLibraryRules::VIDEO_VARIANTS,
                'movement_patterns' => RoutineLibraryRules::MOVEMENT_PATTERNS,
                'review_statuses' => RoutineLibraryRules::EXERCISE_TAG_REVIEW_STATUSES,
                'usage_flags' => array_keys(RoutineLibraryRules::REQUIRED_AUDIT_USAGE),
            ],
        ]);
    }

    public function reviewExerciseTag(Request $request, int $id)
    {
        $tag = ExerciseLibraryTag::find($id);
        if (! $tag) {
            return response()->json([
                'status' => false,
                'message' => 'Exercise tag not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'language' => ['required', Rule::in(RoutineLibraryRules::LANGUAGES)],
            'equipment_category' => ['required', Rule::in(RoutineLibraryRules::EQUIPMENT_CATEGORIES)],
            'equipment_tags' => 'nullable|array',
            'muscle_group' => 'nullable|string|max:64',
            'secondary_muscle_groups' => 'nullable|array',
            'exercise_type' => 'required|string|max:64',
            'movement_patterns' => 'nullable|array',
            'training_styles' => 'nullable|array',
            'workout_sections' => 'nullable|array',
            'impact_level' => ['nullable', Rule::in(RoutineLibraryRules::IMPACT_LEVELS)],
            'intensity_level' => ['nullable', Rule::in(RoutineLibraryRules::INTENSITY_LEVELS)],
            'video_variant' => ['nullable', Rule::in(RoutineLibraryRules::VIDEO_VARIANTS)],
            'recommended_duration_seconds' => 'nullable|integer|min:0|max:3600',
            'recommended_repetitions' => 'nullable|string|max:64',
            'recommended_sets' => 'nullable|string|max:64',
            'recommended_rest_seconds' => 'nullable|integer|min:0|max:600',
            'safety_notes' => 'nullable|array',
            'contraindications' => 'nullable|array',
            'difficulty' => ['required', Rule::in(RoutineLibraryRules::LEVELS)],
            'injury_cautions' => 'nullable|array',
            'goal_fit' => 'nullable|array',
            'usage_flags' => 'nullable|array',
            'review_status' => ['required', Rule::in(RoutineLibraryRules::EXERCISE_TAG_REVIEW_STATUSES)],
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $payload = $validator->validated();
        $payload['approved_for_generation'] = $payload['review_status'] === 'approved';
        $tag->fill($payload);
        $tag->save();
        $tag->load('exercise:id,title,type,language,video_type,video_url,image,custom_thumbnail,content_code,tags');

        return response()->json([
            'status' => true,
            'message' => $payload['approved_for_generation']
                ? 'Exercise approved for routine generation.'
                : 'Exercise review status updated.',
            'data' => $tag,
        ]);
    }

    public function bulkReviewExerciseTags(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:exercise_library_tags,id',
            'review_status' => ['required', Rule::in(RoutineLibraryRules::EXERCISE_TAG_REVIEW_STATUSES)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $status = $request->review_status;
        ExerciseLibraryTag::whereIn('id', $request->ids)->update([
            'review_status' => $status,
            'approved_for_generation' => $status === 'approved',
            'updated_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Exercise tags updated.',
            'updated_count' => count($request->ids),
        ]);
    }

    public function generateBatch(Request $request, RoutineGeneratorService $generator)
    {
        $validator = Validator::make($request->all(), [
            'language' => ['required', Rule::in(RoutineLibraryRules::LANGUAGES)],
            'equipment_category' => ['required', Rule::in(RoutineLibraryRules::EQUIPMENT_CATEGORIES)],
            'fitness_level' => ['required', Rule::in(RoutineLibraryRules::LEVELS)],
            'workout_types' => 'nullable|array',
            'workout_types.*' => [Rule::in(array_keys(RoutineLibraryRules::WORKOUT_TYPES))],
            'target_minutes' => ['nullable', Rule::in(RoutineLibraryRules::PROGRAM_DURATIONS_MINUTES)],
            'variations_per_type' => 'nullable|integer|min:1|max:15',
            'limit' => 'nullable|integer|min:1|max:2340',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $filters = $validator->validated();
        $filters['created_by'] = Auth::id();
        $batch = $generator->generateBatch($filters);

        return response()->json([
            'status' => $batch->status !== 'blocked_missing_content',
            'data' => $batch,
            'message' => $batch->status === 'blocked_missing_content'
                ? 'Missing approved exercise coverage. Review missing_content_report before generation.'
                : 'Routine batch generated for review.',
        ]);
    }

    public function batches()
    {
        return response()->json([
            'status' => true,
            'data' => RoutineGenerationBatch::latest('id')->paginate(25),
        ]);
    }

    public function routines(Request $request)
    {
        $routines = Workout::query()
            ->whereNotNull('routine_status')
            ->when($request->status, fn ($q, $status) => $q->where('routine_status', $status))
            ->when($request->language, fn ($q, $language) => $q->where('language', $language))
            ->when($request->equipment_category, fn ($q, $equipment) => $q->where('equipment_category', $equipment))
            ->when($request->fitness_level, fn ($q, $level) => $q->where('fitness_level', $level))
            ->withCount('workoutExercises')
            ->orderBy('id', 'desc')
            ->paginate(25);

        return response()->json([
            'status' => true,
            'data' => $routines,
        ]);
    }

    public function reviewRoutine(Request $request, int $id, RoutineValidatorService $validatorService)
    {
        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(['approved', 'rejected', 'revision', 'pending_review'])],
            'review_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $workout = Workout::where('id', $id)->whereNotNull('routine_status')->first();
        if (! $workout) {
            return response()->json([
                'status' => false,
                'message' => 'Routine not found.',
            ], 404);
        }

        $validation = $validatorService->validateWorkout($workout);
        if ($request->status === 'approved' && ! $validation['valid']) {
            $workout->routine_validation_errors = $validation['errors'];
            $workout->routine_status = 'revision';
            $workout->review_notes = $request->review_notes;
            $workout->reviewed_at = now();
            $workout->reviewed_by = Auth::id();
            $workout->save();

            return response()->json([
                'status' => false,
                'message' => 'Routine cannot be approved until validation errors are fixed.',
                'data' => $workout,
            ], 422);
        }

        $workout->routine_status = $request->status;
        $workout->routine_validation_errors = $validation['errors'];
        $workout->review_notes = $request->review_notes;
        $workout->reviewed_at = now();
        $workout->reviewed_by = Auth::id();
        if ($request->status === 'approved') {
            $workout->approved_at = now();
            $workout->approved_by = Auth::id();
        }
        $workout->save();

        return response()->json([
            'status' => true,
            'data' => $workout,
        ]);
    }

    public function bulkReviewRoutines(Request $request, RoutineValidatorService $validatorService)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:workouts,id',
            'status' => ['required', Rule::in(['approved', 'rejected', 'revision', 'pending_review'])],
            'review_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $updated = 0;
        $blocked = 0;
        $workouts = Workout::whereIn('id', $request->ids)->whereNotNull('routine_status')->get();
        foreach ($workouts as $workout) {
            $validation = $validatorService->validateWorkout($workout);
            if ($request->status === 'approved' && ! $validation['valid']) {
                $workout->routine_status = 'revision';
                $workout->routine_validation_errors = $validation['errors'];
                $workout->review_notes = $request->review_notes;
                $workout->reviewed_at = now();
                $workout->reviewed_by = Auth::id();
                $workout->save();
                $blocked++;
                continue;
            }

            $workout->routine_status = $request->status;
            $workout->routine_validation_errors = $validation['errors'];
            $workout->review_notes = $request->review_notes;
            $workout->reviewed_at = now();
            $workout->reviewed_by = Auth::id();
            if ($request->status === 'approved') {
                $workout->approved_at = now();
                $workout->approved_by = Auth::id();
            }
            $workout->save();
            $updated++;
        }

        return response()->json([
            'status' => true,
            'message' => "Updated {$updated} routines. {$blocked} moved to revision.",
            'updated_count' => $updated,
            'blocked_count' => $blocked,
        ]);
    }

    public function recommendForUser(Request $request, int $userId, ConsultationRecommendationService $service)
    {
        $validator = Validator::make($request->all(), [
            'language' => ['nullable', Rule::in(RoutineLibraryRules::CONTENT_LANGUAGES)],
            'training_level' => ['nullable', Rule::in(RoutineLibraryRules::LEVELS)],
            'equipment_category' => ['nullable', Rule::in(RoutineLibraryRules::EQUIPMENT_CATEGORIES)],
            'weekly_workout_frequency' => 'nullable|integer|min:3|max:6',
            'preferred_duration_minutes' => ['nullable', Rule::in([15, 20, 30, 45, 60])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $recommendation = $service->recommendForUser($userId, $validator->validated());

        return response()->json([
            'status' => true,
            'data' => $this->recommendationPayload($recommendation),
        ]);
    }

    public function latestRecommendationForUser(int $userId)
    {
        $recommendation = ConsultationRecommendation::where('user_id', $userId)
            ->latest('id')
            ->first();

        return response()->json([
            'status' => true,
            'data' => $this->recommendationPayload($recommendation),
        ]);
    }

    public function assignRecommendedProgram(Request $request, int $userId)
    {
        $validator = Validator::make($request->all(), [
            'program_id' => 'required|exists:programs,id',
            'replace_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $recommendation = ConsultationRecommendation::where('user_id', $userId)->latest('id')->first();
        if (! $recommendation) {
            return response()->json([
                'status' => false,
                'message' => 'Generate a recommendation before assigning a program.',
            ], 404);
        }

        $programIds = is_array($recommendation->recommended_program_ids)
            ? array_map('intval', $recommendation->recommended_program_ids)
            : [];
        $programId = (int) $request->program_id;
        if (! in_array($programId, $programIds, true)) {
            return response()->json([
                'status' => false,
                'message' => 'Selected program is not part of the latest recommendation.',
            ], 422);
        }

        $program = Program::find($programId);
        $phaseIds = ProgramPhase::where('program_id', $programId)->pluck('id');
        if ($phaseIds->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => "Can't assign this program. It has no training phase.",
            ], 422);
        }

        $emptyPhaseCount = ProgramPhase::where('program_id', $programId)
            ->whereDoesntHave('phaseWorkouts')
            ->count();
        if ($emptyPhaseCount > 0 || ProgramPhaseWorkout::whereIn('program_phase_id', $phaseIds)->count() === 0) {
            return response()->json([
                'status' => false,
                'message' => "Can't assign this program. It is partially built.",
            ], 422);
        }

        $activeProgram = ProgramSub::where('user_id', $userId)
            ->whereIn('status', ['subscribed', 'in-progress', 'resumed', 'paused'])
            ->latest('id')
            ->first();

        if ($activeProgram && (int) $activeProgram->program_id === $programId) {
            if (WeekWiseProgram::where('program_sub_id', $activeProgram->id)->count() === 0) {
                (new ProgramSubTrackingController())->generateTracking($activeProgram->id, $programId);
            }

            return response()->json([
                'status' => true,
                'message' => 'Client is already subscribed to this program.',
                'data' => [
                    'program_subscription' => $activeProgram,
                    'recommendation' => $this->recommendationPayload($recommendation->fresh()),
                ],
            ]);
        }

        if ($activeProgram && ! $request->boolean('replace_active')) {
            return response()->json([
                'status' => false,
                'message' => 'Client already has an active program. Complete, pause, or replace it before assigning another.',
                'data' => [
                    'active_program' => [
                        'id' => $activeProgram->program_id,
                        'status' => $activeProgram->status,
                    ],
                ],
            ], 422);
        }

        $programSub = DB::transaction(function () use ($activeProgram, $programId, $userId) {
            if ($activeProgram) {
                ProgramSub::where('user_id', $userId)
                    ->whereIn('status', ['subscribed', 'in-progress', 'resumed', 'paused'])
                    ->update([
                        'status' => 'switched',
                        'complete_date' => Carbon::today(),
                    ]);
            }

            $programSub = new ProgramSub();
            $programSub->user_id = $userId;
            $programSub->program_id = $programId;
            $programSub->subscribe_date = Carbon::today();
            $programSub->status = 'subscribed';
            $programSub->save();

            (new ProgramSubTrackingController())->generateTracking($programSub->id, $programId);

            return $programSub;
        });

        return response()->json([
            'status' => true,
            'message' => 'Program assigned to client.',
            'data' => [
                'assigned_program' => [
                    'id' => $program->id,
                    'content_code' => $program->content_code,
                    'title' => $program->title,
                ],
                'program_subscription' => $programSub,
                'recommendation' => $this->recommendationPayload($recommendation->fresh()),
            ],
        ]);
    }

    public function assignRecommendedRoutine(Request $request, int $userId, RoutineValidatorService $validatorService)
    {
        $validator = Validator::make($request->all(), [
            'workout_id' => 'required|exists:workouts,id',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $recommendation = ConsultationRecommendation::where('user_id', $userId)->latest('id')->first();
        if (! $recommendation) {
            return response()->json([
                'status' => false,
                'message' => 'Generate a recommendation before assigning a routine.',
            ], 404);
        }

        $routineIds = is_array($recommendation->recommended_routine_ids)
            ? $recommendation->recommended_routine_ids
            : [];
        $workoutId = (int) $request->workout_id;
        if (! in_array($workoutId, array_map('intval', $routineIds), true)) {
            return response()->json([
                'status' => false,
                'message' => 'Selected routine is not part of the latest recommendation.',
            ], 422);
        }

        $workout = Workout::where('id', $workoutId)->whereNotNull('routine_status')->first();
        if (! $workout || in_array($workout->routine_status, ['rejected', 'revision'], true)) {
            return response()->json([
                'status' => false,
                'message' => 'Selected routine is not ready for assignment.',
            ], 422);
        }

        $validation = $validatorService->validateWorkout($workout);
        if (! $validation['valid']) {
            $workout->routine_status = 'revision';
            $workout->routine_validation_errors = $validation['errors'];
            $workout->reviewed_at = now();
            $workout->reviewed_by = Auth::id();
            $workout->save();

            return response()->json([
                'status' => false,
                'message' => 'Routine failed validation and was moved to revision.',
                'data' => $workout,
            ], 422);
        }

        if ($workout->routine_status !== 'approved') {
            $workout->routine_status = 'approved';
            $workout->routine_validation_errors = [];
            $workout->approved_at = now();
            $workout->approved_by = Auth::id();
            $workout->reviewed_at = now();
            $workout->reviewed_by = Auth::id();
            $workout->save();
        }

        $assignment = ClientRoutineAssignment::updateOrCreate(
            [
                'user_id' => $userId,
                'workout_id' => $workout->id,
            ],
            [
                'consultation_recommendation_id' => $recommendation->id,
                'status' => 'assigned',
                'assigned_by' => Auth::id(),
                'assigned_at' => now(),
                'notes' => $request->notes,
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'Routine approved and assigned to client.',
            'data' => [
                'assignment' => $assignment,
                'recommendation' => $this->recommendationPayload($recommendation->fresh()),
            ],
        ]);
    }

    public function createProgramFromAssignedRoutines(int $userId, ClientRoutineProgramService $programService)
    {
        try {
            $result = $programService->createProgramFromAssignments($userId, Auth::id());

            return response()->json([
                'status' => true,
                'message' => 'Client program created from assigned routines.',
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function recommendationPayload(?ConsultationRecommendation $recommendation): ?array
    {
        if (! $recommendation) {
            return null;
        }

        $payload = $recommendation->toArray();
        $routineIds = is_array($recommendation->recommended_routine_ids)
            ? $recommendation->recommended_routine_ids
            : [];
        $programIds = is_array($recommendation->recommended_program_ids)
            ? $recommendation->recommended_program_ids
            : [];

        $payload['recommended_routines'] = $routineIds === []
            ? []
            : Workout::query()
                ->whereIn('id', $routineIds)
                ->withCount('workoutExercises')
                ->get([
                    'id',
                    'content_code',
                    'title',
                    'language',
                    'equipment_category',
                    'fitness_level',
                    'workout_type',
                    'routine_status',
                ])
                ->sortBy(fn ($routine) => array_search($routine->id, $routineIds, true))
                ->values()
                ->map(fn ($routine) => [
                    'id' => $routine->id,
                    'content_code' => $routine->content_code,
                    'title' => $routine->title,
                    'language' => $routine->language,
                    'equipment_category' => $routine->equipment_category,
                    'fitness_level' => $routine->fitness_level,
                    'workout_type' => $routine->workout_type,
                    'routine_status' => $routine->routine_status,
                    'workout_exercises_count' => $routine->workout_exercises_count,
                ])
                ->all();

        $assignments = ClientRoutineAssignment::where('user_id', $recommendation->user_id)
            ->whereIn('workout_id', $routineIds)
            ->get()
            ->keyBy('workout_id');

        $payload['recommended_routines'] = collect($payload['recommended_routines'])
            ->map(function ($routine) use ($assignments) {
                $assignment = $assignments->get($routine['id']);
                $routine['assignment_status'] = $assignment ? $assignment->status : null;
                $routine['assigned_at'] = $assignment && $assignment->assigned_at
                    ? $assignment->assigned_at->toDateTimeString()
                    : null;

                return $routine;
            })
            ->all();

        $payload['recommended_programs'] = $programIds === []
            ? []
            : Program::query()
                ->whereIn('id', $programIds)
                ->withCount('aiWeekDays')
                ->get([
                    'id',
                    'content_code',
                    'title',
                    'language',
                    'level',
                    'type',
                ])
                ->sortBy(fn ($program) => array_search($program->id, $programIds, true))
                ->values()
                ->map(function ($program) use ($recommendation) {
                    $phaseIds = ProgramPhase::where('program_id', $program->id)->pluck('id');
                    $activeSubscription = ProgramSub::where('user_id', $recommendation->user_id)
                        ->where('program_id', $program->id)
                        ->whereIn('status', ['subscribed', 'in-progress', 'resumed', 'paused'])
                        ->latest('id')
                        ->first();

                    return [
                        'id' => $program->id,
                        'content_code' => $program->content_code,
                        'title' => $program->title,
                        'language' => $program->language,
                        'level' => $program->level,
                        'type' => $program->type,
                        'weeks' => (int) ProgramPhase::where('program_id', $program->id)->sum('weeks'),
                        'days' => $program->ai_week_days_count,
                        'routines_count' => ProgramPhaseWorkout::whereIn('program_phase_id', $phaseIds)->count(),
                        'subscription_status' => optional($activeSubscription)->status,
                        'subscribed_at' => $activeSubscription ? $activeSubscription->subscribe_date : null,
                    ];
                })
                ->all();

        return $payload;
    }
}
