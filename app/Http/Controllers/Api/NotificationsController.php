<?php

namespace App\Http\Controllers\Api;

use App\Helpers\JsonSanitizer;
use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\User;
use App\Models\Notification;
use App\Models\UserDetail;
use Illuminate\Http\Request;
use App\Traits\NotificationsTrait;
use App\Traits\JsonSanitizeTrait;
use Illuminate\Support\Facades\Auth;
use App\Models\AppUsageCount;
use App\Models\ExerciseCompilation;
use App\Models\ProgramSub;
use App\Models\WeeklyWorkout;
use App\Models\WeekWiseProgram;
use App\Models\WorkoutCompilation;
use App\Traits\ActivitiesTrait;
use Carbon\Carbon;
use stdClass;

class NotificationsController extends Controller
{
    use NotificationsTrait, ActivitiesTrait, JsonSanitizeTrait;
    //

    function getNotifications(){
        $adminId = User::where('role',2)->pluck('id')->first();
        // IMPORTANT: Return ALL notifications, not just the last 100
        // Remove limit to show all notifications in overview
        $notis = Notification::where('reciever',$adminId)->orderBy('id','desc')->get();
        $unreadMsgs = Notification::where('reciever',$adminId)->where('status',0)->update(['status' => 1]);
        foreach ($notis as $noti) {
            if(!is_null($noti->source)){
                $noti->userName = $noti->userName();
                $noti->userImage = $noti->userImage();
            }
            $noti->timeDiff = $noti->created_at->diffForHumans();
        }
        $returnData = new stdClass;
        $returnData->notifications = $notis;
        $returnData->unread_count = $unreadMsgs;
        return response()->json([
            'status' => true,
            'data' => $returnData,
        ]);
    }

