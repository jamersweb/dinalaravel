<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivitiesCategory;
use App\Models\Activity;
use App\Models\Comment;
use App\Models\Habit;
use App\Models\Meal;
use App\Models\PosturePicture;
use App\Models\ScheduledTask;
use App\Models\STask;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\UserActivities;
use App\Models\UserHabit;
use App\Models\Workout;
use Carbon\Carbon;
use stdClass;

class ActivitiesController extends Controller
{
    //
    function getActivities(){
        $adminId = User::where('role', 2)->value('id');
        if ($adminId === null) {
            return response()->json([
                'status' => true,
                'data' => [],
            ]);
        }
        // IMPORTANT: Return ALL activities, not just the last 150
        // Remove limit to show all activities in overview
        if(Auth::user()->role===2)
        $acts = Activity::where('reciever',$adminId)->orderBy('id','desc')->get();
        else
        $acts = Activity::where('reciever',$adminId)->where('category_id','!=',6)->orderBy('id','desc')->get();
        foreach ($acts as $act) {
            if(!is_null($act->source)){
                $act->userName = $act->userName();
                $act->userImage = $act->userImage();
            }
            $act->timeDiff = $act->created_at->diffForHumans();
        }
        return response()->json([
            'status' => true,
            'data' => $acts
        ]);
    }
    function getCategories(){
        if(Auth::user()->role===2)
        $cats = ActivitiesCategory::whereNotIn('id',[2,7])->get();  // temporary hiding cardios and payments
        else
        $cats = ActivitiesCategory::where('id','!=',6)->whereNotIn('id',[2,7])->get();  // temporary hiding cardios and payments
        return response()->json([
            'status' => true,
            'data' => $cats
        ]);
    }
    public function createUserActivity(Request $request){
        $validate = Validator::make($request->all(),[
			'name' => 'required',
            'time' => 'required'
		]);
		if($validate->fails()){
			return response()->json([
				'status' => false,
				'message' => $validate->errors()->all()[0]
			]);
		}
        $id = Auth::user()->id;
        $data = new UserActivities;

        $data->user_id = $id;
		$data->name = $request->name;
        if(isset($request->date)){
            $data->date = $request->date;
            $data->date_stamp = date('Y-m-d H:i:s', strtotime($request->date));
        }
        else{
            $data->date = date('d-m-Y');
            $data->date_stamp = Carbon::now()->timestamp;
        }
        if(isset($request->distance)){
            $data->distance = $request->distance;
        }
        $data->time = $request->time;

        $data->save();

        return response()->json([
			'status' => true,
			'message' => 'User activity successfully created'
		]);

    }
    public function getUserActivities(){
        $id = Auth::user()->id;
        $date = date('d-m-Y');
        $data = UserActivities::where([['user_id',$id],['date',$date]])->get();
		return response()->json([
			'status' => true,
			'data' => $data
		]);
    }


    public function deleteActivity(Request $request){
        $validate = Validator::make($request->all(),[
			'id' => 'required',
		]);
		if($validate->fails()){
			return response()->json([
				'status' => false,
				'message' => $validate->errors()->all()[0]
			]);
		}
        $data = UserActivities::find($request->id);
        if($data){
            $data->delete();
            return response()->json([
                'status' => true,
                'message' => 'Activity Deleted Successfully'
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => 'Invalid Activity ID.'
        ]);
    }

    function activityComments($id){
        $act = Activity::find($id);     // find activity
        if(is_null($act))
        return response()->json([
            'status' => false,
            'message' => 'Invalid Activity ID.'
        ]);
        $returnData = new stdClass;
        if($act->activity_type==='workout' || $act->activity_type==='photos' || $act->activity_type==='meal' || $act->activity_type==='habit'){
            $commentsFrom = User::where('role','!=',1)->pluck('id')->toArray(); //admin and team members
            array_push($commentsFrom,$act->source);     //related user
            //get comments of above users
            $returnData->comments = Comment::where('type',$act->activity_type)->where('target_id',$act->activity_target)->whereIn('user_id',$commentsFrom)->get();
            if($act->activity_type==='workout'){     // get workout detail
                $returnData->workoutDetail = Workout::find($act->activity_target);
            } else if($act->activity_type==='photos'){
                if(Auth::user()->role==2)           //get photos detail
                $returnData->photosDetail = PosturePicture::find($act->activity_target);
                else
                $returnData->photosDetail = 'Images Only Visible To Admin.';
            } else if($act->activity_type==='meal'){
                $task = STask::find($act->activity_target);     // target is task id in case of meal
                if($task){  // task exist
                    $returnData->date = Carbon::parse(ScheduledTask::where('id',$task->schedule_id)->pluck('date_stamp')->first())->format('d M, Y');   // get date of the task
                    $dayMealTasks = STask::where('schedule_id',$task->schedule_id)->where('type','meal')->get();    // get all meal task of that day
                    $tempMealIds = [];  // for meal ids
                    foreach ($dayMealTasks as $tsk) {
                        array_push($tempMealIds,$tsk->target);
                        $tsk->mealDetail = Meal::find($tsk->target);
                        $tsk->time = $tsk->updated_at->format('h:i a');
                    }       // get comments of full day meals
                    $returnData->comments = Comment::where('type','meal')->whereIn('target_id',$tempMealIds)->whereIn('user_id',$commentsFrom)->get();
                    $returnData->mealDetail = $dayMealTasks;
                }
            } else {    // rest case is habit
                $returnData->habitDetail = Habit::find($act->activity_target);
                $temp = UserHabit::where('user_id',$act->source)->where('habit_id',$act->activity_target)->first(['start_on','end_on']); // finding completion percentage
                if($temp){
                    $temp = ScheduledTask::where('user_id',$act->source)->whereBetween('date_stamp',[Carbon::parse($temp->start_on),Carbon::parse($temp->end_on)])->pluck('id')->toArray();
                    $total = STask::whereIn('schedule_id',$temp)->where('type','habit')->where('target',$act->activity_target)->count();
                    $comp = STask::whereIn('schedule_id',$temp)->where('type','habit')->where('target',$act->activity_target)->where('status',1)->count();
                    $returnData->habitPerc = $total===0?0:round(($comp*100)/$total,2);
                    $returnData->habitDate = $act->created_at->format('d M, Y');
                }
            }
            foreach ($returnData->comments as $com) {
                $com->user_name = $com->fullName();
                $com->user_image = $com->userImage();
                $com->datetime = $com->created_at->format('h:i a, d/m/y');
            }
            return response()->json([
                'status' => true,
                'data' => $returnData
            ]); 
        }
        return response()->json([
            'status' => false,
            'data' => []
        ]);
    }

    function postActivityComment(Request $request){
        $validate = Validator::make($request->all(),[
            'comments' => 'required|string',
            'activity_id' => 'required'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);

        $act = Activity::find($request->activity_id);
        if(is_null($act)) 
        return response()->json([
            'status' => false,
            'message' => 'Invalid Activity Id.'
        ]);
        if(is_null($act->activity_type))
        return response()->json([
            'status' => false,
            'message' => 'No Comments Data.'
        ]);
        if($act->activity_type==='meal')
        $mealId = STask::where('id',$act->activity_target)->pluck('target')->first();
        else
        $mealId = 0;

        $comment = new Comment();
        $comment->user_id = Auth::id();
        $comment->content = $request->comments;
        $comment->type = $act->activity_type;
        $comment->target_id = $mealId===0?$act->activity_target:$mealId;
        $comment->save();
        return response()->json([
            'status' => true,
            'message' => 'Comment Submitted.'
        ]);
    }
}
