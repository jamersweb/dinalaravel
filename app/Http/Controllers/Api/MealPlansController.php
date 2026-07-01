<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meal;
use App\Models\MealDay;
use App\Models\MealPlan;
use App\Models\MealPlanWeek;
use App\Models\MealWeek;
use App\Models\NutritionCompilance;
// use App\Models\ScheduledTask;
// use App\Models\STask;
use App\Models\Tag;
use App\Models\UMPTracking;
use App\Models\UserMealPlan;
use App\Traits\ActivitiesTrait;
use App\Traits\JsonSanitizeTrait;
use App\Traits\NotificationsTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MealPlansController extends Controller
{
    use NotificationsTrait, ActivitiesTrait, JsonSanitizeTrait;

    public function createMealDay(Request $request){
        $validate = Validator::make($request->all(),[
            'name' => 'required',
            'description' => 'string|nullable',
            'tags' => 'array',
            'breakfast' => 'integer|nullable',
            'dinner' => 'integer|nullable',
            'lunch' => 'integer|nullable',
            'snacks' => 'integer|nullable',
            'drinks' => 'integer|nullable',
            'language' => 'required|in:en,ar'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $image = null;
        if(isset($request->breakfast) && !is_null($request->breakfast)){
            $meal = DB::table('meals')->where('id',$request->breakfast)->first();
            if($meal->file_type == 'image'){
                $image = $meal->file;
            }else{
                $image = $meal->video_thumbnail;
            }
        }
        elseif(isset($request->dinner) && !is_null($request->dinner)){
            $meal = DB::table('meals')->where('id',$request->dinner)->first();
            if($meal->file_type == 'image'){
                $image = $meal->file;
            }else{
                $image = $meal->video_thumbnail;
            }
        }
        elseif(isset($request->lunch) && !is_null($request->lunch)){
            $meal = DB::table('meals')->where('id',$request->lunch)->first();
            if($meal->file_type == 'image'){
                $image = $meal->file;
            }else{
                $image = $meal->video_thumbnail;
            }
        }
        elseif(isset($request->snacks) && !is_null($request->snacks)){
            $meal = DB::table('meals')->where('id',$request->snacks)->first();
            if($meal->file_type == 'image'){
                $image = $meal->file;
            }else{
                $image = $meal->video_thumbnail;
            }
        }
        elseif(isset($request->drinks) && !is_null($request->drinks)){
            $meal = DB::table('meals')->where('id',$request->drinks)->first();
            if($meal->file_type == 'image'){
                $image = $meal->file;
            }else{
                $image = $meal->video_thumbnail;
            }
        }

        $mealDay = new MealDay();
        $mealDay->name = $request->name;
        $mealDay->description = $this->normalizeOptionalText($request->description);
        $mealDay->language = $request->language;
        if(isset($request->tags))
        $mealDay->tags = json_encode($request->tags);
        $mealDay->breakfast = $request->breakfast;
        $mealDay->dinner = $request->dinner;
        $mealDay->lunch = $request->lunch;
        $mealDay->snacks = $request->snacks;
        $mealDay->drinks = $request->drinks;
        $mealDay->image = $image;
        $mealDay->save();

        return response()->json([
            'status' => true,
            'message' => 'Meal Day Added Successfully.'
        ]);
    }

    public function getMealDays(Request $request){
        if($request->has('lang')){
            if(($request->lang==='en' || $request->lang==='ar'))
            $mealDays = MealDay::where('status',1)->where('language',$request->lang)->orderBy('id','desc')->get(['id','name','image','tags','language']);
            else
            return response()->json([
                'status' => false,
                'data' => 'Invalid Language Selection'
            ]);
        } else 
        $mealDays = MealDay::where('status',1)->orderBy('id','desc')->get(['id','name','image','tags','language']);
        foreach ($mealDays as $meal) {
            if(is_null($meal->tags))
            $meal->tagNames = [];
            else{
                $meal->tags = json_decode($meal->tags);
                $meal->tagNames = Tag::whereIn('id',$meal->tags)->pluck('name')->toArray();
            }
        }
        return response()->json([
            'status' => true,
            'data' => $mealDays
        ]);
    }

    public function deleteMealDay(Request $request){
        $validate = Validator::make($request->all(),[
            'ids' => 'required|array',
            'delete_type' => 'required|in:days,weeks,plan'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        if($request->delete_type == 'days'){
            MealDay::whereIn('id',$request->ids)->update(['status'=>0]);
        }elseif($request->delete_type == 'weeks'){
            MealWeek::whereIn('id',$request->ids)->update(['status'=>0]);
        }elseif($request->delete_type == 'plan'){
            MealPlan::whereIn('id',$request->ids)->update(['status'=>0]);
        }
        
        return response()->json([
            'status' => true,
            'message' => 'Meal Day Deleted'
        ]);
    }
    public function getMealDayDetail($id){
        $mealDay = MealDay::where('id',$id)->first();
        if($mealDay){
            if(is_null($mealDay->tags))
            $mealDay->tagNames = [];
            else{
                $mealDay->tags = json_decode($mealDay->tags);
                $mealDay->tagNames = Tag::whereIn('id',$mealDay->tags)->pluck('name')->toArray();
            }
            $mealDay['breakfast_detail'] = Meal::whereId($mealDay->breakfast)->first(['id','name','file','file_type','video_thumbnail']);
            $mealDay['dinner_detail'] = Meal::whereId($mealDay->dinner)->first(['id','name','file','file_type','video_thumbnail']);
            $mealDay['lunch_detail'] = Meal::whereId($mealDay->lunch)->first(['id','name','file','file_type','video_thumbnail']);
            $mealDay['snacks_detail'] = Meal::whereId($mealDay->snacks)->first(['id','name','file','file_type','video_thumbnail']);
            $mealDay['drinks_detail'] = Meal::whereId($mealDay->drinks)->first(['id','name','file','file_type','video_thumbnail']);
            return response()->json([
                'status' => true,
                'data' => $mealDay
            ]);
        }else{
            return response()->json([
                'status' => false,
                'message' => 'Meal Day Not Found'
            ]);
        }
    }
    public function createMealWeek(Request $request){
        $validate = Validator::make($request->all(),[
            'name' => 'required',
            'description' => 'string|nullable',
            'tags' => 'array',
            'meal_day1' => 'integer|nullable',
            'meal_day2' => 'integer|nullable',
            'meal_day3' => 'integer|nullable',
            'meal_day4' => 'integer|nullable',
            'meal_day5' => 'integer|nullable',
            'meal_day6' => 'integer|nullable',
            'meal_day7' => 'integer|nullable',
            'language' => 'required|in:en,ar'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);

        $image = null;
        if(isset($request->meal_day1) && !is_null($request->meal_day1)){
            $image = DB::table('meal_days')->where('id',$request->meal_day1)->pluck('image')->first();
        }
        elseif(isset($request->meal_day2) && !is_null($request->meal_day2)){
            $image = DB::table('meal_days')->where('id',$request->meal_day2)->pluck('image')->first();
        }
        elseif(isset($request->meal_day3) && !is_null($request->meal_day3)){
            $image = DB::table('meal_days')->where('id',$request->meal_day3)->pluck('image')->first();
        }
        elseif(isset($request->meal_day4) && !is_null($request->meal_day4)){
            $image = DB::table('meal_days')->where('id',$request->meal_day4)->pluck('image')->first();
        }
        elseif(isset($request->meal_day5) && !is_null($request->meal_day5)){
            $image = DB::table('meal_days')->where('id',$request->meal_day5)->pluck('image')->first();
        }
        elseif(isset($request->meal_day6) && !is_null($request->meal_day6)){
            $image = DB::table('meal_days')->where('id',$request->meal_day6)->pluck('image')->first();
        }
        elseif(isset($request->meal_day7) && !is_null($request->meal_day7)){
            $image = DB::table('meal_days')->where('id',$request->meal_day7)->pluck('image')->first();
        }

        $mealWeek = new MealWeek();
        $mealWeek->name = $request->name;
        $mealWeek->image = $image;
        $mealWeek->description = $this->normalizeOptionalText($request->description);
        $mealWeek->language = $request->language;
        if(isset($request->tags))
        $mealWeek->tags = json_encode($request->tags);
        $mealWeek->meal_day1 = $request->meal_day1;
        $mealWeek->meal_day2 = $request->meal_day2;
        $mealWeek->meal_day3 = $request->meal_day3;
        $mealWeek->meal_day4 = $request->meal_day4;
        $mealWeek->meal_day5 = $request->meal_day5;
        $mealWeek->meal_day6 = $request->meal_day6;
        $mealWeek->meal_day7 = $request->meal_day7;
        $mealWeek->save();

        return response()->json([
            'status' => true,
            'message' => 'Meal Week Added Successfully.'
        ]);
    }

    public function getMealWeeks(Request $request){
        if($request->has('lang')){
            if(($request->lang==='en' || $request->lang==='ar'))
            $mealWeeks = MealWeek::where('status',1)->where('language',$request->lang)->orderBy('id','desc')->get(['id','name','image','tags','language']);
            else
            return response()->json([
                'status' => false,
                'data' => 'Invalid Language Selection'
            ]);
        } else 
        $mealWeeks = MealWeek::where('status',1)->orderBy('id','desc')->get(['id','name','image','tags','language']);
        foreach ($mealWeeks as $meal) {
            if(is_null($meal->tags))
            $meal->tagNames = [];
            else{
                $meal->tags = json_decode($meal->tags);
                $meal->tagNames = Tag::whereIn('id',$meal->tags)->pluck('name')->toArray();
            }
        }
        return response()->json([
            'status' => true,
            'data' => $mealWeeks
        ]);
    }

    public function getMealWeekDetail($id){
        $mealWeek = MealWeek::where('id',$id)->first();
        if($mealWeek){
            if(is_null($mealWeek->tags))
            $mealWeek->tagNames = [];
            else{
                $mealWeek->tags = json_decode($mealWeek->tags);
                $mealWeek->tagNames = Tag::whereIn('id',$mealWeek->tags)->pluck('name')->toArray();
            }
            $mealWeek['meal_day1_detail'] = MealDay::find($mealWeek->meal_day1);
            $mealWeek['meal_day2_detail'] = MealDay::find($mealWeek->meal_day2);
            $mealWeek['meal_day3_detail'] = MealDay::find($mealWeek->meal_day3);
            $mealWeek['meal_day4_detail'] = MealDay::find($mealWeek->meal_day4);
            $mealWeek['meal_day5_detail'] = MealDay::find($mealWeek->meal_day5);
            $mealWeek['meal_day6_detail'] = MealDay::find($mealWeek->meal_day6);
            $mealWeek['meal_day7_detail'] = MealDay::find($mealWeek->meal_day7);
            return response()->json([
                'status' => true,
                'data' => $mealWeek
            ]);
        }else{
            return response()->json([
                'status' => false,
                'message' => 'Meal Week Not Found'
            ]);
        }
    }

    public function createMealPlan(Request $request){
        $validate = Validator::make($request->all(),[
            'name' => 'required',
            'description' => 'string|nullable',
            'tags' => 'nullable|string',
            'duration' => 'integer',
            'week_data' => 'nullable|string',
            'attatchment' => 'nullable|mimes:pdf,PDF',
            'attatchment2' => 'nullable|mimes:pdf,PDF',
            'attatchment3' => 'nullable|mimes:pdf,PDF',
            'image' => 'image|mimes:jpg,jpeg,png,webp,gif',
            'language' => 'required|in:en,ar'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $weekArray = json_decode($request->week_data ?? '[]');
        $weekArray = is_array($weekArray) ? $weekArray : [];
        $newId = (MealPlan::max('id') ?? 0) + 1;
        $file = null;
        $fileName = null;
        if($request->hasFile('attatchment')){
            $file = $newId."_1_meal_plan_file_".time().'.'.$request->file('attatchment')->getClientOriginalExtension();
            $request->file('attatchment')->storeAs('meals', $file, 'fwd_media');
            $fileName = $request->file('attatchment')->getClientOriginalName();
        }
        $file2 = null;
        $file3 = null;
        if($request->hasFile('attatchment2')){
            $file2 = $newId."_2_meal_plan_file_".time().'.'.$request->file('attatchment2')->getClientOriginalExtension();
            $request->file('attatchment2')->storeAs('meals', $file2, 'fwd_media');
        }
        if($request->hasFile('attatchment3')){
            $file3 = $newId."_3_meal_plan_file_".time().'.'.$request->file('attatchment3')->getClientOriginalExtension();
            $request->file('attatchment3')->storeAs('meals', $file3, 'fwd_media');
        }

        $image = null;
        foreach($weekArray as $weekId){
            $image = DB::table('meal_weeks')->where('id',$weekId)->pluck('image')->first();
            if(!is_null($image))
            break;
        }
        if($request->hasFile('image')){
            $image = $newId."_meal_plan_thumbnail_".time().'.'.$request->file('image')->getClientOriginalExtension();
            $request->file('image')->storeAs('meals', $image, 'fwd_media');
        }
        $mealPlan = new MealPlan();
        $mealPlan->name = $request->name;
        $mealPlan->image = $image;
        $mealPlan->description = $this->normalizeOptionalText($request->description);
        $mealPlan->language = $request->language;
        $mealPlan->duration = $request->duration;
        $mealPlan->attatchment = $file;
        $mealPlan->attatchment_name = $fileName;
        $mealPlan->attatchment2 = $file2;
        $mealPlan->attatchment2_name = is_null($file2)?null:$request->file('attatchment2')->getClientOriginalName();
        $mealPlan->attatchment3 = $file3;
        $mealPlan->attatchment3_name = is_null($file3)?null:$request->file('attatchment3')->getClientOriginalName();
        $mealPlan->tags = $request->tags ?? json_encode([]);
        $mealPlan->save();

        foreach($weekArray as $weekId){
            $mealPlanWeek = new MealPlanWeek();
            $mealPlanWeek->meal_plan_id = $mealPlan->id;
            $mealPlanWeek->meal_week_id = $weekId;
            $mealPlanWeek->save();
        }

        return response()->json([
            'status' => true,
            'message' => 'Meal Plan Added Successfully.'
        ]);
    }
    public function getMealPlans(){
        // IMPORTANT: For CMS, show all meal plans. For API (mobile), filter by user's language
        // Check if this is a CMS request (admin user) or API request (regular user)
        $userId = Auth::id();
        $user = Auth::user();
        $isAdmin = $user && $user->role == 2; // Admin role
        
        if ($isAdmin) {
            // CMS: Show all meal plans without language filter
            $mealPlans = MealPlan::where('status', 1)->get();
        } else {
            // API/Mobile: Filter by user's selected language
            $userLanguage = $this->userSelecetdLanguage($userId);
            
            // If user has no language setting, default to 'en' (English)
            if (is_null($userLanguage) || empty($userLanguage)) {
                $userLanguage = 'en';
            }
            
            $mealPlans = MealPlan::where('status', 1)
                ->where('language', $userLanguage)
                ->get();
        }
        
        foreach ($mealPlans as $meal) {
            if(is_null($meal->tags))
            $meal->tagNames = [];
            else{
                $meal->tags = json_decode($meal->tags);
                $meal->tagNames = Tag::whereIn('id',$meal->tags)->pluck('name')->toArray();
            }
            // Get attachment links (file_view, file_downoad, etc.)
            $temp = $meal->links();
            $meal->file_view = $temp['file_view'];
            $meal->file_downoad = $temp['file_downoad'];
            $meal->file_view2 = $temp['file_view2'];
            $meal->file_downoad2 = $temp['file_downoad2'];
            $meal->file_view3 = $temp['file_view3'];
            $meal->file_downoad3 = $temp['file_downoad3'];
            // Add calories field (default to 150 if not in database, can be calculated later)
            $meal->calories = 150; // TODO: Calculate from meals or add to database
            // Sanitize text fields for valid JSON (description, attatchment_name, etc.)
            $meal->description = $this->sanitizeForJson($this->normalizeOptionalText($meal->description));
            $meal->attatchment_name = $this->sanitizeForJson($meal->attatchment_name);
            $meal->attatchment2_name = $this->sanitizeForJson($meal->attatchment2_name ?? null);
            $meal->attatchment3_name = $this->sanitizeForJson($meal->attatchment3_name ?? null);
            $meal->name = $this->sanitizeForJson($meal->name);
        }
        return response()->json([
            'status' => true,
            'data' => $mealPlans
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function allMealPlans(){
        $alreadySubs = UserMealPlan::where('user_id',Auth::id())->pluck('meal_plan_id')->toArray();
        $mealPlans = MealPlan::where('status',1)->whereNotIn('id',$alreadySubs)->where('language',$this->userSelecetdLanguage(Auth::id()))->get();
        
        // Get total meals count for description
        $totalMealsCount = Meal::where('status', 1)->count();
        
        foreach ($mealPlans as $meal) {
            if(is_null($meal->tags))
            $meal->tagNames = [];
            else{
                $meal->tags = json_decode($meal->tags);
                $meal->tagNames = Tag::whereIn('id',$meal->tags)->pluck('name')->toArray();
            }
            $temp = $meal->links();     //get attacthmengt links
            $meal->file_view = $temp['file_view'];
            $meal->file_downoad = $temp['file_downoad'];
            $meal->file_view2 = $temp['file_view2'];
            $meal->file_downoad2 = $temp['file_downoad2'];
            $meal->file_view3 = $temp['file_view3'];
            $meal->file_downoad3 = $temp['file_downoad3'];
            
            // IMPORTANT: Add description about accessing all meals
            // If description exists, append the note about all meals access
            // Otherwise, set a default message
            $meal->description = $this->normalizeOptionalText($meal->description);
            if(empty($meal->description)) {
                $meal->description = "By subscribing to this meal plan, you'll have access to hundreds of meal recipes for all health backgrounds. You can view meals by day, by week, or the entire plan, plus browse all available meals in our meal library.";
            } else {
                $meal->description = $meal->description . " Plus, you'll have access to hundreds of meal recipes for all health backgrounds - view them by day, by week, or browse the entire meal library.";
            }
        }
        
        return response()->json([
            'status' => true,
            'data' => $mealPlans,
            'all_meals_access_note' => "By subscribing to any meal plan, you'll have access to browse and view all available meals in our meal library, regardless of which plan you choose.",
            'total_meals_available' => $totalMealsCount
        ]);
    }

    public function getMealPlanDetail($id){
        $mealPlan = MealPlan::where('id',$id)->first();
        if($mealPlan){
            if(is_null($mealPlan->tags))    //get tags
            $mealPlan->tagNames = [];
            else{
                $mealPlan->tags = json_decode($mealPlan->tags);
                $mealPlan->tagNames = Tag::whereIn('id',$mealPlan->tags)->pluck('name')->toArray();
            }   //get tags end
            $temp = $mealPlan->links();     //get attacthmengt links
            $mealPlan->file_view = $temp['file_view'];
            $mealPlan->file_downoad = $temp['file_downoad'];
            $mealPlan->file_view2 = $temp['file_view2'];
            $mealPlan->file_downoad2 = $temp['file_downoad2'];
            $mealPlan->file_view3 = $temp['file_view3'];
            $mealPlan->file_downoad3 = $temp['file_downoad3'];
            //get attacthmengt links end
            
            // IMPORTANT: Add description about accessing all meals
            $mealPlan->description = $this->normalizeOptionalText($mealPlan->description);
            if(empty($mealPlan->description)) {
                $mealPlan->description = "By subscribing to this meal plan, you'll have access to hundreds of meal recipes for all health backgrounds. You can view meals by day, by week, or the entire plan, plus browse all available meals in our meal library.";
            } else {
                $mealPlan->description = $mealPlan->description . " Plus, you'll have access to hundreds of meal recipes for all health backgrounds - view them by day, by week, or browse the entire meal library.";
            }
            
            $planWeeks = MealPlanWeek::where('meal_plan_id',$id)->orderBy('id','asc')->get();
            $weekData = [];
            foreach($planWeeks as $week){
                $weekDetail = MealWeek::where('id',$week->meal_week_id)->first();
                array_push($weekData,$weekDetail);
            }
            $mealPlan['week_detail'] = $weekData;
            return response()->json([
                'status' => true,
                'data' => $mealPlan
            ]);
        }else{
            return response()->json([
                'status' => false,
                'message' => 'Meal Plan Not Found'
            ]);
        }
    }

    function subscribePlan($id){
        $mealPlan = MealPlan::where('id',$id)->where('status',1)->first();
        if(is_null($mealPlan))
        return response()->json([
            'status' => false,
            'message' => 'Meal Plan Not Found'
        ]);

        $userId = Auth::id();
        
        // BUSINESS RULE: Consultation form must be completed before subscribing to meal plans
        $consultation = \App\Models\ConsultationForm::where('user_id', $userId)
            ->whereNotNull('completed_at')
            ->first();
        if (!$consultation) {
            $hasAnswers = \App\Models\UserAnswer::where('user_id', $userId)->count() > 0;
            if (!$hasAnswers) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please complete the consultation form before subscribing to a meal plan.',
                    'consultation_required' => true
                ], 403);
            }
        }
        
        // BUSINESS RULE: ENFORCE - One meal plan at a time rule
        $activeMealPlan = UserMealPlan::where('user_id',$userId)
            ->where('status','active')
            ->first();
            
        if($activeMealPlan) {
            // Check if trying to subscribe to same plan
            if($activeMealPlan->meal_plan_id == $id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Already Subscribed to this Meal Plan.'
                ]);
            }
            // Active meal plan exists - cannot subscribe to another
            return response()->json([
                'status' => false,
                'message' => "You can only follow ONE meal plan at a time. Please complete or switch your current meal plan first.",
                'current_meal_plan_id' => $activeMealPlan->meal_plan_id,
            ]);
        }
        
        // Old check for basic subscription (keep for backward compatibility)
        if(!$this->userFullAccess($userId)){
            $existsAlready = UserMealPlan::where('user_id',$userId)->where('status','active')->count();
            if($existsAlready>0)
            return response()->json([
                'status' => false,
                'message' => 'Cannot subscribe more than 1 meal plan for basic subscription.'
            ]);
        };

        if(UserMealPlan::where('user_id',$userId)->where('meal_plan_id',$id)->exists())
        return response()->json([
            'status' => false,
            'message' => 'This Meal Plan is Subscribed Already.'
        ]);

        $usrPlan = new UserMealPlan();
        $usrPlan->user_id = $userId;
        $usrPlan->meal_plan_id = $id;
        $usrPlan->subscribe_date = Carbon::today();
        $usrPlan->status = 'active';
        $usrPlan->save();

        // $mealWeekIds = MealPlanWeek::where('meal_plan_id',$id)->orderBy('id','asc')->pluck('meal_week_id')->toArray();
        // $weekNo = 0;    // one less for correct date calculation, actually its 1
        // foreach ($mealWeekIds as $weekId) {
        //     $mealWeek = MealWeek::where('id',$weekId)->first(['meal_day1','meal_day2','meal_day4','meal_day5','meal_day6','meal_day7'])->toArray();
        //     $dayNo = 0;     // one less for correct date calculation, actually its 1
        //     foreach (array_values($mealWeek) as $mealDayId) {
        //         $date = Carbon::today()->addDays((7*$weekNo)+$dayNo);
        //         $mealDay = MealDay::where('id',$mealDayId)->first(['breakfast','lunch','dinner','snacks','drinks']);
        //         if(!is_null($mealDay)){
        //             if(!is_null($mealDay->breakfast))
        //             $this->geneerateTask($mealDay->breakfast,'breakfast',$userId,$date);
        //             if(!is_null($mealDay->lunch))
        //             $this->geneerateTask($mealDay->lunch,'lunch',$userId,$date);
        //             if(!is_null($mealDay->dinner))
        //             $this->geneerateTask($mealDay->dinner,'dinner',$userId,$date);
        //             if(!is_null($mealDay->snacks))
        //             $this->geneerateTask($mealDay->snacks,'snacks',$userId,$date);
        //             if(!is_null($mealDay->drinks))
        //             $this->geneerateTask($mealDay->drinks,'drinks',$userId,$date);
        //         }
        //         $dayNo++;
        //     }
        //     $weekNo++;
        // }

        $mealWeekIds = MealPlanWeek::where('meal_plan_id',$mealPlan->id)->pluck('meal_week_id')->toArray();
        foreach ($mealWeekIds as $mwId) {
            $mealWeek = MealWeek::find($mwId);
            $mealDays = [
                $mealWeek->meal_day1,
                $mealWeek->meal_day2,
                $mealWeek->meal_day3,
                $mealWeek->meal_day4,
                $mealWeek->meal_day5,
                $mealWeek->meal_day6,
                $mealWeek->meal_day7
            ];
            foreach ($mealDays as $md) {
                $mealDay = MealDay::where('id',$md)->first(['breakfast','lunch','dinner','snacks','drinks']);
                if(!is_null($mealDay)){
                    if(!is_null($mealDay->breakfast))
                    UMPTracking::create(['ump_id' => $usrPlan->id,'mw_id' => $mwId,'md_id' => $md,'meal_id' => $mealDay->breakfast,'meal_type' => 'breakfast']);
                    if(!is_null($mealDay->lunch))
                    UMPTracking::create(['ump_id' => $usrPlan->id,'mw_id' => $mwId,'md_id' => $md,'meal_id' => $mealDay->lunch,'meal_type' => 'lunch']);
                    if(!is_null($mealDay->dinner))
                    UMPTracking::create(['ump_id' => $usrPlan->id,'mw_id' => $mwId,'md_id' => $md,'meal_id' => $mealDay->dinner,'meal_type' => 'dinner']);
                    if(!is_null($mealDay->snacks))
                    UMPTracking::create(['ump_id' => $usrPlan->id,'mw_id' => $mwId,'md_id' => $md,'meal_id' => $mealDay->snacks,'meal_type' => 'snacks']);
                    if(!is_null($mealDay->drinks))
                    UMPTracking::create(['ump_id' => $usrPlan->id,'mw_id' => $mwId,'md_id' => $md,'meal_id' => $mealDay->drinks,'meal_type' => 'drinks']);
                }
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Meal Plan Subscribed.'
        ]);
    }

    function mealComplete(Request $request){
        $validate = Validator::make($request->all(),[
            'user_plan_id' => 'required|numeric',
            'week_id' => 'required|numeric',
            'day_id' => 'required|numeric',
            'meal_id' => 'required|numeric',
            'meal_type' => 'required|in:breakfast,lunch,dinner,snacks,drinks',
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $userPlan = UserMealPlan::where('user_id',Auth::id())->where('id',$request->user_plan_id)->first();
        if(is_null($userPlan))
        return response()->json([
            'status' => false,
            'message' => 'Invalid Meal Plan'
        ]);
        $track = UMPTracking::where('ump_id',$userPlan->id)->where('mw_id',$request->week_id)->where('md_id',$request->day_id)
        ->where('meal_id',$request->meal_id)->where('meal_type',$request->meal_type)->first();
        if(is_null($track))
        return response()->json([
            'status' => false,
            'message' => 'Invalid Details Provided'
        ]);
        if($track->status)
        return response()->json([
            'status' => false,
            'message' => 'Already Completed'
        ]);
        $track->status = 1;
        $track->update();
        $nutrition = Meal::where('id',$request->meal_id)->first(['id','calories_per_serving','carbs_per_serving','protein_per_serving','fat_per_serving']);
        if(!is_null($nutrition))
        NutritionCompilance::create([
            'user_id' => Auth::id(),
            'meal_id' => $nutrition->id,
            'meal_plan_id' => $userPlan->meal_plan_id,
            'calories' => $nutrition->calories_per_serving,
            'carbs' => $nutrition->carbs_per_serving,
            'proteins' => $nutrition->protein_per_serving,
            'fats' => $nutrition->fat_per_serving,
        ]);
        return response()->json([
            'status' => true,
            'message' => 'Meal Completed'
        ]);
    }

    // function geneerateTask($mealId,$mealFor,$user,$date){
    //     $schId = ScheduledTask::where('user_id',$user)->whereDate('date_stamp',$date)->pluck('id')->first();
    //     if(is_null($schId)){
    //         $sch = new ScheduledTask();
    //         $sch->user_id = $user;
    //         $sch->date_stamp = $date;
    //         $sch->date = $date->clone()->format('d-m-Y');
    //         $sch->save();
    //         $schId = $sch->id;
    //     }
    //     $task = new STask();
    //     $task->schedule_id = $schId;
    //     $task->type = 'meal_plan';
    //     $task->target = $mealId;
    //     $task->detail = $mealFor;
    //     $task->status = 0;
    //     $task->save();
    // }

    function myMealPlans(){
        $plans = UserMealPlan::where('user_id',Auth::id())->with(['planDetail' => function($q){
            $q->select('name','image','description','duration','id');
        }])->get(['id','meal_plan_id']);
        
        // Add description about accessing all meals to each plan
        foreach($plans as $plan) {
            if($plan->planDetail) {
                $plan->planDetail->description = $this->normalizeOptionalText($plan->planDetail->description);
                if(empty($plan->planDetail->description)) {
                    $plan->planDetail->description = "You have access to hundreds of meal recipes for all health backgrounds. View meals by day, by week, or browse the entire meal library.";
                } else {
                    $plan->planDetail->description = $plan->planDetail->description . " Plus, you have access to browse all available meals in our meal library.";
                }
            }
        }
        
        return response()->json([
            'status' => true,
            'data' => $plans
        ]);
    }

    /**
     * Preview meal plan by meal_plan.id (for shop/browse before subscribing).
     * Use meal-plan-preview/{id} when passing meal_plans.id.
     */
    function planPreview($id){
        $planDetail = MealPlan::where('id',$id)->where('status',1)->first();
        if(is_null($planDetail))
        return response()->json([
            'status' => false,
            'message' => 'Meal Plan Not Found.'
        ]);
        $planDetail->makeHidden(['tags','status','created_at','updated_at']);
        $planDetail->description = $this->normalizeOptionalText($planDetail->description);
        if(empty($planDetail->description)) {
            $planDetail->description = "By subscribing to this meal plan, you'll have access to hundreds of meal recipes for all health backgrounds. You can view meals by day, by week, or the entire plan, plus browse all available meals in our meal library.";
        } else {
            $planDetail->description = $planDetail->description . " Plus, you'll have access to browse all available meals in our meal library.";
        }
        $weekIds = MealPlanWeek::where('meal_plan_id',$planDetail->id)->orderBy('id','asc')->pluck('meal_week_id')->toArray();
        $planDetail->weeks = empty($weekIds) ? collect() : MealWeek::whereIn('id',$weekIds)->orderByRaw('FIELD(id, ' . implode(',', $weekIds) . ')')->get()->makeHidden(['tags','status','created_at','updated_at']);
        foreach ($planDetail->weeks as $mwk) {
            for($i = 1; $i <= 7; $i++) {
                $dayField = 'meal_day' . $i;
                $mwk->$dayField = $this->getMealDayWithDetails($mwk->$dayField);
            }
        }
        return response()->json([
            'status' => true,
            'data' => $planDetail
        ]);
    }

    /**
     * Detail for subscribed plan - expects user_meal_plans.id.
     * Use meal-plan-detail/{id} when passing user_meal_plans.id.
     * If id not found in UserMealPlan, delegates to planPreview (meal_plans.id).
     */
    function planDetail($id){
        $userPlan = UserMealPlan::where('id',$id)->where('user_id',Auth::id())->first(['meal_plan_id','status']);
        if(is_null($userPlan)) {
            // Fallback: id might be meal_plans.id (e.g. from Things to Do or shop)
            $mealPlan = MealPlan::where('id',$id)->where('status',1)->first();
            if($mealPlan) {
                return $this->planPreview($id);
            }
            return response()->json([
                'status' => false,
                'message' => 'Invalid ID.'
            ]);
        }
        if($userPlan->status!=='active')
        return response()->json([
            'status' => false,
            'message' => 'Meal Plan Completed.'
        ]);
        $planDetail = MealPlan::where('id',$userPlan->meal_plan_id)->first()->makeHidden(['tags','status','created_at','updated_at']);
        
        // IMPORTANT: Add description about accessing all meals
        $planDetail->description = $this->normalizeOptionalText($planDetail->description);
        if(empty($planDetail->description)) {
            $planDetail->description = "You have access to hundreds of meal recipes for all health backgrounds. View meals by day, by week, or browse the entire meal library.";
        } else {
            $planDetail->description = $planDetail->description . " Plus, you have access to browse all available meals in our meal library, regardless of this plan.";
        }
        
        $weekIds = MealPlanWeek::where('meal_plan_id',$planDetail->id)->orderBy('id','asc')->pluck('meal_week_id')->toArray();
        $planDetail->weeks = empty($weekIds) ? collect() : MealWeek::whereIn('id',$weekIds)->orderByRaw('FIELD(id, ' . implode(',', $weekIds) . ')')->get()->makeHidden(['tags','status','created_at','updated_at']);
        foreach ($planDetail->weeks as $mwk) {
            for($i = 1; $i <= 7; $i++) {
                $dayField = 'meal_day' . $i;
                $mwk->$dayField = $this->getMealDayWithDetails($mwk->$dayField);
            }
        }

        $weekStatuses = [];
        foreach ($planDetail->weeks as $pw) {
            $pw = $this->getWeekStatus($pw,$id);
            array_push($weekStatuses,$pw->completion);
        }
        $planDetail->completion = in_array(0,$weekStatuses)?0:1;
        return response()->json([
            'status' => true,
            'data' => $planDetail
        ]);
    }

    function getDayStatus($day,$umpId,$mwId){
        $mealStatuses = [];
        if(!is_null($day->breakfast)){
            $mealStatus = UMPTracking::where('ump_id',$umpId)->where('mw_id',$mwId)->where('md_id',$day->id)
            ->where('meal_id',$day->breakfast->id)->pluck('status')->first();
            $day->breakfast->completion = $mealStatus;
            array_push($mealStatuses,$mealStatus);
        }
        if(!is_null($day->lunch)){
            $mealStatus = UMPTracking::where('ump_id',$umpId)->where('mw_id',$mwId)->where('md_id',$day->id)
            ->where('meal_id',$day->lunch->id)->pluck('status')->first();
            $day->lunch->completion = $mealStatus;
            array_push($mealStatuses,$mealStatus);
        }
        if(!is_null($day->dinner)){
            $mealStatus = UMPTracking::where('ump_id',$umpId)->where('mw_id',$mwId)->where('md_id',$day->id)
            ->where('meal_id',$day->dinner->id)->pluck('status')->first();
            $day->dinner->completion = $mealStatus;
            array_push($mealStatuses,$mealStatus);
        }
        if(!is_null($day->snacks)){
            $mealStatus = UMPTracking::where('ump_id',$umpId)->where('mw_id',$mwId)->where('md_id',$day->id)
            ->where('meal_id',$day->snacks->id)->pluck('status')->first();
            $day->snacks->completion = $mealStatus;
            array_push($mealStatuses,$mealStatus);
        }
        if(!is_null($day->drinks)){
            $mealStatus = UMPTracking::where('ump_id',$umpId)->where('mw_id',$mwId)->where('md_id',$day->id)
            ->where('meal_id',$day->drinks->id)->pluck('status')->first();
            $day->drinks->completion = $mealStatus;
            array_push($mealStatuses,$mealStatus);
        }
        $day->completion = in_array(0,$mealStatuses)?0:1;
        return $day;
    }

    function getWeekStatus($week,$umpId){
        $dayStatuses = [];
        if(!is_null($week->meal_day1)){
            $week->meal_day1 = $this->getDayStatus($week->meal_day1,$umpId,$week->id);
            array_push($dayStatuses,$week->meal_day1->completion);
        }
        if(!is_null($week->meal_day2)){
            $week->meal_day2 = $this->getDayStatus($week->meal_day2,$umpId,$week->id);
            array_push($dayStatuses,$week->meal_day2->completion);
        }
        if(!is_null($week->meal_day3)){
            $week->meal_day3 = $this->getDayStatus($week->meal_day3,$umpId,$week->id);
            array_push($dayStatuses,$week->meal_day3->completion);
        }
        if(!is_null($week->meal_day4)){
            $week->meal_day4 = $this->getDayStatus($week->meal_day4,$umpId,$week->id);
            array_push($dayStatuses,$week->meal_day4->completion);
        }
        if(!is_null($week->meal_day5)){
            $week->meal_day5 = $this->getDayStatus($week->meal_day5,$umpId,$week->id);
            array_push($dayStatuses,$week->meal_day5->completion);
        }
        if(!is_null($week->meal_day6)){
            $week->meal_day6 = $this->getDayStatus($week->meal_day6,$umpId,$week->id);
            array_push($dayStatuses,$week->meal_day6->completion);
        }
        if(!is_null($week->meal_day7)){
            $week->meal_day7 = $this->getDayStatus($week->meal_day7,$umpId,$week->id);
            array_push($dayStatuses,$week->meal_day7->completion);
        }
        $week->completion = in_array(0,$dayStatuses)?0:1;
        return $week;
    }

    function getMealDetails($mealDay){
        $columns = ['name','id','file','file_type','video_thumbnail','no_of_servings','ingredients','directions',
        'calories_per_serving','protein_per_serving','carbs_per_serving','fat_per_serving','fiber_per_serving'];
        $mealDay->breakfast = Meal::where('id',$mealDay->breakfast)->first($columns);
        $mealDay->lunch = Meal::where('id',$mealDay->lunch)->first($columns);
        $mealDay->dinner = Meal::where('id',$mealDay->dinner)->first($columns);
        $mealDay->snacks = Meal::where('id',$mealDay->snacks)->first($columns);
        $mealDay->drinks = Meal::where('id',$mealDay->drinks)->first($columns);
        return $mealDay;
    }

    private function normalizeOptionalText($value){
        if(is_null($value))
        return null;

        $value = trim((string) $value);
        if($value === '' || strtolower($value) === 'null')
        return null;

        return $value;
    }

    function getMealDayWithDetails($mealDayId){
        if(is_null($mealDayId))
        return null;

        $mealDay = MealDay::where('id',$mealDayId)->first();
        if(is_null($mealDay))
        return null;

        $mealDay->makeHidden(['tags','status','created_at','updated_at']);
        return $this->getMealDetails($mealDay);
    }

    function updateMealDay(Request $request){
        $validate = Validator::make($request->all(),[
            'id' => 'required|numeric',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'tags' => 'nullable|array',
            'language' => 'required|in:en,ar',
            'breakfast' => 'numeric|nullable',
            'lunch' => 'numeric|nullable',
            'dinner' => 'numeric|nullable',
            'snacks' => 'numeric|nullable',
            'drinks' => 'numeric|nullable',
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $mealDay = MealDay::whereId($request->id)->whereStatus(1)->first();
        if(is_null($mealDay))
        return response()->json([
            'status' => false,
            'message' => 'Invalid Meal Day'
        ]);
        if(is_null($request->breakfast) && is_null($request->lunch) && is_null($request->dinner) && is_null($request->snacks) && (is_null($request->drinks)))
        return response()->json([
            'status' => false,
            'message' => 'Atleast 1 meal is required.'
        ]);
        $mealDay->name = $request->name;
        $mealDay->description = $this->normalizeOptionalText($request->description);
        $mealDay->tags = json_encode($request->tags ?? []);
        $mealDay->language = $request->language;
        $mealDay->breakfast = $request->breakfast;
        $mealDay->lunch = $request->lunch;
        $mealDay->snacks = $request->snacks;
        $mealDay->dinner = $request->dinner;
        $mealDay->drinks = $request->drinks;
        $mealDay->update();
        return response()->json([
            'status' => true,
            'message' => 'Meal Day Updated Successfully.'
        ]);
    }

    function updateMealWeek(Request $request){
        $vld = Validator::make($request->all(),[
            'id' => 'required|numeric',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'tags' => 'nullable|array',
            'language' => 'required|in:en,ar',
            'meal_day1' => 'nullable|numeric',
            'meal_day2' => 'nullable|numeric',
            'meal_day3' => 'nullable|numeric',
            'meal_day4' => 'nullable|numeric',
            'meal_day5' => 'nullable|numeric',
            'meal_day6' => 'nullable|numeric',
            'meal_day7' => 'nullable|numeric',
        ]);
        if($vld->fails())
        return response()->json([
            'status' => false,
            'message' => $vld->errors()->all()[0]
        ]);
        $mealWeek = MealWeek::whereId($request->id)->whereStatus(1)->first();
        if(is_null($mealWeek))
        return response()->json([
            'status' => false,
            'message' => 'Invalid Meal Week'
        ]);
        $mealWeek->name = $request->name;
        $mealWeek->description = $this->normalizeOptionalText($request->description);
        $mealWeek->tags = json_encode($request->tags ?? []);
        $mealWeek->language = $request->language;
        $mealWeek->meal_day1 = $request->meal_day1;
        $mealWeek->meal_day2 = $request->meal_day2;
        $mealWeek->meal_day3 = $request->meal_day3;
        $mealWeek->meal_day4 = $request->meal_day4;
        $mealWeek->meal_day5 = $request->meal_day5;
        $mealWeek->meal_day6 = $request->meal_day6;
        $mealWeek->meal_day7 = $request->meal_day7;
        $mealWeek->save();
        return response()->json([
            'status' => true,
            'message' => 'Meal Week Updated Successfully.'
        ]);
    }

    function updateMealPlan(Request $request){
        $vld = Validator::make($request->all(),[
            'id' => 'required|numeric',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'tags' => 'nullable|string',
            'language' => 'required|in:en,ar',
            'week_data' => 'nullable',
            'attatchment' => 'nullable|mimes:pdf,PDF',
            'attatchment2' => 'nullable|mimes:pdf,PDF',
            'attatchment3' => 'nullable|mimes:pdf,PDF',
            'image' => 'image|mimes:jpg,jpeg,png,webp,gif'
        ]);
        if($vld->fails())
        return response()->json([
            'status' => false,
            'message' => $vld->errors()->all()[0]
        ]);
        $mealPlan = MealPlan::whereId($request->id)->whereStatus(1)->first();
        if(is_null($mealPlan))
        return response()->json([
            'status' => false,
            'message' => 'Invalid Meal Plan'
        ]);
        $mealPlan->name = $request->name;
        $mealPlan->description = $this->normalizeOptionalText($request->description);
        $mealPlan->tags = $request->tags ?? json_encode([]);
        $mealPlan->language = $request->language;
        $mealPlan->duration = $request->duration;
        if($request->hasFile('image')){
            $fileName = $request->id."_meal_plan_thumbnail_".time().'.'.$request->file('image')->getClientOriginalExtension();
            $request->file('image')->storeAs('meals', $fileName, 'fwd_media');
            $mealPlan->image = $fileName;
        }
        if($request->hasFile('attatchment')){
            $fileName = $request->id."_1_meal_plan_file_".time().'.'.$request->file('attatchment')->getClientOriginalExtension();
            $request->file('attatchment')->storeAs('meals', $fileName, 'fwd_media');
            $mealPlan->attatchment = $fileName;
            $mealPlan->attatchment_name = $request->file('attatchment')->getClientOriginalName();
        }
        if($request->hasFile('attatchment2')){
            $fileName = $request->id."_2_meal_plan_file_".time().'.'.$request->file('attatchment2')->getClientOriginalExtension();
            $request->file('attatchment2')->storeAs('meals', $fileName, 'fwd_media');
            $mealPlan->attatchment2 = $fileName;
            $mealPlan->attatchment2_name = $request->file('attatchment2')->getClientOriginalName();
        }
        if($request->hasFile('attatchment3')){
            $fileName = $request->id."_3_meal_plan_file_".time().'.'.$request->file('attatchment3')->getClientOriginalExtension();
            $request->file('attatchment3')->storeAs('meals', $fileName, 'fwd_media');
            $mealPlan->attatchment3 = $fileName;
            $mealPlan->attatchment3_name = $request->file('attatchment3')->getClientOriginalName();
        }
        $mealPlan->save();
        MealPlanWeek::where('meal_plan_id',$request->id)->delete();
        $requestWeeks = json_decode($request->week_data ?? '[]');
        $requestWeeks = is_array($requestWeeks) ? $requestWeeks : [];
        foreach ($requestWeeks as $rw) {
            $newWeek = new MealPlanWeek();
            $newWeek->meal_plan_id = $request->id;
            $newWeek->meal_week_id = $rw->id;
            $newWeek->save();
        }
        return response()->json([
            'status' => true,
            'message' => 'Meal Plan Updated Successfully'
        ]);
    }

    /**
     * Switch from one meal plan to another
     */
    function switchMealPlan($id){
        $userId = Auth::id();
        $newMealPlan = MealPlan::where('id',$id)->where('status',1)->first();
        
        if(is_null($newMealPlan))
        return response()->json([
            'status' => false,
            'message' => 'Meal Plan Not Found'
        ]);

        // Get current active meal plan
        $currentMealPlan = UserMealPlan::where('user_id',$userId)
            ->where('status','active')
            ->first();

        if(!$currentMealPlan) {
            // No active meal plan, just subscribe
            return $this->subscribePlan($id);
        }

        // Check if trying to switch to same plan
        if($currentMealPlan->meal_plan_id == $id) {
            return response()->json([
                'status' => false,
                'message' => "You are already subscribed to this meal plan."
            ]);
        }

        try {
            // Deactivate current meal plan
            $currentMealPlan->status = 'switched';
            $currentMealPlan->save();

            // Subscribe to new meal plan
            $usrPlan = new UserMealPlan();
            $usrPlan->user_id = $userId;
            $usrPlan->meal_plan_id = $id;
            $usrPlan->subscribe_date = Carbon::today();
            $usrPlan->status = 'active';
            $usrPlan->save();

            // Generate tracking for new meal plan
            $mealWeekIds = MealPlanWeek::where('meal_plan_id',$newMealPlan->id)->pluck('meal_week_id')->toArray();
            foreach ($mealWeekIds as $mwId) {
                $mealWeek = MealWeek::find($mwId);
                $mealDays = [
                    $mealWeek->meal_day1,
                    $mealWeek->meal_day2,
                    $mealWeek->meal_day3,
                    $mealWeek->meal_day4,
                    $mealWeek->meal_day5,
                    $mealWeek->meal_day6,
                    $mealWeek->meal_day7
                ];
                foreach ($mealDays as $md) {
                    $mealDay = MealDay::where('id',$md)->first(['breakfast','lunch','dinner','snacks','drinks']);
                    if(!is_null($mealDay)){
                        if(!is_null($mealDay->breakfast))
                        UMPTracking::create(['ump_id' => $usrPlan->id,'mw_id' => $mwId,'md_id' => $md,'meal_id' => $mealDay->breakfast,'meal_type' => 'breakfast']);
                        if(!is_null($mealDay->lunch))
                        UMPTracking::create(['ump_id' => $usrPlan->id,'mw_id' => $mwId,'md_id' => $md,'meal_id' => $mealDay->lunch,'meal_type' => 'lunch']);
                        if(!is_null($mealDay->dinner))
                        UMPTracking::create(['ump_id' => $usrPlan->id,'mw_id' => $mwId,'md_id' => $md,'meal_id' => $mealDay->dinner,'meal_type' => 'dinner']);
                        if(!is_null($mealDay->snacks))
                        UMPTracking::create(['ump_id' => $usrPlan->id,'mw_id' => $mwId,'md_id' => $md,'meal_id' => $mealDay->snacks,'meal_type' => 'snacks']);
                        if(!is_null($mealDay->drinks))
                        UMPTracking::create(['ump_id' => $usrPlan->id,'mw_id' => $mwId,'md_id' => $md,'meal_id' => $mealDay->drinks,'meal_type' => 'drinks']);
                    }
                }
            }

            return response()->json([
                'status' => true,
                'message' => "Meal Plan Switched Successfully",
                'data' => [
                    'old_meal_plan_id' => $currentMealPlan->meal_plan_id,
                    'new_meal_plan_id' => $id,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error switching meal plan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download meal plan PDF
     */
    function downloadPDF($id){
        $userPlan = UserMealPlan::where('id',$id)->where('user_id',Auth::id())->first(['meal_plan_id']);
        if(is_null($userPlan))
        return response()->json([
            'status' => false,
            'message' => 'Invalid Meal Plan.'
        ]);
        
        $mealPlan = MealPlan::where('id',$userPlan->meal_plan_id)->first();
        if(is_null($mealPlan))
        return response()->json([
            'status' => false,
            'message' => 'Meal Plan Not Found.'
        ]);

        $temp = $mealPlan->links();
        
        return response()->json([
            'status' => true,
            'data' => [
                'pdf_url' => $temp['file_downoad'],
                'pdf_url2' => $temp['file_downoad2'],
                'pdf_url3' => $temp['file_downoad3'],
            ]
        ]);
    }

    /**
     * Get meal plan with viewing options (day/week/full)
     */
    function getMealPlanWithView($id, Request $request){
        $view = $request->get('view', 'full'); // day, week, or full
        $day = $request->get('day');
        $week = $request->get('week');
        
        $userPlan = UserMealPlan::where('id',$id)->where('user_id',Auth::id())->first(['meal_plan_id','status']);
        if(is_null($userPlan))
        return response()->json([
            'status' => false,
            'message' => 'Invalid ID.'
        ]);
        if($userPlan->status!=='active')
        return response()->json([
            'status' => false,
            'message' => 'Meal Plan Completed.'
        ]);
        
        $planDetail = MealPlan::where('id',$userPlan->meal_plan_id)->first()->makeHidden(['tags','status','created_at','updated_at']);
        
        // IMPORTANT: Add description about accessing all meals
        $planDetail->description = $this->normalizeOptionalText($planDetail->description);
        if(empty($planDetail->description)) {
            $planDetail->description = "You have access to hundreds of meal recipes for all health backgrounds. View meals by day, by week, or browse the entire meal library.";
        } else {
            $planDetail->description = $planDetail->description . " Plus, you have access to browse all available meals in our meal library.";
        }
        
        $weekIds = MealPlanWeek::where('meal_plan_id',$planDetail->id)->orderBy('id','asc')->pluck('meal_week_id')->toArray();
        
        if($view == 'day' && $day && $week) {
            // Return specific day of specific week
            $weekIndex = (int)$week - 1;
            if($weekIndex >= 0 && $weekIndex < count($weekIds)) {
                $weekId = $weekIds[$weekIndex];
                $mealWeek = MealWeek::where('id', $weekId)->first();
                if($mealWeek) {
                    $dayFields = ['meal_day1','meal_day2','meal_day3','meal_day4','meal_day5','meal_day6','meal_day7'];
                    $dayField = $dayFields[$day-1] ?? null;
                    if($dayField) {
                        $mealDayId = $mealWeek->$dayField;
                        $mealDay = MealDay::where('id',$mealDayId)->first();
                        if($mealDay) {
                            $mealDay = $this->getMealDetails($mealDay);
                            return response()->json([
                                'status' => true,
                                'data' => [
                                    'plan' => $planDetail,
                                    'week_number' => $week,
                                    'day_number' => $day,
                                    'meal_day' => $mealDay,
                                ],
                                'view_mode' => 'day'
                            ]);
                        }
                    }
                }
            }
            return response()->json([
                'status' => false,
                'message' => 'Invalid week or day number.'
            ]);
        } elseif($view == 'week' && $week) {
            // Return specific week
            $weekIndex = (int)$week - 1;
            if($weekIndex >= 0 && $weekIndex < count($weekIds)) {
                $weekId = $weekIds[$weekIndex];
                $mealWeek = MealWeek::where('id', $weekId)->first();
                if($mealWeek) {
                    // Load all days for this week
                    for($i = 1; $i <= 7; $i++) {
                        $dayField = 'meal_day' . $i;
                        $mealWeek->$dayField = $this->getMealDayWithDetails($mealWeek->$dayField);
                    }
                    return response()->json([
                        'status' => true,
                        'data' => [
                            'plan' => $planDetail,
                            'week_number' => $week,
                            'week' => $mealWeek,
                        ],
                        'view_mode' => 'week'
                    ]);
                }
            }
            return response()->json([
                'status' => false,
                'message' => 'Invalid week number.'
            ]);
        } else {
            // Return full plan (default) - all weeks with all days
            $planDetail->weeks = empty($weekIds) ? collect() : MealWeek::whereIn('id',$weekIds)->orderByRaw('FIELD(id, ' . implode(',', $weekIds) . ')')->get()->makeHidden(['tags','status','created_at','updated_at']);
            foreach ($planDetail->weeks as $mwk) {
                for($i = 1; $i <= 7; $i++) {
                    $dayField = 'meal_day' . $i;
                    $mwk->$dayField = $this->getMealDayWithDetails($mwk->$dayField);
                }
            }

            $weekStatuses = [];
            foreach ($planDetail->weeks as $pw) {
                $pw = $this->getWeekStatus($pw,$id);
                array_push($weekStatuses,$pw->completion);
            }
            $planDetail->completion = in_array(0,$weekStatuses)?0:1;
            
            return response()->json([
                'status' => true,
                'data' => $planDetail,
                'view_mode' => 'full'
            ]);
        }
        
        // Fallback return (should not reach here)
        return response()->json([
            'status' => true,
            'data' => $planDetail,
            'view_mode' => $view
        ]);
    }
}
