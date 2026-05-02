<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BodyStats;
use App\Models\Habit;
use App\Models\Meal;
use App\Models\NutritionCompilance;
use App\Models\ScheduledTask;
use App\Models\STask;
use App\Models\UserBloodPressure;
use App\Models\UserCaloriesBurn;
use App\Models\UserHeartRate;
use App\Models\UserSleep;
use App\Models\UserStep;
use App\Models\Workout;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use stdClass;
use App\Traits\ActivitiesTrait;

class SchedulingController extends Controller
{
    //
    use ActivitiesTrait;
    
    function scheduleTask(Request $request){
        $validate = Validator::make($request->all(),[
			'type' => 'required|in:workout,activity,bodystat,meal',
			'date' => 'required|date',
		]);
		if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $date = Carbon::parse($request->date)->startOfDay();
        if($date<=Carbon::today())
        return response()->json([
            'status' => false,
            'message' => 'Only Future dates can be scheduled.'
        ]);
        $schid = null;
        $exists = ScheduledTask::whereDate('date_stamp',$date)->where('user_id',Auth::id())->first();
        if($exists){
            $schid = $exists->id;
        } else {
            $sch = new ScheduledTask;
            $sch->user_id = Auth::id();
            $sch->date = $request->date;
            $sch->date_stamp = $date;
            $sch->save();
            $schid = $sch->id;
        }
        $tsk = new STask();
        $tsk->schedule_id = $schid;
        $tsk->type = $request->type;
        $tsk->target = $request->target;
        // meal time for meal case
        if(isset($request->meal_at) && $request->meal_at!==null)
        $tsk->detail = $request->meal_at;
        $tsk->save();

        return response()->json([
            'status' => true,
            'message' => 'Task Scheduled'
        ]);
    }

    function addTasks(Request $request){
        $validate = Validator::make($request->all(),[
			'type' => 'required|in:workout,activity,bodystat,meal,sleep,photos,steps,rest_hr,calories_burn,blood_pressure',
			'date' => 'required|date',
		]);
		if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $date = Carbon::parse($request->date);
        if($date>Carbon::today())
        return response()->json([
            'status' => false,
            'message' => 'Cannot add task for future dates.'
        ]);
        $schid = null;
        $exists = ScheduledTask::where('user_id',Auth::id())->whereDate('date_stamp',$date)->first();
        if($exists){
            $schid = $exists->id;
        } else {
            $sch = new ScheduledTask;
            $sch->user_id = Auth::id();
            $sch->date = $request->date;
            $sch->date_stamp = $date;
            $sch->save();
            $schid = $sch->id;
        }
        $tsk = new STask();
        $tsk->schedule_id = $schid;
        $tsk->type = $request->type;
        $tsk->target = $request->target;
        // meal time for meal case
        if(isset($request->meal_at) && $request->meal_at!==null)
        $tsk->detail = $request->meal_at;
        else
        $tsk->detail = json_encode($request->detail);
        //workout not made completed default
        if($request->type==='workout')
        $tsk->status = 0;
        else
        $tsk->status = 1;
        $tsk->save();

        //add nutrition in case of meal
        $nutrition = Meal::find($request->target);
        if($nutrition)
        NutritionCompilance::create([
            'user_id' => Auth::id(),
            'meal_id' => $nutrition->id,
            'calories' => $nutrition->calories_per_serving,
            'carbs' => $nutrition->carbs_per_serving,
            'proteins' => $nutrition->protein_per_serving,
            'fats' => $nutrition->fat_per_serving,
        ]);
        
        $category = $tsk->type==='habit'?3:($tsk->type==='bodystat'?5:($tsk->type==='meal'?8:($tsk->type==='workout'?1:4)));
        if($request->type!=='photos'){
            if($request->type==='meal')
            $this->generateActivityForAdmin("Task Done","Added ".ucfirst($request->meal_at),$category,Auth::id(),'meal',$tsk->id);
            else
            $this->generateActivityForAdmin("Task Done","Completed ".$request->type." task for ".$date->format('d M, y'),$category,Auth::id());
        }
        $message = ucwords(str_replace('_',' ',$request->type));
        return response()->json([
            'status' => true,
            'message' => $message.' Task Added Successfully!'
        ]);
    }

