<?php

namespace App\Http\Controllers\Api;

use App\Helpers\JsonSanitizer;
use App\Http\Controllers\Controller;
use App\Models\AutomatedMessage;
use App\Models\Chat;
use App\Models\Comment;
use App\Models\ExerciseCompilation;
use App\Models\Message;
use App\Models\Program;
use App\Models\ProgramPhase;
use App\Models\ProgramPhaseWorkout;
use App\Models\ProgramSub;
use App\Models\ProgramSubTracking;
use App\Models\ProgramHistory;
use App\Models\User;
use App\Models\UserMealPlan;
use App\Models\UserSetting;
use App\Models\WeeklyWorkout;
use App\Models\WeekWiseProgram;
use App\Models\Workout;
use App\Models\WorkoutCompilation;
use App\Support\ContentLocaleResolver;
use App\Support\UserContentLocale;
use App\Traits\ActivitiesTrait;
use App\Traits\NotificationsTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use stdClass;

class ProgramSubTrackingController extends Controller
{
    use NotificationsTrait,ActivitiesTrait;

    function subscribeUsers(Request $request){
        $validate = Validator::make($request->all(),[
            'ids' => 'required|array|min:1',
            'program_id' => 'required|numeric'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $prog = Program::find($request->program_id);
        if($prog==null)
        return response()->json([
            'status' => false,
            'message' => "Invalid Program."
        ]);
        $prPhIds = ProgramPhase::where('program_id',$request->program_id)->pluck('id')->toArray();
        $emptyPhase = 0;    // to check if workouts exist in phase
        $add = false;   // to check if no phase
        foreach ($prPhIds as $value) {
            $add = true;
            $phaseWorkouts = ProgramPhaseWorkout::where('program_phase_id',$value)->count();
            if($phaseWorkouts===0)
            $emptyPhase++;
        }
        if(!($add && $emptyPhase===0))
        return response()->json([
            'status' => false,
            'message' => "Can't Subscribe Partially Built Program. Please Import Workouts to All Phases."
        ]);

        $notiRecievers = [];
        $notiRecieversAr = [];
        $notiTitle = 'New Program Subscribed!';
        $notiTitleAr = 'تم الاشتراك ببرنامج جديد  ';
        $notiContent = strtoupper(Auth::user()->name).' subscribed you to '.strtoupper($prog->title).' program.';
        $notiContentAr = strtoupper(Auth::user()->name).' اشترك لك في برنامج '.strtoupper($prog->title);
        $notiSource = Auth::id();
        foreach ($request->ids as $id) {
            // only one program at a time
            if(ProgramSub::where('user_id',$id)->count() > 0){
                if(ProgramSub::where('user_id',$id)->whereNull('complete_date')->count()>0)    //active program exists
                continue;
            }

            $userFcm = User::where('id',$id)->where('role',1)->pluck('fcm_token')->first();
            $alreadySub = ProgramSub::where('user_id',$id)->where('program_id',$request->program_id)->first();
            if(is_null($alreadySub)){
                $proSub = new ProgramSub();
                $proSub->user_id = $id;
                $proSub->program_id = $request->program_id;
                $proSub->subscribe_date = Carbon::today();
                $proSub->status = 'subscribed';
                $proSub->save();
                $this->generateTracking($proSub->id,$request->program_id);

                $this->storeNotification($id,$notiTitle,$notiTitleAr,$notiContent,$notiContentAr);
                if($this->userSelecetdLanguage($id)==='en')
                array_push($notiRecievers,$userFcm);
                else 
                array_push($notiRecieversAr,$userFcm);
                

                // sending auto msg for first subscription
                $subCount = ProgramSub::where('user_id',$id)->count();
                if($subCount===1)
                AutomatedMessagesController::sendAutoMessage($id,'firstProg');
            }
        }
        if(count($notiRecievers)>0)
        $this->sendFirebaseNotification($notiRecievers,$notiTitle,$notiContent);
        if(count($notiRecieversAr)>0)
        $this->sendFirebaseNotification($notiRecieversAr,$notiTitleAr,$notiContentAr);
        return response()->json([
            'status' => true,
            'message' => "Program Subscribed to Users."
        ]);
    }

    function unSubUsers(Request $request){
        $validate = Validator::make($request->all(),[
            'user_ids' => 'required|array|min:1',
            'program_id' => 'required|numeric|exists:programs,id'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $prog = Program::find($request->program_id);
        // ProgramSub::where('program_id',$request->program_id)->whereIn('user_id',$request->user_ids)->delete();
        $notiRecievers = [];
        $notiRecieversAr = [];
        $notiTitle = 'Program Unsubscribed!';
        $notiTitleAr = 'البرنامج غير مشترك!';
        $notiContent = ucfirst(Auth::user()->name).' unsubscribed you from '.strtoupper($prog->title).' program.';
        $notiContentAr = 'ألغى '.ucfirst(Auth::user()->name).' اشتراكك من البرنامج'.strtoupper($prog->title);
        $notiSource = Auth::id();
        foreach ($request->user_ids as $id) {
            $proSub = ProgramSub::where('program_id',$request->program_id)->where('user_id',$id)->first();
            if($proSub){
                $proSub->delete();
                $userFcm = User::where('id',$id)->pluck('fcm_token')->first();
                $this->storeNotification($id,$notiTitle,$notiTitleAr,$notiContent,$notiContentAr,$notiSource);
                if($this->userSelecetdLanguage($id)==='en')
                array_push($notiRecievers,$userFcm);
                else
                array_push($notiRecieversAr,$userFcm);
            }
        }
        if(count($notiRecievers)>0)
        $this->sendFirebaseNotification($notiRecievers,$notiTitle,$notiContent);
        if(count($notiRecieversAr)>0)
        $this->sendFirebaseNotification($notiRecieversAr,$notiTitleAr,$notiContentAr);
        return response()->json([
            'status' => true,
            'message' => "Users Unsubscribed."
        ]); 
    }

    function generateTracking($proSubId,$proId){
        $phases = ProgramPhase::where('program_id',$proId)->get();
        $duration = array_sum(ProgramPhase::where('program_id',$proId)->pluck('weeks')->toArray());
        for ($i=1; $i <= $duration; $i++) { 
            $proWeek = new WeekWiseProgram();
            $proWeek->program_sub_id = $proSubId;
            $proWeek->week_no = $i;
            $proWeek->save();

            $phaseId = null;
            foreach ($phases as $phase) {
                $phaseWeeks = $phase->phase_no*$phase->weeks;
                if($i<=$phaseWeeks){
                    $phaseId = $phase->id;
                    break;
                }
            }
            $phaseWorkouts = ProgramPhaseWorkout::where('program_phase_id',$phaseId)->get();
            foreach ($phaseWorkouts as $pWrk) {
                $weekly = new WeeklyWorkout();
                $weekly->week_id = $proWeek->id;
                $weekly->workout_id = $pWrk->workout_id;
                $weekly->display_name = $pWrk->display_name;
                $weekly->save();
            }
        }
    }

    function subscribeProgram($id){
        $programSub = Program::find($id);
        $userId = Auth::id();
        if(is_null($programSub))
        return response()->json([
            'status' => false,
            'message' => 'Invalid Program.'
        ]);
        
        // BUSINESS RULE: Consultation form must be completed before subscribing to programs
        $consultation = \App\Models\ConsultationForm::where('user_id', $userId)
            ->whereNotNull('completed_at')
            ->first();
        if (!$consultation) {
            $hasAnswers = \App\Models\UserAnswer::where('user_id', $userId)->count() > 0;
            if (!$hasAnswers) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please complete the consultation form before subscribing to a program.',
                    'consultation_required' => true
                ], 403);
            }
        }
        
        // BUSINESS RULE: only one program at a time - ENFORCE RULE
        $activeProgram = ProgramSub::where('user_id',$userId)
            ->whereIn('status', ['subscribed', 'in-progress', 'resumed'])
            ->first();
        
        if($activeProgram) {
            // Check if trying to subscribe to same program
            if($activeProgram->program_id == $id) {
                return response()->json([
                    'status' => false,
                    'message' => "Already Subscribed to this Program."
                ]);
            }
            // Active program exists - cannot subscribe to another
            return response()->json([
                'status' => false,
                'message' => "You can only follow ONE program at a time. Please complete or switch your current program first.",
                'current_program_id' => $activeProgram->program_id,
                'current_program_status' => $activeProgram->status
            ]);
        }
        
        // Check if already subscribed (even if completed)
        if(ProgramSub::where('user_id',$userId)->count() > 0)
        {
            $alreadySub = ProgramSub::where('user_id',$userId)->where('program_id',$id)->first();
            if($alreadySub)
            return response()->json([
                'status' => false,
                'message' => "Already Subscribed."
            ]);
                $prPhIds = ProgramPhase::where('program_id',$id)->pluck('id')->toArray();
            $emptyPhase = 0;    // to check if workouts exist in phase
            $add = false;   // to check if no phase
            foreach ($prPhIds as $value) {
                $add = true;
                $phaseWorkouts = ProgramPhaseWorkout::where('program_phase_id',$value)->count();
                if($phaseWorkouts===0)
                $emptyPhase++;
            }
            if(!($add && $emptyPhase===0))
            return response()->json([
                'status' => false,
                'message' => "Can't Subscribe to this Program. (Partially Built)"
            ]);
            $notiTitle = 'Program Subscribed!';
            $notiContent = ucfirst(Auth::user()->name).' Subscribed '.strtoupper($programSub->title).' Program.';
            ProgramSub::where('user_id',$userId)->update([
                'program_id' =>$id
            ]);
            $proSub=ProgramSub::where('user_id',$userId)->first();
            // $proSub = ProgramSub::where('user_id', $userId)->first(); 
            // $proSub->update([
            //     'program_id' => $id
            // ]);
            // $proSub = new ProgramSub();
            // $proSub->user_id = $userId;
            // $proSub->program_id = $id;
            // $proSub->subscribe_date = Carbon::today();
            // $proSub->status = 'subscribed';
            // $proSub->save();
            // $proSub = ProgramSub::updateOrCreate(
            //     [
            //         'user_id' => $userId, 
            //         'program_id' => $id
            //     ], // Search criteria
            //     [
            //         'program_id' => $id,
            //         // 'subscribe_date' => Carbon::today(),
            //         'status' => 'subscribed'
            //     ] // Fields to update or insert
            // );
            // dd($proSub);

            $adminId = User::where('role',2)->pluck('id')->first();
            // dd($proSub->id,$id);
            $this->generateTracking($proSub->id,$id);
            $this->storeNotification($adminId,$notiTitle,null,$notiContent,null,$userId);

            if(ProgramSub::where('user_id',$userId)->count()===1)
            AutomatedMessagesController::sendAutoMessage($userId,'firstProg');
            $mealPlan = UserMealPlan::where('user_id',$userId)->count();
            return response()->json([
                'status' => true,
                'message' => "Program Subscribed",
                'select_meal_plan' => $mealPlan>0?false:true
            ]);
        }
        $alreadySub = ProgramSub::where('user_id',$userId)->where('program_id',$id)->first();
        if($alreadySub)
        return response()->json([
            'status' => false,
            'message' => "Already Subscribed."
        ]);
        $prPhIds = ProgramPhase::where('program_id',$id)->pluck('id')->toArray();
        $emptyPhase = 0;    // to check if workouts exist in phase
        $add = false;   // to check if no phase
        foreach ($prPhIds as $value) {
            $add = true;
            $phaseWorkouts = ProgramPhaseWorkout::where('program_phase_id',$value)->count();
            if($phaseWorkouts===0)
            $emptyPhase++;
        }
        if(!($add && $emptyPhase===0))
        return response()->json([
            'status' => false,
            'message' => "Can't Subscribe to this Program. (Partially Built)"
        ]);
        $notiTitle = 'Program Subscribed!';
        $notiContent = ucfirst(Auth::user()->name).' Subscribed '.strtoupper($programSub->title).' Program.';

        $proSub = new ProgramSub();
        $proSub->user_id = $userId;
        $proSub->program_id = $id;
        $proSub->subscribe_date = Carbon::today();
        $proSub->status = 'subscribed';
        $proSub->save();

        $adminId = User::where('role',2)->pluck('id')->first();
        $this->generateTracking($proSub->id,$id);
        $this->storeNotification($adminId,$notiTitle,null,$notiContent,null,$userId);

        if(ProgramSub::where('user_id',$userId)->count()===1)
        AutomatedMessagesController::sendAutoMessage($userId,'firstProg');
        $mealPlan = UserMealPlan::where('user_id',$userId)->count();
        return response()->json([
            'status' => true,
            'message' => "Program Subscribed",
            'select_meal_plan' => $mealPlan>0?false:true
        ]);
    }

    function unsubProgram($id){
        ProgramSub::where('user_id',Auth::id())->where('program_id',$id)->delete();
        return response()->json([
            'status' => true,
            'message' => "Program Un-Subscribed"
        ]);
    }

    function startProgram($id){
        $programSub = ProgramSub::where('user_id',Auth::id())->where('program_id',$id)->first();
        if(is_null($programSub))
        return response()->json([
            'status' => false,
            'message' => 'You are Not Subscribed to this Program.'
        ]);
        if($programSub->status!=='subscribed')
        return response()->json([
            'status' => false,
            'message' => 'Already Started.'
        ]);
        // $this->generateTracking($programSub->id,$id);
        $programSub->status = 'in-progress';
        $programSub->start_date = Carbon::today();
        $programSub->update();

        $notiReciever = User::where('role',2)->pluck('id')->first();
        $notiSource = Auth::id();
        $notiTitle = 'Program Started!';
        $notiContent = Auth::user()->name.' just started '.Program::where('id',$id)->pluck('title')->first().' program.';
        $this->storeNotification($notiReciever,$notiTitle,null,$notiContent,null,$notiSource);
        return response()->json([
            'status' => true,
            'message' => 'Program Started.'
        ]);
    }

    function pauseProgram($id){
        $programSub = ProgramSub::where('user_id',Auth::id())->where('program_id',$id)->first();
        if(is_null($programSub))
        return response()->json([
            'status' => false,
            'message' => 'You are Not Subscribed to this Program.'
        ]);
        if($programSub->status!=='in-progress')
        return response()->json([
            'status' => false,
            'message' => 'Program is not in progress.'
        ]);
        $programSub->pause_date = Carbon::today();
        $programSub->status = 'paused';
        $programSub->save();
        return response()->json([
            'status' => true,
            'message' => 'Program Paused.'
        ]);
    }

    function resumeProgram($id){
        $programSub = ProgramSub::where('user_id',Auth::id())->where('program_id',$id)->first();
        if(is_null($programSub))
        return response()->json([
            'status' => false,
            'message' => 'You are Not Subscribed to this Program.'
        ]);
        if($programSub->status!=='paused')
        return response()->json([
            'status' => false,
            'message' => 'Program is paused.'
        ]);
        $programSub->resume_date = Carbon::today();
        $programSub->status = 'resumed';
        $programSub->save();
        return response()->json([
            'status' => true,
            'message' => 'Program Resumed.'
        ]);
    }

    function resetProgram($id){
        $programSub = ProgramSub::where('user_id',Auth::id())->where('program_id',$id)->first();
        if(is_null($programSub))
        return response()->json([
            'status' => false,
            'message' => 'You are Not Subscribed to this Program.'
        ]);
        WeekWiseProgram::where('program_sub_id',$programSub->id)->delete();
        $this->generateTracking($programSub->id,$id);
        $programSub->start_date = Carbon::today();
        $programSub->pause_date = null;
        $programSub->resume_date = null;
        $programSub->complete_date = null;
        $programSub->status = 'in-progress';
        $programSub->save();
        return response()->json([
            'status' => true,
            'message' => 'Program Reseted.'
        ]);
    }

    function workoutCompleted(Request $request){
        $validate = Validator::make($request->all(),[
            'pro_sub_id' => 'required|integer',
            'week_id' => 'required|integer',
            'weekly_work_id' => 'required|integer',
            'rating' => 'required|integer|between:1,12',
            'comment' => 'nullable|string'
        ]);
        $mealPlanExist = false;     // for app deveoper convenience
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0],
            'ask_new_program' => false,
            'meal_plan_exist' => $mealPlanExist
        ]);
        $proSub = ProgramSub::where('user_id',Auth::id())->where('id',$request->pro_sub_id)->first();
        if(is_null($proSub))
        return response()->json([
            'status' => false,
            'message' => 'You are not Subscribed to this program',
            'ask_new_program' => false,
            'meal_plan_exist' => $mealPlanExist
        ]);
        if($proSub->status==='subscribed')
        return response()->json([
            'status' => false,
            'message' => 'You need to start program first',
            'ask_new_program' => false,
            'meal_plan_exist' => $mealPlanExist
        ]);
        if($proSub->status==='paused' || $proSub->status==='completed')
        return response()->json([
            'status' => false,
            'message' => 'Program is '.ucfirst($proSub->status),
            'ask_new_program' => false,
            'meal_plan_exist' => $mealPlanExist
        ]); 
        $week = WeekWiseProgram::where('program_sub_id',$request->pro_sub_id)->where('id',$request->week_id)->first();
        $weeklyWrk = WeeklyWorkout::where('week_id',$request->week_id)->where('id',$request->weekly_work_id)->first();
        if(is_null($weeklyWrk))
        return response()->json([
            'status' => false,
            'message' => 'Payload is Invalid',
            'ask_new_program' => false,
            'meal_plan_exist' => $mealPlanExist
        ]); 
        if($weeklyWrk->status===1)
        return response()->json([
            'status' => false,
            'message' => 'Already Completed.',
            'ask_new_program' => false,
            'meal_plan_exist' => $mealPlanExist
        ]);

        $weeklyWrk->status = 1;
        $weeklyWrk->done_date = Carbon::today();
        $weeklyWrk->update();

        WorkoutCompilation::create(['user_id' => Auth::id(),'workout_id' => $weeklyWrk->workout_id]);
        BadgesAchievementsController::weeklyAchievementCheck($request->week_id,Auth::id(),$proSub->program_id,$week->week_no);
        
        $programName = Program::where('id',$proSub->program_id)->pluck('title')->first();
        $weekCompleted = false;
        $programCompleted = false;
        $workoutsOfThisWeekStatus = WeeklyWorkout::where('week_id',$request->week_id)->pluck('status')->toArray();

        if($this->arrayAllOne($workoutsOfThisWeekStatus)){
            $week->status = 1;
            $week->done_date = Carbon::today();
            $week->update();
            $weekCompleted = true;
            $allWeeksStatus = WeekWiseProgram::where('program_sub_id',$request->pro_sub_id)->pluck('status')->toArray();
            if($this->arrayAllOne($allWeeksStatus)){
                $proSub->status = 'completed';
                $proSub->complete_date = Carbon::today();
                $proSub->update();
                $programCompleted = true;
            }
        }
        
        if(!is_null($request->comment)){
            $comment = new Comment();
            $comment->user_id = Auth::id();
            $comment->content = $request->comment;
            $comment->rating = $request->rating;
            $comment->type = 'workout';
            $comment->target_id = $weeklyWrk->workout_id;
            $comment->save();
        }

        $actTitle = 'Workout Completed';
        $actContent = ucfirst(Auth::user()->name).' just completed '.strtoupper($weeklyWrk->display_name).' workout of '.strtoupper($programName).' program';
        $this->generateActivityForAdmin($actTitle,$actContent,1,Auth::id(),'workout',$weeklyWrk->workout_id);
        if($weekCompleted){
            $actTitle = 'Week Completed';
            $actContent = ucfirst(Auth::user()->name).' just completed Week '.strtoupper($week->week_no).'of '.strtoupper($programName).' program';
            $this->generateActivityForAdmin($actTitle,$actContent,1,Auth::id());
            if($programCompleted){
                $actTitle = 'Program Completed';
                $actContent = ucfirst(Auth::user()->name).' just completed '.strtoupper($programName).' program';
                $this->generateActivityForAdmin($actTitle,$actContent,1,Auth::id());

                // for app develeper convenience, send meal plan subscribed check
                if(UserMealPlan::where('user_id',Auth::id())->where('status','active')->first())
                $mealPlanExist = true;
            }
        }
        

        return response()->json([
            'status' => true,
            'message' => 'Workout Marked Done.',
            'ask_new_program' => $this->userFullAccess(Auth::id()) && $programCompleted,
            'meal_plan_exist' => $mealPlanExist
        ]); 
    }

    function arrayAllOne($array){
        $result = array_search(0,$array);
        if($result===false)
        return 1;
        return 0;
    }

    function exerciseCompleted(Request $request){
        $validate = Validator::make($request->all(),[
            'program_sub_id' => 'required|integer',
            'week_id' => 'required|integer',
            'weekly_workout_id' => 'required|integer',
            'workout_exercise_id' => 'required|integer',
            'exercise_id' => 'required|integer',
            'sets' => 'nullable|integer',
            'rounds' => 'nullable|integer',
            'weights' => 'required|numeric',
            // 'target_muscle' => 'required|in:legs,biceps,triceps,back,shoulders,chest,arms,abdominals,forearms'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $userId = Auth::id();
        $exists = ExerciseCompilation::where('user_id',$userId)->where('program_sub_id',$request->program_sub_id)
        ->where('week_id',$request->week_id)->where('weekly_workout_id',$request->weekly_workout_id)
        ->where('workout_exercise_id',$request->workout_exercise_id)->where('exercise_id',$request->exercise_id)->first();
        if($exists)
        return response()->json([
            'status' => false,
            'message' => 'Already Completed'
        ]);
        $proSub = ProgramSub::where('user_id',$userId)->where('id',$request->program_sub_id)->first();
        if($proSub){
            if(!($proSub->status==='in-progress' || $proSub->status==='resumed'))
            return response()->json([
                'status' => false,
                'message' => 'Program is paused or completed or not started yet.'
            ]);
            if(WeekWiseProgram::where('id',$request->week_id)->where('program_sub_id',$request->program_sub_id)->first()){
                if(WeeklyWorkout::where('id',$request->weekly_workout_id)->where('week_id',$request->week_id)->first()){
                    $exComp = new ExerciseCompilation();
                    $exComp->user_id = $userId;
                    $exComp->program_sub_id = $request->program_sub_id;
                    $exComp->week_id = $request->week_id;
                    $exComp->weekly_workout_id = $request->weekly_workout_id;
                    $exComp->workout_exercise_id = $request->workout_exercise_id;
                    $exComp->exercise_id = $request->exercise_id;
                    $exComp->sets = $request->sets;
                    $exComp->rounds = $request->rounds;
                    $exComp->weight = $request->weights;
                    $exComp->weight_unit = UserSetting::where('user_id',$userId)->pluck('weight_unit')->first();
                    $exComp->save();
                    
                    // IMPORTANT: Also track weight in ExerciseWeightTracking for progress tracking and personal best detection
                    // This allows users to see their last weight and know when to go heavier
                    $previousMax = \App\Models\ExerciseWeightTracking::where('user_id', $userId)
                        ->where('exercise_id', $request->exercise_id)
                        ->max('weight');
                    
                    $isPersonalBest = false;
                    if (!$previousMax || $request->weights > $previousMax) {
                        $isPersonalBest = true;
                        // Update all previous records to false
                        \App\Models\ExerciseWeightTracking::where('user_id', $userId)
                            ->where('exercise_id', $request->exercise_id)
                            ->update(['is_personal_best' => false]);
                    }
                    
                    $weightTracking = \App\Models\ExerciseWeightTracking::create([
                        'user_id' => $userId,
                        'exercise_id' => $request->exercise_id,
                        'weight' => $request->weights,
                        'sets' => $request->sets,
                        'reps' => $request->rounds, // rounds is used for reps in ExerciseCompilation
                        'is_personal_best' => $isPersonalBest,
                    ]);
                    
                    // If personal best, create achievement and notify admin
                    if ($isPersonalBest) {
                        $exercise = \App\Models\Exercise::find($request->exercise_id);
                        
                        // Create achievement with star
                        \App\Models\UserAchievement::create([
                            'user_id' => $userId,
                            'achievement_type' => 'personal_best',
                            'achievement_data' => [
                                'exercise_id' => $request->exercise_id,
                                'exercise_name' => $exercise ? $exercise->title : 'Exercise',
                                'weight' => $request->weights,
                            ],
                            'stars' => 1,
                        ]);
                        
                        // Notify admin (Dina) about personal best
                        $adminId = \App\Models\User::where('role', 2)->pluck('id')->first();
                        if ($adminId) {
                            $user = \App\Models\User::find($userId);
                            $userName = $user->name;
                            $exerciseName = $exercise ? $exercise->title : 'Exercise';
                            $weightUnit = $exComp->weight_unit ?? 'kg';
                            $notiTitle = 'Personal Best Achieved! ⭐';
                            $notiTitleAr = 'تم تحقيق أفضل أداء شخصي! ⭐';
                            $notiContent = "{$userName} achieved a personal best in {$exerciseName} with {$request->weights} {$weightUnit} - They earned a star!";
                            $notiContentAr = "حققت {$userName} أفضل أداء شخصي في {$exerciseName} بـ {$request->weights} {$weightUnit} - لقد حصلوا على نجمة!";
                            
                            $this->storeNotification($adminId, $notiTitle, $notiTitleAr, $notiContent, $notiContentAr, $userId);
                            
                            // Generate activity for admin
                            $this->generateActivityForAdmin(
                                'Personal Best ⭐',
                                $notiContent,
                                4, // Goals Hit category
                                $userId,
                                'personal_best',
                                $exercise ? $exercise->id : null
                            );
                        }
                    }
                    
                    return response()->json([
                        'status' => true,
                        'message' => $isPersonalBest ? 'Weight Added and Exercise Completed! Personal best achieved! ⭐' : 'Weight Added and Exercise Completed',
                        'is_personal_best' => $isPersonalBest,
                        'star_earned' => $isPersonalBest,
                    ]); 
                } else
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid Payload'
                ]);
            } else
            return response()->json([
                'status' => false,
                'message' => 'Invalid Payload'
            ]);
        } else
        return response()->json([
            'status' => false,
            'message' => 'Invalid Payload'
        ]);
    }

    function getProgramDetail($id){
        $programSub = ProgramSub::where('user_id',Auth::id())->where('program_id',$id)->first();
        if(is_null($programSub))
        return response()->json([
            'status' => false,
            'message' => 'You are Not Subscribed to this Program.'
        ]);
        
        // Get program information including description (note: database uses 'discription' with typo)
        $program = Program::find($id);
        $programInfo = null;
        if($program) {
            ContentLocaleResolver::overlayProgram($program, UserContentLocale::forAuthenticatedUser());
            $programInfo = [
                'id' => $program->id,
                'title' => $program->title,
                'description' => $program->discription ?? null, // Database column is 'discription' (typo)
                'image' => $program->image,
                'content_code' => $program->content_code,
            ];
        }
        
        // BUSINESS RULE: Users can only see ONE week in advance (current week + 1 week ahead)
        $currentWeek = 1;
        if($programSub->status=='in-progress'){
            $startDate = $programSub->start_date ?? $programSub->subscribe_date;
            $endate = Carbon::now();
            $weeksPassed = $endate->diffInWeeks($startDate);
            $currentWeek = max(1, $weeksPassed + 1);
        }
        else if($programSub->status=='paused'){
            $startDate = $programSub->start_date ?? $programSub->subscribe_date;
            $endate = $programSub->pause_date;
            $weeksPassed = Carbon::parse($endate)->diffInWeeks($startDate);
            $currentWeek = max(1, $weeksPassed + 1);
        }
        else if($programSub->status=='resumed'){
            $startDate1 = $programSub->start_date ?? $programSub->subscribe_date;
            $endate1 = $programSub->pause_date;
            $weeksPassed1 = Carbon::parse($endate1)->diffInWeeks($startDate1);
            $startDate2 = $programSub->resume_date;
            $endate2 = Carbon::now();
            $weeksPassed2 = $endate2->diffInWeeks($startDate2);
            $currentWeek = max(1, ($weeksPassed1+$weeksPassed2) + 1);
        }
        else if($programSub->status=='completed'){
            // Show all weeks for completed programs
            $programDetail = WeekWiseProgram::where('program_sub_id',$programSub->id)->get();
            foreach ($programDetail as $week) {
                $week->weekly_workouts = $week->weeklyWorkouts();
                foreach ($week->weekly_workouts as $workout) {
                    // Explicitly ensure daily_summary is included in workout detail
                    $workout->workout_detail = Workout::where('id',$workout->workout_id)
                        ->select('*') // Select all fields including daily_summary and description
                        ->with(['workoutExercises' => function($wrkExs){
                            // Ensure exercises are ordered by ID to preserve creation order
                            // This maintains the exact layout/category structure the user created
                            // IMPORTANT: All fields (sets, reps, rest_period) must be preserved
                            $wrkExs->orderBy('id', 'asc')
                                  ->with(['exerciseDetail' => function($exDetail){
                                      // Ensure exercise title/name is always loaded
                                      // This is critical for displaying exercise information
                                  }]);
                        }])->first();
                    if(!is_null($workout->workout_detail)){
                        // Ensure daily_summary is always present (even if null)
                        if(!isset($workout->workout_detail->daily_summary)) {
                            $workout->workout_detail->daily_summary = null;
                        }
                        // Ensure instructions is always present (even if null) - workouts use 'instructions' not 'description'
                        if(!isset($workout->workout_detail->instructions)) {
                            $workout->workout_detail->instructions = null;
                        }
                        $this->localizeWorkoutWithExercises($workout->workout_detail);
                        $workout->workout_detail->workoutExercises = $this->organizeExercises($workout->workout_detail->type,$workout->workout_detail->workoutExercises);
                        unset($workout->workout_exercises);
                    }
                }
            }
            $data = JsonSanitizer::sanitize($programDetail->toArray());
            return response()->json([
                'status' => true,
                'data' => $data,
                'program' => $programInfo
            ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Start Program to Get Detail.'
            ]);
        }
        
        // Show only current week + 1 week ahead (max 2 weeks visible)
        $maxWeekToShow = $currentWeek + 1;
        
        $programDetail = WeekWiseProgram::where('program_sub_id',$programSub->id)
            ->where('week_no', '<=', $maxWeekToShow)
            ->orderBy('week_no', 'asc')
            ->get();
            
        foreach ($programDetail as $week) {
            $week->weekly_workouts = $week->weeklyWorkouts();
            foreach ($week->weekly_workouts as $workout) {
                // Explicitly ensure daily_summary and description are included in workout detail
                $workout->workout_detail = Workout::where('id',$workout->workout_id)
                    ->select('*') // Select all fields including daily_summary and description
                    ->with(['workoutExercises' => function($wrkExs){
                        // Ensure exercises are ordered by ID to preserve creation order
                        // This maintains the exact layout/category structure the user created
                        // IMPORTANT: All fields (sets, reps, rest_period) must be preserved
                        $wrkExs->orderBy('id', 'asc')
                              ->with(['exerciseDetail' => function($exDetail){
                                  // Ensure exercise title/name is always loaded
                                  // This is critical for displaying exercise information
                              }]);
                    }])->first();
                if(!is_null($workout->workout_detail)){
                    // Ensure daily_summary is always present (even if null)
                    if(!isset($workout->workout_detail->daily_summary)) {
                        $workout->workout_detail->daily_summary = null;
                    }
                    // Ensure instructions is always present (even if null) - workouts use 'instructions' not 'description'
                    if(!isset($workout->workout_detail->instructions)) {
                        $workout->workout_detail->instructions = null;
                    }
                    $this->localizeWorkoutWithExercises($workout->workout_detail);
                    $workout->workout_detail->workoutExercises = $this->organizeExercises($workout->workout_detail->type,$workout->workout_detail->workoutExercises);
                    unset($workout->workout_exercises);
                }
            }
        }
        
        $data = JsonSanitizer::sanitize($programDetail->toArray());
        return response()->json([
            'status' => true,
            'data' => $data,
            'program' => $programInfo,
            'current_week' => $currentWeek,
            'weeks_visible' => $maxWeekToShow,
            'visibility_rule' => 'Only current week + 1 week ahead visible'
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
    }

    function organizeExercises($wrkType,$exercises){
        // Group by (group_type, group_id) so multiple supersets show as separate blocks (matches WorkoutController)
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
     * Switch from one program to another
     * Allows users to switch from their current program to a different program
     */
    function switchProgram($id){
        $userId = Auth::id();
        $newProgram = Program::find($id);
        
        if(is_null($newProgram))
        return response()->json([
            'status' => false,
            'message' => 'Invalid Program.'
        ]);

        // BUSINESS RULE: Consultation form must be completed before switching programs
        $consultation = \App\Models\ConsultationForm::where('user_id', $userId)
            ->whereNotNull('completed_at')
            ->first();
        if (!$consultation) {
            $hasAnswers = \App\Models\UserAnswer::where('user_id', $userId)->count() > 0;
            if (!$hasAnswers) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please complete the consultation form before switching programs.',
                    'consultation_required' => true
                ], 403);
            }
        }

        // Validate new program is fully built
        $prPhIds = ProgramPhase::where('program_id',$id)->pluck('id')->toArray();
        $emptyPhase = 0;
        $add = false;
        foreach ($prPhIds as $value) {
            $add = true;
            $phaseWorkouts = ProgramPhaseWorkout::where('program_phase_id',$value)->count();
            if($phaseWorkouts===0)
            $emptyPhase++;
        }
        if(!($add && $emptyPhase===0))
        return response()->json([
            'status' => false,
            'message' => "Can't Switch to this Program. (Partially Built)"
        ]);

        // Get current active program
        $currentProgram = ProgramSub::where('user_id',$userId)
            ->whereIn('status', ['subscribed', 'in-progress', 'resumed'])
            ->first();

        if(!$currentProgram) {
            // No active program, just subscribe
            return $this->subscribeProgram($id);
        }

        // Check if trying to switch to same program
        if($currentProgram->program_id == $id) {
            return response()->json([
                'status' => false,
                'message' => "You are already subscribed to this program."
            ]);
        }

        try {
            // Save progress snapshot
            $progressSnapshot = [
                'program_id' => $currentProgram->program_id,
                'status' => $currentProgram->status,
                'start_date' => $currentProgram->start_date,
                'weeks_completed' => WeekWiseProgram::where('program_sub_id', $currentProgram->id)
                    ->where('status', 1)
                    ->count(),
            ];

            // Create program history if model exists
            if(class_exists('App\Models\ProgramHistory')) {
                ProgramHistory::create([
                    'user_id' => $userId,
                    'old_program_id' => $currentProgram->program_id,
                    'new_program_id' => $id,
                    'progress_snapshot' => json_encode($progressSnapshot),
                    'switched_at' => Carbon::now(),
                ]);
            }

            // Deactivate current program
            $currentProgram->status = 'switched';
            $currentProgram->save();

            // Subscribe to new program
            $proSub = new ProgramSub();
            $proSub->user_id = $userId;
            $proSub->program_id = $id;
            $proSub->subscribe_date = Carbon::today();
            $proSub->status = 'subscribed';
            $proSub->save();

            // Generate tracking for new program
            $this->generateTracking($proSub->id, $id);

            $adminId = User::where('role',2)->pluck('id')->first();
            $notiTitle = 'Program Switched!';
            $oldProgramTitle = Program::find($currentProgram->program_id)->title ?? 'Previous Program';
            $notiContent = ucfirst(Auth::user()->name).' switched from '.$oldProgramTitle.' to '.strtoupper($newProgram->title).' program.';
            $this->storeNotification($adminId,$notiTitle,null,$notiContent,null,$userId);

            return response()->json([
                'status' => true,
                'message' => "Program Switched Successfully",
                'data' => [
                    'old_program_id' => $currentProgram->program_id,
                    'old_program_title' => $oldProgramTitle,
                    'new_program_id' => $id,
                    'new_program_title' => $newProgram->title,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error switching program',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get program detail - Updated to show only one week ahead
     */
    function getProgramDetailUpdated($id){
        $programSub = ProgramSub::where('user_id',Auth::id())->where('program_id',$id)->first();
        if(is_null($programSub))
        return response()->json([
            'status' => false,
            'message' => 'You are Not Subscribed to this Program.'
        ]);
        
        // Show only current week + 1 week ahead (visibility rule)
        $weeksToShow = 2; // Current week + 1 ahead
        
        if($programSub->status=='in-progress'){
            $startDate = $programSub->subscribe_date;
            $endate = Carbon::now();
            $weeksPassed = $endate->diffInWeeks($startDate);
            $weeksToShow = min($weeksToShow, $weeksPassed + 2); // Max 2 weeks visible
        }
        else if($programSub->status=='paused'){
            $startDate = $programSub->subscribe_date;
            $endate = $programSub->pause_date;
            $weeksPassed = Carbon::parse($endate)->diffInWeeks($startDate);
            $weeksToShow = min($weeksToShow, $weeksPassed + 2);
        }
        else if($programSub->status=='resumed'){
            $startDate1 = $programSub->subscribe_date;
            $endate1 = $programSub->pause_date;
            $weeksPassed1 = Carbon::parse($endate1)->diffInWeeks($startDate1);
            $startDate2 = $programSub->resume_date;
            $endate2 = Carbon::now();
            $weeksPassed2 = $endate2->diffInWeeks($startDate2);
            $weeksToShow = min($weeksToShow, ($weeksPassed1+$weeksPassed2) + 2);
        }
        else if($programSub->status=='completed'){
            return response()->json([
                'status' => false,
                'message' => 'Program is Completed.'
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Start Program to Get Detail.'
            ]);
        }
        
        $programDetail = WeekWiseProgram::where('program_sub_id',$programSub->id)
            ->where('week_no', '<=', $weeksToShow) // Only show visible weeks
            ->get();
            
        foreach ($programDetail as $week) {
            $week->weekly_workouts = $week->weeklyWorkouts();
            foreach ($week->weekly_workouts as $workout) {
                // Explicitly ensure daily_summary is included in workout detail
                $workout->workout_detail = Workout::where('id',$workout->workout_id)
                    ->select('*') // Select all fields including daily_summary
                    ->with(['workoutExercises' => function($wrkExs){
                        // Ensure exercises are ordered by ID to preserve creation order
                        // This maintains the exact layout/category structure the user created
                        // IMPORTANT: All fields (sets, reps, rest_period) must be preserved
                        $wrkExs->orderBy('id', 'asc')
                              ->with(['exerciseDetail' => function($exDetail){
                                  // Ensure exercise title/name is always loaded
                                  // This is critical for displaying exercise information
                              }]);
                    }])->first();
                if(!is_null($workout->workout_detail)){
                    // Ensure daily_summary is always present (even if null)
                    if(!isset($workout->workout_detail->daily_summary)) {
                        $workout->workout_detail->daily_summary = null;
                    }
                    // Ensure instructions is always present (even if null) - workouts use 'instructions' not 'description'
                    if(!isset($workout->workout_detail->instructions)) {
                        $workout->workout_detail->instructions = null;
                    }
                    $this->localizeWorkoutWithExercises($workout->workout_detail);
                    $workout->workout_detail->workoutExercises = $this->organizeExercises($workout->workout_detail->type,$workout->workout_detail->workoutExercises);
                    unset($workout->workout_exercises);
                }
            }
        }
        
        return response()->json([
            'status' => true,
            'data' => $programDetail,
            'weeks_visible' => $weeksToShow
        ]);
    }

    private function localizeWorkoutWithExercises(?Workout $workout): void
    {
        if (is_null($workout)) {
            return;
        }
        $userLang = UserContentLocale::forAuthenticatedUser();
        ContentLocaleResolver::overlayWorkout($workout, $userLang);
        foreach ($workout->workoutExercises as $we) {
            if ($we->exerciseDetail) {
                ContentLocaleResolver::overlayExercise($we->exerciseDetail, $userLang);
            }
        }
    }
}
