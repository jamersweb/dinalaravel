<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\AppUsageCount;
use App\Models\Attatchment;
use App\Models\BodyStats;
use App\Models\Consultation;
use App\Models\DiscountCode;
use App\Models\ExerciseCompilation;
use App\Models\ExercisesTracking;
use App\Models\NutritionCompilance;
use App\Models\Payment;
use App\Models\PosturePicture;
use App\Models\Program;
use App\Models\ProgramPhase;
use App\Models\ProgramsTracking;
use App\Models\ProgramSub;
use App\Models\ProgramSubscriber;
use App\Models\Question;
use App\Models\User;
use App\Models\UserSetting;
use App\Models\UserStat;
use App\Models\WeeksTracking;
use App\Models\WorkoutsTracking;
use App\Models\Subscription;
use App\Models\Tag;
use App\Models\UserAnswer;
use App\Models\UserDetail;
use App\Models\UserSub;
use App\Models\WeeklyWorkout;
use App\Models\WeekWiseProgram;
use App\Models\Workout;
use App\Models\WorkoutCompilation;
use App\Traits\ActivitiesTrait;
use App\Traits\NotificationsTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use stdClass;

class ClientsController extends Controller
{
    //
    use NotificationsTrait,ActivitiesTrait;
    function activeClients(){
        try{
            $subscribedUserIds = UserDetail::where('subscription_status','active')->pluck('user_id')->toArray();
            $clients = User::where('role',1)->whereIn('id',$subscribedUserIds)->get(['id','name']);
            if(is_null($clients))
            return response()->json([
                'status' => false,
                'message' => 'No Client Found With Paid Subscription'
            ]);
            foreach ($clients as $client) {
                $client->full_name = $client->fullName();
                $client->subscription = $client->subs();
                $client->image = $client->profilePicture();
            }
            return response()->json([
                'status' => true,
                'data' => $clients
            ]);
        } catch(Exception $er){
            return response()->json([
                'status' => false,
                'error' => $er->getMessage().'--Line# '.$er->getLine().'---File: '.$er->getFile(),
                'message' => 'Oops! Something Went Wrong.'
            ]);
        }
    }

    function clientsQuestions($id){
        $answers = UserAnswer::where('user_id',$id)->get();
        foreach ($answers as $ans) {
            $ans->question = Question::where('id',$ans->question_id)->pluck('question_en')->first();
            if($ans->answer_type === 'multiple'){
                $ans->answer = json_decode($ans->answer);
            }
        }
        return response()->json([
            'status' => true,
            'data' => $answers
        ]);
    }

