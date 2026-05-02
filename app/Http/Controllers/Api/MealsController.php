<?php

namespace App\Http\Controllers\Api;

use App\Helpers\JsonSanitizer;
use App\Http\Controllers\Concerns\ResolvesUserLanguage;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Meal;
use App\Models\ScheduledTask;
use App\Models\STask;
use App\Models\Tag;
use App\Services\GoogleTranslateService;
use App\Support\ContentLocaleResolver;
use App\Traits\ApiResponse;
use App\Traits\ActivitiesTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use stdClass;
class MealsController extends Controller
{
    use ApiResponse, ActivitiesTrait, ResolvesUserLanguage;
    //
    function createMeal(Request $request){
        $validate = Validator::make($request->all(),[
            'name' => 'required|string',
            'prep_time' => 'required|string',
            'cook_time' => 'required|string',
            'suitable_for' => 'required',
            'tags' => 'required|string',
            'contains' => 'string',
            'file' => 'required|mimes:jpg,JPG,jpeg,JPEG,png,PNG,mp4,MP4,mkv,MKV|max:10240',
            'file_type' => 'required|in:image,video',
            'video_thumbnail' => 'mimes:jpg,JPG,jpeg,JPEG,png,PNG',
            'no_of_servings' => 'required|numeric',
            'calories_per_serving' => 'required|numeric',
            'protein_per_serving' => 'required|numeric',
            'carbs_per_serving' => 'required|numeric',
            'fat_per_serving' => 'required|numeric',
            'fiber_per_serving' => 'required|numeric',
            'ingredients' => 'required|string',
            'directions' => 'string',
            'meal_type' => 'required|in:auto,manual',
            'language' => 'required|in:en,ar',
        ]);
        if($validate->fails())
            return $this->validationError($validate);
        $newId = Meal::orderBy('id','desc')->pluck('id')->first()+1;
        $url2 = null;
        $url = $newId."_meal_file_".time().'_'.uniqid().'.'.request()->file->getClientOriginalExtension();
        $request->file->storeAs('meals', $url, 'fwd_media');
        if($request->file_type==='video') {
            if(!$request->has('video_thumbnail'))
            return response()->json([
                'status' => false,
                'message' => 'Thumbnail is required for video file.'
            ]);
            $url2 = $newId."_meal_file_thumb".time().'_'.uniqid().'.'.request()->video_thumbnail->getClientOriginalExtension();
            $request->video_thumbnail->storeAs('meals', $url2, 'fwd_media');
        }

        $meal = new Meal();
        $meal->user_id = Auth::id();
        $meal->name = $request->name;
        $meal->language = $request->language;
        $meal->prep_time = $request->prep_time;
        $meal->cook_time = $request->cook_time;
        $meal->suitable_for = $request->suitable_for;
        $meal->tags = $request->tags;
        $meal->contains = $request->contains;
        $meal->file = $url;
        $meal->video_thumbnail = $url2;
        $meal->file_type = $request->file_type;
        $meal->no_of_servings = $request->no_of_servings;
        $meal->calories_per_serving = $request->calories_per_serving;
        $meal->protein_per_serving = $request->protein_per_serving;
        $meal->carbs_per_serving = $request->carbs_per_serving;
        $meal->fat_per_serving = $request->fat_per_serving;
        $meal->fiber_per_serving = $request->fiber_per_serving;
        $meal->ingredients = $request->ingredients;
        $meal->directions = $request->directions;
        if($request->has('nutrient')){
            $meal->nutrient = $request->nutrient;
        }
        $meal->meal_type = $request->meal_type;
        $meal->save();

        if($request->language == 'en'){
            $meal = new Meal();
            $meal->user_id = Auth::id();
            $meal->name = $this->getTranslatedText($request->name, 'ar');
            $meal->language = 'ar';
            $meal->prep_time = $request->prep_time;
            $meal->cook_time = $request->cook_time;
            $meal->suitable_for = $request->suitable_for;
            $meal->tags = $request->tags;
            $meal->contains = $request->contains;
            $meal->file = $url;
            $meal->video_thumbnail = $url2;
            $meal->file_type = $request->file_type;
            $meal->no_of_servings = $request->no_of_servings;
            $meal->calories_per_serving = $request->calories_per_serving;
            $meal->protein_per_serving = $request->protein_per_serving;
            $meal->carbs_per_serving = $request->carbs_per_serving;
            $meal->fat_per_serving = $request->fat_per_serving;
            $meal->fiber_per_serving = $request->fiber_per_serving;
            $meal->ingredients = $request->ingredients;
            $meal->directions = $request->directions;
            if($request->has('nutrient')){
                $meal->nutrient = $request->nutrient;
            }
            $meal->meal_type = $request->meal_type;
            $meal->save();
        }

        return response()->json([
            'status' => true,
            'message' => 'Meal Added Successfully.'
        ]);
    }

