<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserSettingsController extends Controller
{
    //

    function getSettings(){
        $settings = UserSetting::where('user_id',Auth::id())->first();
        return response()->json([
            'status' => true,
            'settings' => $settings
        ]);
    }

    function updateSettings(Request $request){
        $validation = Validator::make($request->all(),[
            'group_chat_noti' => 'integer|between:0,1',
            'private_chat_noti' => 'integer|between:0,1',
            'comments_noti' => 'integer|between:0,1',
            'payments_noti' => 'integer|between:0,1',
            'activities_noti' => 'integer|between:0,1',
            'weight_unit' => 'string|in:lbs,kg',
            'distance_unit' => 'string|in:miles,km',
            'body_measures' => 'string|in:inches,cm',
            'video_quality' => 'string|in:alwaysHD,wifiHD',
            'daily_noti_time' => 'string',
            'language' => ['string', Language::activeCodeRule()],
        ]);
        if($validation->fails())
        return response()->json([
            'status' => false,
            'message' => $validation->errors()->all()[0]
        ]);

        $settings = UserSetting::where('user_id',Auth::id())->first(); 
        if(isset($request->group_chat_noti))
        $settings->group_chat_noti = $request->group_chat_noti;
        if(isset($request->private_chat_noti))
        $settings->private_chat_noti = $request->private_chat_noti;
        if(isset($request->comments_noti))
        $settings->comments_noti = $request->comments_noti;
        if(isset($request->payments_noti))
        $settings->payments_noti = $request->payments_noti;
        if(isset($request->activities_noti))
        $settings->activities_noti = $request->activities_noti;
        if(isset($request->weight_unit))
        $settings->weight_unit = $request->weight_unit;
        if(isset($request->distance_unit))
        $settings->distance_unit = $request->distance_unit;
        if(isset($request->body_measures))
        $settings->body_measures = $request->body_measures;
        if(isset($request->video_quality))
        $settings->video_quality = $request->video_quality;
        if(isset($request->daily_noti_time))
        $settings->task_noti_time = $request->daily_noti_time;
        if(isset($request->language))
        $settings->language = $request->language;
        $settings->update();

        return response()->json([
            'status' => true,
            'message' => 'Settings Updated Successfully.'
        ]);
    }
    function updateSequence(Request $request){
        $validate = Validator::make($request->all(),[
            'stats' => 'required|array'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $settings = UserSetting::where('user_id',Auth::id())->first();
        $settings->stats_sequence = json_encode( $request->stats);
        $settings->update();
        return response()->json([
            'status' => true,
            'message' => 'Stats Sequence Updated Successfully.'
        ]);
    }

    function getStatsSequence(){
        $sequence = UserSetting::where('user_id',Auth::id())->pluck('stats_sequence')->first();
        if(is_null($sequence))
        return response()->json([
            'status' => false,
            'message' => 'No Stats Data.'
        ]);
        return response()->json([
            'status' => true,
            'data' => json_decode($sequence)
        ]);
    }

    function turnOffTutorial(){
        UserSetting::where('user_id',Auth::id())->update(['show_tutorial' => 0]);
        return response()->json([
            'status' => true,
            'message' => 'Tutorial Switched Off.'
        ]);
    }
}
