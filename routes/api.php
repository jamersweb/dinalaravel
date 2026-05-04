<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\UserInformationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\NotificationsController;
use App\Http\Controllers\Api\ActivitiesController;
use App\Http\Controllers\Api\BadgesAchievementsController;
use App\Http\Controllers\Api\ChatsController;
use App\Http\Controllers\Api\ProgramsController;
use App\Http\Controllers\Api\SubscriptionsController;
use App\Http\Controllers\Api\AppIntegrationsController;
use App\Http\Controllers\Api\UserSettingsController;
use App\Http\Controllers\Api\BodyStatsController;
use App\Http\Controllers\Api\CommentsController;
use App\Http\Controllers\Api\ExerciseController;
use App\Http\Controllers\Api\GroupChatController;
use App\Http\Controllers\Api\HabitsController;
use App\Http\Controllers\Api\MealPlansController;
use App\Http\Controllers\Api\MealsController;
use App\Http\Controllers\Api\PodcastsController;
use App\Http\Controllers\Api\ProgramSubTrackingController;
use App\Http\Controllers\Api\QuestionsController;
use App\Http\Controllers\Api\SchedulingController;
use App\Http\Controllers\Api\WorkoutController;
use App\Http\Controllers\Api\FoodsController;
use App\Http\Controllers\Api\ConsultationFormController;
use App\Http\Controllers\Api\MealPhotosController;
use App\Http\Controllers\Api\HabitListsController;
use App\Http\Controllers\Api\WeightTrackingController;
use App\Http\Controllers\Api\TagsController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\LanguageController;
use App\Mail\contactEmail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/




