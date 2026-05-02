<?php

namespace App\Traits;

use App\Models\Activity;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\UserSetting;
use Exception;
use Illuminate\Support\Facades\Http;

trait ActivitiesTrait {

    function generateActivityForUser($reciever,$title,$content,$cat_id,$source=null){
        $act = new Activity();
        $act->reciever = $reciever;
        $act->title = $title;
        $act->content = $content;
        $act->source = $source;
        $act->category_id = $cat_id;
        $act->save();
        return;
    }
    function generateActivityForAdmin($title,$content,$cat_id,$source=null,$type=null,$target=null){
        $act = new Activity();
        $act->reciever = User::where('role',2)->pluck('id')->first();
        $act->title = $title;
        $act->content = $content;
        $act->source = $source;
        $act->category_id = $cat_id;
        $act->activity_type = $type;
        $act->activity_target = $target;
        $act->save();
        return;
    }

    /* ---------------------------------------------------------------------
        Other general functions
    ------------------------------------------------------------------------ */

    function userFullAccess($userId){
        $subId = UserDetail::where('user_id',$userId)->pluck('subscription')->first();
        $access = Subscription::where('id',$subId)->pluck('access_type')->first();
        return $access==='full_access'?true:false;
    }

    function userSelecetdLanguage($userId){
        return UserSetting::where('user_id',$userId)->pluck('language')->first();
    }

    function userSelecetdWeightUnit($userId){
        return UserSetting::where('user_id',$userId)->pluck('weight_unit')->first();
    }
    
    function userSelecetdDistanceUnit($userId){
        return UserSetting::where('user_id',$userId)->pluck('distance_unit')->first();
    }
    
    function userSelecetdBodyMeasureUnit($userId){
        return UserSetting::where('user_id',$userId)->pluck('body_measures')->first();
    }

    function getTranslatedText(string $inputText,string $targetLanguage) {
        try {
            $languageModel = config('app.google_trans_model');
            $translateBaseUrl = config('app.google_trans_baseUrl');
            $tranlateApiKey = config('app.google_api_key');

            $response = Http::get($translateBaseUrl."?q=".$inputText."&target=".$targetLanguage."&model=".$languageModel."&key=".$tranlateApiKey);
            if(isset($response['data']) && isset($response['data']['translations']) && count($response['data']['translations'])>0)
            return $response['data']['translations'][0]['translatedText'];
            else
            return '-- no translation --';
        } catch(Exception $er){
            return '-- no translation --'; 
        }
    }

    function detectLanguage(string $inputText){
        try {
            $languageModel = config('app.google_trans_model');
            $translateBaseUrl = config('app.google_trans_baseUrl');
            $tranlateApiKey = config('app.google_api_key');

            $response = Http::get($translateBaseUrl."/detect?q=".substr($inputText,0,50)."&model=".$languageModel."&key=".$tranlateApiKey);
            if(isset($response['data']) && isset($response['data']['detections']) && count($response['data']['detections'])>0)
            return $response['data']['detections'][0][0]['language'];
            else
            return null;
        } catch(Exception $er){
            return '-- no translation --'; 
        }
    }
}
