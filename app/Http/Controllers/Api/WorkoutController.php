<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProgramPhaseWorkout;
use App\Models\Tag;
use App\Models\UserSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Models\WorkoutFeedback;
use App\Support\ContentCodeNormalizer;
use App\Support\ContentLocaleResolver;
use App\Support\UserContentLocale;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use stdClass;

class WorkoutController extends Controller
{
    public function createWorkout(Request $request){
        $request->merge([
            'content_code' => ContentCodeNormalizer::normalize($request->input('content_code')),
        ]);
		$validate = Validator::make($request->all(),[
			'title' => 'required',
			'type' => 'required|in:regular,circuit,interval',
            'language' => 'required|in:ar,en,no',
            'tags' => 'array',
            'content_code' => [
                'nullable',
                'string',
                'max:64',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('workouts', 'content_code'),
            ],
		]);
		if($validate->fails()){
			return response()->json([
				'status' => false,
				'message' => $validate->errors()->all()[0]
			]);
		}

        $data = new Workout();

		$data->title = $request->title;
		$data->type = $request->type;
        if (isset($request->tags)) {
            $data->tags = json_encode($request->tags);
        }
        $data->language = $request->language;
        $data->category = 'master';
        $data->user_id = Auth::id();
        $data->content_code = $request->input('content_code');
		$data->save();

        if(is_null($data->tags))
        $data->tagNames = [];
        else{
            $data->tags = json_decode($data->tags);
            $data->tagNames = Tag::whereIn('id',$data->tags)->pluck('name')->toArray();
        }

        return response()->json([
			'status' => true,
			'data' => $data
		]);

    }

    public function createWorkoutExercise(Request $request){
        $validate = Validator::make($request->all(),[
            'workout_id' => 'required|numeric',
			'data' => 'required|array',
		]);
		if($validate->fails()){
			return response()->json([
				'status' => false,
				'message' => $validate->errors()->all()[0]
			]);
		}
        $workout = Workout::find($request->workout_id);
        if(is_null($workout)){
            return response()->json([
                'status' => false,
                'message' => 'Invalid Workout Id'
            ]);
        }

        foreach($request->data as $block){
            if(($block['type'] ?? null)==='simple'){
                $exerciseId = $block['item']['exercise_id'] ?? null;
                if(is_null($exerciseId) || !DB::table('exercises')->where('id',$exerciseId)->exists()){
                    return response()->json([
                        'status' => false,
                        'message' => 'Invalid exercise_id in workout data.'
                    ]);
                }
            } else {
                foreach(($block['items'] ?? []) as $subItem){
                    $exerciseId = $subItem['exercise_id'] ?? null;
                    if(is_null($exerciseId) || !DB::table('exercises')->where('id',$exerciseId)->exists()){
                        return response()->json([
                            'status' => false,
                            'message' => 'Invalid exercise_id in workout data.'
                        ]);
                    }
                }
            }
        }

        $workout->instructions = $request->instructions;
        if($request->data[0]['type']==='simple'){
            $loc = $request->data[0]['item']['exercise_id'];
        } else {
            $loc = $request->data[0]['items'][0]['exercise_id'];
        }
        $workout->image = DB::table('exercises')->where('id',$loc)->pluck('image')->first();
        $workout->update();

        foreach($request->data as $blockOrder => $item){
            if($item['type']!=='simple'){
                $groupId = $item['group_id'] ?? null;
                $groupType = $item['type'];
                foreach ($item['items'] as $idx => $item2) {
                    $data = new WorkoutExercise();
                    $data->workout_id = $request->workout_id;
                    $data->exercise_id = $item2['exercise_id'];
                    $data->sets = $item2['sets'];
                    $data->time = $item2['time'];
                    $data->reps = $item2['reps'];
                    $data->reps_type = $item2['reps_type'];
                    if(isset($item2['description']))
                    $data->description = $item2['description'];
                    $data->rest_period = $this->normalizeRestPeriod($item2['rest_period'] ?? 0);
                    $data->sets_rounds = $item['sets_rounds'];
                    $data->category = $item['type'];
                    $data->group_id = $groupId;
                    $data->group_type = $groupType;
                    $data->group_order = $blockOrder;
                    $data->save();
                }
            } else {
                $data = new WorkoutExercise();
                $data->workout_id = $request->workout_id;
                $data->exercise_id = $item['item']['exercise_id'];
                $data->sets = $item['item']['sets'];
                $data->time = $item['item']['time'];
                $data->reps = $item['item']['reps'];
                $data->reps_type = $item['item']['reps_type'];
                if(isset($item['item']['description']))
                $data->description = $item['item']['description'];
                $data->rest_period = $this->normalizeRestPeriod($item['item']['rest_period'] ?? 0);
                $data->category = 'simple';
                $data->group_order = $blockOrder;
                $data->save();
            }
        }
        return response()->json([
			'status' => true,
			'message' => 'Workout Exercise Added',
            'workout' => $workout->id
		]);

    }

