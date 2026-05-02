<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Habit;
use App\Models\HabitFolder;
use App\Models\ScheduledTask;
use App\Models\STask;
use App\Models\User;
use App\Models\UserHabit;
use App\Traits\ActivitiesTrait;
use App\Traits\NotificationsTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use stdClass;

class HabitsController extends Controller
{
    use NotificationsTrait,ActivitiesTrait;
    //

    function createFolder(Request $request){
        $validate = Validator::make($request->all(),[
			'name' => 'required|string'
		]);
        if($validate->fails()){
			return response()->json([
				'status' => false,
				'message' => $validate->errors()->all()[0]
			]);
		}
        $habFold = new HabitFolder();
        $habFold->name = $request->name;
        $habFold->save();
        return response()->json([
            'status' => true,
            'message' => 'Folder Created Successfully.'
        ]);
    }

    function renameFolder(Request $request){
        $validate = Validator::make($request->all(),[
			'new_name' => 'required|string',
            'folder_id' => 'required|numeric|exists:habit_folders,id'
		]);
        if($validate->fails()){
			return response()->json([
				'status' => false,
				'message' => $validate->errors()->all()[0]
			]);
		}
        $habFold = HabitFolder::find($request->folder_id);
        $habFold->name = $request->new_name;
        $habFold->update();
        return response()->json([
            'status' => true,
            'message' => 'Folder Renamed Successfully.'
        ]);
    }

    function deleteFolder(Request $request){
        $validate = Validator::make($request->all(),[
            'folder_id' => 'required|numeric|exists:habit_folders,id'
		]);
        if($validate->fails()){
			return response()->json([
				'status' => false,
				'message' => $validate->errors()->all()[0]
			]);
		}
        HabitFolder::destroy($request->folder_id);
        return response()->json([
            'status' => true,
            'message' => 'Folder Deleted Successfully.'
        ]);
    }

    function getFolders(){
        $habFolds = HabitFolder::get();
        return response()->json([
            'status' => true,
            'data' => $habFolds
        ]);
    }

    function createHabit(Request $request){
        $validate = Validator::make($request->all(),[
            'folder_id' => 'required|numeric|exists:habit_folders,id',
            'title' => 'required|string',
            'content' => 'required|string'
		]);
        if($validate->fails()){
			return response()->json([
				'status' => false,
				'message' => $validate->errors()->all()[0]
			]);
		}
        $habit = new Habit();
        $habit->title = $request->title;
        $habit->content = $request->content;
        $habit->habit_folder_id = $request->folder_id;
        $habit->save();
        return response()->json([
            'status' => true,
            'message' => 'Habit Added Successfully'
        ]);
    }

    function editHabit(Request $request){
        $validate = Validator::make($request->all(),[
            'id' => 'required|numeric|exists:habits',
            'folder_id' => 'required|numeric|exists:habit_folders,id',
            'title' => 'required|string',
            'content' => 'required|string'
		]);
        if($validate->fails()){
			return response()->json([
				'status' => false,
				'message' => $validate->errors()->all()[0]
			]);
		}
        $habit = Habit::find($request->id);
        $habit->title = $request->title;
        $habit->content = $request->content;
        $habit->habit_folder_id = $request->folder_id;
        $habit->update();
        return response()->json([
            'status' => true,
            'message' => 'Habit Updated Successfully'
        ]);
    }