    function clientTags($id){
        $user = User::where('id',$id)->where('role',1)->first();
        if($user){
            $tags = new stdClass;
            if(is_null($user->tags)){
                $tags->ids = [];
                $tags->names = [];
            } else {
                $tags->ids = json_decode($user->tags);
                $tags->names = Tag::whereIn('id',$tags->ids)->pluck('name')->toArray();
            }
            return response()->json([
                'status' => true,
                'data' => $tags
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => 'Invalid User Id.'
        ]);
    }

    function assignTagClient(Request $request){
        $validate = Validator::make($request->all(),[
            'user_id' => 'required|exists:users,id',
            'tags' => 'array'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $user = User::find($request->user_id);
        if (is_null($request->tags) || count($request->tags)==0)
        $user->tags = null;
        else
        $user->tags = json_encode($request->tags);
        $user->update();
        return response()->json([
            'status' => true,
            'message' => 'Tags Assigned.'
        ]);
    }

    function cilentsSummary(){
        $users = User::where('role',1)->orderBy('created_at','desc')->get(['id','created_at','status','tags']);
        foreach ($users as $user) {
            $user->full_name = ucfirst($user->fullName());
            $user->image = $user->profilePicture();
            $user->since = $user->created_at->format('d M,Y');
            $program = ProgramSub::where('user_id',$user->id)->orderBy('id','desc')->first();
            if(is_null($program)){
                $user->main_program = '-';
                $user->progress = null;
                $user->current_phase = '-';
                $user->next_phase = '-';
                $user->program_status = '-';
            } else {
                $user->main_program = strtoupper(Program::where('id',$program->program_id)->pluck('title')->first());
                $user->program_status = $program->status;
                // $checkStart = WeeklyWorkout::whereIn('week_id',WeekWiseProgram::where('program_sub_id',$program->id)->pluck('id')->toArray())->where('status',1)->count();
                // return $checkStart;
                if($program->status !== 'subscribed'){
                    $allWeekIds = WeekWiseProgram::where('program_sub_id',$program->id)->pluck('id')->toArray();    //week ids of all program

                    $lastCompletedWeekId = WeeklyWorkout::whereIn('week_id',$allWeekIds)->orderBy('id','desc')
                    ->where('status',1)->pluck('week_id')->first();     // id of last completed week
                    if(is_null($lastCompletedWeekId)){
                        $user->current_phase = 'Week '.WeekWiseProgram::where('program_sub_id',$program->id)->pluck('week_no')->first();
                        $temp1 = WeekWiseProgram::where('program_sub_id',$program->id)->pluck('week_no')->skip(1)->first();
                        $user->next_phase = $temp1===null?'-':'Week '.$temp1;
                        $user->progress = 0;
                    } else {
                        $user->progress = $this->calculateProgress($lastCompletedWeekId);   // progress of this week
    
                        $temp1 = WeekWiseProgram::where('id',$lastCompletedWeekId)->pluck('week_no')->first();      // Week number of last completed
                        $user->current_phase = 'Week '.$temp1;
    
                        $temp2 = WeekWiseProgram::where('program_sub_id',$program->id)->where('week_no',$temp1+1)->pluck('week_no')->first();
                        $user->next_phase = $temp2==null?'-':'Week '.$temp2;
                    }
                } else {
                    $user->progress = null;
                    // $user->current_phase = 'Week '.WeekWiseProgram::where('program_sub_id',$program->id)->pluck('week_no')->first();
                    // $nextWeek = WeekWiseProgram::where('program_sub_id',$program->id)->pluck('week_no')->skip(1)->first();
                    // $user->next_phase = $nextWeek==null?'-':'Week '.$nextWeek;
                    $user->current_phase = 'Not Started Yet';
                    $user->next_phase = 'Not Started Yet';
                }
            }
            // if(is_null(UserSub::where('user_id',$user->id)->where('status','!=','awaiting_payment')->first()))
            // $user->status = 'pending';
            if(is_null($user->tags))
            $user->tagNames = [];
            else{
                $user->tags = json_decode($user->tags);
                $user->tagNames = Tag::whereIn('id',$user->tags)->pluck('name')->toArray();
            }
        }
        return response()->json([
            'status' => true,
            'data' => $users
        ]);
    }
    
    function cilentsExercises(){
        $users = User::where('role',1)->orderBy('created_at','desc')->get(['id','created_at','status','tags']);
        foreach ($users as $user) {
            $user->full_name = ucfirst($user->fullName());
            $user->image = $user->profilePicture();
            if(is_null(UserSub::where('user_id',$user->id)->where('status','!=','awaiting_payment')->first()))
            $user->status = 'pending';
            if(is_null($user->tags))
            $user->tagNames = [];
            else{
                $user->tags = json_decode($user->tags);
                $user->tagNames = Tag::whereIn('id',$user->tags)->pluck('name')->toArray();
            }

            $exs = $this->exsCompilation($user->id);
            $user->ex_two_weeks = $exs->twoWeeks;
            $user->ex_last_week = $exs->lastWeek;
            $user->ex_this_week = $exs->thisWeek;
            $user->ex_next_week = $exs->nextWeek;
        }
        return response()->json([
            'status' => true,
            'data' => $users
        ]);
    }

    function cilentsWeight(){
        $users = User::where('role',1)->orderBy('created_at','desc')->get(['id','created_at','status','tags']);
        foreach ($users as $user) {
            $user->full_name = ucfirst($user->fullName());
            $user->image = $user->profilePicture();
            if(is_null(UserSub::where('user_id',$user->id)->where('status','!=','awaiting_payment')->first()))
            $user->status = 'pending';
            if(is_null($user->tags))
            $user->tagNames = [];
            else{
                $user->tags = json_decode($user->tags);
                $user->tagNames = Tag::whereIn('id',$user->tags)->pluck('name')->toArray();
            }
            $user->current_weight = $this->currentWeight($user->id);
            $user->current_body_fat = $this->currentFat($user->id);
        }
        return response()->json([
            'status' => true,
            'data' => $users
        ]);
    }

    function cilentsPayments(){
        $users = User::where('role',1)->orderBy('created_at','desc')->get(['id','created_at','status','tags']);
        foreach ($users as $user) {
            $user->full_name = ucfirst($user->fullName());
            $user->image = $user->profilePicture();
            if(is_null(UserSub::where('user_id',$user->id)->where('status','!=','awaiting_payment')->first()))
            $user->status = 'pending';
            if(is_null($user->tags))
            $user->tagNames = [];
            else{
                $user->tags = json_decode($user->tags);
                $user->tagNames = Tag::whereIn('id',$user->tags)->pluck('name')->toArray();
            }
            $user->current_product = Subscription::where('id',UserDetail::where('user_id',$user->id)->pluck('subscription')->first())->pluck('name')->first();
            $user->next_product = '-';
            $user->credit_card = $this->creditCard($user->id);
        }
        return response()->json([
            'status' => true,
            'data' => $users
        ]);
    }

    function cilentsEngagement(){
        $users = User::where('role',1)->orderBy('created_at','desc')->get(['id','created_at','status','tags']);
        foreach ($users as $user) {
            $user->full_name = ucfirst($user->fullName());
            $user->image = $user->profilePicture();
            if(is_null(UserSub::where('user_id',$user->id)->where('status','!=','awaiting_payment')->first()))
            $user->status = 'pending';
            if(is_null($user->tags))
            $user->tagNames = [];
            else{
                $user->tags = json_decode($user->tags);
                $user->tagNames = Tag::whereIn('id',$user->tags)->pluck('name')->toArray();
            }
            $today = Carbon::today()->endOfDay();
            $weekBefore = Carbon::today()->subWeek();
            $user->no_of_workouts = WorkoutCompilation::where('user_id',$user->id)->whereBetween('created_at',[$weekBefore,$today])->count();
            $user->no_of_signins = AppUsageCount::where('user_id',$user->id)->whereBetween('created_at',[$weekBefore,$today])->count();
        }
        return response()->json([
            'status' => true,
            'data' => $users
        ]);
    }

    function cilentsNutrition(){
        $users = User::where('role',1)->orderBy('created_at','desc')->get(['id','created_at','status','tags']);
        foreach ($users as $user) {
            $user->full_name = ucfirst($user->fullName());
            $user->image = $user->profilePicture();
            if(is_null(UserSub::where('user_id',$user->id)->where('status','!=','awaiting_payment')->first()))
            $user->status = 'pending';
            if(is_null($user->tags))
            $user->tagNames = [];
            else{
                $user->tags = json_decode($user->tags);
                $user->tagNames = Tag::whereIn('id',$user->tags)->pluck('name')->toArray();
            }
            $today = Carbon::today()->endOfDay();
            $weekBefore = Carbon::today()->subWeek();
            $user->total_meals = NutritionCompilance::where('user_id',$user->id)->whereBetween('created_at',[$weekBefore,$today])->count();
            // if($user->total_meals < 1){
            //     $user->total_carbs = 0;
            //     $user->total_calories = 0;
            //     $user->total_proteins = 0;
            //     $user->total_fats = 0;
            // } else {
                // $user->total_carbs = array_sum(NutritionCompilance::where('user_id',$user->id)->whereBetween('created_at',[$weekBefore,$today])
                // ->whereNotNull('carbs')->pluck('carbs')->toArray())/$user->total_meals;
                // $user->total_fats = array_sum(NutritionCompilance::where('user_id',$user->id)->whereBetween('created_at',[$weekBefore,$today])
                // ->whereNotNull('fats')->pluck('fats')->toArray())/$user->total_meals;
                // $user->total_proteins = array_sum(NutritionCompilance::where('user_id',$user->id)->whereBetween('created_at',[$weekBefore,$today])
                // ->whereNotNull('proteins')->pluck('proteins')->toArray())/$user->total_meals;
                // $user->total_calories = array_sum(NutritionCompilance::where('user_id',$user->id)->whereBetween('created_at',[$weekBefore,$today])
                // ->whereNotNull('calories')->pluck('calories')->toArray())/$user->total_meals;
            // }
            $user->total_carbs = NutritionCompilance::where('user_id',$user->id)->whereBetween('created_at',[$weekBefore,$today])->whereNotNull('carbs')->sum('carbs');
            $user->total_fats = NutritionCompilance::where('user_id',$user->id)->whereBetween('created_at',[$weekBefore,$today])->whereNotNull('fats')->sum('fats');
            $user->total_proteins = NutritionCompilance::where('user_id',$user->id)->whereBetween('created_at',[$weekBefore,$today])->whereNotNull('proteins')->sum('proteins');
            $user->total_calories = NutritionCompilance::where('user_id',$user->id)->whereBetween('created_at',[$weekBefore,$today])->whereNotNull('calories')->sum('calories');
        }
        return response()->json([
            'status' => true,
            'data' => $users
        ]);
    }

    function calculateProgress($proSubId){
        $totalWorks = WeeklyWorkout::where('week_id',$proSubId)->count();
        $doneWorks = WeeklyWorkout::where('week_id',$proSubId)->where('status',1)->count();
        return $totalWorks===0?0:(float)(($doneWorks*100)/$totalWorks);
    }

    function currentWeight($uId){
        $weight = BodyStats::where('user_id',$uId)->whereNotNull('weight')->orderBy('date','desc')->first(['weight','unit']);
        if(is_null($weight))
        return '-';
        else
        return $weight->weight.' '.$weight->unit;
    }

    function currentFat($uId){
        $fat = BodyStats::where('user_id',$uId)->whereNotNull('fat')->orderBy('date','desc')->pluck('fat')->first();
        if(is_null($fat))
        return '-';
        else
        return $fat.'%';
    }

    function creditCard($uId){
        $paymentId = UserSub::where('user_id',$uId)->orderBy('id', 'DESC')->pluck('payment_id')->first();
        if(is_null($paymentId) || $paymentId==='')
        return '-';
        return Payment::where('id',$paymentId)->pluck('card_used')->first();
    }

    function exsCompilation($uId){
        $returnData = new stdClass;
        // this week
        $rangeStart = Carbon::today()->subWeek();
        $rangeEnd = Carbon::today()->endOfDay();
        $returnData->thisWeek = ExerciseCompilation::where('user_id',$uId)->whereBetween('created_at',[$rangeStart,$rangeEnd])->count();
        // last week
        $rangeStart = Carbon::today()->subWeeks(2);
        $rangeEnd = Carbon::today()->subWeek()->endOfDay();
        $returnData->lastWeek = ExerciseCompilation::where('user_id',$uId)->whereBetween('created_at',[$rangeStart,$rangeEnd])->count();
        // last 2 week
        $rangeStart = Carbon::today()->subWeeks(3);
        $rangeEnd = Carbon::today()->subWeeks(2)->endOfDay();
        $returnData->twoWeeks = ExerciseCompilation::where('user_id',$uId)->whereBetween('created_at',[$rangeStart,$rangeEnd])->count();
        $returnData->nextWeek = '-';
        return $returnData;
    }

    function nutrCompilation($uId){
        $returnData = new stdClass;
        $returnData->twoWeeks = '-';
        $returnData->lastWeek = '-';
        $returnData->thisWeek = '-';
        $returnData->nextWeek = '-';
        return $returnData;
    }

    function clientDetail($id){
        $user = User::where('id',$id)->where('role',1)->first();
        if(is_null($user))
        return response()->json([
            'status' => false,
            'message' => 'Invalid User ID.'
        ]);
        $userDetails = UserDetail::where('user_id',$id)->first();
        $currentSub = Subscription::where('id',$userDetails->subscription)->pluck('name')->first();
        $achv = Achievement::where('user_id',$id)->get();
        foreach ($achv as $item) {
            $item->badge_name = $item->badgeName();
            $item->program_name = $item->programName();
        }

        $info = new stdClass;
        $info->full_name = $userDetails->name.' '.$userDetails->Lastname;
        $info->email = $user->email;
        $info->status = $user->status;
        $info->phone = $userDetails->phone;
        $info->dob = $userDetails->DOB;
        $info->height = $userDetails->height;
        $info->gender = $userDetails->gender;
        $info->image = $userDetails->picture;
        $info->activitylevel = 'Secondary';
        $info->totalWorkouts = Workout::where('user_id',$id)->count();
        $info->added_on = $user->created_at->format('D d M, Y');
        $info->setup_on = $user->created_at->format('D d M, Y'); // put questiodns here
        $info->timezone = Carbon::now()->timezone->getName();
        $info->subscription = $currentSub;
        $info->units = UserSetting::where('user_id',$id)->first(['weight_unit','distance_unit','body_measures']);;
        $info->meal_workflow = null;
        $info->badges = $achv;
        $info->exercise_compile = $this->exsCompilation($id);
        $info->nutrition_compile = $this->nutrCompilation($id);
        $info->body_weight = $this->currentWeight($id);
        $info->training_plan = Program::where('id',ProgramSub::where('user_id',$user->id)->orderBy('subscribe_date','desc')->pluck('program_id')->first())->pluck('title')->first();

        return response()->json([
            'status' => true,
            'data' => $info
        ]);
    }

    function clientInvoices($id){
        $subs = UserSub::where('user_id',$id)->where('status','!=','awaiting_payment')->orderBy('id','desc')->get();
        foreach ($subs as $sub) {
            $relatedPayment = Payment::find($sub->payment_id);
            $discount = DiscountCode::find($sub->discount_code);
            $sub->subscription = $sub->subscription();
            $sub->amount_paid = $relatedPayment===null?'0':$relatedPayment->amount;
            $sub->card = $relatedPayment===null?'-':$relatedPayment->card_used;
            $sub->discount_used = $discount===null?'-':$discount->code;
            $sub->sub_start_date = is_null($sub->sub_start_date)?'':Carbon::parse($sub->sub_start_date)->format('d M, y');
            $sub->sub_expire_date = is_null($sub->sub_expire_date)?'':Carbon::parse($sub->sub_expire_date)->format('d M, y');
        }
        return response()->json([
            'status' => true,
            'data' => $subs
        ]);
    }

    function userConsults($id){
        $consult = Consultation::where('admin_id',Auth::id())->where('user_id',$id)->orderBy('id','desc')->get();
        return response()->json([
            'status' => true,
            'data' => $consult
        ]);
    }

    function createConsult(Request $request){
        $validate = Validator::make($request->all(),[
            'user_id' => 'required|exists:users,id',
            'content' => 'required'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);

        $consult = new Consultation();
        $consult->admin_id = Auth::id();
        $consult->user_id = $request->user_id;
        $consult->content = $request->content;
        $consult->sender = 'admin';
        $consult->status = 0;
        $consult->save();
        return response()->json([
            'status' => true,
            'message' => 'Consult Message Created.'
        ]);
    }

    function createAttatchment(Request $request){
        $validate = Validator::make($request->all(),[
            'user_id' => 'required|exists:users,id',
            'file' => 'required|file|max:10240'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $fileUrl = $request->user_id."client_attatchment".time().'.'.request()->file->getClientOriginalExtension();
        $fileName = $request->file->getClientOriginalName();
        $request->file->storeAs('attatchments', $fileUrl, config('filesystems.default'));

        $attc = new Attatchment();
        $attc->admin_id = Auth::id();
        $attc->user_id = $request->user_id;
        $attc->file = $fileUrl;
        $attc->file_name = $fileName;
        $attc->sender = 'admin';
        $attc->status = 0;
        $attc->save();
        return response()->json([
            'status' => true,
            'message' => 'Attatchment Created.'
        ]);
    }

    function userPostures($id){
        $atc = PosturePicture::where('user_id',$id)->orderBy('id','desc')->get();
        foreach ($atc as $key => $value) {
            $value->datetime = $value->created_at->format('D d, M Y');
        }
        return response()->json([
            'status' => true,
            'data' => $atc
        ]);
    }

    function clientActivationSwitch($id){
        $user = User::where('id',$id)->where('role',1)->first();
        if(is_null($user))
        return response()->json([
            'status' => false,
            'message' => 'Invalid User Id.'
        ]);
        if($user->status==='active'){
            $user->status = 'deactive';
        } else {
            $user->status = 'active';
        }
        $user->update();
        DB::table('oauth_access_tokens')->where('user_id',$id)->delete(); // logging out
        if($user->status==='active'){
            $notiTitleEn = 'Account Activated';
            $notiTitleAr = 'تم تنشيط الحساب';
            $notiContentEn = 'Your Account has been Activated by Administrator';
            $notiContentAr = 'تم تنشيط حسابك من قبل المسؤول';
            $resMsg = 'User Activated Successfully';
        } else {
            $notiTitleEn = 'Account Deactivated';
            $notiTitleAr = 'الحساب معطل';
            $notiContentEn = 'Your Account has been Deactivated by Administrator';
            $notiContentAr = 'تم إلغاء تنشيط حسابك من قبل المسؤول';
            $resMsg = 'User Dectivated Successfully';
        }
        $notiRecievers = [$user->fcm_token];
        $userLang = $this->userSelecetdLanguage($id);
        $this->storeNotification($user->id,$notiTitleEn,$notiTitleAr,$notiContentEn,$notiContentAr);
        if($userLang==='en')
        $this->sendFirebaseNotification($notiRecievers,$notiTitleEn,$notiContentEn);
        else
        $this->sendFirebaseNotification($notiRecievers,$notiTitleAr,$notiContentAr);
        return response()->json([
            'status' => true,
            'message' => $resMsg
        ]);
    }
}
