<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ResolvesUserLanguage;
use App\Http\Controllers\Controller;
use App\Helpers\JsonList;
use App\Support\ContentCodeNormalizer;
use App\Support\ContentLocaleResolver;
use App\Models\Achievement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Exercise;
use App\Models\ExerciseCompilation;
use App\Models\ExercisesTracking;
use App\Models\Program;
use App\Models\ProgramPhase;
use App\Models\ProgramPhaseWorkout;
use App\Models\ProgramsTracking;
use App\Models\ProgramSub;
use App\Models\ProgramSubscriber;
use App\Models\Tag;
use App\Models\WeeksTracking;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Models\WorkoutsTracking;
use App\Services\RoutineExerciseAutoTaggerService;
use App\Traits\ActivitiesTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use stdClass;

class ExerciseController extends Controller
{
    use ActivitiesTrait;
    use ResolvesUserLanguage;

    private function isSupportedUploadedVideo($file): bool
    {
        if (! $file) {
            return false;
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        $clientMime = strtolower((string) $file->getClientMimeType());
        $serverMime = strtolower((string) $file->getMimeType());

        $allowedExtensions = ['mp4', 'm4v', 'mov', 'webm', 'mkv'];
        $allowedMimes = [
            'video/mp4',
            'video/quicktime',
            'video/x-m4v',
            'video/webm',
            'video/x-matroska',
            'application/octet-stream',
        ];

        return in_array($extension, $allowedExtensions, true)
            || in_array($clientMime, $allowedMimes, true)
            || in_array($serverMime, $allowedMimes, true);
    }

    function testDelete($code)
    {
        if ($code == 1289) {
            Program::query()->truncate();
            ProgramPhase::query()->truncate();
            ProgramPhaseWorkout::query()->truncate();
            Workout::query()->truncate();
            WorkoutExercise::query()->truncate();
            Exercise::query()->truncate();
            ProgramsTracking::query()->truncate();
            WeeksTracking::query()->truncate();
            WorkoutsTracking::query()->truncate();
            ExercisesTracking::query()->truncate();
            ProgramSub::query()->truncate();
            Achievement::query()->truncate();
            return "done";
        }
        return "code mismatch";
    }



    public function createExercise(Request $request)
    {
        $request->merge([
            'content_code' => ContentCodeNormalizer::normalize($request->input('content_code')),
        ]);
        $validate = Validator::make($request->all(), [
            'title' => 'required',
            'type' => 'required|string',
            'video' => 'nullable|file|max:51200',
            'video_type' => 'required|in:custom,youtube,image',
            // 'video_duration' => 'required|numeric',
            'weights' => 'nullable|string',
            'language' => 'required|in:ar,en,no',
            'tags' => 'string',
            'alternates' => 'string',
            'ytVideoId' => 'string',
            'custom_thumbnail' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'content_code' => [
                'nullable',
                'string',
                'max:64',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('exercises', 'content_code'),
            ],
        ]);
        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors()->all()[0]
            ]);
        }
        $uploadedVideo = $request->file('video');
        if ($request->video_type === 'custom' && $uploadedVideo && ! $this->isSupportedUploadedVideo($uploadedVideo)) {
            return response()->json([
                'status' => false,
                'message' => 'Unsupported video file. Please upload MP4, M4V, MOV, WEBM, or MKV.'
            ]);
        }
        if ($request->video_type == "youtube") {
            $videoDuration = 0;
        } else {
            $videoDuration = $request->video_type;
        }
        $videoUrl = '';
        $thumbUrl = '';
        $newId = Exercise::orderBy('id', 'desc')->pluck('id')->first() + 1;
        if ($request->video_type == 'custom') {
            if ($request->has('video')) {
                $videoUrl = $newId . "_exercise_video_" . time() . '_' . uniqid() . '.' . request()->video->getClientOriginalExtension();
                $request->video->storeAs('exercises', $videoUrl, 'fwd_media');
                $thumbUrl = $newId . "_exercise_thumbnail_" . time() . '_' . uniqid() . '.' . request()->thumbnail->getClientOriginalExtension();
                $request->thumbnail->storeAs('exercises', $thumbUrl, 'fwd_media');
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Video is Required'
                ]);
            }
        } else if ($request->video_type == 'youtube') {
            if ($request->has('ytVideoId')) {
                $videoUrl = $request->ytVideoId;
                $thumbUrl = $request->ytVideoId;
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Video URL is Required'
                ]);
            }
        } else {
            $videoUrl = $thumbUrl = $newId . "_exercise_thumbnail_" . time() . '_' . uniqid() . '.' . request()->thumbnail->getClientOriginalExtension();
            $request->thumbnail->storeAs('exercises', $thumbUrl, 'fwd_media');
        }
        $data = new Exercise;
        $data->title = $request->title;
        $data->type = $request->type;
        $data->language = $request->language;
        $data->tags = $request->tags;
        $data->instructions = $request->instructions;
        $data->weights = is_null($request->weights) ? '' : $request->weights;
        $data->video_url = $videoUrl;
        $data->video_duration = $videoDuration;
        $data->video_type = $request->video_type;
        $data->alternates = $request->alternates;
        $data->content_code = $request->input('content_code');
        $data->image = $thumbUrl;
        if ($request->hasFile('custom_thumbnail')) {
            $customPath = $newId.'_exercise_custom_thumb_'.time().'_'.uniqid().'.'.$request->custom_thumbnail->getClientOriginalExtension();
            $request->custom_thumbnail->storeAs('exercises', $customPath, 'fwd_media');
            $data->custom_thumbnail = $customPath;
        }
        $data->save();
        return response()->json([
            'status' => true,
            'message' => 'Exercise Successfully Created'
        ]);
    }

    public function updateExercise(Request $request, RoutineExerciseAutoTaggerService $routineTagger)
    {
        $request->merge([
            'content_code' => ContentCodeNormalizer::normalize($request->input('content_code')),
        ]);
        $validate = Validator::make($request->all(), [
            'id' => 'required|numeric',
            'type' => 'string',
            'video' => 'nullable|file|max:51200',
            'video_type' => 'in:custom,youtube,image',
            'video_duration' => 'numeric',
            'weights' => 'nullable|string',
            'language' => 'in:ar,en,no',
            'tags' => 'string',
            'alternates' => 'string',
            'custom_thumbnail' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'content_code' => [
                'nullable',
                'string',
                'max:64',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('exercises', 'content_code')->ignore($request->id),
            ],
        ]);
        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors()->all()[0]
            ]);
        }

        $uploadedVideo = $request->file('video');
        if ($request->video_type === 'custom' && $uploadedVideo && ! $this->isSupportedUploadedVideo($uploadedVideo)) {
            return response()->json([
                'status' => false,
                'message' => 'Unsupported video file. Please upload MP4, M4V, MOV, WEBM, or MKV.'
            ]);
        }

        $data = Exercise::where('id', $request->id)->first();
        if ($data) {
            $routineTagInputsBefore = $data->only([
                'tags',
                'type',
                'language',
                'video_url',
                'video_type',
                'video_duration',
            ]);
            if ($request->exists('tags')) {
                $data->tags = $request->input('tags');
            }
            if ($request->exists('title')) {
                $data->title = $request->input('title');
            }
            if ($request->exists('type')) {
                $data->type = $request->input('type');
            }
            if ($request->has('weights')) {
                $data->weights = is_null($request->weights) ? '' : $request->weights;
            }
            if ($request->exists('language')) {
                $data->language = $request->input('language');
            }
            if ($request->exists('instructions')) {
                $data->instructions = $request->input('instructions');
            }
            if ($request->exists('alternates')) {
                $data->alternates = $request->input('alternates');
            }
            if (isset($request->video_type)) {
                $videoUrl = '';
                $thumbUrl = '';
                if ($request->video_type == 'custom') {
                    if ($request->has('video')) {
                        $videoUrl = $request->id . "_exercise_video_" . time() . '_' . uniqid() . '.' . request()->video->getClientOriginalExtension();
                        $request->video->storeAs('exercises', $videoUrl, 'fwd_media');
                        $thumbUrl = $request->id . "_exercise_thumbnail_" . time() . '_' . uniqid() . '.' . request()->thumbnail->getClientOriginalExtension();
                        $request->thumbnail->storeAs('exercises', $thumbUrl, 'fwd_media');
                        $data->video_url = $videoUrl;
                        $data->image = $thumbUrl;
                        $data->custom_thumbnail = null;
                    } elseif ($request->hasFile('thumbnail')) {
                        $thumbUrl = $request->id . "_exercise_thumbnail_" . time() . '_' . uniqid() . '.' . request()->thumbnail->getClientOriginalExtension();
                        $request->thumbnail->storeAs('exercises', $thumbUrl, 'fwd_media');
                        $data->image = $thumbUrl;
                        $data->custom_thumbnail = null;
                    }
                } else if ($request->video_type == 'youtube') {
                    if ($request->has('ytVideoId')) {
                        $oldVideoRaw = $data->getRawOriginal('video_url');
                        $videoUrl = $request->ytVideoId;
                        if ($oldVideoRaw !== $videoUrl && ! $request->hasFile('custom_thumbnail')) {
                            $data->custom_thumbnail = null;
                        }
                        $data->video_url = $videoUrl;
                        $data->image = $videoUrl;
                    }
                } else {
                    if ($request->has('thumbnail')) {
                        $videoUrl = $thumbUrl = $request->id . "_exercise_thumbnail_" . time() . '_' . uniqid() . '.' . request()->thumbnail->getClientOriginalExtension();
                        $request->thumbnail->storeAs('exercises', $thumbUrl, 'fwd_media');
                        $data->video_url = $videoUrl;
                        $data->image = $thumbUrl;
                        $data->custom_thumbnail = null;
                    }
                }
                $data->video_type = $request->video_type;
            }
            if (isset($request->video_duration)) {
                $data->video_duration = $request->video_duration;
            }

            if ($request->hasFile('custom_thumbnail')) {
                $customPath = $request->id.'_exercise_custom_thumb_'.time().'_'.uniqid().'.'.$request->custom_thumbnail->getClientOriginalExtension();
                $request->custom_thumbnail->storeAs('exercises', $customPath, 'fwd_media');
                $data->custom_thumbnail = $customPath;
            }
            if ($request->exists('content_code')) {
                $data->content_code = ContentCodeNormalizer::normalize($request->input('content_code'));
            }
            $data->update();

            $freshExercise = $data->fresh();
            $routineTagInputsAfter = $freshExercise->only(array_keys($routineTagInputsBefore));
            if ($routineTagInputsBefore != $routineTagInputsAfter) {
                try {
                    $routineTagger->tagSingleExercise($freshExercise, [
                        'approve' => false,
                        'preserve_review_status' => true,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Routine library tag refresh failed after exercise update.', [
                        'exercise_id' => $data->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Exercise Successfully Updated'
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Exercise Not Found'
            ]);
        }
    }

    public function getAllExercises(Request $request)
    {
        if ($request->has('lang') && ($request->lang === 'en' || $request->lang === 'ar')) {
            $notLang = $request->lang === 'en' ? 'ar' : 'en';
            $exercises = Exercise::where('language', '!=', $notLang)
                ->orderBy('id', 'desc')->get(['id', 'content_code', 'title', 'tags', 'language', 'image', 'video_url', 'video_type', 'custom_thumbnail']);
        } else {
            $exercises = Exercise::orderBy('id', 'desc')->get(['id', 'content_code', 'title', 'tags', 'language', 'image', 'video_url', 'video_type', 'custom_thumbnail']);
        }
        foreach ($exercises as $item) {
            $tagIds = JsonList::ids($item->tags);
            $item->tags = $tagIds;
            $item->tagNames = $tagIds === []
                ? []
                : Tag::whereIn('id', $tagIds)->pluck('name')->toArray();
        }
        return response()->json([
            'status' => true,
            'data' => $exercises
        ]);
    }

    function getExerciseTags()
    {
        try {
            $tagCats = Tag::where('category', 'exercise')->groupBy('type')->pluck('type');
            $tagsArray = [];
            foreach ($tagCats as $key => $tagcat) {
                $tags = Tag::where('category', 'exercise')->where('type', $tagcat)->get(['id', 'name']);
                $temp = new stdClass;
                $temp->tagType = $tagCats[$key];
                $temp->tagList = $tags;
                array_push($tagsArray, $temp);
            }
            return response()->json([
                'status' => true,
                'data' => $tagsArray
            ]);
        } catch (Exception $er) {
            return response()->json([
                'status' => false,
                'message' => $er->getMessage() . "--- Line # " . $er->getLine()
            ]);
        }
    }

    function getExerciseDetail($id)
    {
        $ex = Exercise::find($id);
        if (is_null($ex))
            return response()->json([
                'status' => false,
                'message' => 'Invalid Id'
            ]);
        ContentLocaleResolver::overlayExercise($ex, $this->currentUserLanguage());
        $tagIds = JsonList::ids($ex->tags);
        $ex->tags = $tagIds;
        $ex->tagNames = $tagIds === []
            ? []
            : Tag::whereIn('id', $tagIds)->pluck('name')->toArray();
        if (is_null($ex->alternates))
            $ex->altNames = [];
        else
            $ex->altNames = Exercise::whereIn('id', $ex->alternates)->pluck('title')->toArray();
        return response()->json([
            'status' => true,
            'data' => $ex
        ]);
    }
    function deleteExercises(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);
        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors()->all()[0]
            ]);
        }

        $ids = collect($request->ids)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No exercises selected'
            ]);
        }

        $deleted = 0;
        $workoutReferencesRemoved = 0;

        DB::transaction(function () use ($ids, &$deleted, &$workoutReferencesRemoved) {
            $existingIds = Exercise::whereIn('id', $ids)->pluck('id');

            if ($existingIds->isEmpty()) {
                return;
            }

            $workoutExerciseIds = WorkoutExercise::whereIn('exercise_id', $existingIds)->pluck('id');
            $workoutReferencesRemoved = $workoutExerciseIds->count();

            ExerciseCompilation::whereIn('exercise_id', $existingIds)
                ->orWhereIn('workout_exercise_id', $workoutExerciseIds)
                ->delete();

            ExercisesTracking::whereIn('exercise_id', $existingIds)->delete();
            WorkoutExercise::whereIn('id', $workoutExerciseIds)->delete();

            if (Schema::hasTable('exercise_weight_tracking')) {
                DB::table('exercise_weight_tracking')->whereIn('exercise_id', $existingIds)->delete();
            }

            if (Schema::hasTable('ai_exercise_tag_proposals')) {
                DB::table('ai_exercise_tag_proposals')->whereIn('exercise_id', $existingIds)->delete();
            }

            if (Schema::hasTable('exercise_library_tags')) {
                DB::table('exercise_library_tags')->whereIn('exercise_id', $existingIds)->delete();

                if (Schema::hasColumn('exercise_library_tags', 'regression_exercise_id')) {
                    DB::table('exercise_library_tags')->whereIn('regression_exercise_id', $existingIds)->update(['regression_exercise_id' => null]);
                }

                if (Schema::hasColumn('exercise_library_tags', 'progression_exercise_id')) {
                    DB::table('exercise_library_tags')->whereIn('progression_exercise_id', $existingIds)->update(['progression_exercise_id' => null]);
                }
            }

            if (Schema::hasTable('user_exercise_replacements')) {
                DB::table('user_exercise_replacements')
                    ->whereIn('original_exercise_id', $existingIds)
                    ->orWhereIn('alternate_exercise_id', $existingIds)
                    ->delete();
            }

            $deleted = Exercise::whereIn('id', $existingIds)->delete();
        });

        return response()->json([
            'status' => true,
            'message' => $workoutReferencesRemoved > 0
                ? "Deleted {$deleted} exercise(s) and removed {$workoutReferencesRemoved} workout reference(s)."
                : "Deleted {$deleted} exercise(s)."
        ]);
    }

    public function duplicateExercise(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:exercises,id',
        ]);
        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors()->all()[0]
            ]);
        }

        $exercise = Exercise::find($request->id);
        if (is_null($exercise)) {
            return response()->json([
                'status' => false,
                'message' => 'Exercise Not Found'
            ]);
        }

        $newExercise = $exercise->replicate();
        $newExercise->title = $exercise->title . ' (Copy)';
        $newExercise->content_code = null;
        $newExercise->save();

        return response()->json([
            'status' => true,
            'message' => 'Exercise duplicated successfully.',
            'data' => [
                'id' => $newExercise->id
            ]
        ]);
    }

    public function searchExercise(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'keyword' => 'required'
        ]);
        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors()->all()[0]
            ]);
        }
        $keyword = $request->keyword;
        $userLang = $this->currentUserLanguage();
        if (in_array($userLang, ['en', 'ar'], true)) {
            $notLang = $userLang === 'en' ? 'ar' : 'en';
            $data = Exercise::where('title', 'like', '%' . $keyword . '%')->where('language', '!=', $notLang)->get(['title', 'id', 'content_code', 'image', 'video_type', 'language', 'locale_translations', 'video_url', 'custom_thumbnail']);
        } else {
            $data = Exercise::where(function ($q) {
                $q->where('language', 'en')->orWhere('language', 'no');
            })->where('title', 'like', '%' . $keyword . '%')->get(['title', 'id', 'content_code', 'image', 'video_type', 'language', 'locale_translations', 'video_url', 'custom_thumbnail']);
        }
        foreach ($data as $row) {
            ContentLocaleResolver::overlayExercise($row, $userLang);
        }
        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    function getExercisesForUsers(Request $request)
    {
        // Add tag filtering support
        $tags = $request->get('tags');
        $tagArray = [];
        if($tags) {
            $tagArray = is_array($tags) ? $tags : explode(',', $tags);
        }
        $userLang = $this->currentUserLanguage();
        if (in_array($userLang, ['en', 'ar'], true)) {
            $query = Exercise::where(function ($q) use ($userLang) {
                $q->where('language', $userLang)->orWhere('language', 'no');
            });
        } else {
            $query = Exercise::where(function ($q) {
                $q->where('language', 'en')->orWhere('language', 'no');
            });
        }
        
        // Filter by tags if provided
        if(!empty($tagArray)) {
            $query->where(function($q) use ($tagArray) {
                foreach($tagArray as $tagId) {
                    $q->orWhere('tags', 'like', '%'.$tagId.'%');
                }
            });
        }
        
        $exercises = $query->orderBy('created_at', 'desc')->get();
        foreach ($exercises as $item) {
            ContentLocaleResolver::overlayExercise($item, $userLang);
            $tags = $item->tags ? explode(',', $item->tags) : [];
            $item['tags_array'] = $tags;
            // Add exercise details
            if(!is_null($item->tags)) {
                $item->tagNames = Tag::whereIn('id', $tags)->pluck('name')->toArray();
            } else {
                $item->tagNames = [];
            }
        }
        return response()->json([
            'status' => true,
            'data' => $exercises
        ]);
    }

    function getTags()
    {
        $tagCats = Tag::where('category', 'exercise')->groupBy('type')->pluck('type');
        $tagg = new stdClass;
        foreach ($tagCats as $tagcat) {
            $tags = Tag::where('category', 'exercise')->where('type', $tagcat)->pluck('name');
            $tagg->$tagcat = $tags;
        }
        return response()->json([
            'status' => true,
            'data' => $tagg
        ]);
    }

    public function getTagsByTypes(Request $request)
    {
        // Get all unique tag types including null
        $tagCats = Tag::select('type')->distinct()->pluck('type');

        $tagg = new stdClass;

        foreach ($tagCats as $tagcat) {
            // Replace null with "uncategorized"
            $typeKey = $tagcat ?? 'uncategorized';

            $tags = Tag::where('type', $tagcat)
                ->orWhere(function ($query) use ($tagcat) {
                    if (is_null($tagcat)) {
                        $query->whereNull('type');
                    }
                })
                ->pluck('name');

            $tagg->$typeKey = $tags;
        }

        return response()->json([
            'status' => true,
            'data' => $tagg,
        ]);
    }


    public function assignTagsToExercises(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'exercise_ids' => 'required',
            'tag_ids' => 'required|array',
        ]);
        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors()->all()[0]
            ]);
        }
        foreach ($request->exercise_ids as $exerciseId) {
            $exercise = Exercise::find($exerciseId);
            $tagsArray = JsonList::ids($exercise->tags);
            foreach ($request->tag_ids as $tagId) {
                if (!in_array((int) $tagId, $tagsArray, true)) {
                    $tagsArray[] = (int) $tagId;
                }
            }
            $exercise->tags = json_encode($tagsArray);
            $exercise->update();
        }
        return response()->json([
            'status' => true,
            'message' => 'Exercises tags updated'
        ]);
    }

    function exerciseAlternates($id)
    {
        $exercise = Exercise::find($id);
        if(is_null($exercise))
        return response()->json([
            'status' => false,
            'message' => 'Exercise not found'
        ]);
        
        $altIds = $exercise->alternates;
        if (is_null($altIds) || empty($altIds))
            return response()->json([
                'status' => true,
                'data' => []
            ]);
        
        // Get full exercise details so users can review (see names) and click to view entire video
        // IMPORTANT: Include all fields needed for video viewing and exercise preview
        $alternates = Exercise::whereIn('id', $altIds)
            ->select('*') // Select all fields including id, title, video_url, image, video_type, etc.
            ->get();
        
        foreach($alternates as $alt) {
            $tagIds = JsonList::ids($alt->tags);
            $alt->tags = $tagIds;
            $alt->tagNames = $tagIds === []
                ? []
                : Tag::whereIn('id', $tagIds)->pluck('name')->toArray();
            $alt->alternates = JsonList::ids($alt->alternates);
        }
        
        return response()->json([
            'status' => true,
            'data' => $alternates
        ]);
    }

    /**
     * Replace exercise with alternate
     */
    function replaceExercise(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'original_exercise_id' => 'required|exists:exercises,id',
            'alternate_exercise_id' => 'required|exists:exercises,id',
            'workout_id' => 'nullable|exists:workouts,id',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors()->all()[0]
            ]);
        }

        try {
            $user = Auth::user();
            
            // Check if alternate is valid for this exercise
            $exercise = Exercise::find($request->original_exercise_id);
            if(!$exercise || !in_array($request->alternate_exercise_id, $exercise->alternates ?? [])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid alternate exercise for this exercise'
                ]);
            }

            // Store replacement
            \App\Models\UserExerciseReplacement::create([
                'user_id' => $user->id,
                'workout_id' => $request->workout_id,
                'original_exercise_id' => $request->original_exercise_id,
                'alternate_exercise_id' => $request->alternate_exercise_id,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Exercise replaced successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error replacing exercise',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    function weightProgress(Request $request)
    {

        /*--------------------------------------------
            method 1
        ----------------------------------------------*/

        // $validate = Validator::make($request->all(),[
        //     'duration' => 'required|in:month,3month,6month,year',
        //     'muscle' => 'required|in:legs,biceps,triceps,back,shoulders,chest,arms,abdominals,forearms',
        // ]);
        // if($validate->fails())
        // return response()->json([
        //     'status' => false,
        //     'message' => $validate->errors()->all()[0]
        // ]);
        // $dateToday = Carbon::today();
        // $dateRanges = [];
        // for ($i=0; $i<6 ; $i++) { 
        //     if($request->duration==='month'){
        //         $dateRanges[$i]['endDate'] = $dateToday->clone()->subDays($i*5)->endOfDay();
        //         $dateRanges[$i]['startDate'] = $dateToday->clone()->subDays(($i+1)*5);
        //     } else if($request->duration==='3month'){
        //         $dateRanges[$i]['endDate'] = $dateToday->clone()->subDays($i*15)->endOfDay();
        //         $dateRanges[$i]['startDate'] = $dateToday->clone()->subDays(($i+1)*15);
        //     } else if($request->duration==='6month'){
        //         $dateRanges[$i]['endDate'] = $dateToday->clone()->subMonthsNoOverflow($i)->endOfDay();
        //         $dateRanges[$i]['startDate'] = $dateToday->clone()->subMonthsNoOverflow($i+1);
        //     } else {
        //         $dateRanges[$i]['endDate'] = $dateToday->clone()->subMonthsNoOverflow($i*2)->endOfDay();
        //         $dateRanges[$i]['startDate'] = $dateToday->clone()->subMonthsNoOverflow(($i+1)*2);
        //     } 
        // }
        // $labels = [];
        // $dataset = [];
        // $userWeightUnit = $this->userSelecetdWeightUnit(Auth::id());
        // foreach ($dateRanges as $dateRange) {
        //     array_push($labels,$dateRange['startDate']->clone()->format('M d').' - '.$dateRange['endDate']->clone()->format('M d'));
        //     $weights = ExerciseCompilation::where('user_id',Auth::id())->where('target_muscle',$request->muscle)
        //     ->whereBetween('updated_at',[$dateRange['startDate'],$dateRange['endDate']])->get(['weight','weight_unit']);
        //     $allWeighsSameUnit = [];
        //     foreach ($weights as $wt) {
        //         if($wt->weight_unit==='kg')
        //         array_push($allWeighsSameUnit,$wt->weight);
        //         else    // unit is lbs
        //         array_push($allWeighsSameUnit,$wt->weight*0.453592);
        //     }
        //     if(count($allWeighsSameUnit)>0){
        //         if($userWeightUnit=='kg')
        //         array_push($dataset,round((array_sum($allWeighsSameUnit)/count($allWeighsSameUnit)),2));
        //         else    // unit is lbs
        //         array_push($dataset,round((array_sum($allWeighsSameUnit)/count($allWeighsSameUnit))*2.20462,2));
        //     }
        //     else
        //     array_push($dataset,0);
        // }
        // $returnData['labels'] = $labels;
        // $returnData['dataset'] = $dataset;
        // $returnData['weights_unit'] = $userWeightUnit;
        // return response()->json([
        //     'status' => true,
        //     'data' => $returnData
        // ]);

        /*--------------------------------------------
            method 2
        ----------------------------------------------*/

        // $validate = Validator::make($request->all(),[
        //     'muscle' => 'required|integer|between:0,8',
        // ]);
        // if($validate->fails())
        // return response()->json([
        //     'status' => false,
        //     'message' => $validate->errors()->all()[0]
        // ]);
        $dateRange['end'] = Carbon::today()->endOfDay();
        $dateRange['month_start'] = Carbon::today()->subMonthNoOverflow();
        $dateRange['3month_start'] = Carbon::today()->subMonthsWithNoOverflow(3);
        $dateRange['6month_start'] = Carbon::today()->subMonthsWithNoOverflow(6);
        $dateRange['year_start'] = Carbon::today()->subYearNoOverflow();
        $userWeightUnit = $this->userSelecetdWeightUnit(Auth::id());
        // $muscleNo = (int)$request->muscle;
        // if((int)$muscleNo===0)
        // $muscle = 'legs';
        // else if($muscleNo===1)
        // $muscle = 'biceps';
        // else if($muscleNo===2)
        // $muscle = 'triceps';
        // else if($muscleNo===3)
        // $muscle = 'back';
        // else if($muscleNo===4)
        // $muscle = 'shoulders';
        // else if($muscleNo===5)
        // $muscle = 'chest';
        // else if($muscleNo===6)
        // $muscle = 'arms';
        // else if($muscleNo===7)
        // $muscle = 'abdominals';
        // else if($muscleNo===8)
        // $muscle = 'forearms';
        // else
        // return response()->json([
        //     'status' => false,
        //     'message' => 'Invalid muscle selection'
        // ]);
        $returnData['month'] = [];
        $returnData['3month'] = [];
        $returnData['6month'] = [];
        $returnData['year'] = [];

        $returnData['year'] = ExerciseCompilation::where('user_id', Auth::id())->orderBy('created_at', 'asc')
            ->whereBetween('created_at', [$dateRange['year_start'], $dateRange['end']])->get(['weight', 'weight_unit', 'created_at']);
        foreach ($returnData['year'] as $record) {
            if ($userWeightUnit === 'kg')
                $record->weight = round($record->weight_unit === 'kg' ? $record->weight : $record->weight * 0.453592, 2);    // convert to kg if lbs
            else
                $record->weight = round($record->weight_unit === 'kg' ? $record->weight * 2.20462 : $record->weight, 2);     // convert to lbs if kg

            $record->date = $record->created_at->clone()->format('d-m-y H:i:s');
            unset($record->weight_unit);

            if ($record->created_at >= $dateRange['month_start'])
                array_push($returnData['month'], $record);

            if ($record->created_at >= $dateRange['3month_start'])
                array_push($returnData['3month'], $record);

            if ($record->created_at >= $dateRange['6month_start'])
                array_push($returnData['6month'], $record);

            unset($record->created_at);
        }
        return response()->json([
            'status' => true,
            'data' => $returnData
        ]);
    }

}
