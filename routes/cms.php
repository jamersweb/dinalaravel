<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\NotificationsController;
use App\Http\Controllers\Api\ActivitiesController;
use App\Http\Controllers\Api\AutomatedMessagesController;
use App\Http\Controllers\Api\ClientsController;
use App\Http\Controllers\Api\ExerciseController;
use App\Http\Controllers\Api\FoodsController;
use App\Http\Controllers\Api\HabitsController;
use App\Http\Controllers\Api\HabitListsController;
use App\Http\Controllers\Api\MealsController;
use App\Http\Controllers\Api\ChatsController;
use App\Http\Controllers\Api\GroupChatController;
use App\Http\Controllers\Api\MealPlansController;
use App\Http\Controllers\Api\ProgramsController;
use App\Http\Controllers\Api\SubscriptionsController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\UserInformationController;
use App\Http\Controllers\Api\WorkoutController;
use App\Http\Controllers\Api\PodcastsController;
use App\Http\Controllers\Api\ProgramSubTrackingController;
use App\Http\Controllers\Api\QuestionsController;
use App\Http\Controllers\Api\TagsController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\CmsLocalizationController;

Route::get('/test-noti',[NotificationsController::class,'sendNotification']);

// Media: Use public api.media.show from api.php (no auth) - CMS and mobile both use it.
// Removed duplicate auth-protected route that caused 401 for mobile image loading.
//Questions
// Route::get('delete-all-questions',[QuestionsController::class,'deleteAllQuestions']);
// Route::get('store-questions',[QuestionsController::class,'index']);