    public function canEditWorkout($id){
        return !ProgramPhaseWorkout::where('workout_id',$id)->exists();
    }

    public function updateWorkout(Request $request){
        $workout = Workout::find($request->id);
        if(is_null($workout))
        return response()->json([
			'status' => false,
			'message' => 'Invalid Workout Id'
		]);
        // if(ProgramPhaseWorkout::where('workout_id',$workout->id)->exists())
        // return response()->json([
		// 	'status' => false,
		// 	'message' => 'Cannot Edit. (being used in a program)'
		// ]);

        $workout->title = $request->title;
        $workout->instructions = $request->instructions;
        $workout->language = $request->language;
        $workout->tags = json_encode($request->tags);
        if ($request->has('content_code')) {
            $request->merge([
                'content_code' => ContentCodeNormalizer::normalize($request->input('content_code')),
            ]);
            $codeValidate = Validator::make($request->only('content_code'), [
                'content_code' => [
                    'nullable',
                    'string',
                    'max:64',
                    'regex:/^[A-Za-z0-9._-]+$/',
                    Rule::unique('workouts', 'content_code')->ignore($workout->id),
                ],
            ]);
            if ($codeValidate->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $codeValidate->errors()->first(),
                ]);
            }
            $workout->content_code = $request->input('content_code');
        }
        $workout->update();

        $newCreated = [];
        try {
            foreach ($request->exs as $blockOrder => $item) {
                if($item['type']!=='simple'){
                    $groupId = $item['group_id'] ?? null;
                    $groupType = $item['type'];
                    foreach ($item['items'] as $item2) {
                        $data = new WorkoutExercise();
                        $data->workout_id = $workout->id;
                        $data->exercise_id = $item2['exercise_id'];
                        $data->sets = $item2['sets'];
                        $data->time = $item2['time'];
                        $data->reps = $item2['reps'];
                        $data->reps_type = $item2['reps_type'];
                        if(isset($item2['description']))
                        $data->description = $item2['description'];
                        $data->rest_period = $this->normalizeRestPeriod($item2['rest_period'] ?? 0);
                        $data->sets_rounds = $item['sets_rounds'];
                        $data->category = $item['type'];
                        $data->group_id = $groupId;
                        $data->group_type = $groupType;
                        $data->group_order = $blockOrder;
                        $data->save();
                        $newCreated[] = $data->id;
                    }
                } else {
                    $data = new WorkoutExercise();
                    $data->workout_id = $workout->id;
                    $data->exercise_id = $item['item']['exercise_id'];
                    $data->sets = $item['item']['sets'];
                    $data->time = $item['item']['time'];
                    $data->reps = $item['item']['reps'];
                    $data->reps_type = $item['item']['reps_type'];
                    if(isset($item['item']['description']))
                    $data->description = $item['item']['description'];
                    $data->rest_period = $this->normalizeRestPeriod($item['item']['rest_period'] ?? 0);
                    $data->category = 'simple';
                    $data->group_order = $blockOrder;
                    $data->save();
                    $newCreated[] = $data->id;
                }
            }
            // delete older exercises
            WorkoutExercise::where('workout_id',$workout->id)->whereNotIn('id',$newCreated)->delete();
            return response()->json([
                'status' => true,
                'message' => 'Workout Updated Successfully.',
            ]);
        } catch(Exception $er){
            // delete cuurrent exercises
            WorkoutExercise::whereIn('id',$newCreated)->delete();
            return response()->json([
                'status' => false,
                'message' => 'Something Went Wrong.',
                'error' => $er->getMessage().'----'.$er->getFile().'----'.$er->getLine()
            ]);
        }
    }

    public function getAllWorkouts(){
        $workouts = Workout::where('category','master')->withCount('workoutExercises')->orderBy('title', 'asc')->get();
        $returnData = [];
        foreach ($workouts as $item) {
            if($item->workout_exercises_count>0){
                $item->time = $item->created_at->format('D d M, Y');
                $tagIds = $this->parseTagIds($item->tags);
                $item->tags = $tagIds;
                $item->tagNames = $tagIds === [] ? [] : Tag::whereIn('id', $tagIds)->pluck('name')->toArray();
                array_push($returnData,$item);
            }
        }
        return response()->json([
            'status' => true,
            'data' => $returnData
        ]);
    }

    function deleteWorkout($id){
        $used = ProgramPhaseWorkout::where('workout_id',$id)->first();
        if($used)
        return response()->json([
            'status' => true,
            'message' => "Cannot Delete Workout being Used in a Program."
        ]);
        Workout::destroy($id);
        return response()->json([
            'status' => true,
            'message' => "Workout deleted successfully."
        ]);
    }

    function detailedWorkout($id){
        $data = Workout::where('id',$id)->with(['workoutExercises' => function($query2){
            // Respect block order first; fallback to id for legacy rows.
            $query2->orderByRaw('COALESCE(group_order, id) asc')
                  ->orderBy('id', 'asc')
                  ->with(['exerciseDetail' => function($exDetail){
                      // Ensure exercise title/name is always loaded
                      // This is critical for displaying exercise information
                  }]);
        }])->first();
        if(is_null($data))
        return response()->json([
			'status' => false,
			'message' => "Invalid Workout."
		]);
        $hubLang = UserContentLocale::forAuthenticatedUser();
        ContentLocaleResolver::overlayWorkout($data, $hubLang);
        foreach ($data->workoutExercises as $we) {
            if ($we->exerciseDetail) {
                ContentLocaleResolver::overlayExercise($we->exerciseDetail, $hubLang);
            }
        }
        // Ensure daily_summary is always returned (even if null)
        if(!isset($data->daily_summary)) {
            $data->daily_summary = null;
        }
        // Ensure instructions is always returned (even if null) - this is the workout description/details
        if(!isset($data->instructions)) {
            $data->instructions = null;
        }
        $data->exs = $this->organizeExercises($data->type,$data->workoutExercises);
        $tagIds = $this->parseTagIds($data->tags);
        $data->tags = $tagIds;
        $data->tagNames = $tagIds === [] ? [] : Tag::whereIn('id', $tagIds)->pluck('name')->toArray();
        unset($data->workoutExercises);
        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    function delteWorkoutDetaled(Request $request){
        try{
            $validate = Validator::make($request->all(),[
                'ids' => 'required|array'
            ]);
            if($validate->fails())
            return response()->json([
                'status' => false,
                'message' => $validate->errors()->all()[0]
            ]);
            $idsToDelete = [];
            foreach ($request->ids as $key => $value) {
                $inPhase = ProgramPhaseWorkout::where('workout_id',$value)->first();
                if(is_null($inPhase))
                array_push($idsToDelete,$value);
            }
            Workout::destroy($idsToDelete);
            return response()->json([
                'status' => true,
                'message' => 'Deleted Successfully. (Workouts being used in programs can not be deleted)'
            ]);
        } catch(Exception $er){
            return response()->json([
                'status' => true,
                'message' => 'Something Went Wrong.',
                'error' => $er->getMessage().'------Line # '.$er->getLine().'----File: '.$er->getFile()
            ]);
        }
    }

    function allWorkoutsList(Request $request){
        $lang = strtolower((string) $request->input('lang', UserContentLocale::forAuthenticatedUser()));
        $tagIds = $this->normalizeWorkoutTagIds($request->input('tag_ids'));
        $wrks = Workout::where('category', 'master')
            ->availableInContentLocale($lang)
            ->orderBy('title', 'asc')
            ->orderBy('id', 'asc')
            ->get(['id', 'title', 'type', 'content_code', 'image', 'tags', 'language', 'locale_translations']);
        $returnData = [];
        foreach ($wrks as $wrk) {
            if (WorkoutExercise::where('workout_id', $wrk->id)->count() === 0) {
                continue;
            }
            if ($tagIds !== [] && ! $this->workoutMatchesAnyTag($wrk, $tagIds)) {
                continue;
            }
            ContentLocaleResolver::overlayWorkout($wrk, $lang);
            $returnData[] = $wrk;
        }

        return response()->json([
            'status' => true,
            'data' => $returnData,
        ]);
    }

    /**
     * @return list<int>
     */
    private function normalizeWorkoutTagIds($raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        if (is_string($raw)) {
            return array_values(array_filter(array_map('intval', explode(',', $raw))));
        }
        if (is_array($raw)) {
            return array_values(array_filter(array_map('intval', $raw)));
        }

        return [];
    }

    /**
     * @param  list<int>  $selectedIds
     */
    private function workoutMatchesAnyTag(Workout $wrk, array $selectedIds): bool
    {
        if ($selectedIds === []) {
            return true;
        }
        $tags = $wrk->tags;
        if ($tags === null || $tags === '') {
            return false;
        }
        $ids = $this->parseTagIds($tags);
        if ($ids === []) {
            return false;
        }
        foreach ($ids as $tid) {
            if (in_array((int) $tid, $selectedIds, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed $tagsRaw
     * @return list<int>
     */
    private function parseTagIds($tagsRaw): array
    {
        if ($tagsRaw === null || $tagsRaw === '') {
            return [];
        }
        if (is_array($tagsRaw)) {
            return array_values(array_filter(array_map('intval', $tagsRaw)));
        }
        if (! is_string($tagsRaw)) {
            return [];
        }

        $decoded = json_decode($tagsRaw, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map('intval', $decoded)));
        }

        return array_values(array_filter(array_map('intval', explode(',', $tagsRaw))));
    }
    public function createUserDefinedWorkouts(Request $request){
        $request->merge([
            'content_code' => ContentCodeNormalizer::normalize($request->input('content_code')),
        ]);
        $validate = Validator::make($request->all(),[
			'title' => 'required',
			// 'date' => 'required',
			'type' => 'required|in:regular,circuit,interval',
			'data' => 'required',
            'content_code' => [
                'nullable',
                'string',
                'max:64',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('workouts', 'content_code'),
            ],
		]);
		if($validate->fails()){
			return response()->json([
				'status' => false,
				'message' => $validate->errors()->all()[0]
			]);
		}
        $userLang = UserSetting::where('user_id',Auth::id())->pluck('language')->first();
        $workout = new Workout();
		$workout->title = $request->title;
		$workout->type = $request->type;
		$workout->language =$userLang;
		$workout->category = 'user defined';
		$workout->user_id = Auth::id();
        $workout->content_code = $request->input('content_code');
        if($request->data[0]['type']==='simple'){
            $loc = $request->data[0]['item']['exercise_id'];
        } else {
            $loc = $request->data[0]['items'][0]['exercise_id'];
        }
        $workout->image = DB::table('exercises')->where('id',$loc)->pluck('image')->first();

		$workout->save();

        foreach($request->data as $blockOrder => $item){
            if($item['type']!=='simple'){
                $groupId = $item['group_id'] ?? null;
                $groupType = $item['type'];
                foreach ($item['items'] as $item2) {
                    $data = new WorkoutExercise();
                    $data->workout_id = $workout->id;
                    $data->exercise_id = $item2['exercise_id'];
                    $data->sets = $item2['sets'];
                    $data->reps = $item2['reps'];
                    $data->reps_type = "text";
                    if(isset($item2['description']))
                    $data->description = $item2['description'];
                    $data->rest_period = $this->normalizeRestPeriod($item2['rest_period'] ?? 0);
                    $data->sets_rounds = $item['sets_rounds'];
                    $data->category = $item['type'];
                    $data->group_id = $groupId;
                    $data->group_type = $groupType;
                    $data->group_order = $blockOrder;
                    $data->save();
                }
            } else {
                $data = new WorkoutExercise();
                $data->workout_id = $workout->id;
                $data->exercise_id = $item['item']['exercise_id'];
                $data->sets = $item['item']['sets'];
                $data->reps = $item['item']['reps'];
                $data->reps_type = "text";
                if(isset($item['item']['description']))
                $data->description = $item['item']['description'];
                $data->rest_period = $this->normalizeRestPeriod($item['item']['rest_period'] ?? 0);
                $data->save();
            }
        }
        return response()->json([
			'status' => true,
			'message' => 'Workout Exercise Added.',
            'workout' => $workout->id
		]);
    }

    function myworkout($id){
        // Explicitly select daily_summary to ensure it's always included
        $workout = Workout::where('id',$id)
            ->where('user_id',Auth::id())
            ->select('*') // Select all fields including daily_summary
            ->with(['workoutExercises' => function($q){
                // Respect block order first; fallback to id for legacy rows.
                $q->orderByRaw('COALESCE(group_order, id) asc')
                  ->orderBy('id', 'asc')
                  ->with(['exerciseDetail' => function($exDetail){
                      // Ensure exercise title/name is always loaded
                      // This is critical for displaying exercise information
                  }]);
            }])->first();
        if(is_null($workout))
        return response()->json([
			'status' => false,
            'message' => 'Invalid Workout ID'
		]);
        if(is_null($workout->tags))
        $workout->tagNames = [];
        else{
            $workout->tags = json_decode($workout->tags);
            $workout->tagNames = Tag::whereIn('id',$workout->tags)->pluck('name')->toArray();
        }
        // Ensure daily_summary is always returned (even if null)
        if(!isset($workout->daily_summary)) {
            $workout->daily_summary = null;
        }
        // Ensure instructions is always returned (even if null) - this is the workout description/details
        if(!isset($workout->instructions)) {
            $workout->instructions = null;
        }
        $userLang = UserContentLocale::forAuthenticatedUser();
        ContentLocaleResolver::overlayWorkout($workout, $userLang);
        foreach ($workout->workoutExercises as $wrkEx) {
            if (! is_null($wrkEx->exercise_id) && ! is_null($wrkEx->exerciseDetail)) {
                ContentLocaleResolver::overlayExercise($wrkEx->exerciseDetail, $userLang);
                if (is_null($wrkEx->exerciseDetail->tags)) {
                    $wrkEx->exerciseDetail->tagNames = [];
                } else {
                    $wrkEx->exerciseDetail->tags = gettype($wrkEx->exerciseDetail->tags) === 'string'
                        ? json_decode($wrkEx->exerciseDetail->tags)
                        : $wrkEx->exerciseDetail->tags;
                    $wrkEx->exerciseDetail->tagNames = Tag::whereIn('id', $wrkEx->exerciseDetail->tags)->pluck('name')->toArray();
                }
            }
        }
        $workout->exs = $this->organizeExercises($workout->type,$workout->workoutExercises);
        unset($workout->workoutExercises);
        return response()->json([
			'status' => true,
            'data' => $workout
		]);
    }

    function organizeExercises($wrkType,$exercises){
        // Group by (group_type, group_id) so multiple supersets show as separate blocks.
        // rest_period is now int (seconds) - format for display in frontend or via accessor.
        $exs = [];
        $group = false;
        $group_count = 0;
        $group_type = 'none';
        $current_group_id = null;
        foreach ($exercises as $i => $ex) {
            if($ex->category==='simple'){
                if($group){
                    $group = false;
                    $group_count = 0;
                    $group_type = 'none';
                    $current_group_id = null;
                }

                unset($ex->sets_rounds);
                unset($ex->category);
                unset($ex->group_id);
                unset($ex->group_type);
                unset($ex->group_order);
                $temp = new stdClass;
                $temp->order = $i;
                $temp->type = 'simple';
                $temp->items = [];
                $temp->item = $ex;
                array_push($exs,$temp);
            } else {
                $exGroupId = $ex->group_id ?? null;
                $exGroupType = $ex->category;
                $startNewGroup = ($group_type !== $exGroupType) || ($current_group_id !== $exGroupId);

                if($startNewGroup){
                    $group_type = $exGroupType;
                    $current_group_id = $exGroupId;
                    $group = true;
                    $group_count = 0;
                }
                if($group_count===0){
                    $temp = new stdClass;
                    $temp->type = $group_type;
                    $temp->type_name = $this->snakeToCapitalize($group_type);
                    $temp->sets_rounds = $ex->sets_rounds;
                    $temp->group_id = $exGroupId;
                    unset($ex->sets_rounds);
                    unset($ex->category);
                    unset($ex->group_id);
                    unset($ex->group_type);
                    unset($ex->group_order);
                    $temp->order = $i;
                    $temp->items = [$ex];
                    $temp->item = null;
                    $group_count++;
                    array_push($exs,$temp);
                } else {
                    unset($ex->sets_rounds);
                    unset($ex->category);
                    unset($ex->group_id);
                    unset($ex->group_type);
                    unset($ex->group_order);
                    array_push($exs[sizeof($exs)-1]->items,$ex);
                }
            }
        }
        return $exs;
    }

    function snakeToCapitalize($value) {
        return preg_replace_callback('/^[-_]*(.)/', function($matches) {
            return strtoupper($matches[1]);
        }, preg_replace_callback('/[-_]+(.)/', function($matches) {
            return ' ' . strtoupper($matches[1]);
        }, $value));
    }

    /**
     * Normalize rest_period to int seconds (0-240). Handles legacy string values.
     */
    private function normalizeRestPeriod($value): int
    {
        if (is_numeric($value)) {
            return min(240, max(0, (int) $value));
        }
        if (is_string($value)) {
            if (stripos($value, 'min') !== false) {
                $num = (int) preg_replace('/[^0-9]/', '', $value);
                return min(240, $num * 60);
            }
            if (stripos($value, 'sec') !== false) {
                $num = (int) preg_replace('/[^0-9]/', '', $value);
                return min(240, $num);
            }
        }
        return 0;
    }

    function workoutFeedback(Request $request){
        $validate = Validator::make($request->all(),[
            'workout_id' => 'required|exists:workouts,id',
            'rating' => 'required|integer|between:0,9',
            'review' => 'required|string'
        ]);
		if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $feedbackExists = WorkoutFeedback::where('workout_id',$request->workout_id)->where('user_id',Auth::id())->first();
        if($feedbackExists)
        return response()->json([
            'status' => false,
            'message' => 'Feedback Already Submitted.'
        ]);
        $feedback = new WorkoutFeedback();
        $feedback->workout_id = $request->workout_id;
        $feedback->user_id  = Auth::id();
        $feedback->rating = $request->rating;
        $feedback->review = $request->review;
        $feedback->save();
        return response()->json([
            'status' => true,
            'message' => 'Feedback Submitted.'
        ]);
    }
}
