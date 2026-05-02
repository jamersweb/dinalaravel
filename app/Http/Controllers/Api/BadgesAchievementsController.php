<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\Badge;
use App\Models\Program;
use App\Models\User;
use App\Models\UserSetting;
use App\Models\WeeklyWorkout;
use App\Traits\NotificationsTrait;
use Illuminate\Support\Facades\Auth;

class BadgesAchievementsController extends Controller
{
    use NotificationsTrait;

    static function weeklyAchievementCheck($weekId,$userId,$programId,$weekNo){
        $totalWorkouts = WeeklyWorkout::where('week_id',$weekId)->count();
        $completedWorkouts = WeeklyWorkout::where('week_id',$weekId)->where('status',1)->count();
        $completePerc = $totalWorkouts===0?0:($completedWorkouts*100)/$totalWorkouts;
        self::checkForBadge($completePerc,$programId,$userId,$weekNo);
        return;
    }

    static function checkForBadge($percentage,$programId,$userId,$weekNo){
        $badgeId = 0;
        if($percentage<50){
            return;
        } elseif($percentage<70){
            $badgeId = 1;
        } elseif($percentage<90){
            $badgeId = 2;
        } else{
            $badgeId = 3;
        }
        $already = Achievement::where('user_id',$userId)->where('program_id',$programId)->where('week_no',$weekNo)->first();
        if($already){
            if($already->badge_id!==$badgeId){
                $already->badge_id = $badgeId;
                $already->update();
                self::sendNotification($userId,$badgeId,$weekNo,$programId);
            }
        } else {
            $ach = new Achievement();
            $ach->user_id = $userId;
            $ach->program_id = $programId;
            $ach->week_no = $weekNo;
            $ach->badge_id = $badgeId;
            $ach->save();
            self::sendNotification($userId,$badgeId,$weekNo,$programId);
        }
        return;
    }

    static function sendNotification($userId,$badgeId,$weekNo,$programId){
        $fcmToken = [];
        $userFcm = User::where('id',$userId)->pluck('fcm_token')->first();
        array_push($fcmToken,$userFcm);
        $badgeName = Badge::where('id',$badgeId)->first(['name','name_ar']);
        $programName = Program::where('id',$programId)->pluck('title')->first();

        $userLang = UserSetting::where('user_id',$userId)->pluck('language')->first();
        $notiTitleEn = 'Achievement Completed!';
        $notiContentEn = 'You got '.$badgeName->name.' Badge for Week '.$weekNo.' of '.$programName.' Program. Keep working to get more!';
        $notiTitleAr = 'تم الإنجاز';
        $notiContentAr = 'لقد حصلت على '.$badgeName->name_ar.' شارة لأسبوع '.$weekNo.' من برنامج '.$programName.'. استمر في العمل للحصول على المزيد!';
        
        self::storeNotificationStatic($userId,$notiTitleEn,$notiTitleAr,$notiContentEn,$notiContentAr);
        if($userLang==='en')
        self::sendFirebaseNotificationStatic($fcmToken,$notiTitleEn,$notiContentEn);
        else
        self::sendFirebaseNotificationStatic($fcmToken,$notiTitleAr,$notiContentAr);
        return;
    }

    static function getmyBadges(){
        $userLang = UserSetting::where('user_id',Auth::id())->pluck('language')->first();

        $badges = Achievement::where('user_id',Auth::id())->with('badgeDetail')->with('programDetail')->orderBy('created_at','desc')->get();
        foreach ($badges as $badge) {
            if($userLang==='ar')
            $badge->badgeDetail->name = $badge->badgeDetail->name_ar;
            unset($badge->badgeDetail->name_ar);
        }
        return response()->json([
            'status' => true,
            'data' => $badges
        ]);
    }
}