    function getUserNotifications(){
        // Limit to last 200 to avoid huge payloads and malformed JSON from bad data
        $notis = Notification::where('reciever',Auth::id())->orderBy('id','desc')->limit(200)->get();
        Notification::where('reciever',Auth::id())->where('status',0)->update(['status' => 1]);
        $lang = $this->userSelecetdLanguage(Auth::id());
        $returnData = [];
        foreach ($notis as $noti) {
            $row = $noti->toArray();
            if(!is_null($noti->source)){
                $row['userName'] = $this->sanitizeForJson($noti->userName());
                $row['userImage'] = $this->sanitizeForJson($noti->userImage());
            } else {
                $row['userName'] = null;
                $row['userImage'] = null;
            }
            if($lang==='ar'){
                $row['title'] = $this->sanitizeForJson(is_null($noti->title_ar)?'':$noti->title_ar);
                $row['content'] = $this->sanitizeForJson(is_null($noti->content_ar)?'':$noti->content_ar);
            } else {
                $row['title'] = $this->sanitizeForJson($noti->title ?? '');
                $row['content'] = $this->sanitizeForJson($noti->content ?? '');
            }
            unset($row['content_ar']);
            unset($row['title_ar']);
            $row['timeDiff'] = $this->sanitizeForJson($noti->created_at->diffForHumans());
            $returnData[] = JsonSanitizer::sanitize($row);
        }
        return response()->json([
            'status' => true,
            'data' => $returnData
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
    }

    function autoTaggedClients(){
        $data = new stdClass;

        //clients who need help start
        $teamIds = User::where('role','!=',1)->pluck('id')->toArray();
        $data->need_help = UserDetail::where('subscription',0)->whereNotIn('user_id',$teamIds)->get(['name','Lastname','user_id','picture']);
        //clients who need help end

        //clients who need phases start
        $temp1 = ProgramSub::where('status','completed')->pluck('user_id')->toArray();   //clients who completed a program
        $temp2 = ProgramSub::where('status','!=','completed')->whereIn('user_id',$temp1)->pluck('user_id')->toArray();   //clients who have another program active
        $data->need_phases = UserDetail::whereIn('user_id',array_values(array_diff($temp1,$temp2)))->get(['name','Lastname','user_id','picture']);
        //clients who need phases end

        //clients with personal bests start
        $temp3 = array_unique(Achievement::where('created_at','>=',Carbon::today()->subWeek())->pluck('user_id')->toArray());
        $data->bests = UserDetail::whereIn('user_id',$temp3)->get(['name','Lastname','user_id','picture']);
        //clients with personal bests start

        //client program about to end start
        $subs = ProgramSub::where('status','in-progress')->orWhere('status','resumed')->get(['user_id','program_id','id','start_date','resume_date','status']);
        $temp4 = [];
        foreach ($subs as $sub) {
            $lastCompeletedWeek = WeekWiseProgram::where('program_sub_id',$sub->id)->where('status',1)->orderBy('id','desc')->pluck('id')->first()+1;
            if(is_null($lastCompeletedWeek))
            break;
            $nextWeek = WeekWiseProgram::where('program_sub_id',$sub->id)->where('id','>',$lastCompeletedWeek)->first();
            if(is_null($nextWeek))      // if no next week, then needs new
            array_push($temp4,$sub->user_id);
            // $totalWeeks = WeekWiseProgram::where('program_sub_id',$sub->id)->count();
            // $compWeeks = WeekWiseProgram::where('program_sub_id',$sub->id)->where('status',1)->count();
            // $perc = $totalWeeks===0?0:($compWeeks*100)/$totalWeeks;
            // if($perc>90)
            // array_push($temp4,$sub->user_id);
        }
        $data->phases_end = UserDetail::whereIn('user_id',$temp4)->get(['name','Lastname','user_id','picture']);
        //client program about to end end

        //client need motivation start
        $temp5 = [];
        foreach ($subs as $sub) {
            $weekIds = WeekWiseProgram::where('program_sub_id',$sub->id)->pluck('id')->toArray();     //ids of weeks
            $lastComletedDate = WeeklyWorkout::whereIn('week_id',$weekIds)->where('status',1)
            ->orderBy('done_date','desc')->pluck('done_date')->first();     // date of last completed workout
            if(is_null($lastComletedDate)){     // not completed any workout
                if($sub->status==='in-progress'){
                    if(Carbon::parse($sub->start_date)->diffInDays(Carbon::today()) > 3)    // 4 days since started
                    array_push($temp5,$sub->user_id);
                }elseif($sub->status==='resumed'){
                    if(Carbon::parse($sub->resume_date)->diffInDays(Carbon::today()) > 3)   // 4 days since resumed
                    array_push($temp5,$sub->user_id);
                }
            }
            elseif(Carbon::parse($lastComletedDate)->diffInDays(Carbon::today()) > 3)   // 4 days since last workout done
            array_push($temp5,$sub->user_id);
        }
        $data->motivation = UserDetail::whereIn('user_id',$temp5)->get(['name','Lastname','user_id','picture']);
        //client need motivation end        
        return response()->json([
            'status' => true,
            'data' => $data,
        ]); 
    }

    function businessGrowth(){
        $labels = [];
        $dataset = [];
        for ($i=0; $i<12; $i++){
            $monthName = Carbon::today()->subMonthsNoOverflow($i)->format('M y');
            $startMonth = Carbon::today()->subMonthsNoOverflow($i)->startOfMonth();
            $endMonth = Carbon::today()->subMonthsNoOverflow($i)->endOfMonth();
            $stats = User::where('role',1)->whereBetween('created_at',[$startMonth,$endMonth])->count();
            array_push($labels,$monthName);
            array_push($dataset,$stats);
            if($i===0)
            $thisMonthPayments = $stats;
            if($i===1)
            $prevMonthPayments = $stats;
        }
        $response = new stdClass;
        $response->status = true;
        $response->labels = $labels;
        $response->dataset = $dataset;
        $response->change = $thisMonthPayments>$prevMonthPayments?'inc':($thisMonthPayments<$prevMonthPayments?'dec':'no');
        if($thisMonthPayments===0 || $prevMonthPayments===0)
        $response->changeRatio = $thisMonthPayments===0?($prevMonthPayments===0?0:$prevMonthPayments):$thisMonthPayments;
        else
        $response->changeRatio = round($response->change==='inc'?$thisMonthPayments/$prevMonthPayments:$prevMonthPayments/$thisMonthPayments,2);
        return response()->json($response);
    }

    function signInsPerWeek(){
        return response()->json($this->perWeekStats('signins'));
    }

    function workoutsPerWeek(){
        return response()->json($this->perWeekStats('workouts'));
    }

    function exercisesPerWeek(){
        return response()->json($this->perWeekStats('exercises'));
    }

    function nutritionPerWeek(){
        return response()->json($this->perWeekStats('nutrition'));
    }

    function perWeekStats($type){
        $labels = [];
        $dataset = [];
        for ($i=0; $i < 7; $i++) { 
            $initialDate = Carbon::today()->subWeeks($i)->endOfWeek();
            $iniPrevDate = Carbon::today()->subWeeks($i)->startOfWeek();

            array_push($labels,$iniPrevDate->clone()->format('M d').'-'.$initialDate->clone()->format('M d'));
            if($type==='signins')
            $stats = AppUsageCount::whereBetween('created_at',[$iniPrevDate,$initialDate])->count();
            else if($type==='workouts')
            $stats = WorkoutCompilation::whereBetween('created_at',[$iniPrevDate,$initialDate])->count();
            else if($type==='exercises')
            $stats = ExerciseCompilation::whereBetween('created_at',[$iniPrevDate,$initialDate])->count();
            else 
            $stats = 0;

            array_push($dataset,$stats);
            if($i===0)
            $thisWeek = $stats;
            if($i===1)
            $prevWeek = $stats;
        }

        $response = new stdClass;
        $response->status = true;
        $response->labels = $labels;
        $response->dataset = $dataset;
        $response->change = $thisWeek>$prevWeek?'inc':($thisWeek<$prevWeek?'dec':'no');
        if($thisWeek===0 || $prevWeek===0)
        $response->changeRatio = $thisWeek===0?$prevWeek:$thisWeek;
        else
        $response->changeRatio = round($response->change==='inc'?$thisWeek/$prevWeek:$prevWeek/$thisWeek,2);

        return $response;
    }
}
