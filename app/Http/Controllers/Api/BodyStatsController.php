<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserStep;
use App\Models\UserSleep;
use App\Models\UserCaloriesBurn;
use App\Models\UserHeartRate;
use App\Models\UserBloodPressure;
use App\Models\BodyStats;
use App\Models\BodyMeasurement;
use App\Models\UserSetting;
use App\Models\UserDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use stdClass;
use Carbon\Carbon;
use App\Traits\ActivitiesTrait;
use App\Traits\ApiResponse;

class BodyStatsController extends Controller
{
    use ActivitiesTrait, ApiResponse;
    function setStepsData(Request $request){ 
        $validate = Validator::make($request->all(),[
            'steps' => 'required|numeric',
            'distance' => 'numeric|nullable',
            'calories' => 'numeric|nullable',
            'time' => 'numeric|nullable',
            'date' => 'required'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $today = $request->date;
        $date_stamp = date('Y-m-d H:i:s', strtotime($today));

        $record = UserStep::where('date',$date_stamp)->where('user_id',Auth::id())->first();
        if($record){
            $record->steps = $request->steps+(int)$record->steps;
            $record->distance = $request->distance+(int)$record->distance;
            $record->burn_calories = $request->calories+(int)$record->burn_calories;
            $record->time = $request->time+(int)$record->time;
            $record->update();
            return response()->json([
                'status' => true,
                'message' => 'Steps Added Successfully!',
                'refernce' => $record->id,
                'task_api' => false
            ]);
        }

        $data = new UserStep();
        $data->steps = $request->steps;
        if(isset($request->distance)){
            $data->distance = $request->distance;   
        }
        if(isset($request->calories)){
            $data->burn_calories = $request->calories;
        }
        if(isset($request->time)){
            $data->time = $request->time;
        }
        $data->date = $date_stamp;
        $data->user_id = Auth::id();
        $data->unit = UserSetting::where('user_id',Auth::id())->pluck('distance_unit')->first();
        $data->save();

        $title = 'Added Steps Data!';
        $content = Auth::user()->name.' walked '.$request->steps.' steps or '.$request->distance. ' on '.$today;
        $category = 5;
        $source = Auth::id();
        $this->generateActivityForAdmin($title,$content,$category,$source);

        return response()->json([
            'status' => true,
            'message' => 'Steps Added Successfully!',
            'refernce' => $data->id,
            'task_api' => true
        ]);
    }
    function setSleepData(Request $request){
        $validate = Validator::make($request->all(),[
            'bed_time' => 'required',
            'bed_date' => 'required',
            'wakeup_time' => 'required',
            'wakeup_date' => 'required',
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $t1 = date('Y-m-d', strtotime($request->bed_date));
        $t2 = date('Y-m-d', strtotime($request->wakeup_date));
        if($t2>=$t1){
            
        }else{
            return response()->json([
                'status' => false,
                'message' => 'Please choose right Date'
            ]);
        }
        $bedTimeStamp= date('Y-m-d H:i:s', strtotime($request->bed_date.' '.$request->bed_time));
        $wakeTimeStamp= date('Y-m-d H:i:s', strtotime($request->wakeup_date.' '.$request->wakeup_time));
        $ts1 = strtotime($bedTimeStamp);
        $ts2 = strtotime($wakeTimeStamp);     
        $seconds_diff = $ts2 - $ts1;
        if($seconds_diff<0){
            return response()->json([
                'status' => false,
                'message' => 'Please choose greater wakeup time'
            ]);
        }
        $hours = floor($seconds_diff/3600);
        $minutes = floor($seconds_diff/60);
        $remainingMinutes = $minutes - ($hours*60);
        $sleepTime = $hours.':'.sprintf("%02d", $remainingMinutes);


        $percent = ($seconds_diff/3600)/8;
        $percent = round($percent*100,2);



        $today = $request->bed_date;
        $date_stamp = date('Y-m-d H:i:s', strtotime($request->bed_date));

        $record = UserSleep::where('date',$date_stamp)->where('user_id',Auth::id())->first();
        if($record){
            $record->bed_time = $request->bed_time;
            $record->bed_date = $request->bed_date;
            $record->wakeup_time = $request->wakeup_time;
            $record->wakeup_date = $request->wakeup_date;
            $record->sleep_time = $sleepTime;
            $record->sleep_percent = $percent;
            $record->update();
            return response()->json([
                'status' => true,
                'message' => 'Sleep Added Successfully!',
                'refernce' => $record->id,
                'task_api' => false
            ]);
        }


        $newData = new UserSleep();
        $newData->date = $date_stamp;
        $newData->bed_time = $request->bed_time;
        $newData->bed_date = $request->bed_date;
        $newData->wakeup_time = $request->wakeup_time;
        $newData->wakeup_date = $request->wakeup_date;
        $newData->sleep_time = $sleepTime;
        $newData->sleep_percent = $percent;
        $newData->user_id = Auth::id();
        $newData->save();
        
        $title = 'Added Sleep Data!';
        $content = Auth::user()->name.' slept for '.$sleepTime.' hours on '.$today;
        $category = 5;
        $source = Auth::id();
        $this->generateActivityForAdmin($title,$content,$category,$source);
        return response()->json([
            'status' => true,
            'message' => 'Sleep Added Successfully!',
            'refernce' => $newData->id,
            'task_api' => true
        ]);
    }
    function setCaloriesBurn(Request $request){
        $validate = Validator::make($request->all(),[
            'carbs' => 'numeric|nullable',
            'proteins' => 'numeric|nullable',
            'fats' => 'numeric|nullable',
            'total' => 'required|numeric',
            'date' => 'required'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);

        $today = $request->date;
        $date_stamp = date('Y-m-d H:i:s', strtotime($today));

        $record = UserCaloriesBurn::where('date',$date_stamp)->where('user_id',Auth::id())->first();
        if($record){
            if(!is_null($request->carbs))
            $record->carbs = $request->carbs + $record->carbs;
            if(!is_null($request->proteins))
            $record->proteins = $request->proteins + $record->proteins;
            if(!is_null($request->fats))
            $record->fats = $request->fats + $record->fats;
            $record->total = $request->total + $record->total;
            $record->update();
            return response()->json([
                'status' => true,
                'message' => 'Calories Burn Added Successfully!',
                'refernce' => $record->id,
                'task_api' => false
            ]);
        }

        $newData = new UserCaloriesBurn();
        $newData->carbs = $request->carbs;
        $newData->proteins = $request->proteins;
        $newData->fats = $request->fats;
        $newData->total = $request->total;
        $newData->date = $date_stamp;
        $newData->user_id = Auth::id();
        $newData->save();
        return response()->json([
            'status' => true,
            'message' => 'Calories Burn Added Successfully!',
            'refernce' => $newData->id,
            'task_api' => true
        ]);
    }
    function setRestingHeartRate(Request $request){
        $validate = Validator::make($request->all(),[
            'min' => 'numeric|nullable',
            'max' => 'numeric|nullable',
            'average' => 'required|numeric',
            'date' => 'required'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);

        $today = $request->date;
        $date_stamp = date('Y-m-d H:i:s', strtotime($today));

        $record = UserHeartRate::where('date',$date_stamp)->where('user_id',Auth::id())->first();
        if($record){
            if(!is_null($request->min))
            $record->min = $request->min;
            if(!is_null($request->max))
            $record->max = $request->max;
            $record->average = $request->average;
            $record->update();
            return response()->json([
                'status' => true,
                'message' => 'Resting Heart Rate Added Successfully!',
                'refernce' => $record->id,
                'task_api' => false
            ]);
        }

        $newData = new UserHeartRate();
        $newData->min = $request->min;
        $newData->max = $request->max;
        $newData->average = $request->average;
        $newData->date = $date_stamp;
        $newData->user_id = Auth::id();
        $newData->save();
        return response()->json([
            'status' => true,
            'message' => 'Resting Heart Rate Added Successfully!',
            'refernce' => $newData->id,
            'task_api' => true
        ]);
    }

    function setBloodPressure(Request $request){
        $validate = Validator::make($request->all(),[
            'sys' => 'required|numeric',
            'dia' => 'required|numeric',
            'date' => 'required'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);

        $today = $request->date;
        $date_stamp = date('Y-m-d H:i:s', strtotime($today));

        $record = UserBloodPressure::where('date',$date_stamp)->where('user_id',Auth::id())->first();
        if($record){
            $record->sys = $request->sys;
            $record->dia = $request->dia;
            $record->update();
            return response()->json([
                'status' => true,
                'message' => 'Blood Pressure Added Successfully!',
                'refernce' => $record->id,
                'task_api' => false
            ]);
        }

        $newData = new UserBloodPressure();
        $newData->sys = $request->sys;
        $newData->dia = $request->dia;
        $newData->date = $date_stamp;
        $newData->user_id = Auth::id();
        $newData->save();
        return response()->json([
            'status' => true,
            'message' => 'Blood Pressure Added Successfully!',
            'refernce' => $newData->id,
            'task_api' => true
        ]);
    }
    function setWeight(Request $request){
        $validate = Validator::make($request->all(),[
            'date' => 'required',
            'weight' => 'nullable|numeric',
            'fat' => 'numeric|nullable',
            'fat_mass' => 'numeric|nullable',
            'lean_body_mass' => 'numeric|nullable',
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $today = $request->date;
        $date_stamp = date('Y-m-d H:i:s', strtotime($today));

        $record = BodyStats::where('date',$date_stamp)->where('user_id',Auth::id())->first();
        if($record){
            if(!is_null($request->weight));
            $record->weight = $request->weight;
            if(!is_null($request->fat))
            $record->fat = $request->fat;
            if(!is_null($request->caliper_body_fat))
            $record->caliper_body_fat = $request->caliper_body_fat;
            if(!is_null($request->fat_mass))
            $record->fat_mass = $request->fat_mass;
            if(!is_null($request->lean_body_mass))
            $record->lean_body_mass = $request->lean_body_mass;
            $record->update();
            $measurement = BodyMeasurement::where('date',$date_stamp)->where('user_id',Auth::id())->first();
            if($measurement){
                $temp = 0;
                if(isset($request->shoulders)){
                    $measurement->shoulders = $request->shoulders;
                    $temp = 1;
                }
                if(isset($request->neck)){
                    $measurement->neck = $request->neck;
                    $temp = 1;
                }
                if(isset($request->chest)){
                    $measurement->chest = $request->chest;
                    $temp = 1;
                }
                if(isset($request->left_bicep)){
                    $measurement->left_bicep = $request->left_bicep;
                    $temp = 1;
                }
                if(isset($request->right_bicep)){
                    $measurement->right_bicep = $request->right_bicep;
                    $temp = 1;
                }
                if(isset($request->left_forearm)){
                    $measurement->left_forearm = $request->left_forearm;
                    $temp = 1;
                }
                if(isset($request->right_forearm)){
                    $measurement->right_forearm = $request->right_forearm;
                    $temp = 1;
                }
                if(isset($request->waist)){
                    $measurement->waist = $request->waist;
                    $temp = 1;
                }
                if(isset($request->hips)){
                    $measurement->hips = $request->hips;
                    $temp = 1;
                }
                if(isset($request->left_thigh)){
                    $measurement->left_thigh = $request->left_thigh;
                    $temp = 1;
                }
                if(isset($request->right_thigh)){
                    $measurement->right_thigh = $request->right_thigh;
                    $temp = 1;
                }
                if(isset($request->left_calf)){
                    $measurement->left_calf = $request->left_calf;
                    $temp = 1;
                }
                if(isset($request->right_calf)){
                    $measurement->right_calf = $request->right_calf;
                    $temp = 1;
                }
                if(isset($request->belly_button)){
                    $measurement->belly_button = $request->belly_button;
                    $temp = 1;
                }
                if(isset($request->under_belly)){
                    $measurement->under_belly = $request->under_belly;
                    $temp = 1;
                }
                if($temp == 1){
                    $measurement->unit = UserSetting::where('user_id',Auth::id())->pluck('body_measures')->first();
                    $measurement->update();
                }
            }else{
                $measurement = new BodyMeasurement;
                $temp = 0;
                if(isset($request->shoulders)){
                    $measurement->shoulders = $request->shoulders;
                    $temp = 1;
                }
                if(isset($request->neck)){
                    $measurement->neck = $request->neck;
                    $temp = 1;
                }
                if(isset($request->chest)){
                    $measurement->chest = $request->chest;
                    $temp = 1;
                }
                if(isset($request->left_bicep)){
                    $measurement->left_bicep = $request->left_bicep;
                    $temp = 1;
                }
                if(isset($request->right_bicep)){
                    $measurement->right_bicep = $request->right_bicep;
                    $temp = 1;
                }
                if(isset($request->left_forearm)){
                    $measurement->left_forearm = $request->left_forearm;
                    $temp = 1;
                }
                if(isset($request->right_forearm)){
                    $measurement->right_forearm = $request->right_forearm;
                    $temp = 1;
                }
                if(isset($request->waist)){
                    $measurement->waist = $request->waist;
                    $temp = 1;
                }
                if(isset($request->hips)){
                    $measurement->hips = $request->hips;
                    $temp = 1;
                }
                if(isset($request->left_thigh)){
                    $measurement->left_thigh = $request->left_thigh;
                    $temp = 1;
                }
                if(isset($request->right_thigh)){
                    $measurement->right_thigh = $request->right_thigh;
                    $temp = 1;
                }
                if(isset($request->left_calf)){
                    $measurement->left_calf = $request->left_calf;
                    $temp = 1;
                }
                if(isset($request->right_calf)){
                    $measurement->right_calf = $request->right_calf;
                    $temp = 1;
                }
                if(isset($request->belly_button)){
                    $measurement->belly_button = $request->belly_button;
                    $temp = 1;
                }
                if(isset($request->under_belly)){
                    $measurement->under_belly = $request->under_belly;
                    $temp = 1;
                }
                if($temp == 1){
                    $measurement->user_id = Auth::id();
                    $measurement->unit = UserSetting::where('user_id',Auth::id())->pluck('body_measures')->first();
                    $measurement->date = $date_stamp;
                    $measurement->save();
                }
            }
            return response()->json([
                'status' => true,
                'message' => 'Body Stats Added Successfully!',
                'refernce' => $record->id,
                'task_api' => false
            ]);
        }

        $data = new BodyStats;
        $data->weight = $request->weight;
        $data->fat = $request->fat;
        $data->caliper_body_fat = $request->caliper_body_fat;
        $data->fat_mass = $request->fat_mass;
        $data->lean_body_mass = $request->lean_body_mass;
        $data->date = $date_stamp;
        $data->unit = UserSetting::where('user_id',Auth::id())->pluck('weight_unit')->first();
        $data->user_id = Auth::id();
        $data->save();

        $measurement = new BodyMeasurement;
        $temp = 0;
        if(isset($request->shoulders)){
            $measurement->shoulders = $request->shoulders;
            $temp = 1;
        }
        if(isset($request->neck)){
            $measurement->neck = $request->neck;
            $temp = 1;
        }
        if(isset($request->chest)){
            $measurement->chest = $request->chest;
            $temp = 1;
        }
        if(isset($request->left_bicep)){
            $measurement->left_bicep = $request->left_bicep;
            $temp = 1;
        }
        if(isset($request->right_bicep)){
            $measurement->right_bicep = $request->right_bicep;
            $temp = 1;
        }
        if(isset($request->left_forearm)){
            $measurement->left_forearm = $request->left_forearm;
            $temp = 1;
        }
        if(isset($request->right_forearm)){
            $measurement->right_forearm = $request->right_forearm;
            $temp = 1;
        }
        if(isset($request->waist)){
            $measurement->waist = $request->waist;
            $temp = 1;
        }
        if(isset($request->hips)){
            $measurement->hips = $request->hips;
            $temp = 1;
        }
        if(isset($request->left_thigh)){
            $measurement->left_thigh = $request->left_thigh;
            $temp = 1;
        }
        if(isset($request->right_thigh)){
            $measurement->right_thigh = $request->right_thigh;
            $temp = 1;
        }
        if(isset($request->left_calf)){
            $measurement->left_calf = $request->left_calf;
            $temp = 1;
        }
        if(isset($request->right_calf)){
            $measurement->right_calf = $request->right_calf;
            $temp = 1;
        }
        if(isset($request->belly_button)){
            $measurement->belly_button = $request->belly_button;
            $temp = 1;
        }
        if(isset($request->under_belly)){
            $measurement->under_belly = $request->under_belly;
            $temp = 1;
        }
        if($temp == 1){
            $measurement->user_id = Auth::id();
            $measurement->unit = UserSetting::where('user_id',Auth::id())->pluck('body_measures')->first();
            $measurement->date = $date_stamp;
            $measurement->save();
        }
        

        $title = 'Added Body Weight';
        $content = Auth::user()->name.' updated weight, new weight: '.$request->weight;
        $category = 5;
        $source = Auth::id();
        $this->generateActivityForAdmin($title,$content,$category,$source);
        
        return response()->json([
            'status' => true,
            'message' => 'Body Stats Added Successfully!',
            'refernce' => $data->id,
            'task_api' => true
        ]);
    }

    public function getsStepsByDate(Request $request){
        $validate = Validator::make($request->all(),[
            'date' => 'required'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $stamp = date('Y-m-d H:i:s', strtotime($request->date));
        $data = UserStep::where('date',$stamp)->where('user_id', Auth::user()->id)->first();
        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    public function getSleepByDate(Request $request){
        $validate = Validator::make($request->all(),[
            'date' => 'required'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $stamp = date('Y-m-d H:i:s', strtotime($request->date));
        $data = UserSleep::where('date',$stamp)->where('user_id', Auth::user()->id)->first();
        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    public function getFatByDate(Request $request){
        $validate = Validator::make($request->all(),[
            'date' => 'required'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $stamp = date('Y-m-d H:i:s', strtotime($request->date));
        $data = BodyStats::select('id','fat','date')->where('date',$stamp)->where('user_id', Auth::user()->id)->first();
        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    public function getLeanBodyMassByDate(Request $request){
        $validate = Validator::make($request->all(),[
            'date' => 'required'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $stamp = date('Y-m-d H:i:s', strtotime($request->date));
        $data = BodyStats::select('id','lean_body_mass','date')->where('date',$stamp)->where('user_id', Auth::user()->id)->first();
        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    public function getWeightByDate(Request $request){
        $validate = Validator::make($request->all(),[
            'date' => 'required'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $stamp = date('Y-m-d H:i:s', strtotime($request->date));
        $data = BodyStats::where('date',$stamp)->where('user_id', Auth::user()->id)->first();
        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }
    public function getbodyStatsByDate(Request $request){
        $validate = Validator::make($request->all(),[
            'date' => 'required'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $stamp = date('Y-m-d H:i:s', strtotime($request->date));
        $data = BodyMeasurement::where('date',$stamp)->where('user_id', Auth::user()->id)->first();
        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    public function getCaloriesBurnByDate(Request $request){
        $validate = Validator::make($request->all(),[
            'date' => 'required'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $stamp = date('Y-m-d H:i:s', strtotime($request->date));
        $data = UserCaloriesBurn::where('date',$stamp)->where('user_id', Auth::user()->id)->first();
        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    public function getRestingHrByDate(Request $request){
        $validate = Validator::make($request->all(),[
            'date' => 'required'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $stamp = date('Y-m-d H:i:s', strtotime($request->date));
        $data = UserHeartRate::where('date',$stamp)->where('user_id', Auth::user()->id)->first();
        if($data){
            $userDetail = UserDetail::where('user_id',Auth::id())->first();
            $dateOfBirth = date('Y-m-d H:i:s', strtotime($userDetail->DOB));
            $age = Carbon::now()->diffInYears($dateOfBirth);
            $percent = $data->average/(220-$age);
            $data->percent = round($percent*100,2);
        }
        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    public function getBloodPressureByDate(Request $request){
        $validate = Validator::make($request->all(),[
            'date' => 'required'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $stamp = date('Y-m-d H:i:s', strtotime($request->date));
        $data = UserBloodPressure::where('date',$stamp)->where('user_id', Auth::user()->id)->first();
        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    function getSleep(Request $request){
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
            // $current = $current->addMonths(2);
            
            $month1 = UserSleep::whereBetween('date', [$temp1, $temp])->where('user_id', Auth::user()->id)->get();
            $month3 = UserSleep::whereBetween('date', [$temp2, $temp1])->where('user_id', Auth::user()->id)->get();
            $month6 = UserSleep::whereBetween('date', [$temp3, $temp2])->where('user_id', Auth::user()->id)->get();
            $year = UserSleep::whereBetween('date', [$temp4, $temp3])->where('user_id', Auth::user()->id)->get();

            $dummyData['id'] = 0;
            $dummyData['user_id'] = 0;
            $dummyData['bed_time'] = "0";
            $dummyData['bed_date'] = "0";
            $dummyData['wakeup_time'] = "0";
            $dummyData['wakeup_date'] = "0";
            $dummyData['sleep_time'] = "00:00";
            $dummyData['sleep_percent'] = "0";
            $dummyData['date'] = Carbon::now();
            $dummyData['created_at'] = Carbon::now();
            $dummyData['updated_at'] = Carbon::now();

            if(sizeof($month1) == 0){
                $tempDate = Carbon::today()->subMonths();
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $month1[] = $dummyData;
            }else{
                $temp = $month1->where('date',Carbon::today()->subMonths())->first();
                if(!$temp){
                    $tempDate = Carbon::today()->subMonths();
                    $tempDate = explode("T",$tempDate);
                    $tempDate = $tempDate[0];
                    $dummyData['date'] = $tempDate;
                    $month1[] = $dummyData;
                }
            }

            if(sizeof($month3) == 0){
                $tempDate = Carbon::today()->subMonths(3);
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $month3[] = $dummyData;
            }else{
                $temp = $month3->where('date',Carbon::today()->subMonths(3))->first();
                if(!$temp){
                    $tempDate = Carbon::today()->subMonths(3);
                    $tempDate = explode("T",$tempDate);
                    $tempDate = $tempDate[0];
                    $dummyData['date'] = $tempDate;
                    $month3[] = $dummyData;
                }
            }

            if(sizeof($month6) == 0){
                $tempDate = Carbon::today()->subMonths(6);
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $month6[] = $dummyData;
            }else{
                $temp = $month6->where('date',Carbon::today()->subMonths(6))->first();
                if(!$temp){
                    $tempDate = Carbon::today()->subMonths(6);
                    $tempDate = explode("T",$tempDate);
                    $tempDate = $tempDate[0];
                    $dummyData['date'] = $tempDate;
                    $month6[] = $dummyData;
                }
            }
            
            if(sizeof($year) == 0){
                $tempDate = Carbon::today()->subYears();
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $year[] = $dummyData;
            }else{
                $temp = $year->where('date',Carbon::today()->subYears())->first();
                if(!$temp){
                    $tempDate = Carbon::today()->subYears();
                    $tempDate = explode("T",$tempDate);
                    $tempDate = $tempDate[0];
                    $dummyData['date'] = $tempDate;
                    $year[] = $dummyData;
                }
            }

            $data['sleep_month1'] = $month1;
            $data['sleep_month3'] = $month3;
            $data['sleep_month6'] = $month6;
            $data['sleep_year'] = $year;
    
            return response()->json([
                'status' => true,
                'data' => $data,
            ]);
        }
        $month1 = UserSleep::whereBetween('date', [Carbon::now()->subMonths(), Carbon::now()])->where('user_id', Auth::user()->id)->get();
        $month3 = UserSleep::whereBetween('date', [Carbon::now()->subMonths(3), Carbon::now()->subMonths()])->where('user_id', Auth::user()->id)->get();
        $month6 = UserSleep::whereBetween('date', [Carbon::now()->subMonths(6), Carbon::now()->subMonths(3)])->where('user_id', Auth::user()->id)->get();
        $year = UserSleep::whereBetween('date', [Carbon::now()->subYears(), Carbon::now()->subMonths(6)])->where('user_id', Auth::user()->id)->get();

        $data['sleep_month1'] = $month1;
        $data['sleep_month3'] = $month3;
        $data['sleep_month6'] = $month6;
        $data['sleep_year'] = $year;

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }

    function getSteps(Request $request){
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
            // $current = $current->addMonths(2);
            
            $month1 = UserStep::whereBetween('date', [$temp1, $temp])->where('user_id', Auth::user()->id)->get();
            $month3 = UserStep::whereBetween('date', [$temp2, $temp1])->where('user_id', Auth::user()->id)->get();
            $month6 = UserStep::whereBetween('date', [$temp3, $temp2])->where('user_id', Auth::user()->id)->get();
            $year = UserStep::whereBetween('date', [$temp4, $temp3])->where('user_id', Auth::user()->id)->get();

            $dummyData['id'] = 0;
            $dummyData['user_id'] = 0;
            $dummyData['steps'] = 0;
            $dummyData['distance'] = 0;
            $dummyData['burn_calories'] = "0";
            $dummyData['time'] = "0";
            $dummyData['unit'] = "km";
            $dummyData['date'] = Carbon::now();
            $dummyData['created_at'] = Carbon::now();
            $dummyData['updated_at'] = Carbon::now();

            if(sizeof($month1) == 0){
                $tempDate = Carbon::today()->subMonths();
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $month1[] = $dummyData;
            }else{
                $temp = $month1->where('date',Carbon::today()->subMonths())->first();
                if(!$temp){
                    $tempDate = Carbon::today()->subMonths();
                    $tempDate = explode("T",$tempDate);
                    $tempDate = $tempDate[0];
                    $dummyData['date'] = $tempDate;
                    $month1[] = $dummyData;
                }
            }

            if(sizeof($month3) == 0){
                $tempDate = Carbon::today()->subMonths(3);
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $month3[] = $dummyData;
            }else{
                $temp = $month3->where('date',Carbon::today()->subMonths(3))->first();
                if(!$temp){
                    $tempDate = Carbon::today()->subMonths(3);
                    $tempDate = explode("T",$tempDate);
                    $tempDate = $tempDate[0];
                    $dummyData['date'] = $tempDate;
                    $month3[] = $dummyData;
                }
            }

            if(sizeof($month6) == 0){
                $tempDate = Carbon::today()->subMonths(6);
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $month6[] = $dummyData;
            }else{
                $temp = $month6->where('date',Carbon::today()->subMonths(6))->first();
                if(!$temp){
                    $tempDate = Carbon::today()->subMonths(6);
                    $tempDate = explode("T",$tempDate);
                    $tempDate = $tempDate[0];
                    $dummyData['date'] = $tempDate;
                    $month6[] = $dummyData;
                }
            }
            
            if(sizeof($year) == 0){
                $tempDate = Carbon::today()->subYears();
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $year[] = $dummyData;
            }else{
                $temp = $year->where('date',Carbon::today()->subYears())->first();
                if(!$temp){
                    $tempDate = Carbon::today()->subYears();
                    $tempDate = explode("T",$tempDate);
                    $tempDate = $tempDate[0];
                    $dummyData['date'] = $tempDate;
                    $year[] = $dummyData;
                }
            }
            $data['steps_month1'] = $month1;
            $data['steps_month3'] = $month3;
            $data['steps_month6'] = $month6;
            $data['steps_year'] = $year;
    
            return response()->json([
                'status' => true,
                'data' => $data,
            ]);
        }
        $month1 = UserStep::whereBetween('date', [Carbon::now()->subMonths(), Carbon::now()])->where('user_id', Auth::user()->id)->get();
        $month3 = UserStep::whereBetween('date', [Carbon::now()->subMonths(3), Carbon::now()->subMonths()])->where('user_id', Auth::user()->id)->get();
        $month6 = UserStep::whereBetween('date', [Carbon::now()->subMonths(6), Carbon::now()->subMonths(3)])->where('user_id', Auth::user()->id)->get();
        $year = UserStep::whereBetween('date', [Carbon::now()->subYears(), Carbon::now()->subMonths(6)])->where('user_id', Auth::user()->id)->get();

        $dummyData['id'] = 0;
        $dummyData['user_id'] = 0;
        $dummyData['steps'] = 0;
        $dummyData['distance'] = 0;
        $dummyData['burn_calories'] = "0";
        $dummyData['time'] = "0";
        $dummyData['unit'] = "km";
        $dummyData['date'] = Carbon::now();
        $dummyData['created_at'] = Carbon::now();
        $dummyData['updated_at'] = Carbon::now();

        if(sizeof($month1) == 0){
            $tempDate = Carbon::today()->subMonths();
            $tempDate = explode("T",$tempDate);
            $tempDate = $tempDate[0];
            $dummyData['date'] = $tempDate;
            $month1[] = $dummyData;
        }else{
            $temp = $month1->where('date',Carbon::today()->subMonths())->first();
            if(!$temp){
                $tempDate = Carbon::today()->subMonths();
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $month1[] = $dummyData;
            }
        }

        if(sizeof($month3) == 0){
            $tempDate = Carbon::today()->subMonths(3);
            $tempDate = explode("T",$tempDate);
            $tempDate = $tempDate[0];
            $dummyData['date'] = $tempDate;
            $month3[] = $dummyData;
        }else{
            $temp = $month3->where('date',Carbon::today()->subMonths(3))->first();
            if(!$temp){
                $tempDate = Carbon::today()->subMonths(3);
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $month3[] = $dummyData;
            }
        }

        if(sizeof($month6) == 0){
            $tempDate = Carbon::today()->subMonths(6);
            $tempDate = explode("T",$tempDate);
            $tempDate = $tempDate[0];
            $dummyData['date'] = $tempDate;
            $month6[] = $dummyData;
        }else{
            $temp = $month6->where('date',Carbon::today()->subMonths(6))->first();
            if(!$temp){
                $tempDate = Carbon::today()->subMonths(6);
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $month6[] = $dummyData;
            }
        }
        
        if(sizeof($year) == 0){
            $tempDate = Carbon::today()->subYears();
            $tempDate = explode("T",$tempDate);
            $tempDate = $tempDate[0];
            $dummyData['date'] = $tempDate;
            $year[] = $dummyData;
        }else{
            $temp = $year->where('date',Carbon::today()->subYears())->first();
            if(!$temp){
                $tempDate = Carbon::today()->subYears();
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $year[] = $dummyData;
            }
        }
        $data['steps_month1'] = $month1;
        $data['steps_month3'] = $month3;
        $data['steps_month6'] = $month6;
        $data['steps_year'] = $year;

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }

    function getRestingHR(Request $request){
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
            
            $month1 = UserHeartRate::whereBetween('date', [$temp1, $temp])->where('user_id', Auth::user()->id)->get();
            $month3 = UserHeartRate::whereBetween('date', [$temp2, $temp1])->where('user_id', Auth::user()->id)->get();
            $month6 = UserHeartRate::whereBetween('date', [$temp3, $temp2])->where('user_id', Auth::user()->id)->get();
            $year = UserHeartRate::whereBetween('date', [$temp4, $temp3])->where('user_id', Auth::user()->id)->get();

            $dummyData['id'] = 0;
            $dummyData['user_id'] = 0;
            $dummyData['min'] = 0;
            $dummyData['max'] = 0;
            $dummyData['average'] = 0;
            $dummyData['date'] = Carbon::now();
            $dummyData['created_at'] = Carbon::now();
            $dummyData['updated_at'] = Carbon::now();

            if(sizeof($month1) == 0){
                $tempDate = Carbon::today()->subMonths();
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $month1[] = $dummyData;
            }else{
                $temp = $month1->where('date',Carbon::today()->subMonths())->first();
                if(!$temp){
                    $tempDate = Carbon::today()->subMonths();
                    $tempDate = explode("T",$tempDate);
                    $tempDate = $tempDate[0];
                    $dummyData['date'] = $tempDate;
                    $month1[] = $dummyData;
                }
            }

            if(sizeof($month3) == 0){
                $tempDate = Carbon::today()->subMonths(3);
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $month3[] = $dummyData;
            }else{
                $temp = $month3->where('date',Carbon::today()->subMonths(3))->first();
                if(!$temp){
                    $tempDate = Carbon::today()->subMonths(3);
                    $tempDate = explode("T",$tempDate);
                    $tempDate = $tempDate[0];
                    $dummyData['date'] = $tempDate;
                    $month3[] = $dummyData;
                }
            }

            if(sizeof($month6) == 0){
                $tempDate = Carbon::today()->subMonths(6);
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $month6[] = $dummyData;
            }else{
                $temp = $month6->where('date',Carbon::today()->subMonths(6))->first();
                if(!$temp){
                    $tempDate = Carbon::today()->subMonths(6);
                    $tempDate = explode("T",$tempDate);
                    $tempDate = $tempDate[0];
                    $dummyData['date'] = $tempDate;
                    $month6[] = $dummyData;
                }
            }
            
            if(sizeof($year) == 0){
                $tempDate = Carbon::today()->subYears();
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $year[] = $dummyData;
            }else{
                $temp = $year->where('date',Carbon::today()->subYears())->first();
                if(!$temp){
                    $tempDate = Carbon::today()->subYears();
                    $tempDate = explode("T",$tempDate);
                    $tempDate = $tempDate[0];
                    $dummyData['date'] = $tempDate;
                    $year[] = $dummyData;
                }
            }

            $data['resting_hr_month1'] = $month1;
            $data['resting_hr_month3'] = $month3;
            $data['resting_hr_month6'] = $month6;
            $data['resting_hr_year'] = $year;
    
            return response()->json([
                'status' => true,
                'data' => $data,
            ]);
        }
        $month1 = UserHeartRate::whereBetween('date', [Carbon::now()->subMonths(), Carbon::now()])->where('user_id', Auth::user()->id)->get();
        $month3 = UserHeartRate::whereBetween('date', [Carbon::now()->subMonths(3), Carbon::now()->subMonths()])->where('user_id', Auth::user()->id)->get();
        $month6 = UserHeartRate::whereBetween('date', [Carbon::now()->subMonths(6), Carbon::now()->subMonths(3)])->where('user_id', Auth::user()->id)->get();
        $year = UserHeartRate::whereBetween('date', [Carbon::now()->subYears(), Carbon::now()->subMonths(6)])->where('user_id', Auth::user()->id)->get();

        $data['resting_hr_month1'] = $month1;
        $data['resting_hr_month3'] = $month3;
        $data['resting_hr_month6'] = $month6;
        $data['resting_hr_year'] = $year;

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }

    function getBloodPressure(Request $request){
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
            
            $month1 = UserBloodPressure::whereBetween('date', [$temp1, $temp])->where('user_id', Auth::user()->id)->get();
            $month3 = UserBloodPressure::whereBetween('date', [$temp2, $temp1])->where('user_id', Auth::user()->id)->get();
            $month6 = UserBloodPressure::whereBetween('date', [$temp3, $temp2])->where('user_id', Auth::user()->id)->get();
            $year = UserBloodPressure::whereBetween('date', [$temp4, $temp3])->where('user_id', Auth::user()->id)->get();

            $dummyData['id'] = 0;
            $dummyData['user_id'] = 0;
            $dummyData['sys'] = 0;
            $dummyData['dia'] = 0;
            $dummyData['date'] = Carbon::now();
            $dummyData['created_at'] = Carbon::now();
            $dummyData['updated_at'] = Carbon::now();

            if(sizeof($month1) == 0){
                $tempDate = Carbon::today()->subMonths();
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $month1[] = $dummyData;
            }else{
                $temp = $month1->where('date',Carbon::today()->subMonths())->first();
                if(!$temp){
                    $tempDate = Carbon::today()->subMonths();
                    $tempDate = explode("T",$tempDate);
                    $tempDate = $tempDate[0];
                    $dummyData['date'] = $tempDate;
                    $month1[] = $dummyData;
                }
            }

            if(sizeof($month3) == 0){
                $tempDate = Carbon::today()->subMonths(3);
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $month3[] = $dummyData;
            }else{
                $temp = $month3->where('date',Carbon::today()->subMonths(3))->first();
                if(!$temp){
                    $tempDate = Carbon::today()->subMonths(3);
                    $tempDate = explode("T",$tempDate);
                    $tempDate = $tempDate[0];
                    $dummyData['date'] = $tempDate;
                    $month3[] = $dummyData;
                }
            }

            if(sizeof($month6) == 0){
                $tempDate = Carbon::today()->subMonths(6);
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $month6[] = $dummyData;
            }else{
                $temp = $month6->where('date',Carbon::today()->subMonths(6))->first();
                if(!$temp){
                    $tempDate = Carbon::today()->subMonths(6);
                    $tempDate = explode("T",$tempDate);
                    $tempDate = $tempDate[0];
                    $dummyData['date'] = $tempDate;
                    $month6[] = $dummyData;
                }
            }
            
            if(sizeof($year) == 0){
                $tempDate = Carbon::today()->subYears();
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $year[] = $dummyData;
            }else{
                $temp = $year->where('date',Carbon::today()->subYears())->first();
                if(!$temp){
                    $tempDate = Carbon::today()->subYears();
                    $tempDate = explode("T",$tempDate);
                    $tempDate = $tempDate[0];
                    $dummyData['date'] = $tempDate;
                    $year[] = $dummyData;
                }
            }

            $data['blood_pressure_month1'] = $month1;
            $data['blood_pressure_month3'] = $month3;
            $data['blood_pressure_month6'] = $month6;
            $data['blood_pressure_year'] = $year;
    
            return response()->json([
                'status' => true,
                'data' => $data,
            ]);
        }
        $month1 = UserBloodPressure::whereBetween('date', [Carbon::now()->subMonths(), Carbon::now()])->where('user_id', Auth::user()->id)->get();
        $month3 = UserBloodPressure::whereBetween('date', [Carbon::now()->subMonths(3), Carbon::now()->subMonths()])->where('user_id', Auth::user()->id)->get();
        $month6 = UserBloodPressure::whereBetween('date', [Carbon::now()->subMonths(6), Carbon::now()->subMonths(3)])->where('user_id', Auth::user()->id)->get();
        $year = UserBloodPressure::whereBetween('date', [Carbon::now()->subYears(), Carbon::now()->subMonths(6)])->where('user_id', Auth::user()->id)->get();

        $data['blood_pressure_month1'] = $month1;
        $data['blood_pressure_month3'] = $month3;
        $data['blood_pressure_month6'] = $month6;
        $data['blood_pressure_year'] = $year;

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }

    function getCaloriesBurn(Request $request){
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
            
            $month1 = UserCaloriesBurn::whereBetween('date', [$temp1, $temp])->where('user_id', Auth::user()->id)->get();
            $month3 = UserCaloriesBurn::whereBetween('date', [$temp2, $temp1])->where('user_id', Auth::user()->id)->get();
            $month6 = UserCaloriesBurn::whereBetween('date', [$temp3, $temp2])->where('user_id', Auth::user()->id)->get();
            $year = UserCaloriesBurn::whereBetween('date', [$temp4, $temp3])->where('user_id', Auth::user()->id)->get();

            $dummyData['id'] = 0;
            $dummyData['user_id'] = 0;
            $dummyData['carbs'] = 0;
            $dummyData['proteins'] = 0;
            $dummyData['fats'] = 0;
            $dummyData['total'] = 0;
            $dummyData['date'] = Carbon::now();
            $dummyData['created_at'] = Carbon::now();
            $dummyData['updated_at'] = Carbon::now();

            if(sizeof($month1) == 0){
                $tempDate = Carbon::today()->subMonths();
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $month1[] = $dummyData;
            }else{
                $temp = $month1->where('date',Carbon::today()->subMonths())->first();
                if(!$temp){
                    $tempDate = Carbon::today()->subMonths();
                    $tempDate = explode("T",$tempDate);
                    $tempDate = $tempDate[0];
                    $dummyData['date'] = $tempDate;
                    $month1[] = $dummyData;
                }
            }

            if(sizeof($month3) == 0){
                $tempDate = Carbon::today()->subMonths(3);
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $month3[] = $dummyData;
            }else{
                $temp = $month3->where('date',Carbon::today()->subMonths(3))->first();
                if(!$temp){
                    $tempDate = Carbon::today()->subMonths(3);
                    $tempDate = explode("T",$tempDate);
                    $tempDate = $tempDate[0];
                    $dummyData['date'] = $tempDate;
                    $month3[] = $dummyData;
                }
            }

            if(sizeof($month6) == 0){
                $tempDate = Carbon::today()->subMonths(6);
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $month6[] = $dummyData;
            }else{
                $temp = $month6->where('date',Carbon::today()->subMonths(6))->first();
                if(!$temp){
                    $tempDate = Carbon::today()->subMonths(6);
                    $tempDate = explode("T",$tempDate);
                    $tempDate = $tempDate[0];
                    $dummyData['date'] = $tempDate;
                    $month6[] = $dummyData;
                }
            }
            
            if(sizeof($year) == 0){
                $tempDate = Carbon::today()->subYears();
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $year[] = $dummyData;
            }else{
                $temp = $year->where('date',Carbon::today()->subYears())->first();
                if(!$temp){
                    $tempDate = Carbon::today()->subYears();
                    $tempDate = explode("T",$tempDate);
                    $tempDate = $tempDate[0];
                    $dummyData['date'] = $tempDate;
                    $year[] = $dummyData;
                }
            }

            $data['caloric_burn_month1'] = $month1;
            $data['caloric_burn_month3'] = $month3;
            $data['caloric_burn_month6'] = $month6;
            $data['caloric_burn_year'] = $year;
    
            return response()->json([
                'status' => true,
                'data' => $data,
            ]);
        }
        $month1 = UserCaloriesBurn::whereBetween('date', [Carbon::now()->subMonths(), Carbon::now()])->where('user_id', Auth::user()->id)->get();
        $month3 = UserCaloriesBurn::whereBetween('date', [Carbon::now()->subMonths(3), Carbon::now()->subMonths()])->where('user_id', Auth::user()->id)->get();
        $month6 = UserCaloriesBurn::whereBetween('date', [Carbon::now()->subMonths(6), Carbon::now()->subMonths(3)])->where('user_id', Auth::user()->id)->get();
        $year = UserCaloriesBurn::whereBetween('date', [Carbon::now()->subYears(), Carbon::now()->subMonths(6)])->where('user_id', Auth::user()->id)->get();

        $data['caloric_burn_month1'] = $month1;
        $data['caloric_burn_month3'] = $month3;
        $data['caloric_burn_month6'] = $month6;
        $data['caloric_burn_year'] = $year;

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }

    function getLeanBodyMass(Request $request){
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
            
            $month1 = BodyStats::whereBetween('date', [$temp1, $temp])->where('user_id', Auth::user()->id)->get(['id','lean_body_mass','date']);
            $month3 = BodyStats::whereBetween('date', [$temp2, $temp1])->where('user_id', Auth::user()->id)->get(['id','lean_body_mass','date']);
            $month6 = BodyStats::whereBetween('date', [$temp3, $temp2])->where('user_id', Auth::user()->id)->get(['id','lean_body_mass','date']);
            $year = BodyStats::whereBetween('date', [$temp4, $temp3])->where('user_id', Auth::user()->id)->get(['id','lean_body_mass','date']);

            $dummyData['id'] = 0;
            $dummyData['lean_body_mass'] = 0;
            $dummyData['date'] = Carbon::now();

            if(sizeof($month1) == 0){
                $tempDate = Carbon::today()->subMonths();
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $month1[] = $dummyData;
            }else{
                $temp = $month1->where('date',Carbon::today()->subMonths())->first();
                if(!$temp){
                    $tempDate = Carbon::today()->subMonths();
                    $tempDate = explode("T",$tempDate);
                    $tempDate = $tempDate[0];
                    $dummyData['date'] = $tempDate;
                    $month1[] = $dummyData;
                }
            }

            if(sizeof($month3) == 0){
                $tempDate = Carbon::today()->subMonths(3);
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $month3[] = $dummyData;
            }else{
                $temp = $month3->where('date',Carbon::today()->subMonths(3))->first();
                if(!$temp){
                    $tempDate = Carbon::today()->subMonths(3);
                    $tempDate = explode("T",$tempDate);
                    $tempDate = $tempDate[0];
                    $dummyData['date'] = $tempDate;
                    $month3[] = $dummyData;
                }
            }

            if(sizeof($month6) == 0){
                $tempDate = Carbon::today()->subMonths(6);
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $month6[] = $dummyData;
            }else{
                $temp = $month6->where('date',Carbon::today()->subMonths(6))->first();
                if(!$temp){
                    $tempDate = Carbon::today()->subMonths(6);
                    $tempDate = explode("T",$tempDate);
                    $tempDate = $tempDate[0];
                    $dummyData['date'] = $tempDate;
                    $month6[] = $dummyData;
                }
            }
            
            if(sizeof($year) == 0){
                $tempDate = Carbon::today()->subYears();
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $year[] = $dummyData;
            }else{
                $temp = $year->where('date',Carbon::today()->subYears())->first();
                if(!$temp){
                    $tempDate = Carbon::today()->subYears();
                    $tempDate = explode("T",$tempDate);
                    $tempDate = $tempDate[0];
                    $dummyData['date'] = $tempDate;
                    $year[] = $dummyData;
                }
            }

            $data['lean_body_mass_month1'] = $month1;
            $data['lean_body_mass_month3'] = $month3;
            $data['lean_body_mass_month6'] = $month6;
            $data['lean_body_mass_year'] = $year;
    
            return response()->json([
                'status' => true,
                'data' => $data,
            ]);
        }
        $month1 = BodyStats::whereBetween('date', [Carbon::now()->subMonths(), Carbon::now()])->where('user_id', Auth::user()->id)->get(['id','lean_body_mass','date']);
        $month3 = BodyStats::whereBetween('date', [Carbon::now()->subMonths(3), Carbon::now()->subMonths()])->where('user_id', Auth::user()->id)->get(['id','lean_body_mass','date']);
        $month6 = BodyStats::whereBetween('date', [Carbon::now()->subMonths(6), Carbon::now()->subMonths(3)])->where('user_id', Auth::user()->id)->get(['id','lean_body_mass','date']);
        $year = BodyStats::whereBetween('date', [Carbon::now()->subYears(), Carbon::now()->subMonths(6)])->where('user_id', Auth::user()->id)->get(['id','lean_body_mass','date']);

        $data['lean_body_mass_month1'] = $month1;
        $data['lean_body_mass_month3'] = $month3;
        $data['lean_body_mass_month6'] = $month6;
        $data['lean_body_mass_year'] = $year;

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }

    function getBodyFat(Request $request){
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
            
            $month1 = BodyStats::whereBetween('date', [$temp1, $temp])->where('user_id', Auth::user()->id)->get(['id','fat','date']);
            $month3 = BodyStats::whereBetween('date', [$temp2, $temp1])->where('user_id', Auth::user()->id)->get(['id','fat','date']);
            $month6 = BodyStats::whereBetween('date', [$temp3, $temp2])->where('user_id', Auth::user()->id)->get(['id','fat','date']);
            $year = BodyStats::whereBetween('date', [$temp4, $temp3])->where('user_id', Auth::user()->id)->get(['id','fat','date']);

            $dummyData['id'] = 0;
            $dummyData['fat'] = 0;
            $dummyData['date'] = Carbon::now();

            if(sizeof($month1) == 0){
                $tempDate = Carbon::today()->subMonths();
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $month1[] = $dummyData;
            }else{
                $temp = $month1->where('date',Carbon::today()->subMonths())->first();
                if(!$temp){
                    $tempDate = Carbon::today()->subMonths();
                    $tempDate = explode("T",$tempDate);
                    $tempDate = $tempDate[0];
                    $dummyData['date'] = $tempDate;
                    $month1[] = $dummyData;
                }
            }

            if(sizeof($month3) == 0){
                $tempDate = Carbon::today()->subMonths(3);
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $month3[] = $dummyData;
            }else{
                $temp = $month3->where('date',Carbon::today()->subMonths(3))->first();
                if(!$temp){
                    $tempDate = Carbon::today()->subMonths(3);
                    $tempDate = explode("T",$tempDate);
                    $tempDate = $tempDate[0];
                    $dummyData['date'] = $tempDate;
                    $month3[] = $dummyData;
                }
            }

            if(sizeof($month6) == 0){
                $tempDate = Carbon::today()->subMonths(6);
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $month6[] = $dummyData;
            }else{
                $temp = $month6->where('date',Carbon::today()->subMonths(6))->first();
                if(!$temp){
                    $tempDate = Carbon::today()->subMonths(6);
                    $tempDate = explode("T",$tempDate);
                    $tempDate = $tempDate[0];
                    $dummyData['date'] = $tempDate;
                    $month6[] = $dummyData;
                }
            }
            
            if(sizeof($year) == 0){
                $tempDate = Carbon::today()->subYears();
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $year[] = $dummyData;
            }else{
                $temp = $year->where('date',Carbon::today()->subYears())->first();
                if(!$temp){
                    $tempDate = Carbon::today()->subYears();
                    $tempDate = explode("T",$tempDate);
                    $tempDate = $tempDate[0];
                    $dummyData['date'] = $tempDate;
                    $year[] = $dummyData;
                }
            }

            $data['body_fat_month1'] = $month1;
            $data['body_fat_month3'] = $month3;
            $data['body_fat_month6'] = $month6;
            $data['body_fat_year'] = $year;
    
            return response()->json([
                'status' => true,
                'data' => $data,
            ]);
        }
        $month1 = BodyStats::whereBetween('date', [Carbon::now()->subMonths(), Carbon::now()])->where('user_id', Auth::user()->id)->get(['id','fat','date']);
        $month3 = BodyStats::whereBetween('date', [Carbon::now()->subMonths(3), Carbon::now()->subMonths()])->where('user_id', Auth::user()->id)->get(['id','fat','date']);
        $month6 = BodyStats::whereBetween('date', [Carbon::now()->subMonths(6), Carbon::now()->subMonths(3)])->where('user_id', Auth::user()->id)->get(['id','fat','date']);
        $year = BodyStats::whereBetween('date', [Carbon::now()->subYears(), Carbon::now()->subMonths(6)])->where('user_id', Auth::user()->id)->get(['id','fat','date']);

        $data['body_fat_month1'] = $month1;
        $data['body_fat_month3'] = $month3;
        $data['body_fat_month6'] = $month6;
        $data['body_fat_year'] = $year;

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }

    function getWeight(Request $request){
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
            
            $month1 = BodyStats::whereBetween('date', [$temp1, $temp])->where('user_id', Auth::user()->id)->get();
            $month3 = BodyStats::whereBetween('date', [$temp2, $temp1])->where('user_id', Auth::user()->id)->get();
            $month6 = BodyStats::whereBetween('date', [$temp3, $temp2])->where('user_id', Auth::user()->id)->get();
            $year = BodyStats::whereBetween('date', [$temp4, $temp3])->where('user_id', Auth::user()->id)->get();

            $dummyData['id'] = 0;
            $dummyData['user_id'] = 0;
            $dummyData['weight'] = 0;
            $dummyData['fat'] = 0;
            $dummyData['fat_mass'] = 0;
            $dummyData['lean_body_mass'] = 0;
            $dummyData['caliper_body_fat'] = 0;
            $dummyData['unit'] = "km";
            $dummyData['date'] = Carbon::now();
            $dummyData['created_at'] = Carbon::now();
            $dummyData['updated_at'] = Carbon::now();

            if(sizeof($month1) == 0){
                $tempDate = Carbon::today()->subMonths();
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $month1[] = $dummyData;
            }else{
                $temp = $month1->where('date',Carbon::today()->subMonths())->first();
                if(!$temp){
                    $tempDate = Carbon::today()->subMonths();
                    $tempDate = explode("T",$tempDate);
                    $tempDate = $tempDate[0];
                    $dummyData['date'] = $tempDate;
                    $month1[] = $dummyData;
                }
            }

            if(sizeof($month3) == 0){
                $tempDate = Carbon::today()->subMonths(3);
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $month3[] = $dummyData;
            }else{
                $temp = $month3->where('date',Carbon::today()->subMonths(3))->first();
                if(!$temp){
                    $tempDate = Carbon::today()->subMonths(3);
                    $tempDate = explode("T",$tempDate);
                    $tempDate = $tempDate[0];
                    $dummyData['date'] = $tempDate;
                    $month3[] = $dummyData;
                }
            }

            if(sizeof($month6) == 0){
                $tempDate = Carbon::today()->subMonths(6);
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $month6[] = $dummyData;
            }else{
                $temp = $month6->where('date',Carbon::today()->subMonths(6))->first();
                if(!$temp){
                    $tempDate = Carbon::today()->subMonths(6);
                    $tempDate = explode("T",$tempDate);
                    $tempDate = $tempDate[0];
                    $dummyData['date'] = $tempDate;
                    $month6[] = $dummyData;
                }
            }
            
            if(sizeof($year) == 0){
                $tempDate = Carbon::today()->subYears();
                $tempDate = explode("T",$tempDate);
                $tempDate = $tempDate[0];
                $dummyData['date'] = $tempDate;
                $year[] = $dummyData;
            }else{
                $temp = $year->where('date',Carbon::today()->subYears())->first();
                if(!$temp){
                    $tempDate = Carbon::today()->subYears();
                    $tempDate = explode("T",$tempDate);
                    $tempDate = $tempDate[0];
                    $dummyData['date'] = $tempDate;
                    $year[] = $dummyData;
                }
            }

            $data['body_weight_month1'] = $month1;
            $data['body_weight_month3'] = $month3;
            $data['body_weight_month6'] = $month6;
            $data['body_weight_year'] = $year;
    
            return response()->json([
                'status' => true,
                'data' => $data,
            ]);
        }
        $month1 = BodyStats::whereBetween('date', [Carbon::now()->subMonths(), Carbon::now()])->where('user_id', Auth::user()->id)->get();
        $month3 = BodyStats::whereBetween('date', [Carbon::now()->subMonths(3), Carbon::now()->subMonths()])->where('user_id', Auth::user()->id)->get();
        $month6 = BodyStats::whereBetween('date', [Carbon::now()->subMonths(6), Carbon::now()->subMonths(3)])->where('user_id', Auth::user()->id)->get();
        $year = BodyStats::whereBetween('date', [Carbon::now()->subYears(), Carbon::now()->subMonths(6)])->where('user_id', Auth::user()->id)->get();

        $data['body_weight_month1'] = $month1;
        $data['body_weight_month3'] = $month3;
        $data['body_weight_month6'] = $month6;
        $data['body_weight_year'] = $year;

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }

    function getUserStats(){
        $userId = Auth::id();
        $returnData = new stdClass;
        
        // Optimize: Use single query with UNION or fetch all in parallel
        // Steps
        $data = UserStep::select('id', 'steps', 'distance', 'burn_calories', 'time', 'date')
            ->where('user_id', $userId)
            ->orderBy('date', 'DESC')
            ->first();
        if($data){
            $data->formated_date = date('Y-m-d', strtotime($data->date));
        }
        $returnData->steps = $data;

        // Sleep
        $data = UserSleep::select('id', 'sleep', 'date')
            ->where('user_id', $userId)
            ->orderBy('date', 'DESC')
            ->first();
        if($data){
            $data->formated_date = date('Y-m-d', strtotime($data->date));
        }
        $returnData->sleep = $data;

        // Body Weight
        $data = BodyStats::select('id', 'weight', 'date')
            ->where('user_id', $userId)
            ->whereNotNull('weight')
            ->orderBy('date', 'DESC')
            ->first();
        if($data){
            $data->formated_date = date('Y-m-d', strtotime($data->date));
        }
        $returnData->body_weight = $data;

        // Body Fat
        $data = BodyStats::select('id', 'fat', 'date')
            ->where('user_id', $userId)
            ->whereNotNull('fat')
            ->orderBy('date', 'DESC')
            ->first();
        if($data){
            $data->formated_date = date('Y-m-d', strtotime($data->date));
        }
        $returnData->body_fat = $data;

        // Lean Body Mass
        $data = BodyStats::select('id', 'lean_body_mass', 'date')
            ->where('user_id', $userId)
            ->whereNotNull('lean_body_mass')
            ->orderBy('date', 'DESC')
            ->first();
        if($data){
            $data->formated_date = date('Y-m-d', strtotime($data->date));
        }
        $returnData->lean_body_mass = $data;

        // Calories Burn
        $data = UserCaloriesBurn::select('id', 'calories', 'date')
            ->where('user_id', $userId)
            ->orderBy('date', 'DESC')
            ->first();
        if($data){
            $data->formated_date = date('Y-m-d', strtotime($data->date));
        }
        $returnData->caloric_burn = $data;

        $data = UserHeartRate::select('id', 'heart_rate', 'date')
            ->where('user_id', $userId)
            ->orderBy('date', 'DESC')
            ->first();
        if($data){
            $data->formated_date = date('Y-m-d', strtotime($data->date));
        }
        $returnData->resting_hr = $data;

        $data = UserBloodPressure::select('id', 'systolic', 'diastolic', 'date')
            ->where('user_id', $userId)
            ->orderBy('date', 'DESC')
            ->first();
        if($data){
            $data->formated_date = date('Y-m-d', strtotime($data->date));
        }
        $returnData->blood_pressure = $data;

        return $this->success($returnData);
    }
}