    function updateMeal(Request $request){
        $validate = Validator::make($request->all(),[
            'id' => 'required|numeric|exists:meals,id',
            'name' => 'required|string',
            'prep_time' => 'required|string',
            'cook_time' => 'required|string',
            'suitable_for' => 'required',
            'tags' => 'required|string',
            'contains' => 'string',
            'file' => 'mimes:jpg,JPG,jpeg,JPEG,png,PNG,mp4,MP4,mkv,MKV|max:10240',
            'file_type' => 'in:image,video',
            'video_thumbnail' => 'mimes:jpg,JPG,jpeg,JPEG,png,PNG',
            'no_of_servings' => 'required|numeric',
            'calories_per_serving' => 'required|numeric',
            'protein_per_serving' => 'required|numeric',
            'carbs_per_serving' => 'required|numeric',
            'fat_per_serving' => 'required|numeric',
            'fiber_per_serving' => 'required|numeric',
            'ingredients' => 'required|string',
            'directions' => 'string',
            'meal_type' => 'required|in:auto,manual',
            'language' => 'required|in:en,ar',
        ]);
        if($validate->fails())
            return $this->validationError($validate);
        $url = null;
        $url2 = null;
        if($request->has('file')){
            $url = $request->id."_meal_file_".time().'_'.uniqid().'.'.request()->file->getClientOriginalExtension();
            $request->file->storeAs('meals', $url, 'fwd_media');
            if($request->file_type==='video') {
                if(!$request->has('video_thumbnail'))
                return response()->json([
                    'status' => false,
                    'message' => 'Thumbnail is required for video file.'
                ]);
                $url2 = $request->id."_meal_file_thumb".time().'_'.uniqid().'.'.request()->video_thumbnail->getClientOriginalExtension();
                $request->video_thumbnail->storeAs('meals', $url2, 'fwd_media');
            }
        }

        $meal = Meal::find($request->id);
        $meal->user_id = Auth::id();
        $meal->name = $request->name;
        $meal->prep_time = $request->prep_time;
        $meal->cook_time = $request->cook_time;
        $meal->suitable_for = $request->suitable_for;
        $meal->tags = $request->tags;
        $meal->contains = $request->contains;
        $meal->no_of_servings = $request->no_of_servings;
        $meal->calories_per_serving = $request->calories_per_serving;
        $meal->protein_per_serving = $request->protein_per_serving;
        $meal->carbs_per_serving = $request->carbs_per_serving;
        $meal->fat_per_serving = $request->fat_per_serving;
        $meal->fiber_per_serving = $request->fiber_per_serving;
        $meal->ingredients = $request->ingredients;
        $meal->directions = $request->directions;
        $meal->language = $request->language;
        if($request->has('nutrient')){
            $meal->nutrient = $request->nutrient;
        }
        $meal->meal_type = $request->meal_type;
        if($request->has('file')){
            $meal->file = $url;
            $meal->video_thumbnail = $url2;
            $meal->file_type = $request->file_type;
        }
        $meal->save();
        return response()->json([
            'status' => true,
            'message' => 'Meal Updated Successfully.'
        ]);
    }

    function getMeals(){
        $meals = Meal::where('meal_by','admin')->orderBy('created_at','desc')->get(['name','suitable_for','calories_per_serving','id','file','file_type','video_thumbnail','tags','language']);
        
        // Fix N+1: Collect all tag IDs and fetch in one query
        $allTagIds = [];
        foreach ($meals as $meal) {
            if (!is_null($meal->tags)) {
                $tagIds = json_decode($meal->tags, true);
                if (is_array($tagIds)) {
                    $allTagIds = array_merge($allTagIds, $tagIds);
                }
            }
        }
        
        $tagsMap = [];
        if (!empty($allTagIds)) {
            $uniqueTagIds = array_unique($allTagIds);
            $tagsMap = Tag::whereIn('id', $uniqueTagIds)->pluck('name', 'id')->toArray();
        }
        
        foreach ($meals as $meal) {
            if(is_null($meal->tags)) {
                $meal->tagNames = [];
                $meal->tags = [];
            } else {
                $tagIds = json_decode($meal->tags, true);
                $meal->tags = is_array($tagIds) ? $tagIds : [];
                $meal->tagNames = array_filter(array_map(function($tagId) use ($tagsMap) {
                    return $tagsMap[$tagId] ?? null;
                }, $meal->tags));
            }
        }
        
        return $this->success($meals);
    }