Route::group(['prefix' => 'cms'], function(){
	Route::post('login', [AuthController::class,'adminLogin']);
    Route::post('/forgotPassword', [AuthController::class,'forgotPasswordAdmin']);
    Route::post('/verify-otp', [AuthController::class,'verifyOTP']);
    Route::post('/update-password-admin', [AuthController::class,'updatePasswordAdmin']);


	Route::group(['middleware' => ['auth:api','checkAdmin'],'json.response'], function(){


		Route::get('admin-notifications', [NotificationsController::class,'getNotifications']);
		Route::get('admin-activities', [ActivitiesController::class,'getActivities']);
		Route::post('activity-categories', [ActivitiesController::class,'getCategories']);
		Route::get('activity-comments/{id}', [ActivitiesController::class,'activityComments']);
        Route::post('comment-on-activity',[ActivitiesController::class,'postActivityComment']);
		Route::get('isAuth', [AuthController::class,'isAuth']);
    	Route::get('logout', [AuthController::class,'logout']);
        Route::get('my-profile',[UserInformationController::class,'myProfile']);
        Route::post('update-profile',[UserInformationController::class,'updateProfile']);
        Route::post('customize-auto-message',[AutomatedMessagesController::class,'customizeMessage']);
        Route::get('switch-auto-msg-status/{id}',[AutomatedMessagesController::class,'switchStatus']);
        Route::get('auto-messages',[AutomatedMessagesController::class,'autoMsgs']);
        Route::get('delete-everything/{code}', [ExerciseController::class,'testDelete']);
        Route::get('auto-tagged-clients',[NotificationsController::class,'autoTaggedClients']);

        // Stats Routes
        Route::get('business-growth',[NotificationsController::class,'businessGrowth']);
        Route::get('sign-ins-per-week',[NotificationsController::class,'signInsPerWeek']);
        Route::get('workouts-per-week',[NotificationsController::class,'workoutsPerWeek']);
        Route::get('exercises-per-week',[NotificationsController::class,'exercisesPerWeek']);
        Route::get('nutrition-per-week',[NotificationsController::class,'nutritionPerWeek']);

		// Exercise Routes
		Route::post('create-exercise', [ExerciseController::class,'createExercise']);
		Route::post('update-exercise', [ExerciseController::class,'updateExercise']);
		Route::post('assign-tags-to-exercises', [ExerciseController::class,'assignTagsToExercises']);
		Route::post('delete-exercises', [ExerciseController::class,'deleteExercises']);
		Route::get('get-all-exercises', [ExerciseController::class,'getAllExercises']);
        Route::get('get-exercise-detail/{id}',[ExerciseController::class,'getExerciseDetail']);

		// Workout Routes
		Route::post('create-workout', [WorkoutController::class,'createWorkout']);
		Route::get('editable-workout/{id}', [WorkoutController::class,'canEditWorkout']);
		Route::post('update-workout', [WorkoutController::class,'updateWorkout']);
		Route::post('create-workout-exercise', [WorkoutController::class,'createWorkoutExercise']);
		Route::get('get-all-workouts', [WorkoutController::class,'getAllWorkouts']);
        Route::get('delete-workout/{id}', [WorkoutController::class,'deleteWorkout']);
        Route::get('get-workout-detail/{id}',[WorkoutController::class,'detailedWorkout']);
        Route::post('delete-detailed-workout',[WorkoutController::class,'delteWorkoutDetaled']);
        Route::get('all-workouts-list',[WorkoutController::class,'allWorkoutsList']);

		// Programs routes
		Route::post('create-new-program',[ProgramsController::class,'createProgram']);
		Route::post('rename-program',[ProgramsController::class,'renameProgram']);
		Route::post('update-program-image',[ProgramsController::class,'updateProgramImage']);
		Route::get('delete-program/{id}',[ProgramsController::class,'deleteProgram']);
		Route::post('add-program-discription',[ProgramsController::class,'addProgramDiscription']);
		Route::post('add-phase-workout',[ProgramsController::class,'addPhaseWorkouts']);
		Route::post('update-phase-workout-display-name',[ProgramsController::class,'changePhaseWorkoutDisplayName']);
        Route::get('get-all-programs',[ProgramsController::class,'getAllPrograms']);
        Route::get('get-program-detail/{id}',[ProgramsController::class,'getDetailWithSubscribers']);
        Route::get('get-phase-detail/{id}',[ProgramsController::class,'getPhaseDetail']);
        Route::post('rename-phase',[ProgramsController::class,'renamePhase']);
        Route::post('add-phase-summary',[ProgramsController::class,'addPhaseSummary']);
        Route::post('remove-workout',[ProgramsController::class,'removeWorkout']);
        Route::get('remove-program-phase/{id}',[ProgramsController::class,'removeProgramPhase']);
        Route::get('get-clients-to-subscribe/{id}',[ProgramsController::class,'clientsListForProgram']);

        Route::post('subscribe-users-to-program',[ProgramSubTrackingController::class,'subscribeUsers']);
        Route::post('unsub-users-from-program',[ProgramSubTrackingController::class,'unSubUsers']);

        // Habits Routes
        Route::post('create-habit-folder',[HabitsController::class,'createFolder']);
        Route::post('rename-habit-folder',[HabitsController::class,'renameFolder']);
        Route::post('delete-habit-folder',[HabitsController::class,'deleteFolder']);
        Route::get('get-habit-folders',[HabitsController::class,'getFolders']);
        Route::post('create-habit',[HabitsController::class,'createHabit']);
        Route::post('edit-habit',[HabitsController::class,'editHabit']);
        Route::get('get-folder-habits/{id}',[HabitsController::class,'getFolderHabits']);
        Route::post('delete-habit',[HabitsController::class,'deleteHabit']);
        Route::get('get-habit-detail/{id}',[HabitsController::class,'getHabitDetail']);
        Route::post('rename-habit-folder',[HabitsController::class,'renameHabitFolder']);
        Route::post('add-habit-to-users',[HabitsController::class,'addUsersHabit']);
        Route::get('all-folders-with-habits',[HabitsController::class,'allFoldersHabits']);
        Route::post('move-habit-to-folder',[HabitsController::class,'moveHabitToFolder']);

        // Habit Lists Routes (Master Lists)
        Route::post('create-habit-list',[HabitListsController::class,'createHabitList']);
        Route::post('update-habit-list/{id}',[HabitListsController::class,'updateHabitList']);
        Route::get('delete-habit-list/{id}',[HabitListsController::class,'deleteHabitList']);
        Route::get('get-all-habit-lists',[HabitListsController::class,'getAllHabitLists']);
        Route::get('get-habit-list-detail/{id}',[HabitListsController::class,'getHabitListDetail']);
        Route::post('assign-habit-list-to-users',[HabitListsController::class,'assignHabitListToUsers']);
        Route::post('unassign-habit-list-from-users',[HabitListsController::class,'unassignHabitListFromUsers']);
        Route::get('get-habit-list-users/{id}',[HabitListsController::class,'getHabitListUsers']);

        //Foods Routes
        Route::post('create-new-food',[FoodsController::class,'createFood']);
        Route::post('edit-food',[FoodsController::class,'updateFood']);
        Route::get('get-foods',[FoodsController::class,'getFoodsList']);
        Route::get('get-specific-foods/{query}',[FoodsController::class,'getSpecificFoodsList']);
        Route::get('food-detail/{id}',[FoodsController::class,'foodDetail']);
        Route::get('food-full-details/{id}',[FoodsController::class,'fullFoodDetail']);
        Route::post('delete-food',[FoodsController::class,'deleteFood']);

        //Meals Routes
        Route::post('create-new-meal',[MealsController::class,'createMeal']);
        Route::post('update-meal',[MealsController::class,'updateMeal']);
        Route::post('delete-meals',[MealsController::class,'deleteMeals']);
        Route::get('get-meals',[MealsController::class,'getMeals']);
        Route::get('get-meal-detail/{id}',[MealsController::class,'mealDetail']);

        //Meal Plans
        Route::post('create-meal-day',[MealPlansController::class,'createMealDay']);
        Route::get('get-meal-days',[MealPlansController::class,'getMealDays']);
        Route::post('delete-meal-day',[MealPlansController::class,'deleteMealDay']);
        Route::get('get-meal-day-detail/{id}',[MealPlansController::class,'getMealDayDetail']);
        Route::post('create-meal-week',[MealPlansController::class,'createMealWeek']);
        Route::post('create-meal-plan',[MealPlansController::class,'createMealPlan']);
        Route::get('get-meal-weeks',[MealPlansController::class,'getMealWeeks']);
        Route::get('get-meal-week-detail/{id}',[MealPlansController::class,'getMealWeekDetail']);
        Route::get('get-meal-plans',[MealPlansController::class,'getMealPlans']);
        Route::get('get-meal-plan-detail/{id}',[MealPlansController::class,'getMealPlanDetail']);
        Route::post('update-meal-day',[MealPlansController::class,'updateMealDay']);
        Route::post('update-meal-week',[MealPlansController::class,'updateMealWeek']);
        Route::post('update-meal-plan',[MealPlansController::class,'updateMealPlan']);

        //Clients Routes
        Route::get('active-clients-list',[ClientsController::class,'activeClients']);
        Route::get('client-answers/{id}',[ClientsController::class,'clientsQuestions']);
        Route::get('client-tags/{id}',[ClientsController::class,'clientTags']);
        Route::post('client-tags-assign',[ClientsController::class,'assignTagClient']);
        Route::get('clients-summary',[ClientsController::class,'cilentsSummary']);
        Route::get('clients-exercises',[ClientsController::class,'cilentsExercises']);
        Route::get('clients-weight',[ClientsController::class,'cilentsWeight']);
        Route::get('clients-payments',[ClientsController::class,'cilentsPayments']);
        Route::get('clients-engagement',[ClientsController::class,'cilentsEngagement']);
        Route::get('clients-nutrition',[ClientsController::class,'cilentsNutrition']);
        Route::get('client-detail/{id}',[ClientsController::class,'clientDetail']);
        Route::get('client-invoices/{id}',[ClientsController::class,'clientInvoices']);
        Route::get('client-consultations/{id}',[ClientsController::class,'userConsults']);
        Route::post('create-consult-message',[ClientsController::class,'createConsult']);
        Route::get('client-postures/{id}',[ClientsController::class,'userPostures'])->middleware('checkNotTeam');
        // Route::post('create-client-attatchment',[ClientsController::class,'createAttatchment']);
        Route::get('activate-deactivate-client/{id}',[ClientsController::class,'clientActivationSwitch']);

        //Chats Routes
        Route::get('client-list-for-new-chat',[ChatsController::class,'clientListForNewChat']);
        Route::get('start-new-chat/{id}',[ChatsController::class,'createNewChat']);
        Route::get('all-chats',[ChatsController::class,'getAllChats']);
        Route::get('chat-messages/{id}',[ChatsController::class,'chatMessages']);
        Route::post('send-text-message',[ChatsController::class,'sendTextMessageAdmin']);
        Route::post('send-file-message',[ChatsController::class,'sendFileMessageAdmin']);
        Route::post('multiple-users-message',[ChatsController::class,'multipleUsersMessage']);
        Route::get('delete-chat/{id}',[ChatsController::class,'deleteChat']);
        Route::get('archive-chat/{id}',[ChatsController::class,'archiveChat']);
        Route::get('unarchive-chat/{id}',[ChatsController::class,'unArchiveChat']);
        Route::get('delete-message/{id}',[ChatsController::class,'deleteMessage']);

        //Group-chat Routes
        Route::get('my-groups',[GroupChatController::class,'allGroups']);
        Route::post('create-group',[GroupChatController::class,'createGroup']);
        Route::post('add-members',[GroupChatController::class,'addMembers']);
        Route::get('group-members/{id}',[GroupChatController::class,'groupMembers']);
        Route::post('remove-members',[GroupChatController::class,'removeMembers']);
        Route::post('rename-group',[GroupChatController::class,'renameGroup']);
        Route::get('leave-group/{id}',[GroupChatController::class,'leaveGroup']);
        Route::get('delete-group/{id}',[GroupChatController::class,'deleteGroup']);
        Route::post('send-group-text-message',[GroupChatController::class,'sendTextMessage']);
        Route::post('send-group-file-message',[GroupChatController::class,'sendFileMessageAdmin']);
        Route::get('group-messages/{id}',[GroupChatController::class,'groupMessages']);
        Route::get('client-list-for-group/{id}',[GroupChatController::class,'clientListToAdd']);
        Route::get('delete-group-message/{id}',[GroupChatController::class,'deleteMessage']);
        Route::post('add-group-description',[GroupChatController::class,'addGroupDesc']);
        Route::get('switch-group-label/{id}',[GroupChatController::class,'switchGroupLabel']);

        //Teams Routes
        Route::get('all-team',[TeamController::class,'allTeam'])->middleware('checkNotTeam');
        Route::post('create-team-member',[TeamController::class,'createTeamMember'])->middleware('checkNotTeam');
        Route::post('edit-team-member',[TeamController::class,'teamMemberEdit'])->middleware('checkNotTeam');
        Route::get('remove-team-member/{ids}',[TeamController::class,'removeTeamMember'])->middleware('checkNotTeam');
        Route::get('team-member-detail/{id}',[TeamController::class,'teamMemberDetail'])->middleware('checkNotTeam');

        //Payments Routes
        // Stripe/refund removed - RevenueCat only
        Route::get('get-all-products',[SubscriptionsController::class,'allProducts']);
        Route::post('create-product',[SubscriptionsController::class,'createProduct']);
        Route::post('edit-product',[SubscriptionsController::class,'editProduct']);
        Route::get('delete-product/{id}',[SubscriptionsController::class,'deleteProduct']);
        Route::get('all-discount-codes',[SubscriptionsController::class,'allDiscountCodes']);
        Route::post('create-discount-code',[SubscriptionsController::class,'createDiscountCode']);
        Route::get('disable-discount-code/{id}',[SubscriptionsController::class,'disableCode']);

        //Podcast
        Route::post('create-podcast',[PodcastsController::class,'createPodcast']);
        Route::get('get-all-podcasts',[PodcastsController::class,'getAllPodcasts']);
        Route::post('delete-podcast',[PodcastsController::class,'deletePodcast']);
        Route::post('update-podcast',[PodcastsController::class,'updatePodcast']);

        //Tags
        Route::post('create-tag',[TagsController::class,'createTag']);
        Route::get('delete-tag/{id}',[TagsController::class,'deleteTag']);
        Route::get('get-tags',[TagsController::class,'getTags']);
        Route::get('get-uncategorized-tags',[TagsController::class,'uncategorizedTags']);
        Route::post('update-tag',[TagsController::class,'updateTag']);

        //Consultations
        Route::get('consultation-form',[QuestionsController::class,'consultationForm']);
        Route::get('delete-question/{id}',[QuestionsController::class,'deleteQuestion']);
        Route::post('create-question',[QuestionsController::class,'createQuestion']);
        Route::post('update-question',[QuestionsController::class,'updateQuestion']);

        // Localization (languages, bulk translate, per-item overrides)
        Route::get('languages-admin', [CmsLocalizationController::class, 'index']);
        Route::post('languages-admin', [CmsLocalizationController::class, 'store']);
        Route::post('languages-admin/{id}', [CmsLocalizationController::class, 'update']);
        Route::post('bulk-translate-content', [CmsLocalizationController::class, 'bulkTranslate']);
        Route::post('meal-locale-translations', [CmsLocalizationController::class, 'updateMealTranslations']);
        Route::post('exercise-locale-translations', [CmsLocalizationController::class, 'updateExerciseTranslations']);
        Route::post('program-locale-translations', [CmsLocalizationController::class, 'updateProgramTranslations']);
        Route::post('workout-locale-translations', [CmsLocalizationController::class, 'updateWorkoutTranslations']);
        Route::get('localization/export-en-ui-strings', [CmsLocalizationController::class, 'exportEnUiStrings']);
        Route::get('localization/ui-strings', [CmsLocalizationController::class, 'uiStringsEditorData']);
        Route::post('localization/ui-strings', [CmsLocalizationController::class, 'saveUiStrings']);
        Route::get('localization/ui-strings-export-arb', [CmsLocalizationController::class, 'exportUiStringsArb']);
        Route::get('localization/export-top-arbs-zip', [CmsLocalizationController::class, 'exportTopSpokenArbsZip']);
	});
});