//auth - Rate limited to prevent brute force attacks
Route::middleware(['throttle:5,1'])->group(function () {
    Route::post('signup', [AuthController::class, 'signup']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgotPassword', [AuthController::class, 'forgotPassword']);
    Route::post('updatePassword', [AuthController::class, 'updatePassword']);
});

// Less sensitive auth routes
Route::post('emailVerification', [AuthController::class, 'emailVerification'])->middleware('throttle:10,1');
Route::post('resend-email', [AuthController::class, 'resendEmail'])->middleware('throttle:3,1');

// Admin-only clone routes (should be moved to admin panel)
Route::middleware(['auth:api', 'checkAdmin', 'throttle:10,1'])->group(function () {
    Route::get('clone-food', [FoodsController::class, 'convertFoodToArabic']);
    Route::get('clone-meal', [MealsController::class, 'convertMealsToArabic']);
});

// Public routes
Route::get('languages', [LanguageController::class, 'index']);
Route::get('get-all-tags-by-types', [ExerciseController::class, 'getTagsByTypes']);

// Media routes: public so images load (exercise thumbnails, meals, etc. - CachedNetworkImage doesn't attach auth token)
Route::get('media/{type}/{filename}', [MediaController::class, 'show'])->name('api.media.show');

// RevenueCat webhook - no auth, validates REVENUECAT_WEBHOOK_AUTH header
Route::post('webhook/revenuecat', \App\Http\Controllers\Api\RevenueCatWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('webhook.revenuecat');

// Public consultation form routes (before auth)
Route::post('submit-consultation', [ConsultationFormController::class, 'submitConsultation']);
Route::get('check-consultation-status', [ConsultationFormController::class, 'checkConsultationStatus'])->middleware('auth:api');
Route::get('get-consultation', [ConsultationFormController::class, 'getConsultation'])->middleware('auth:api');

Route::group(['middleware' => ['auth:api', 'checkUser'], 'json.response'], function () {

	//temporary
	// Route::get('unsub/{id}',[ProgramsController::class,'unsubProg']);
	// Route::get('create-settings',[UserSettingsController::class,'createSettings']);

	//auth
	Route::get('token-validity', [AuthController::class, 'isTokenValid']);
	Route::get('app-start-checks', [AuthController::class, 'appStartupChecks']);
	Route::post('logout', [AuthController::class, 'logout']);
	Route::post('delete-account', [AuthController::class, 'deleteAccount']);

	// Fitbit OAuth tokens (optional; does not require active subscription)
	Route::post('set-fitbit-auth', [AppIntegrationsController::class, 'setFitbitAuth']);
	Route::get('get-fitbit-auth', [AppIntegrationsController::class, 'getFitbitAuth']);

	//payments - RevenueCat only (Stripe/refund removed)
	Route::get('get-subscriptions', [SubscriptionsController::class, 'getSubscriptions']);
	Route::get('get-subscription-status', [SubscriptionsController::class, 'getSubscriptionStatus']);

	Route::get('chat-messages', [ChatsController::class, 'userChatMessages']);
	Route::get('get-stats-sequence', [UserSettingsController::class, 'getStatsSequence']);
	Route::get('check-questions', [UserInformationController::class, 'checkQuestions']);
	Route::get('get-questions', [QuestionsController::class, 'getQuetions']); // Get questions should be accessible before consultation completion

	// Routes that should be accessible after login, even before subscription/consultation checks
	Route::get('userdata', [UserInformationController::class, 'GetUserData']);
	// Allow browsing program catalog without subscription (subscribe-program, program-detail, etc. still require subscription)
	Route::get('all-programs', [ProgramsController::class, 'getAllProgramsUsers']);
	Route::get('master-workouts', [WorkoutController::class, 'allWorkoutsList']);
	Route::get('workout-detail/{id}', [WorkoutController::class, 'detailedWorkout']);

	// Read/catalog: logged-in users (no active subscription or consultation required)
	Route::get('get-my-programs', [ProgramsController::class, 'getUserRegisteredPrograms']);
	Route::get('get-tags', [TagsController::class, 'getTags']);
	Route::get('get-all-tags', [ExerciseController::class, 'getTags']);
	Route::get('get-all-exercises', [ExerciseController::class, 'getExercisesForUsers']);
	Route::post('search-exercises', [ExerciseController::class, 'searchExercise']);
	Route::get('exercise-alternates/{id}', [ExerciseController::class, 'exerciseAlternates']);
	Route::get('discover-meals', [MealsController::class, 'discoverMeals']);
	Route::get('get-meal-plans', [MealPlansController::class, 'getMealPlans']);
	Route::get('get-foods', [FoodsController::class, 'getFoodsForMobile']);
	Route::get('my-meals', [MealsController::class, 'myPastMeals']);
	Route::get('meals-history', [MealsController::class, 'mealsHistory']);
	Route::get('meal-comments/{id}', [MealsController::class, 'mealComments']);
	Route::get('meal-detail/{id}', [MealsController::class, 'mealDetails']);
	Route::get('my-workout-detail/{id}', [WorkoutController::class, 'myworkout']);
	Route::get('program-detail/{id}', [ProgramSubTrackingController::class, 'getProgramDetail']);
	Route::post('getUserNotification', [NotificationsController::class, 'getUserNotifications']);

	// Sync RevenueCat subscription to backend when app has isPro but webhook hasn't synced (e.g. anonymous app_user_id)
	Route::post('sync-revenuecat-subscription', [AuthController::class, 'syncRevenueCatSubscription']);

	//  Submit consultation answers
	Route::post('submit-answers', [QuestionsController::class, 'submitAnswers']);
	Route::group(['middleware' => ['checkSub', 'checkConsultation']], function () {

		//chats
		Route::get('chat-convo', [ChatsController::class, 'chatConvo']);
		Route::get('delete-message/{id}', [ChatsController::class, 'deleteMessage']);
		Route::post('send-text-message', [ChatsController::class, 'sendTextMessageUser'])->middleware('checkFullAccess');
		Route::post('send-file-message', [ChatsController::class, 'sendFileMessageUser'])->middleware('checkFullAccess');
		Route::get('my-groups', [GroupChatController::class, 'allGroups']);
		Route::get('leave-group/{id}', [GroupChatController::class, 'leaveGroup']);
		Route::get('delete-group-message/{id}', [GroupChatController::class, 'deleteMessage']);
		Route::post('send-group-text-message', [GroupChatController::class, 'sendTextMessage'])->middleware('checkFullAccess');
		Route::post('send-group-file-message', [GroupChatController::class, 'sendFileMessageUser'])->middleware('checkFullAccess');
		Route::get('group-chat-messages/{id}', [GroupChatController::class, 'groupMessages']);
		Route::get('group-members/{id}', [GroupChatController::class, 'groupMembers']);


		// user information routes (require active subscription + completed consultation)
		Route::post('uploaduserprofilepicture', [UserInformationController::class, 'uploadUserProfilePicture']);
		Route::post('updateuserprofilepicture', [UserInformationController::class, 'updateUserProfilePicture']);
		Route::post('posturepicture', [UserInformationController::class, 'posturePicture']);
		Route::get('getpostureimages', [UserInformationController::class, 'getPostureImages']);
		Route::get('user-badges', [BadgesAchievementsController::class, 'getmyBadges']);
		Route::get('get-body-stat', [UserInformationController::class, 'getBodyStat']);
		Route::get('get-user-information', [UserInformationController::class, 'getUserInformation']);
		Route::post('update-user-information', [UserInformationController::class, 'updateUserInformation']);
		Route::get('posture-upload-date', [UserInformationController::class, 'postureUploadDate']);

		Route::post('submit-review', [UserInformationController::class, 'submitReview']);
		Route::get('my-reviews/{id}', [UserInformationController::class, 'myReviews']);
		Route::post('post-comment', [CommentsController::class, 'postComment']);
		Route::get('type-wise-comments', [CommentsController::class, 'typeWiseComments']);
		Route::get('specific-entity-comments', [CommentsController::class, 'specificEntityComments']);

		// programs (all-programs moved above - browsable without subscription)
		Route::get('get-beginner-programs', [ProgramsController::class, 'showBeginnerPrograms']);
		Route::get('subscribe-program/{id}', [ProgramSubTrackingController::class, 'subscribeProgram']);
		Route::get('pause-program/{id}', [ProgramSubTrackingController::class, 'pauseProgram']);
		Route::get('start-program/{id}', [ProgramSubTrackingController::class, 'startProgram']);
		Route::get('resume-program/{id}', [ProgramSubTrackingController::class, 'resumeProgram']);
		Route::get('reset-program/{id}', [ProgramSubTrackingController::class, 'resetProgram']);
		Route::get('unsub-program/{id}', [ProgramSubTrackingController::class, 'unsubProgram']);
		Route::post('workout-completed', [ProgramSubTrackingController::class, 'workoutCompleted']);
		Route::post('exercise-completed', [ProgramSubTrackingController::class, 'exerciseCompleted']);

		//notifications
		//User Settings
		Route::get('get-settings', [UserSettingsController::class, 'getSettings']);
		Route::post('update-settings', [UserSettingsController::class, 'updateSettings']);
		Route::post('update-stats-sequence', [UserSettingsController::class, 'updateSequence']);
		Route::get('turn-off-tutorial', [UserSettingsController::class, 'turnOffTutorial']);

		//user activities
		Route::post('create-user-activity', [ActivitiesController::class, 'createUserActivity']);
		Route::post('delete-activity', [ActivitiesController::class, 'deleteActivity']);
		Route::get('get-user-activities', [ActivitiesController::class, 'getUserActivities']);

		//user stats
		Route::post('set-steps-data', [BodyStatsController::class, 'setStepsData']);
		Route::post('set-sleep-data', [BodyStatsController::class, 'setSleepData']);
		Route::post('set-calories-burn-data', [BodyStatsController::class, 'setCaloriesBurn']);
		Route::post('set-resting-heartrate-data', [BodyStatsController::class, 'setRestingHeartRate']);
		Route::post('set-blood-pressure-data', [BodyStatsController::class, 'setBloodPressure']);
		Route::post('set-body-weight', [BodyStatsController::class, 'setWeight']);
		Route::post('get-specific-body-stats', [BodyStatsController::class, 'getbodyStatsByDate']);
		Route::post('get-steps-by-date', [BodyStatsController::class, 'getsStepsByDate']);
		Route::post('get-sleep-by-date', [BodyStatsController::class, 'getSleepByDate']);
		Route::post('get-weight-by-date', [BodyStatsController::class, 'getWeightByDate']);
		Route::post('get-fat-by-date', [BodyStatsController::class, 'getFatByDate']);
		Route::post('get-calories-burn-by-date', [BodyStatsController::class, 'getCaloriesBurnByDate']);
		Route::post('get-resting-hr-by-date', [BodyStatsController::class, 'getRestingHrByDate']);
		Route::post('get-blood-pressure-by-date', [BodyStatsController::class, 'getBloodPressureByDate']);
		Route::post('get-lean-body-mass-by-date', [BodyStatsController::class, 'getLeanBodyMassByDate']);
		Route::post('get-sleep', [BodyStatsController::class, 'getSleep']);
		Route::post('get-steps', [BodyStatsController::class, 'getSteps']);
		Route::post('get-resting-hr', [BodyStatsController::class, 'getRestingHR']);
		Route::post('get-blood-pressure', [BodyStatsController::class, 'getBloodPressure']);
		Route::post('get-calories-burn', [BodyStatsController::class, 'getCaloriesBurn']);
		Route::post('get-lean-body-mass', [BodyStatsController::class, 'getLeanBodyMass']);
		Route::post('get-weight', [BodyStatsController::class, 'getWeight']);
		Route::post('get-body-fat', [BodyStatsController::class, 'getBodyFat']);
		Route::get('get-user-stats', [BodyStatsController::class, 'getUserStats']);

		//scheduling routes
		Route::post('schedule-task', [SchedulingController::class, 'scheduleTask']);
		Route::get('things-to-do', [SchedulingController::class, 'thingsToDo']);
		Route::post('add-task', [SchedulingController::class, 'addTasks']);
		Route::post('thing-task-done', [SchedulingController::class, 'taskThingDone']);
		Route::post('get-task', [SchedulingController::class, 'getTask']);
		Route::post('update-task', [SchedulingController::class, 'updateTask']);
		Route::get('full-month-tasks', [SchedulingController::class, 'monthData']);
		Route::get('full-month-tasks-last-30', [SchedulingController::class, 'last30DaysTasks']);
		Route::get('delete-task/{id}', [SchedulingController::class, 'deleteScheduledTasks']);

		//exercises (browse/search moved above; mutations stay subscription-gated)
		Route::post('replace-exercise', [ExerciseController::class, 'replaceExercise']);
		Route::get('exercise-weight-progress', [ExerciseController::class, 'weightProgress']);

		//workouts
		Route::post('create-users-workout', [WorkoutController::class, 'createUserDefinedWorkouts'])->middleware('checkFullAccess');
		// Route::post('submit-workout-feedback',[WorkoutController::class,'workoutFeedback']);

		//Habits
		Route::get('habit-task-detail/{id}', [HabitsController::class, 'habitTaskDetail']);
		Route::get('my-habits-list', [HabitsController::class, 'myHabitsList']);
		Route::get('habit-task-progress/{id}', [HabitsController::class, 'habitTaskProgress']);

		//Meals
		Route::post('create-user-meal', [MealsController::class, 'createUserMeal']);
		Route::post('get-meal-calories-by-date', [MealsController::class, 'getMealCaloriesByDate']);
		Route::post('get-calories-for-graph', [MealsController::class, 'getCaloriesForGraph']);
		Route::post('get-month-meals', [MealsController::class, 'getMonthMeals']);
		Route::post('create-comment', [MealsController::class, 'pasteComment']);
		// Note: Tag filtering added to discoverMeals - use ?tags=tag1,tag2 query parameter

		//Meal Plan
		Route::get('subscribe-meal-plan/{id}', [MealPlansController::class, 'subscribePlan']);
		Route::get('my-meal-plans', [MealPlansController::class, 'myMealPlans']);
		Route::get('meal-plan-detail/{id}', [MealPlansController::class, 'planDetail']);
		Route::get('meal-plan-preview/{id}', [MealPlansController::class, 'planPreview']);
		Route::get('meal-plan-view/{id}', [MealPlansController::class, 'getMealPlanWithView']);
		Route::post('plan-meal-complete', [MealPlansController::class, 'mealComplete']);
		Route::get('meal-plan-pdf/{id}', [MealPlansController::class, 'downloadPDF']);
		Route::post('switch-meal-plan/{id}', [MealPlansController::class, 'switchMealPlan']);

		//Meal Photos
		Route::post('upload-meal-photo', [MealPhotosController::class, 'uploadMealPhoto']);
		Route::get('meal-photos', [MealPhotosController::class, 'getMealPhotos']);
		Route::get('meal-photo/{id}', [MealPhotosController::class, 'getMealPhoto']);
		Route::post('meal-photo-comment', [MealPhotosController::class, 'addComment']);
		Route::get('meal-photo-comments/{id}', [MealPhotosController::class, 'getComments']);

		//Habit Lists
		Route::get('my-habit-lists', [HabitListsController::class, 'myHabitLists']);
		Route::post('mark-habit-complete', [HabitListsController::class, 'markHabitComplete']);
		Route::get('habit-progress', [HabitListsController::class, 'getHabitProgress']);

		//Weight Tracking
		Route::post('track-exercise-weight', [WeightTrackingController::class, 'trackWeight']);
		Route::get('exercise-weight-history/{id}', [WeightTrackingController::class, 'getWeightHistory']);
		Route::get('personal-bests', [WeightTrackingController::class, 'getPersonalBests']);
		Route::get('last-weight/{id}', [WeightTrackingController::class, 'getLastWeight']); // Get last weight for exercise

		//Program Switching
		Route::post('switch-program/{id}', [ProgramSubTrackingController::class, 'switchProgram']);
	});
});

Route::post('send-email-to-admin', function (Request $request) {
	$data['from_name'] = $request->name;
	$data['from_email'] = $request->email;
	$data['message'] = $request->message;
	$adminMail = User::where('role', 2)->pluck('email')->first();
	Mail::to($adminMail)->send(new contactEmail($data));
	return response()->json([
		'status' => true,
		'message' => 'Email Sent to Admin.'
	]);
});
Route::get('get-all-podcasts', [PodcastsController::class, 'getAllPodcasts']);

require __DIR__ . '/cms.php';

// Public media route - registered after cms.php so it takes precedence (cms media route was removed).
// Mobile app loads images via CachedNetworkImage without auth token.
Route::get('media/{type}/{filename}', [MediaController::class, 'show'])->name('api.media.show');