    function discoverMeals(Request $request){
        $validate = Validator::make($request->all(),[
            'type' => 'nullable|in:breakfast,lunch,dinner,snacks,all',
            'tags' => 'nullable|string', // Comma-separated tag IDs
        ]);
        if($validate->fails())
            return $this->validationError($validate);

        $userLang = $this->currentUserLanguage();
        $mealQuery = Meal::where('meal_by', 'admin');
        if (in_array($userLang, ['en', 'ar'], true)) {
            $mealQuery->where('language', $userLang);
        } else {
            $mealQuery->where('language', 'en');
        }
        $meals = $mealQuery->orderBy('created_at', 'desc')->get();
        $returnData = [];
        
        $requestType = $request->type ?? 'all';
        Log::info('discoverMeals - Total meals found: ' . $meals->count());
        Log::info('discoverMeals - Request type: ' . $requestType);
        
        // Get tag filter if provided
        $tagFilter = [];
        if($request->has('tags') && !empty($request->tags)) {
            $tagFilter = is_array($request->tags) ? $request->tags : explode(',', $request->tags);
            Log::info('discoverMeals - Tag filter: ' . implode(',', $tagFilter));
        }
        
        // Fix N+1: Collect all tag IDs and fetch in one query
        $allTagIds = [];
        foreach ($meals as $meal) {
            if (!is_null($meal->tags)) {
                $tagIds = json_decode($meal->tags, true);
                if (is_array($tagIds)) {
                    $allTagIds = array_merge($allTagIds, $tagIds);
                }
            }
        }
        
        $tagsMap = [];
        if (!empty($allTagIds)) {
            $uniqueTagIds = array_unique($allTagIds);
            $tagsMap = Tag::whereIn('id', $uniqueTagIds)->pluck('name', 'id')->toArray();
        }
        
        foreach ($meals as $meal) {
            if ($meal->language !== $userLang) {
                $translations = $meal->locale_translations;
                if (is_array($translations) && isset($translations[$userLang]) && is_array($translations[$userLang])) {
                    $p = $translations[$userLang];
                    foreach (ContentLocaleResolver::MEAL_FIELDS as $f) {
                        if (! empty($p[$f]) && is_string($p[$f])) {
                            $meal->{$f} = $p[$f];
                        }
                    }
                }
            }

            $suitableFor = json_decode($meal->suitable_for, true);
            if (!is_array($suitableFor)) {
                Log::warning('discoverMeals - Meal ID ' . $meal->id . ' has invalid suitable_for: ' . $meal->suitable_for);
                continue;
            }
            
            if($requestType === 'all' || in_array($requestType, $suitableFor)){
                // Apply tag filter if provided
                if(!empty($tagFilter)) {
                    $mealTags = $meal->tags ? json_decode($meal->tags, true) : [];
                    if (!is_array($mealTags)) {
                        $mealTags = [];
                    }
                    $hasMatchingTag = false;
                    foreach($tagFilter as $tagId) {
                        // Convert tagId to integer for comparison (meal tags can be int or string in JSON)
                        $tagIdInt = (int)$tagId;
                        if(in_array($tagIdInt, array_map('intval', $mealTags))) {
                            $hasMatchingTag = true;
                            break;
                        }
                    }
                    if(!$hasMatchingTag) {
                        continue; // Skip this meal if no matching tags
                    }
                }
                
                if($meal->file_type==='image')
                $meal->image = $meal->file ?? '';
                else
                $meal->image = $meal->video_thumbnail ?? '';
                unset($meal->file);
                unset($meal->file_type);
                unset($meal->video_thumbnail);
                $meal->ingredients = json_decode($meal->ingredients);
                
                // Add tag names using pre-fetched map
                if(is_null($meal->tags)) {
                    $meal->tagNames = [];
                } else {
                    $tagIds = json_decode($meal->tags, true);
                    $meal->tagNames = array_filter(array_map(function($tagId) use ($tagsMap) {
                        return $tagsMap[$tagId] ?? null;
                    }, is_array($tagIds) ? $tagIds : []));
                }
                
                // Sanitize meal data to prevent malformed JSON (invalid UTF-8, control chars, etc.)
                $mealArray = $meal->toArray();
                $mealArray = JsonSanitizer::sanitize($mealArray);
                // Ensure image is always a string (never null) so mobile parsers and CachedNetworkImage work
                $mealArray['image'] = $mealArray['image'] ?? '';
                array_push($returnData, $mealArray);
            }
        }
        
        // Fallback: when tag filter is applied but no meals match (e.g. meals have no tags), return all meals
        if (!empty($tagFilter) && empty($returnData)) {
            Log::info('discoverMeals - Tag filter returned 0 meals, falling back to all meals');
            $returnData = [];
            foreach ($meals as $meal) {
                if ($meal->language !== $userLang) {
                    $translations = $meal->locale_translations;
                    if (is_array($translations) && isset($translations[$userLang]) && is_array($translations[$userLang])) {
                        $p = $translations[$userLang];
                        foreach (ContentLocaleResolver::MEAL_FIELDS as $f) {
                            if (! empty($p[$f]) && is_string($p[$f])) {
                                $meal->{$f} = $p[$f];
                            }
                        }
                    }
                }
                $suitableFor = json_decode($meal->suitable_for, true);
                if (!is_array($suitableFor)) continue;
                if ($requestType === 'all' || in_array($requestType, $suitableFor)) {
                    if ($meal->file_type === 'image') {
                        $meal->image = $meal->file ?? '';
                    } else {
                        $meal->image = $meal->video_thumbnail ?? '';
                    }
                    unset($meal->file, $meal->file_type, $meal->video_thumbnail);
                    $meal->ingredients = json_decode($meal->ingredients);
                    if (is_null($meal->tags)) {
                        $meal->tagNames = [];
                    } else {
                        $tagIds = json_decode($meal->tags, true);
                        $meal->tagNames = array_filter(array_map(function ($tagId) use ($tagsMap) {
                            return $tagsMap[$tagId] ?? null;
                        }, is_array($tagIds) ? $tagIds : []));
                    }
                    $mealArray = $meal->toArray();
                    $mealArray = JsonSanitizer::sanitize($mealArray);
                    $mealArray['image'] = $mealArray['image'] ?? '';
                    array_push($returnData, $mealArray);
                }
            }
        }
        
        Log::info('discoverMeals - Returning ' . count($returnData) . ' meals');
        return $this->success($returnData);
    }