    function taskThingDone(Request $request){
        $validate = Validator::make($request->all(),[
			'task_id' => 'required|exists:s_tasks,id',
		]);
		if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $userLang = $this->userSelecetdLanguage(Auth::id());
        $task = STask::find($request->task_id);
        if($task->status === 1)
        return response()->json([
            'status' => false,
            'message' => $userLang==='en'?config('responses.already_comp.en'):config('responses.already_comp.ar')
        ]);
        if(isset($request->detail) && $request->detail!=null)
        $task->detail = json_encode($request->detail);
        $task->status = 1;
        $task->update();

        $date = Carbon::parse(ScheduledTask::where('id',$task->schedule_id)->pluck('date_stamp')->first());
        if($task->type!=='meal_plan'){
            $title = 'Daily Task Completed.';
            // $content = Auth::user()->name.' completed '.$task->type;
            $content = "Completed ".$task->type." task for ".$date->format('d M, y');
            $category = $task->type==='habit'?3:($task->type==='bodystat'?5:($task->type==='meal'?8:($task->type==='workout'?1:4)));
            $source = Auth::id();
            if($category===3)
            $this->generateActivityForAdmin($title,$content,$category,$source,'habit',$task->target);
            else if($category===8){
                $nutrition = Meal::find($task->target);
                if($nutrition)
                NutritionCompilance::create([
                    'user_id' => Auth::id(),
                    'meal_id' => $nutrition->id,
                    'calories' => $nutrition->calories_per_serving,
                    'carbs' => $nutrition->carbs_per_serving,
                    'proteins' => $nutrition->protein_per_serving,
                    'fats' => $nutrition->fat_per_serving,
                ]);
                $this->generateActivityForAdmin($title,'Added '.ucfirst($task->detail),$category,$source,'meal',$task->id);
            }
            else
            $this->generateActivityForAdmin($title,$content,$category,$source);
        }
        return response()->json([
            'status' => true,
            'message' => $userLang==='en'?config('responses.tsk_comp.en'):config('responses.tsk_comp.ar')
        ]);
    }

    function thingsToDo(){
        $schedules = ScheduledTask::where('user_id',Auth::id())->with('tasks')->orderBy('date_stamp','asc')->get();
        foreach ($schedules as $item) {
            foreach ($item->tasks as $item2) {
                if($item2->type==='habit')
                $name = Habit::where('id',$item2->target)->pluck('title')->first();
                else if($item2->type==='meal' || $item2->type==='meal_plan')
                $name = Meal::where('id',$item2->target)->pluck('name')->first();
                else if($item2->type==='workout')
                $name = Workout::where('id',$item2->target)->pluck('title')->first();
                else
                $name = "";
                $item2->target_name = $name;
            }
        }
        return response()->json([
            'status' => true,
            'data' => $schedules
        ]);
    }
    function getTask(Request $request){
        $validate = Validator::make($request->all(),[
			'id' => 'required',
		]);
		if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $data = STask::where('id',$request->id)->first();
        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }
    function updateTask(Request $request){
        $validate = Validator::make($request->all(),[
			'id' => 'required|exists:s_tasks,id',
			'detail' => 'required',
		]);
		if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $data = STask::where('id',$request->id)->first();
        $data->detail = json_encode($request->detail);
        $data->status = 1;
        $data->update();
        return response()->json([
            'status' => true,
            'message' => 'Task updated Successfully'
        ]);
    }

