<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AutomatedMessage;
use App\Models\Chat;
use App\Models\Message;
use App\Models\PosturePicture;
use App\Models\ProgramSub;
use App\Models\ScheduledTask;
use App\Models\STask;
use App\Models\UserSub;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\UserSetting;
use App\Models\WeeklyWorkout;
use App\Models\WeekWiseProgram;
use App\Models\HabitList;
use App\Models\UserHabitAssignment;
use App\Models\UserHabitCompletion;
use App\Models\HabitListItem;
use App\Traits\ActivitiesTrait;
use App\Traits\NotificationsTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CronJobsController extends Controller
{
    use NotificationsTrait,ActivitiesTrait;

    // to be run once every hour daily
    function dailyTaskNotification(){
        // this code is to prevent the double running of cron
        if (file_exists('/tmp/daily-task-notification.lock')) {
            return;
        }
        file_put_contents('/tmp/daily-task-notification.lock', time());
        // this code is to prevent the double running of cron

        //logging
        Log::channel('cron_jobs')->info('Enter Cronjob------------'.__METHOD__.'-------------');

        $userIds = UserDetail::where('subscription_status','active')->pluck('user_id')->toArray();
        $users = User::whereIn('id',$userIds)->where('role',1)->get(['id','fcm_token']);
        $timeNow = Carbon::now()->format('H:i');
        $sendToUsersCount = 0;
        $sendToUsersIds = [];
        $notiFcms = [];
        foreach ($users as $user) {
            $userTime = UserSetting::where('user_id',$user->id)->pluck('task_noti_time')->first();
            //logging
            Log::channel('cron_jobs')->info('Inside users loop user_time: ('.$userTime.') time_now: ('.$timeNow.') -----User Id: '.$user->id);

            if($userTime===$timeNow){
                //logging
                Log::channel('cron_jobs')->info('Inside users loop, time matched -----User Id: '.$user->id);
                $send = false;
                $scheduledTasks = ScheduledTask::where('user_id',$user->id)->whereDate('date_stamp',Carbon::today())->with('tasks')->first();
                if($scheduledTasks){
                    $userLang = $this->userSelecetdLanguage($user->id);
                    //logging
                    Log::channel('cron_jobs')->info('Inside users loop, task exist, tasks count:'.count($scheduledTasks->tasks).' -----User Id: '.$user->id);
                    foreach ($scheduledTasks->tasks as $task) {
                        //logging
                        Log::channel('cron_jobs')->info('Inside users loop, task exist, task type:'.$task->type.' | task status: '.$task->status.' -----User Id: '.$user->id);
                        if($task->status===0){
                            if($task->type==='workout'){
                                $notiContentEn = config('responses.workout_reminder.en');
                                $notiContentAr = config('responses.workout_reminder.ar');
                            } else if($task->type==='activity'){
                                $notiContentEn = config('responses.act_reminder.en');
                                $notiContentAr = config('responses.act_reminder.ar');
                            } else if($task->type==='meal') {
                                $notiContentEn = config('responses.meal_reminder.en');
                                $notiContentAr = config('responses.meal_reminder.ar');
                            } else if($task->type==='bodystat') {
                                $notiContentEn = config('responses.bodystats_reminder.en');
                                $notiContentAr = config('responses.bodystats_reminder.ar');
                            } else if($task->type==='habit') {
                                $notiContentEn = config('responses.habit_reminder.en');
                                $notiContentAr = config('responses.habit_reminder.ar');
                            } else 
                            continue;
                            //logging
                            Log::channel('cron_jobs')->info('Inside users loop, sending notification. -----User Id: '.$user->id);
                            $notiTitleEn = config('responses.daily_reminder.en');
                            $notiTitleAr = config('responses.daily_reminder.ar');
                            $this->storeNotification($user->id,$notiTitleEn,$notiTitleAr,$notiContentEn,$notiContentAr);
                            if($userLang==='en')
                            $this->sendFirebaseNotification([$user->fcm_token],$notiTitleEn,$notiContentEn);
                            else
                            $this->sendFirebaseNotification([$user->fcm_token],$notiTitleAr,$notiContentAr);
                            array_push($sendToUsersIds,$user->id);
                            array_push($notiFcms,$user->fcm_token);
                            $send = true;
                        }
                    }
                }
                if($send)
                $sendToUsersCount++;
            }
        }
        //logging
        Log::channel('cron_jobs')->info('Exit Cronjob------------'.__METHOD__.'-------------Sent to '.$sendToUsersCount.' users');
        unlink('/tmp/daily-task-notification.lock');
        return response()->json([
            'status' => true,
            'message' => 'Daily task notifications sent',
            'users_notified' => $sendToUsersCount
        ]);
    }

    /**
     * Daily habit list reminders - to be run once daily (e.g., at 9 AM)
     * Sends reminders to users to check off their habit lists
     */
    function dailyHabitListReminder(){
        // Prevent double running
        $lockFile = '/tmp/daily-habit-list-reminder.lock';
        if (file_exists($lockFile)) {
            $lockTime = file_get_contents($lockFile);
            // If lock is older than 1 hour, remove it (assume previous run failed)
            if (time() - $lockTime > 3600) {
                unlink($lockFile);
            } else {
                return;
            }
        }
        file_put_contents($lockFile, time());

        Log::channel('cron_jobs')->info('Enter Cronjob------------'.__METHOD__.'-------------');

        try {
            // Get all users with assigned habit lists
            $usersWithHabits = UserHabitAssignment::with('user', 'habitList')
                ->whereHas('user', function($q) {
                    $q->where('role', 1)->whereNotNull('fcm_token');
                })
                ->get()
                ->groupBy('user_id');

            $notifiedCount = 0;

            foreach ($usersWithHabits as $userId => $assignments) {
                $user = $assignments->first()->user;
                if (!$user) continue;

                $userLang = $this->userSelecetdLanguage($user->id);
                
                // Get habit list names
                $habitListNames = $assignments->map(function($assignment) {
                    return $assignment->habitList->name;
                })->toArray();
                
                $listNamesText = implode(', ', $habitListNames);
                
                $notiTitle = 'Daily Habit Check-in Reminder';
                $notiTitleAr = 'تذكير بمراجعة العادات اليومية';
                $notiContent = "Don't forget to check off your habits today! You have {$listNamesText} to complete.";
                $notiContentAr = "لا تنس التحقق من عاداتك اليوم! لديك {$listNamesText} لإكمالها.";

                // Store notification
                $this->storeNotification($user->id, $notiTitle, $notiTitleAr, $notiContent, $notiContentAr);

                // Send push notification
                if (!empty($user->fcm_token)) {
                    if ($userLang === 'en') {
                        $this->sendFirebaseNotification([$user->fcm_token], $notiTitle, $notiContent);
                    } else {
                        $this->sendFirebaseNotification([$user->fcm_token], $notiTitleAr, $notiContentAr);
                    }
                }

                $notifiedCount++;
            }

            Log::channel('cron_jobs')->info('Exit Cronjob------------'.__METHOD__.'-------------Notified '.$notifiedCount.' users');

            unlink($lockFile);

            return response()->json([
                'status' => true,
                'message' => 'Habit list reminders sent',
                'users_notified' => $notifiedCount
            ]);

        } catch (\Exception $e) {
            Log::channel('cron_jobs')->error('Error in dailyHabitListReminder: '.$e->getMessage());
            if (file_exists($lockFile)) {
                unlink($lockFile);
            }
            return response()->json([
                'status' => false,
                'message' => 'Error sending habit list reminders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check users not completing habits and send notifications
     * Should run daily in the evening (e.g., 8 PM) to check if users completed their habits
     */
    function habitListCompletionCheck(){
        $lockFile = '/tmp/habit-list-completion-check.lock';
        if (file_exists($lockFile)) {
            $lockTime = file_get_contents($lockFile);
            if (time() - $lockTime > 3600) {
                unlink($lockFile);
            } else {
                return;
            }
        }
        file_put_contents($lockFile, time());

        Log::channel('cron_jobs')->info('Enter Cronjob------------'.__METHOD__.'-------------');

        try {
            // Get all users with assigned habit lists
            $assignments = UserHabitAssignment::with(['user', 'habitList.items'])
                ->whereHas('user', function($q) {
                    $q->where('role', 1)->whereNotNull('fcm_token');
                })
                ->get();

            $notCompletedUsers = [];
            $doingWellUsers = [];

            foreach ($assignments as $assignment) {
                $user = $assignment->user;
                $habitList = $assignment->habitList;
                $allItems = $habitList->items->pluck('id');
                
                if ($allItems->isEmpty()) continue;

                // Check completions for today
                $completedToday = UserHabitCompletion::where('user_id', $user->id)
                    ->whereIn('habit_list_item_id', $allItems)
                    ->where('completed_date', Carbon::today())
                    ->count();

                $totalItems = $allItems->count();
                $completionPercentage = ($completedToday / $totalItems) * 100;

                // If less than 50% completed, send reminder notification
                if ($completionPercentage < 50 && $completedToday < $totalItems) {
                    $notCompletedUsers[] = [
                        'user' => $user,
                        'habit_list' => $habitList,
                        'completed' => $completedToday,
                        'total' => $totalItems,
                    ];
                }
                
                // If 100% completed, mark as doing well (positive notification sent in markHabitComplete)
                // But also check for consistent performance (3+ days in a row)
                if ($completionPercentage >= 100) {
                    // Check last 3 days
                    $last3Days = UserHabitCompletion::where('user_id', $user->id)
                        ->whereIn('habit_list_item_id', $allItems)
                        ->where('completed_date', '>=', Carbon::today()->subDays(2))
                        ->get()
                        ->groupBy('completed_date');

                    // If completed all items for last 3 days
                    $allDaysComplete = true;
                    for ($i = 0; $i < 3; $i++) {
                        $date = Carbon::today()->subDays($i);
                        $completionsForDate = $last3Days->get($date->toDateString());
                        if (!$completionsForDate || $completionsForDate->count() < $totalItems) {
                            $allDaysComplete = false;
                            break;
                        }
                    }

                    if ($allDaysComplete && !in_array($user->id, array_column($doingWellUsers, 'user_id'))) {
                        $doingWellUsers[] = [
                            'user_id' => $user->id,
                            'user' => $user,
                            'habit_list' => $habitList,
                            'streak_days' => 3,
                        ];
                    }
                }
            }

            // Send notifications for users not completing habits
            foreach ($notCompletedUsers as $data) {
                $user = $data['user'];
                $habitList = $data['habit_list'];
                $userLang = $this->userSelecetdLanguage($user->id);

                $notiTitle = 'Complete Your Habits!';
                $notiTitleAr = 'أكمل عاداتك!';
                $remaining = $data['total'] - $data['completed'];
                $notiContent = "You still have {$remaining} habit(s) to complete in '{$habitList->name}' today. Keep going!";
                $notiContentAr = "لا يزال لديك {$remaining} عادة(ات) لإكمالها في '{$habitList->name}' اليوم. استمر!";

                $this->storeNotification($user->id, $notiTitle, $notiTitleAr, $notiContent, $notiContentAr);

                if (!empty($user->fcm_token)) {
                    if ($userLang === 'en') {
                        $this->sendFirebaseNotification([$user->fcm_token], $notiTitle, $notiContent);
                    } else {
                        $this->sendFirebaseNotification([$user->fcm_token], $notiTitleAr, $notiContentAr);
                    }
                }
            }

            // Send positive notifications for users doing well (3+ day streak)
            foreach ($doingWellUsers as $data) {
                $user = $data['user'];
                $habitList = $data['habit_list'];
                $userLang = $this->userSelecetdLanguage($user->id);

                $notiTitle = 'Amazing Streak!';
                $notiTitleAr = 'سلسلة مذهلة!';
                $notiContent = "Wow! You've completed all habits in '{$habitList->name}' for {$data['streak_days']} days in a row! You're doing amazing!";
                $notiContentAr = "رائع! لقد أكملت جميع العادات في '{$habitList->name}' لمدة {$data['streak_days']} أيام متتالية! أنت تبلي بلاءً حسناً!";

                $this->storeNotification($user->id, $notiTitle, $notiTitleAr, $notiContent, $notiContentAr);

                if (!empty($user->fcm_token)) {
                    if ($userLang === 'en') {
                        $this->sendFirebaseNotification([$user->fcm_token], $notiTitle, $notiContent);
                    } else {
                        $this->sendFirebaseNotification([$user->fcm_token], $notiTitleAr, $notiContentAr);
                    }
                }
            }

            Log::channel('cron_jobs')->info('Exit Cronjob------------'.__METHOD__.'-------------Checked '.count($assignments).' assignments, notified '.count($notCompletedUsers).' users about incomplete habits, and '.count($doingWellUsers).' users about their streak');

            unlink($lockFile);

            return response()->json([
                'status' => true,
                'message' => 'Habit completion check completed',
                'not_completed_notified' => count($notCompletedUsers),
                'doing_well_notified' => count($doingWellUsers),
            ]);

        } catch (\Exception $e) {
            Log::channel('cron_jobs')->error('Error in habitListCompletionCheck: '.$e->getMessage());
            if (file_exists($lockFile)) {
                unlink($lockFile);
            }
            return response()->json([
                'status' => false,
                'message' => 'Error checking habit completions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    function postureReminderNotification(){
        $lockFile = '/tmp/posture-upload-notification.lock';
        if (file_exists($lockFile)) {
            $lockTime = file_get_contents($lockFile);
            if (time() - $lockTime > 3600) {
                unlink($lockFile);
            } else {
                return;
            }
        }
        file_put_contents($lockFile, time());

        Log::channel('cron_jobs')->info('Enter Cronjob------------'.__METHOD__.'-------------');

        $users = User::where('role',1)->whereNotNull('fcm_token')->get(['id','fcm_token']);
        $notifiedCount = 0;
        foreach ($users as $user) {
            $lastPosture = PosturePicture::where('user_id',$user->id)->orderBy('created_at','desc')->first(['next_upload_date']);
            if(!is_null($lastPosture)){
                $nextUploadDate = Carbon::parse($lastPosture->next_upload_date);
                $today = Carbon::today();
                if($today->gte($nextUploadDate)){
                    $userLang = $this->userSelecetdLanguage($user->id);
                    $notiTitleEn = config('responses.posture_reminder.en');
                    $notiTitleAr = config('responses.posture_reminder.ar');
                    $notiContentEn = config('responses.posture_reminder_content.en');
                    $notiContentAr = config('responses.posture_reminder_content.ar');
                    $this->storeNotification($user->id,$notiTitleEn,$notiTitleAr,$notiContentEn,$notiContentAr);
                    if($userLang==='en')
                    $this->sendFirebaseNotification([$user->fcm_token],$notiTitleEn,$notiContentEn);
                    else
                    $this->sendFirebaseNotification([$user->fcm_token],$notiTitleAr,$notiContentAr);
                    $notifiedCount++;
                }
            }
        }
        Log::channel('cron_jobs')->info('Exit Cronjob------------'.__METHOD__.'-------------Notified '.$notifiedCount.' users');
        unlink($lockFile);
        return response()->json([
            'status' => true,
            'message' => 'Posture reminder notifications sent',
            'users_notified' => $notifiedCount
        ]);
    }
}