    function mealDetail($id){
        $meal = Meal::where('id',$id)->where('meal_by','admin')->first();
        if(is_null($meal))
            return $this->notFound('Invalid Id Given.');
        
        if(!is_null($meal->nutrient)){
            $meal->nutrient = json_decode($meal->nutrient);
        }
        
        if(is_null($meal->tags)) {
            $meal->tagNames = [];
        } else {
            $tagIds = json_decode($meal->tags, true);
            if (is_array($tagIds) && !empty($tagIds)) {
                $meal->tagNames = Tag::whereIn('id', $tagIds)->pluck('name')->toArray();
            } else {
                $meal->tagNames = [];
            }
        }
        
        return $this->success($meal);
    }

    function deleteMeals(Request $request){
        $validate = Validator::make($request->all(),[
            'ids' => 'required|array',
        ]);
        if($validate->fails())
            return $this->validationError($validate);
        Meal::destroy($request->ids);
        return response()->json([
            'status' => true,
            'message' => 'Meals deleted'
        ]);
    }

    function createUserMeal(Request $request){
        $validate = Validator::make($request->all(),[
            'name' => 'required|string',
            'serving_size' => 'required|string',
            'no_of_servings' => 'required|numeric',
            'meal_type' => 'required|in:breakfast,lunch,dinner,snacks',
            'image' => 'required|mimes:jpg,png,jpeg|max:5120',
            'calories' => 'required|numeric',
            'carbs' => 'required|numeric',
            'fats' => 'required|numeric',
            'proteins' => 'required|numeric',
            'fibers' => 'required|numeric',
        ]);
        if($validate->fails())
            return $this->validationError($validate);
        $newId = Meal::orderBy('id','desc')->pluck('id')->first()+1;
        $meal = new Meal();
        if($request->has('image') && $request->image!==null){
            $url = $newId."_user_meal_".time().'_'.uniqid().'.'.request()->image->getClientOriginalExtension();
            $request->image->storeAs('meals', $url, 'fwd_media');
            $meal->file = $url;
            $meal->file_type = 'image';
        }
        $meal->user_id  = Auth::id();
        $meal->name = $request->name;
        $meal->meal_by = 'user';
        $meal->serving_size = $request->serving_size;
        $meal->no_of_servings = $request->no_of_servings;
        $meal->meal_type = 'auto';
        $meal->suitable_for = $request->meal_type;
        $meal->calories_per_serving = $request->calories;
        $meal->carbs_per_serving = $request->carbs;
        $meal->fat_per_serving = $request->fats;
        $meal->protein_per_serving = $request->proteins;
        $meal->fiber_per_serving = $request->fibers;
        $meal->directions = $request->directions;
        $meal->save();

        // Generate activity for admin to see in overview
        $userName = Auth::user()->name;
        $mealType = ucfirst($request->meal_type);
        $title = 'Added ' . $mealType;
        $content = $userName . ' added ' . $mealType;
        $category = 8; // Meals category
        $this->generateActivityForAdmin($title, $content, $category, Auth::id(), 'user_meal', $meal->id);

        return response()->json([
            'status' => true,
            'message' => 'Meal Created.',
            'refernce' => $meal->id
        ]);
    }