    function getFolderHabits($id){
        $habits = Habit::where('habit_folder_id',$id)->get(['id','title','created_at']);
        if($habits){
            foreach($habits as $habit){
                $habit->date = $habit->created_at->format('D d M, y');
            }
            return response()->json([
                'status' => true,
                'data' => $habits
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => 'Invalid or Empty Folder.'
        ]);
    }

    function deleteHabit(Request $request){
        $validate = Validator::make($request->all(),[
            'ids' => 'required|array',
		]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        Habit::destroy($request->ids);
        return response()->json([
            'status' => true,
            'message' => 'Habit Deleted Successfully'
        ]);
    }
    function getHabitDetail($id){
        $habit = Habit::where('id',$id)->first();

        return response()->json([
            'status' => true,
            'data' => $habit
        ]);
    }
    public function renameHabitFolder(Request $request){
        $validate = Validator::make($request->all(),[
            'id' => 'required',
            'name' => 'required'
		]);
        if($validate->fails()){
			return response()->json([
				'status' => false,
				'message' => $validate->errors()->all()[0]
			]);
		}
        $habit = HabitFolder::where('id',$request->id)->first();
        if($habit){
            $habit->name = $request->name;
            $habit->update();
            return response()->json([
                'status' => true,
                'message' => 'Folder Renamed'
            ]);
        }
        else{
            return response()->json([
                'status' => false,
                'message' => 'Folder Not Found'
            ]);
        }
    }

    function addUsersHabit(Request $request){
        $validate = Validator::make($request->all(),[
            'habit_id' => 'required|exists:habits,id',
            'user_ids' => 'required|array',
            'weeks' => 'required|numeric|between:1,100',
            'week_days' => 'required|array',
            'start_date' => 'required|date'
		]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = $startDate->clone()->addWeeks($request->weeks);
        $i = 0;
        $dates = [];
        $loopDate = $startDate->clone()->addDays($i);
        while ($loopDate<$endDate) {
            if(in_array($loopDate->clone()->format('l'),$request->week_days))
            array_push($dates,$loopDate);
            $i++;
            $loopDate = $startDate->clone()->addDays($i);
        }
        $habitName = strtoupper(Habit::where('id',$request->habit_id)->pluck('title')->first());
        $adminName = strtoupper(Auth::user()->name);
        $notiTitle = 'Habit Assigned!';
        $notiTitleAr = 'تم تعيين عادة ';
        $notiContent = $adminName.' assigned you '.$habitName.' habit Starting from '.$startDate->format('d-M-y');
        $notiContentAr = $adminName.' عيّن '.$habitName.' ابتداءا من '.$startDate->format('d-M-y');
        $notiRecievers = [];
        $notiRecieversAr = [];
        foreach ($request->user_ids as $id) {
            $user = User::where('id',$id)->where('role',1)->first(['id','fcm_token']);
            if($user){
                // $alreadyHabit = UserHabit::where('user_id',$id)->where('habit_id',$request->habit_id)->whereDate('end_on','>=',$startDate)->first();
                $alreadyHabit = UserHabit::where('user_id',$id)->where('habit_id',$request->habit_id)->first();
                if(is_null($alreadyHabit)){
                    $newUserHabit = new UserHabit();
                    $newUserHabit->user_id = $id;
                    $newUserHabit->habit_id = $request->habit_id;
                    $newUserHabit->start_on = $startDate;
                    $newUserHabit->duration_weeks = $request->weeks;
                    $newUserHabit->end_on = $endDate;
                    $newUserHabit->practice_days = json_encode($request->week_days);
                    $newUserHabit->save();

                    foreach ($dates as $date) {
                        $schedule = ScheduledTask::where('user_id',$id)->whereDate('date_stamp',$date)->first();
                        if(is_null($schedule)){
                            $schedule = new ScheduledTask();
                            $schedule->user_id = $id;
                            $schedule->date = $date->format('d-m-Y');
                            $schedule->date_stamp = $date;
                            $schedule->save();
                        }
                        $sTask = new STask();
                        $sTask->schedule_id = $schedule->id;
                        $sTask->type = 'habit';
                        $sTask->target = $request->habit_id;
                        $sTask->save();
                    }
                } else {
                    $currentDays = json_decode($alreadyHabit->practice_days);   // change practice days as updated
                    $newDays = $request->week_days;
                    foreach($newDays as $newDay){
                        if(!in_array($newDay,$currentDays))
                        array_push($currentDays,$newDay);
                    }
                    if($endDate > $alreadyHabit->end_on)
                    $alreadyHabit->end_on = $endDate;
                    $alreadyHabit->practice_days = json_encode($currentDays);
                    $alreadyHabit->update();                // update ending date of habit
                    foreach ($dates as $date) {
                        $schedule = ScheduledTask::where('user_id',$id)->whereDate('date_stamp',$date)->first();
                        if(is_null($schedule)){
                            $schedule = new ScheduledTask();
                            $schedule->user_id = $id;
                            $schedule->date = $date->format('d-m-Y');
                            $schedule->date_stamp = $date;
                            $schedule->save();
                        }
                        $habitExists = STask::where('schedule_id',$schedule->id)->where('type','habit')->where('target',$request->habit_id)->first();
                        if(is_null($habitExists)){
                            $sTask = new STask();
                            $sTask->schedule_id = $schedule->id;
                            $sTask->type = 'habit';
                            $sTask->target = $request->habit_id;
                            $sTask->save();
                        }
                    }
                }
                $this->storeNotification($id,$notiTitle,$notiTitleAr,$notiContent,$notiContentAr);
                if($this->userSelecetdLanguage($id)==='en')
                array_push($notiRecievers,$user->fcm_token);
                else 
                array_push($notiRecieversAr,$user->fcm_token);
                
            }
        }
        if(count($notiRecievers)>0)
        $this->sendFirebaseNotification($notiRecievers,$notiTitle,$notiContent);
        if(count($notiRecieversAr)>0)
        $this->sendFirebaseNotification($notiRecieversAr,$notiTitleAr,$notiContentAr);
        return response()->json([
            'status' => true,
            'message' => 'Habit added to Users'
        ]);
    }

    function allFoldersHabits(){
        $folders = HabitFolder::with('habits')->get(['id','name']);
        return response()->json([
            'status' => true,
            'data' => $folders
        ]);
    }

    function moveHabitToFolder(Request $request){
        $validate = Validator::make($request->all(),[
            'habit_ids' => 'required|array',
            'folder_id' => 'required|exists:habit_folders,id'
		]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        foreach ($request->habit_ids as $habit) {
            $habit = Habit::find($habit);
            if(!is_null($habit)){
                $habit->habit_folder_id = $request->folder_id;
                $habit->update();
            }
        }
        return response()->json([
            'status' => true,
            'message' => 'Habits Moved Successfully.'
        ]);
    }

    function habitTaskDetail($id){
        $task = STask::find($id);
        if(is_null($task))
        return response()->json([
            'status' => false,
            'message' => 'Invalid Task ID.'
        ]);
        if($task->type!='habit')
        return response()->json([
            'status' => false,
            'message' => 'Invalid Task Given.'
        ]);

        $habit = Habit::where('id',$task->target)->first(['id','title','content']);

        $response = new stdClass;
        $response->status = $task->status;
        $response->name = $habit->title;
        $response->detail = $habit->content;

        return response()->json([
            'status' => true,
            'data' => $response
        ]);
    }

    function myHabitsList(){
        $habits = UserHabit::where('user_id',Auth::id())->get(['id','start_on','end_on','practice_days','habit_id']);
        foreach ($habits as $habit) {
            $habit->name = $habit->habitName();
            $habit->practice_days = json_decode($habit->practice_days);
        }
        return response()->json([
            'status' => true,
            'data' => $habits
        ]);
    }

    function habitTaskProgress($id){
        $userHabit = UserHabit::find($id);
        // $userHabit = UserHabit::where('user_id',Auth::id())->where('habit_id',$id)->first();
        if(is_null($userHabit))
        return response()->json([
            'status' => true,
            'message' => 'Invalid Habit ID Given.'
        ]);
        $calendarData = [];
        $schedules = ScheduledTask::where('user_id',Auth::id())
        ->whereBetween('date_stamp',[$userHabit->start_on,$userHabit->end_on])
        ->orderBy('date_stamp','asc')->get(['id','date_stamp']);
        foreach ($schedules as $item) {
            $habitTask = STask::where('schedule_id',$item->id)->where('type','habit')->where('target',$userHabit->habit_id)->pluck('status')->first();
            if($habitTask!==null){
                $temp = new stdClass;
                $temp->date = Carbon::parse($item->date_stamp)->format('Ymd');
                $temp->status = $habitTask;
                array_push($calendarData,$temp);
            }
        }
        $monthlyProgress = [];
        if(count($schedules)>0){
            $continue = true;
            $monthCount = 0;
            while ($continue) {
                $rangeStartDate = Carbon::parse($schedules[0]->date_stamp)->startOfMonth()->addMonths($monthCount);
                $rangeEndDate = Carbon::parse($schedules[0]->date_stamp)->endOfMonth()->addMonths($monthCount);
                if($rangeStartDate<$userHabit->end_on){
                    $temp = new stdClass;
                    $temp->month = $rangeStartDate->format('d-m-y');
                    $schedules2 = ScheduledTask::where('user_id',Auth::id())
                    ->whereBetween('date_stamp',[$rangeStartDate,$rangeEndDate])->pluck('id')->toArray();
                    $temp->days_total = STask::whereIn('schedule_id',$schedules2)->where('type','habit')
                    ->where('target',$userHabit->habit_id)->count();
                    $temp->days_done = STask::whereIn('schedule_id',$schedules2)->where('type','habit')
                    ->where('target',$userHabit->habit_id)->where('status',1)->count();
                    array_push($monthlyProgress,$temp);
                    $temp->comp_perc = $temp->days_total<1?0:round(($temp->days_done*100)/$temp->days_total,2);
                    $monthCount++;
                } else {
                    $continue = false;
                }
            }
        }
        $returnData = new stdClass;
        $returnData->calendar_data = $calendarData;
        $returnData->monthly_progress = $monthlyProgress;
        return response()->json([
            'status' => true,
            'data' => $returnData
        ]);
    }
}
