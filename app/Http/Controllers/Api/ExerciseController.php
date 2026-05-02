<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ResolvesUserLanguage;
use App\Http\Controllers\Controller;
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
use App\Traits\ActivitiesTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use stdClass;

class ExerciseController extends Controller
{
    use ActivitiesTrait;
    use ResolvesUserLanguage;

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
            // 'video' => 'mimes:mp4,MP4|max:51200',
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
        if ($uploadedVideo) {
            $extension = $uploadedVideo->getClientOriginalExtension();
            $allowedVideoExtensions = ['mp4', 'MP4'];
            if (!in_array(strtolower($extension), $allowedVideoExtensions)) {
                return response()->json([
                    'status' => false,
                    'message' => "Video type is not MP4"
                ]);
            }
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

    public function updateExercise(Request $request)
    {
        $request->merge([
            'content_code' => ContentCodeNormalizer::normalize($request->input('content_code')),
        ]);
        $validate = Validator::make($request->all(), [
            'id' => 'required|numeric',
            'type' => 'string',
            'video' => 'mimes:mp4,MP4|max:51200',
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

        $data = Exercise::where('id', $request->id)->first();
        if ($data) {
            if (isset($request->tags)) {
                $data->tags = $request->tags;
            }
            if (isset($request->title)) {
                $data->title = $request->title;
            }
            if (isset($request->type)) {
                $data->type = $request->type;
            }
            if ($request->has('weights')) {
                $data->weights = is_null($request->weights) ? '' : $request->weights;
            }
            if (isset($request->language)) {
                $data->language = $request->language;
            }
            if ($request->instructions) {
                $data->instructions = $request->instructions;
            }
            if ($request->alternates) {
                $data->alternates = $request->alternates;
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
            if (is_null($item->tags))
                $item->tagNames = [];
            else {
                $item->tags = json_decode($item->tags);
                $item->tagNames = Tag::whereIn('id', $item->tags)->pluck('name')->toArray();
            }
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
        if (is_null($ex->tags))
            $ex->tagNames = [];
        else {
            $ex->tags = json_decode($ex->tags);
            $ex->tagNames = Tag::whereIn('id', $ex->tags)->pluck('name')->toArray();
        }
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
        ]);
        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors()->all()[0]
            ]);
        }
        foreach ($request->ids as $id) {
            $workout = WorkoutExercise::where('exercise_id', $id)->first();
            if (is_null($workout)) {
                Exercise::where('id', $id)->delete();
            }
        }
        return response()->json([
            'status' => true,
            'message' => 'Deleted! (Except those being used in workouts)'
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
            if (is_null($exercise->tags)) {
                $exercise->tags = json_encode($request->tag_ids);
            } else {
                $tagsArray = json_decode($exercise->tags);
                foreach ($request->tag_ids as $tagId) {
                    if (!in_array($tagId, $tagsArray))
                        array_push($tagsArray, $tagId);
                }
                $exercise->tags = json_encode($tagsArray);
            }
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
            // Add tag names for display
            if(!is_null($alt->tags)) {
                $tags = json_decode($alt->tags);
                $alt->tagNames = Tag::whereIn('id', $tags)->pluck('name')->toArray();
            } else {
                $alt->tagNames = [];
            }
            
            // Ensure video_url and image are accessible via accessors (they're already handled by the model)
            // The model's getVideoUrlAttribute and getImageAttribute will automatically format URLs
            
            // Ensure alternates field is decoded for consistency
            if(!is_null($alt->alternates)) {
                $alt->alternates = json_decode($alt->alternates);
            }
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