    function monthData(){
        $userLang = $this->userSelecetdLanguage(Auth::id());
        $data  = new stdClass;
        $data->today = [];
        $data->week = [];
        $data->month = [];
        $now = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        $types = ['activity','workout','bodystat','meal'];
        for ($i=0; $i < 31 ; $i++) {
            if($now>$endOfMonth){
                break;
            }
            $temp = new stdClass;
            $temp->date_stamp = $now;
            if($userLang==='en'){
                $temp->date = $now->format('d M, Y');
                $temp->day = $now->format('l');
            } else {
                $temp->date = $now->clone()->locale('ar')->translatedFormat('M d، Y');
                $temp->day = $now->clone()->locale('ar')->translatedFormat('l');
            }
            $dateTasksId = ScheduledTask::where('user_id',Auth::id())->whereDate('date_stamp',$temp->date_stamp)->pluck('id')->first();
            $temp->tasks = STask::where('schedule_id',$dateTasksId)->whereIn('type',$types)->get()->groupBy('type');
            if(count($temp->tasks)===0){
                $temp->tasks = [
                    "activity" => [],
                    "workout" => [],
                    "bodystat" => [],
                    "meal" => []
                ];
            } else {
                if(!isset($temp->tasks['activity']))
                $temp->tasks['activity'] = [];
                if(!isset($temp->tasks['workout'])){
                    $temp->tasks['workout'] = [];
                }else{
                    foreach ($temp->tasks['workout'] as $item) {
                        $item->workout_name = Workout::where('id',$item->target)->pluck('title')->first();
                    }
                }
                if(!isset($temp->tasks['bodystat']))
                $temp->tasks['bodystat'] = [];
                if(!isset($temp->tasks['meal']))
                $temp->tasks['meal'] = [];
            }
            array_push($data->month,$temp);
            if($now == Carbon::today()){
                array_push($data->today,$temp);
            }
            if($now >= $startOfWeek && $now <= $endOfWeek){
                array_push($data->week,$temp);
            }
            $now->addDays(1);
        }
        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    /**
     * Rolling 30-day window ending today (PDF: workout history for past 30 days).
     * Same payload shape as monthData so the mobile ScheduleActivitiesResponse parser works.
     */
    public function last30DaysTasks()
    {
        $userLang = $this->userSelecetdLanguage(Auth::id());
        $data = new stdClass;
        $data->today = [];
        $data->week = [];
        $data->month = [];
        $types = ['activity', 'workout', 'bodystat', 'meal'];
        $start = Carbon::today()->subDays(29)->startOfDay();
        $end = Carbon::today()->startOfDay();

        for ($d = $start->copy(); $d <= $end; $d->addDay()) {
            $temp = new stdClass;
            $temp->date_stamp = $d->copy();
            if ($userLang === 'en') {
                $temp->date = $d->format('d M, Y');
                $temp->day = $d->format('l');
            } else {
                $temp->date = $d->clone()->locale('ar')->translatedFormat('M d، Y');
                $temp->day = $d->clone()->locale('ar')->translatedFormat('l');
            }
            $dateTasksId = ScheduledTask::where('user_id', Auth::id())->whereDate('date_stamp', $temp->date_stamp)->pluck('id')->first();
            $temp->tasks = STask::where('schedule_id', $dateTasksId)->whereIn('type', $types)->get()->groupBy('type');
            if (count($temp->tasks) === 0) {
                $temp->tasks = [
                    'activity' => [],
                    'workout' => [],
                    'bodystat' => [],
                    'meal' => [],
                ];
            } else {
                if (! isset($temp->tasks['activity'])) {
                    $temp->tasks['activity'] = [];
                }
                if (! isset($temp->tasks['workout'])) {
                    $temp->tasks['workout'] = [];
                } else {
                    foreach ($temp->tasks['workout'] as $item) {
                        $item->workout_name = Workout::where('id', $item->target)->pluck('title')->first();
                    }
                }
                if (! isset($temp->tasks['bodystat'])) {
                    $temp->tasks['bodystat'] = [];
                }
                if (! isset($temp->tasks['meal'])) {
                    $temp->tasks['meal'] = [];
                }
            }
            array_push($data->month, $temp);
        }

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }

    function deleteScheduledTasks($id){
        $scheduleTask = STask::where('id',$id)->first();
        if($scheduleTask){
            if($scheduleTask->type == 'meal'){
                $meal = Meal::where([
                    ['id',$scheduleTask->target,],
                    ['user_id',Auth::id()]
                ])->delete();
            }
            elseif($scheduleTask->type == 'workout'){
                $workout = Workout::where([
                    ['id',$scheduleTask->target,],
                    ['user_id',Auth::id()]
                ])->delete();
            }
            elseif($scheduleTask->type == 'bodystat'){
                $bodystat = BodyStats::where([
                    ['id',$scheduleTask->target,],
                    ['user_id',Auth::id()]
                ])->delete();
            }elseif($scheduleTask->type == 'sleep'){
                $bodystat = UserSleep::where([
                    ['id',$scheduleTask->target,],
                    ['user_id',Auth::id()]
                ])->delete();
            }elseif($scheduleTask->type == 'steps'){
                $bodystat = UserStep::where([
                    ['id',$scheduleTask->target,],
                    ['user_id',Auth::id()]
                ])->delete();
            }elseif($scheduleTask->type == 'rest_hr'){
                $bodystat = UserHeartRate::where([
                    ['id',$scheduleTask->target,],
                    ['user_id',Auth::id()]
                ])->delete();
            }elseif($scheduleTask->type == 'calories_burn'){
                $bodystat = UserCaloriesBurn::where([
                    ['id',$scheduleTask->target,],
                    ['user_id',Auth::id()]
                ])->delete();
            }elseif($scheduleTask->type == 'blood_pressure'){
                $bodystat = UserBloodPressure::where([
                    ['id',$scheduleTask->target,],
                    ['user_id',Auth::id()]
                ])->delete();
            }
            $scheduleTask->delete();
            return response()->json([
                'status' => true,
                'message' => 'Scheduled Task Deleted'
            ]);
        }else{
            return response()->json([
                'status' => false,
                'message' => 'Scheduled Task Not Found'
            ]);
        }
    }
}
