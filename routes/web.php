<?php

use App\Http\Controllers\Api\CronJobsController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Middleware\AdminOnlyMiddleware;
use App\Models\Achievement;
use App\Models\Meal;
use App\Models\Program;
use App\Models\ProgramPhase;
use App\Models\ProgramPhaseWorkout;
use App\Models\ProgramSub;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\UserSub;
use App\Models\WeeklyWorkout;
use App\Models\WeekWiseProgram;
use App\Models\Workout;
use App\Models\WorkoutCompilation;
use App\Models\WorkoutExercise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


// Route::get('test-email', 'App\Http\Controllers\MailController@sendEmail');

// SECURITY: Test notification route - should be removed or protected in production
Route::get('test-notification/{id}', function ($id) {
    if (config('app.env') === 'production') {
        abort(404);
    }
    $data = [
        "registration_ids" => [$id],  // fcm_token/device_tokens of users to recieve noti
        "notification" => [
            "title" => 'testing',
            "body" => 'this is test notification',
        ]
    ];
    $dataString = json_encode($data);
    $SERVER_API_KEY = config('app.fcm_server');
    $headers = [
        'Authorization: key=' . $SERVER_API_KEY,
        'Content-Type: application/json',
    ];

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);

    $response = curl_exec($ch);

    return $response;
});


// Public media route (no auth) - images load in CMS/img tags without Authorization header.
// Uses web routes to avoid any api middleware that may cause 401 on some hosts.
Route::get('media/{type}/{filename}', [MediaController::class, 'show'])->name('media.show');

Route::get('/cms', function () {
    return redirect()->to('/cms/login');
});

// when auth token fails
Route::get('/api-not-authenticated', function () {
    return response()->json([
        'status' => false,
        'message' => 'unAuthorized user'
    ]);
})->name('login');

//cms routes

Route::prefix('cms')->group(function () {
    Route::view('/login', 'cms');
    Route::view('/forgot-password', 'cms');
    Route::view('/overview', 'cms');
    Route::view('/messages', 'cms');
    Route::view('/groups', 'cms');
    Route::view('/teams', 'cms');
    Route::view('/program', 'cms');
    Route::view('/workout', 'cms');
    Route::view('/habit', 'cms');
    Route::view('/exercises', 'cms');
    Route::view('/mealplan', 'cms');
    Route::view('/meals', 'cms');
    Route::view('/food', 'cms');
    Route::view('/payments', 'cms');
    Route::view('/settings', 'cms');
    Route::view('/localization', 'cms');
    Route::view('/ui-strings', 'cms');
    Route::view('/clients', 'cms');
    Route::view('/profile', 'cms');
    Route::view('/podcast', 'cms');
    Route::view('/consultation', 'cms');
    Route::view('/tags', 'cms');
    Route::view('/poc', 'cms');
});
Route::prefix('user')->group(function () {
    Route::view('/login', 'cms');
    Route::view('/dash', 'cms');
});
Route::view('/', 'cms');
Route::view('/AboutMe', 'cms');
Route::view('/CalculateBMR&TDEE', 'cms');
Route::view('/ListOfFoods', 'cms');
Route::view('/ContactUS', 'cms');
Route::view('/Podcast', 'cms');

// cron jobs routes
Route::get('cron-job/daily-task-notification', [CronJobsController::class, 'dailyTaskNotification']);
Route::get('cron-job/posture-upload-notification', [CronJobsController::class, 'postureReminderNotification']);
Route::get('cron-job/daily-habit-list-reminder', [CronJobsController::class, 'dailyHabitListReminder']);
Route::get('cron-job/habit-list-completion-check', [CronJobsController::class, 'habitListCompletionCheck']);
Route::get('cron-job/check-sub-status', [CronJobsController::class, 'checkSubscriptionExpire']);
Route::get('cron-job/testing', [CronJobsController::class, 'testingCron']);
Route::get('cron-job/automated-message', [CronJobsController::class, 'sendAutomatedMessage']);

// SECURITY: Admin-only routes for dangerous operations
// These routes are protected and should only be accessible by authenticated admins
// Consider moving these to Artisan commands for better security
Route::middleware(['auth:api', 'adminOnly'])->group(function () {
    // Route to expire user subscription (admin only)
    Route::post('admin/expire-user-subscription', function (Request $request) {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);
        
        $email = strtolower(trim((string) $request->email));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->where('role', 1)->first();
        if ($user) {
            UserDetail::where('user_id', $user->id)->update(['subscription_status' => 'expired']);
            UserSub::where('user_id', $user->id)->where('status', 'active')->update(['status' => 'expired']);
            $user->api_token = 'x';
            $user->update();
            return response()->json(['status' => true, 'message' => 'Subscription expired successfully']);
        }
        return response()->json(['status' => false, 'message' => 'User not found'], 404);
    });

    // Route to clear test data (admin only) - Consider removing in production
    // WARNING: This route deletes all data from multiple tables. Use with extreme caution!
    Route::post('admin/clear-test-data', function () {
        if (config('app.env') === 'production') {
            return response()->json(['status' => false, 'message' => 'This operation is not allowed in production'], 403);
        }
        
        Achievement::truncate();
        WeeklyWorkout::truncate();
        WeekWiseProgram::truncate();
        ProgramSub::truncate();
        ProgramPhaseWorkout::truncate();
        ProgramPhase::truncate();
        Program::truncate();
        WorkoutCompilation::truncate();
        WorkoutExercise::truncate();
        Workout::truncate();
        
        return response()->json(['status' => true, 'message' => 'Test data cleared successfully']);
    });
});



Route::get('health', function () {
    return true;
});