    function myPastMeals(Request $request){
        $validate = Validator::make($request->all(),[
            'type' => 'required|in:breakfast,lunch,dinner,snacks',
        ]);
        if($validate->fails())
            return $this->validationError($validate);
        $userMeals = Meal::where('user_id',Auth::id())->where('suitable_for',$request->type)->orderBy('created_at','desc')->get();
        foreach($userMeals as $meal)
        {
            $comments=DB::table('meal_comments')->where('meal_id',$meal->id)->where('user_id',$meal->user_id)->get();
            $meal->comments=$comments;
        }
        return response()->json([
            'status' => true,
            'data' => $userMeals
        ]);
    }
    
    public function pasteComment(Request $request){
        $validate = Validator::make($request->all(),[
            'meal_id' => 'required',
        ]);
        if($validate->fails())
            return $this->validationError($validate);
        DB::table('meal_comments')->insert([
            'user_id' => Auth::id(),
            'meal_id' => $request->meal_id,
            'comment' =>$request->comment
        ]);
        return response()->json([
            'status' => true,
            'message' => 'Comment Added Successfully.'
        ],200);
    }

    function mealsHistory(Request $request){
        $validate = Validator::make($request->all(),[
            'month' => 'nullable|date|date_format:d-m-Y',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);
        if($validate->fails())
            return $this->validationError($validate);
        if(isset($request->start_date) && isset($request->end_date) && $request->start_date !== null && $request->end_date !== null){
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
        } elseif(isset($request->month) && $request->month !== null && $request->month !== ''){
            $startDate = Carbon::parse($request->month)->startOfMonth();
            $endDate = Carbon::parse($request->month)->endOfMonth();
        } else {
            $startDate = Carbon::today()->startOfMonth();
            $endDate = Carbon::today()->endOfMonth();
        }
        $returnData = [];
        $schedules = ScheduledTask::where('user_id',Auth::id())->whereBetween('date_stamp',[$startDate,$endDate])->get(['id','date_stamp']);
        foreach ($schedules as $sch) {
            $tasks = STask::where('schedule_id',$sch->id)->where('type','meal')->get(['target','detail']);
            foreach ($tasks as $task) {
                $meal = Meal::where('id',$task->target)->first(['id','name','file','file_type','video_thumbnail']);
                if($meal){
                    $temp = new stdClass;
                    $temp->meal_date = Carbon::parse($sch->date_stamp)->format('d-m-Y');
                    $temp->meal_name = $meal->name;
                    $temp->meal_id = $meal->id;
                    $temp->meal_at = $task->detail;
                    $temp->meal_image = $meal->file_type==='image'?$meal->file:$meal->video_thumbnail;
                    array_push($returnData,$temp);
                }
            }
        }
        return response()->json([
            'status' => true,
            'data' => $returnData
        ]); 
    }
    
    function mealComments($id){
        $comments = Comment::where('type','meal')->where('target_id',$id)->get();
        foreach ($comments as $com) {
            $com->user_name = $com->fullName();
            $com->user_image = $com->userImage();
        }
        return response()->json([
            'status' => true,
            'data' => $comments
        ]);
    }

    function getMealCaloriesByDate(Request $request){
        $validate = Validator::make($request->all(),[
            'date' => 'required',
        ]);
        if($validate->fails())
            return $this->validationError($validate);
        $stamp = date('Y-m-d H:i:s', strtotime($request->date));
        $mealTypes = array('breakfast','lunch','dinner','snacks');
        $allTypeCalories = [];
        foreach($mealTypes as $mealType){
            $scheduledTaskIds = ScheduledTask::where('date_stamp',$stamp)->where('user_id', Auth::user()->id)->get('id');
            $mealIds = array();
            foreach($scheduledTaskIds as $item){
                $mealIds = STask::where('schedule_id',$item->id)->where('type','meal')->get('target');
            }
            $calories = 0;
            $image = '';
            foreach($mealIds as $item){
                $calories = $calories+Meal::where('id',$item->target)->where('suitable_for',$mealType)->sum('calories_per_serving');
                $image = Meal::where('id',$item->target)->where('suitable_for',$mealType)->where('file_type','image')->pluck('file')->first();
            }
            
            $start = Carbon::parse($stamp);
            $start = $start->startOfWeek();
            $lastWeek = array();
            for ($x = 0; $x < 7; $x++) {
                $scheduledTaskIds = ScheduledTask::where('date_stamp',$start)->where('user_id', Auth::user()->id)->get('id');
                $mealIds = array();
                foreach($scheduledTaskIds as $item){
                    $mealIds = STask::where('schedule_id',$item->id)->where('type','meal')->get('target');
                }
                $todayCalories = array();
                $todayCalories['calories'] = 0;
                $todayCalories['proteins'] = 0;
                $todayCalories['carbs'] = 0;
                $todayCalories['fats'] = 0;
                foreach($mealIds as $item){
                    $todayCalories['calories'] = $todayCalories['calories']+Meal::where('id',$item->target)->where('suitable_for',$mealType)->sum('calories_per_serving');
                    $todayCalories['proteins'] = $todayCalories['proteins']+Meal::where('id',$item->target)->where('suitable_for',$mealType)->sum('protein_per_serving');
                    $todayCalories['carbs'] = $todayCalories['carbs']+Meal::where('id',$item->target)->where('suitable_for',$mealType)->sum('carbs_per_serving');
                    $todayCalories['fats'] = $todayCalories['fats']+Meal::where('id',$item->target)->where('suitable_for',$mealType)->sum('fat_per_serving');
                }
                $todayCalories['date'] = $start->format('Y-m-d');
                $lastWeek[] = $todayCalories;
                $start = $start->addDay();
            }
            $indexData['meal'] = $mealType; 
            $indexData['today_calories'] = $calories;
            $indexData['meal_image'] = $image;
            $indexData['last_week'] = $lastWeek;
            $allTypeCalories[] = $indexData;
        }

        return response()->json([
            'status' => true,
            'data' => $allTypeCalories
        ]);
    }

    public function getCaloriesForGraph(Request $request){
        if(isset($request->date)){
            $formatdate = date('Y-m-d H:i:s', strtotime($request->date));
            $temp = Carbon::parse($formatdate);
            $temp = $temp->addMonths(1);

            $date1 = date('Y-m-d H:i:s', strtotime($request->date));
            $temp1 = Carbon::parse($date1);

            $date2 = date('Y-m-d H:i:s', strtotime($request->date));
            $temp2 = Carbon::parse($date2);
            $temp2 = $temp2->modify('-2 month');

            $date3 = date('Y-m-d H:i:s', strtotime($request->date));
            $temp3 = Carbon::parse($date3);
            $temp3 = $temp3->modify('-5 month');

            $date4 = date('Y-m-d H:i:s', strtotime($request->date));
            $temp4 = Carbon::parse($date4);
            $temp4 = $temp4->modify('-11 month');


            $month1 = ScheduledTask::whereBetween('date_stamp', [$temp1, $temp])->where('user_id', Auth::user()->id)->get(['id','date']);
            $monthData = [];
            foreach($month1 as $current){
                $mealIds = STask::where('schedule_id',$current->id)->where('type','meal')->get('target');
                $calories = 0;
                foreach($mealIds as $item){
                    $calories = $calories+Meal::where('id',$item->target)->sum('calories_per_serving');
                }
                $arr['calories'] = $calories;
                $arr['date'] = $current->date;
                $monthData[] = $arr;
            }
            $month3 = ScheduledTask::whereBetween('date_stamp', [$temp2, $temp1])->where('user_id', Auth::user()->id)->get(['id','date']);
            $month3Data = [];
            foreach($month3 as $current){
                $mealIds = STask::where('schedule_id',$current->id)->where('type','meal')->get('target');
                $calories = 0;
                foreach($mealIds as $item){
                    $calories = $calories+Meal::where('id',$item->target)->sum('calories_per_serving');
                }
                $arr['calories'] = $calories;
                $arr['date'] = $current->date;
                $month3Data[] = $arr;
            }
            $month6 = ScheduledTask::whereBetween('date_stamp', [$temp3, $temp2])->where('user_id', Auth::user()->id)->get(['id','date']);
            $month6Data = [];
            foreach($month6 as $current){
                $mealIds = STask::where('schedule_id',$current->id)->where('type','meal')->get('target');
                $calories = 0;
                foreach($mealIds as $item){
                    $calories = $calories+Meal::where('id',$item->target)->sum('calories_per_serving');
                }
                $arr['calories'] = $calories;
                $arr['date'] = $current->date;
                $month6Data[] = $arr;
            }
            $year = ScheduledTask::whereBetween('date_stamp', [$temp4, $temp3])->where('user_id', Auth::user()->id)->get(['id','date']);
            $yearData = [];
            foreach($year as $current){
                $mealIds = STask::where('schedule_id',$current->id)->where('type','meal')->get('target');
                $calories = 0;
                foreach($mealIds as $item){
                    $calories = $calories+Meal::where('id',$item->target)->sum('calories_per_serving');
                }
                $arr['calories'] = $calories;
                $arr['date'] = $current->date;
                $yearData[] = $arr;
            }

            $dummyData['calories'] = 0;
            $dummyData['date'] = Carbon::now();

            if(sizeof($monthData) == 0){
                $tempDate = Carbon::today()->subMonths();
                $dummyData['date'] = $tempDate->format('d-m-Y');
                $monthData[] = $dummyData;
            }

            if(sizeof($month3Data) == 0){
                $tempDate = Carbon::today()->subMonths(3);
                $dummyData['date'] = $tempDate->format('d-m-Y');
                $month3Data[] = $dummyData;
            }

            if(sizeof($month6Data) == 0){
                $tempDate = Carbon::today()->subMonths(6);
                $dummyData['date'] = $tempDate->format('d-m-Y');
                $month6Data[] = $dummyData;
            }
            
            if(sizeof($yearData) == 0){
                $tempDate = Carbon::today()->subYears();
                $dummyData['date'] = $tempDate->format('d-m-Y');
                $yearData[] = $dummyData;
            }

            $data['calories_month1'] = $monthData;
            $data['calories_month3'] = $month3Data;
            $data['calories_month6'] = $month6Data;
            $data['calories_year'] = $yearData;
            return response()->json([
                'status' => true,
                'data' => $data,
            ]);
        }

        $month1 = ScheduledTask::whereBetween('date_stamp', [Carbon::now()->subMonths(), Carbon::now()])->where('user_id', Auth::user()->id)->get(['id','date']);
        $monthData = [];
        foreach($month1 as $current){
            $mealIds = STask::where('schedule_id',$current->id)->where('type','meal')->get('target');
            $calories = 0;
            foreach($mealIds as $item){
                $calories = $calories+Meal::where('id',$item->target)->sum('calories_per_serving');
            }
            $arr['calories'] = $calories;
            $arr['date'] = $current->date;
            $monthData[] = $arr;
        }
        $month3 = ScheduledTask::whereBetween('date_stamp', [Carbon::now()->subMonths(3), Carbon::now()->subMonths()])->where('user_id', Auth::user()->id)->get(['id','date']);
        $month3Data = [];
        foreach($month3 as $current){
            $mealIds = STask::where('schedule_id',$current->id)->where('type','meal')->get('target');
            $calories = 0;
            foreach($mealIds as $item){
                $calories = $calories+Meal::where('id',$item->target)->sum('calories_per_serving');
            }
            $arr['calories'] = $calories;
            $arr['date'] = $current->date;
            $month3Data[] = $arr;
        }
        $month6 = ScheduledTask::whereBetween('date_stamp', [Carbon::now()->subMonths(6), Carbon::now()->subMonths(3)])->where('user_id', Auth::user()->id)->get(['id','date']);
        $month6Data = [];
        foreach($month6 as $current){
            $mealIds = STask::where('schedule_id',$current->id)->where('type','meal')->get('target');
            $calories = 0;
            foreach($mealIds as $item){
                $calories = $calories+Meal::where('id',$item->target)->sum('calories_per_serving');
            }
            $arr['calories'] = $calories;
            $arr['date'] = $current->date;
            $month6Data[] = $arr;
        }
        $year = ScheduledTask::whereBetween('date_stamp', [Carbon::now()->subYears(), Carbon::now()->subMonths(6)])->where('user_id', Auth::user()->id)->get(['id','date']);
        $yearData = [];
        foreach($year as $current){
            $mealIds = STask::where('schedule_id',$current->id)->where('type','meal')->get('target');
            $calories = 0;
            foreach($mealIds as $item){
                $calories = $calories+Meal::where('id',$item->target)->sum('calories_per_serving');
            }
            $arr['calories'] = $calories;
            $arr['date'] = $current->date;
            $yearData[] = $arr;
        }
        $data['calories_month1'] = $monthData;
        $data['calories_month3'] = $month3Data;
        $data['calories_month6'] = $month6Data;
        $data['calories_year'] = $yearData;
        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }

    function mealDetails($id){
        $meal = Meal::find($id);
        if(is_null($meal))
        return response()->json([
            'status' => false,
            'message' => 'No Meal Found',
        ]);
        $userLang = $this->currentUserLanguage();
        if ($meal->language !== $userLang) {
            $translations = $meal->locale_translations;
            if (is_array($translations) && isset($translations[$userLang]) && is_array($translations[$userLang])) {
                $p = $translations[$userLang];
                foreach (ContentLocaleResolver::MEAL_FIELDS as $f) {
                    if (! empty($p[$f]) && is_string($p[$f])) {
                        $meal->{$f} = $p[$f];
                    }
                }
            }
        }
        return response()->json([
            'status' => true,
            'data' => $meal,
        ]);
    }

    public function getMonthMeals(Request $request){
        $validate = Validator::make($request->all(),[
            'date' => 'required',
        ]);
        if($validate->fails())
            return $this->validationError($validate);
        $startDate = Carbon::parse($request->date)->startOfMonth();
        $endDate = Carbon::parse($request->date)->endOfMonth();
        $data = [];
        $tasks = ScheduledTask::where('user_id',Auth::id())->whereBetween('date_stamp', [$startDate, $endDate])->pluck('id')->toArray();
        $sTasks = STask::whereIn('schedule_id',$tasks)->where('type','meal')->pluck('target');
        foreach($sTasks as $task){
            $data[] = Meal::where('id',$task)->first();
        }
        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }

    public function convertMealsToArabic(){
        try {
            $meals = Meal::get();
            foreach($meals as $item){
                if($item->language == 'en'){
                    $meal = new Meal();
                    $meal->user_id = $item->user_id;
                    $meal->name = $this->getTranslatedText($item->name, 'ar');
                    $meal->language = 'ar';
                    $meal->prep_time = $item->prep_time;
                    $meal->cook_time = $item->cook_time;
                    $meal->suitable_for = $item->suitable_for;
                    $meal->tags = $item->tags;
                    $meal->contains = $item->contains;
                    $meal->file = basename($item->file);
                    $meal->video_thumbnail = basename($item->video_thumbnail);
                    $meal->file_type = $item->file_type;
                    $meal->no_of_servings = $item->no_of_servings;
                    $meal->calories_per_serving = $item->calories_per_serving;
                    $meal->protein_per_serving = $item->protein_per_serving;
                    $meal->carbs_per_serving = $item->carbs_per_serving;
                    $meal->fat_per_serving = $item->fat_per_serving;
                    $meal->fiber_per_serving = $item->fiber_per_serving;
                    $meal->ingredients = $item->ingredients;
                    $meal->directions = $item->directions;
                    $meal->nutrient = $item->nutrient;
                    $meal->meal_type = $item->meal_type;
                    $meal->save();
                }

            }
            return true;
        } catch(Exception $er){
            Log::info("Error: ".$er->getMessage());
            return $inputText; 
        }
    }

    function getTranslatedText(string $inputText, string $targetLanguage) {
        return app(GoogleTranslateService::class)->translate($inputText, 'en', $targetLanguage);
    }
}
