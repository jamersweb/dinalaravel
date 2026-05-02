<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExercisesTracking;
use App\Models\Program;
use App\Models\ProgramPhase;
use App\Models\ProgramPhaseWorkout;
use App\Models\ProgramsTracking;
use App\Models\ProgramSub;
use App\Models\ProgramSubscriber;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserSetting;
use App\Models\WeeksTracking;
use App\Models\WeekWiseProgram;
use App\Models\Workout;
use App\Models\WorkoutsTracking;
use App\Support\ContentCodeNormalizer;
use App\Support\ContentLocaleResolver;
use App\Support\UserContentLocale;
use App\Traits\NotificationsTrait;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use stdClass;


class ProgramsController extends Controller
{
    use NotificationsTrait, ApiResponse;
    //

    function renamePhase(Request $request)
    {
        try {
            $validate = Validator::make($request->all(), [
                'id' => 'required|exists:program_phases,id',
                'name' => 'required|string',
            ]);
            if ($validate->fails())
                return $this->validationError($validate);
            $prog = ProgramPhase::find($request->id);
            $prog->name = $request->name;
            $prog->update();
            return response()->json([
                'status' => true,
                'message' => 'Renamed Successfully.'
            ]);
        } catch (Exception $er) {
            return $this->error($er->getMessage() . '--Line# ' . $er->getLine(), 500);
        }
    }

    function getAllPrograms()
    {
        try {
            // Fetch all programs with phases
            $data = Program::with(['programPhases' => function ($q) {
                $q->select('id', 'weeks', 'name', 'program_id');
            }])->orderBy('created_at', 'desc')->get(['id', 'title', 'image', 'tags', 'language', 'content_code']);
            
            // Fix N+1: Collect all tag IDs and fetch tags in one query
            $allTagIds = [];
            foreach ($data as $item) {
                if (!is_null($item->tags)) {
                    $tagIds = json_decode($item->tags, true);
                    if (is_array($tagIds)) {
                        $allTagIds = array_merge($allTagIds, $tagIds);
                    }
                }
            }
            
            // Fetch all tags at once
            $tagsMap = [];
            if (!empty($allTagIds)) {
                $uniqueTagIds = array_unique($allTagIds);
                $tags = Tag::whereIn('id', $uniqueTagIds)->pluck('name', 'id')->toArray();
                $tagsMap = $tags;
            }
            
            // Map tags to programs
            foreach ($data as $item) {
                $item->weeks = $item->totalWeeks();
                if (is_null($item->tags)) {
                    $item->tagNames = [];
                    $item->tags = [];
                } else {
                    $tagIds = json_decode($item->tags, true);
                    $item->tags = is_array($tagIds) ? $tagIds : [];
                    $item->tagNames = array_filter(array_map(function($tagId) use ($tagsMap) {
                        return $tagsMap[$tagId] ?? null;
                    }, $item->tags));
                }
            }
            
            return response()->json([
                'status' => true,
                'data' => $data
            ]);
        } catch (Exception $er) {
            return $this->error($er->getMessage() . '--Line# ' . $er->getLine(), 500);
        }
    }

    function renameProgram(Request $request)
    {
        try {
            $validate = Validator::make($request->all(), [
                'id' => 'required|exists:programs,id',
                'name' => 'required|string',
            ]);
            if ($validate->fails())
                return $this->validationError($validate);
            $prog = Program::find($request->id);
            $prog->title = $request->name;
            $prog->update();
            return response()->json([
                'status' => true,
                'message' => 'Renamed Successfully.'
            ]);
        } catch (Exception $er) {
            return response()->json([
                'status' => false,
                'message' => $er->getMessage() . '---------Line# ' . $er->getLine()
            ]);
        }
    }

    function deleteProgram($id)
    {
        try {
            $prog = Program::find($id);
            if (is_null($prog))
                return $this->notFound('Invalid ID.');
            $subs = ProgramSub::where('program_id', $id)->count();
            if ($subs > 0)
                return $this->error('Cannot Delete Program With Active Subscribers.', 400);
            Program::destroy($id);
            return $this->success(null, 'Deleted Successfully.');
        } catch (Exception $er) {
            return response()->json([
                'status' => false,
                'message' => $er->getMessage() . '---------Line# ' . $er->getLine()
            ]);
        }
    }

    function getDetailWithSubscribers($id)
    {
        $program = Program::where('id', $id)->with(['subscribers' => function ($q) {
            $q->select('user_id', 'status', 'start_date', 'program_id');
        }])->first(['id', 'title', 'discription', 'language', 'level', 'tags']);

        if (is_null($program)) {
            return $this->notFound('Program Not Found');
        }

        // Fix N+1: Fetch tags in one query
        if (!is_null($program->tags)) {
            $tagIds = json_decode($program->tags, true);
            if (is_array($tagIds) && !empty($tagIds)) {
                $program->tagNames = Tag::whereIn('id', $tagIds)->pluck('name')->toArray();
            } else {
                $program->tagNames = [];
            }
        } else {
            $program->tagNames = [];
        }

        // Fix N+1: Fetch all user details in one query
        if ($program->subscribers->count() > 0) {
            $userIds = $program->subscribers->pluck('user_id')->unique()->toArray();
            $userDetails = \App\Models\UserDetail::whereIn('user_id', $userIds)
                ->select('user_id', 'name', 'Lastname')
                ->get()
                ->keyBy('user_id');

            foreach ($program->subscribers as $item) {
                $userDetail = $userDetails->get($item->user_id);
                if ($userDetail) {
                    $item->username = $userDetail->name . ' ' . $userDetail->Lastname;
                } else {
                    $item->username = 'Unknown User';
                }
            }
        }

        return $this->success($program);
    }

    function getPhaseDetail($id)
    {
        // Explicitly select summary field to ensure it's always included
        $phase = ProgramPhase::where('id', $id)
            ->select('id', 'program_id', 'phase_no', 'weeks', 'name', 'summary', 'created_at', 'updated_at')
            ->with(['phaseWorkouts' => function ($q) {
                $q->select('id', 'workout_id', 'display_name', 'program_phase_id')->with(['workoutDetail' => function ($q2) {
                    $q2->select('id', 'image')->withCount('workoutExercises');
                }]);
            }])->first();
        if (is_null($phase))
            return response()->json([
                'status' => false,
                'message' => 'Invalid Phase Selected'
            ]);
        return response()->json([
            'status' => true,
            'data' => $phase
        ]);
    }

    function createProgram(Request $request)
    {
        $request->merge([
            'content_code' => ContentCodeNormalizer::normalize($request->input('content_code')),
        ]);
        $validate = Validator::make($request->all(), [
            'title' => 'required',
            'type' => 'required|in:predefined,custom',
            'phases' => 'required|numeric',
            'weeks_per_phase' => 'required|numeric',
            'level' => 'required|in:beginner,intermediate,expert',
            'language' => 'required|in:ar,en,no',
            'tags' => 'array',
            'content_code' => [
                'nullable',
                'string',
                'max:64',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('programs', 'content_code'),
            ],
        ]);
        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors()->all()[0]
            ]);
        }
        $program = new Program();
        $program->title = $request->title;
        $program->type = $request->type;
        $program->phases = $request->phases;
        $program->level = $request->level;
        $program->language = $request->language;
        $program->tags = json_encode($request->tags);
        $program->content_code = $request->input('content_code');
        $program->save();
        for ($i = 0; $i < $program->phases; $i++) {
            $phase = new ProgramPhase();
            $phase->program_id = $program->id;
            $phase->phase_no = 1 + $i;
            $phase->weeks = $request->weeks_per_phase;
            $startWeek = ($i * $request->weeks_per_phase) + 1;
            $endWeek = ($i * $request->weeks_per_phase) + $request->weeks_per_phase;
            $phase->name = 'Week ' . $startWeek . ' to ' . $endWeek;
            $phase->save();
        }
        return response()->json([
            'status' => true,
            'message' => 'Program Created Successfully'
        ]);
    }

    function addProgramDiscription(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'program_id' => 'required|numeric',
            'discription' => 'required|string',
        ]);
        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors()->all()[0]
            ]);
        }
        $program = Program::find($request->program_id);
        if ($program == null) {
            return $this->notFound('Wrong Program Id Given.');
        }
        $program->discription = $request->discription;
        $program->update();
        return $this->success(null, 'Program Discription Added.');
    }

    function addPhaseWorkouts(Request $request)
    {
        try {
            $validate = Validator::make($request->all(), [
                'program_phase_id' => 'required',
                'workout_ids' => 'required|array',
            ]);
            if ($validate->fails()) {
                return $this->validationError($validate);
            }
            //check if program have active subscribers

            // Fix N+1: Fetch all workouts in one query
            $workoutIds = $request->workout_ids;
            $workouts = Workout::whereIn('id', $workoutIds)
                ->select('id', 'title')
                ->get()
                ->keyBy('id');

            // Fix N+1: Fetch existing phase workouts in one query
            $allWorksOfPhase = ProgramPhaseWorkout::where('program_phase_id', $request->program_phase_id)
                ->pluck('display_name')
                ->toArray();

            $nameExists = [];
            foreach ($request->workout_ids as $item) {
                $wrkId = $item;
                $workout = $workouts->get($wrkId);
                $wrkName = $workout ? $workout->title : null;
                if (!in_array($wrkName, $allWorksOfPhase)) {
                    $phaseWorkout = new ProgramPhaseWorkout();
                    $phaseWorkout->program_phase_id = $request->program_phase_id;
                    $phaseWorkout->workout_id = $wrkId;
                    $phaseWorkout->display_name = $wrkName;
                    $phaseWorkout->save();
                } else {
                    array_push($nameExists, $wrkName);
                }
            }
            if (count($nameExists) > 0) {
                $alreadyNames = implode(',', $nameExists);
                $msg = "Workouts Added. Except Already Existing (" . $alreadyNames . ")";
            } else {
                $msg = "Workouts Added.";
            }

            $image = DB::table('workouts')->where('id', $request->workout_ids[0])->pluck('image')->first();
            $program_id = ProgramPhase::where('id', $request->program_phase_id)->pluck('program_id')->first();
            Program::where('id', $program_id)->update(array('image' => $image));

            return $this->success(null, $msg);
        } catch (Exception $er) {
            return $this->error($er->getMessage() . '---------Line# ' . $er->getLine(), 500);
        }
    }

    function addPhaseSummary(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'phase_id' => 'required|exists:program_phases,id',
            'summary' => 'required|string',
        ]);
        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors()->all()[0]
            ]);
        }
        $phase = ProgramPhase::find($request->phase_id);
        $phase->summary = $request->summary;
        $phase->update();
        return response()->json([
            'status' => true,
            'message' => 'Phase Discription Added.'
        ]);
    }

    function changePhaseWorkoutDisplayName(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'phase_workout_id' => 'required|exists:program_phase_workouts,id',
            'display_name' => 'required|string'
        ]);
        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors()->all()[0]
            ]);
        }
        $data = ProgramPhaseWorkout::find($request->phase_workout_id);
        $data->display_name = $request->display_name;
        $data->update();
        return response()->json([
            'status' => true,
            'message' => 'Workout Display Name Changed Successfully'
        ]);
    }

    function showBeginnerPrograms()
    {
        $userLang = UserContentLocale::forAuthenticatedUser();
        $progs = Program::where('level', 'beginner')->availableInContentLocale($userLang)->get();
        
        if ($progs->isEmpty()) {
            return $this->success([]);
        }

        // Fix N+1: Fetch all phases in one query
        $programIds = $progs->pluck('id')->toArray();
        $phases = ProgramPhase::whereIn('program_id', $programIds)
            ->select('id', 'program_id')
            ->get()
            ->groupBy('program_id');

        // Fix N+1: Fetch all phase workouts in one query
        $phaseIds = $phases->flatten()->pluck('id')->toArray();
        $phaseWorkouts = ProgramPhaseWorkout::whereIn('program_phase_id', $phaseIds)
            ->select('program_phase_id')
            ->get()
            ->groupBy('program_phase_id');

        // Fix N+1: Fetch all subscriptions in one query
        $userId = Auth::id();
        $subscribedProgramIds = ProgramSub::where('user_id', $userId)
            ->whereIn('program_id', $programIds)
            ->pluck('program_id')
            ->toArray();

        $returnData = [];
        foreach ($progs as $prog) {
            ContentLocaleResolver::overlayProgram($prog, $userLang);
            $programPhases = $phases->get($prog->id, collect());
            $add = true;
            
            foreach ($programPhases as $phase) {
                if (!$phaseWorkouts->has($phase->id) || $phaseWorkouts->get($phase->id)->isEmpty()) {
                    $add = false;
                    break;
                }
            }
            
            if ($add) {
                $prog->duration = $prog->totalWeeks();
                $prog->subscribed = in_array($prog->id, $subscribedProgramIds);
                $returnData[] = $prog;
            }
        }
        
        return $this->success($returnData);
    }

    function getAllProgramsUsers()
    {
        $userLang = UserContentLocale::forAuthenticatedUser();
        $progs = Program::availableInContentLocale($userLang)->get();

        if ($progs->isEmpty()) {
            return $this->success([]);
        }

        // Fix N+1: Fetch all subscriptions in one query
        $userId = Auth::id();
        $programIds = $progs->pluck('id')->toArray();
        $subscribedProgramIds = ProgramSub::where('user_id', $userId)
            ->whereIn('program_id', $programIds)
            ->pluck('program_id')
            ->toArray();

        $returnData = [];
        foreach ($progs as $prog) {
            ContentLocaleResolver::overlayProgram($prog, $userLang);
            $prog->subscribed = in_array($prog->id, $subscribedProgramIds);
            $returnData[] = $prog;
        }
        
        return $this->success($returnData);
    }


    function getUserRegisteredPrograms()
    {
        $programs = ProgramSub::where('user_id', Auth::id())->get();
        
        if ($programs->isEmpty()) {
            return $this->success([]);
        }

        // Fix N+1: Fetch all programs in one query
        $programIds = $programs->pluck('program_id')->unique()->toArray();
        $userLang = UserContentLocale::forAuthenticatedUser();
        $programsData = Program::whereIn('id', $programIds)
            ->select('id', 'title', 'image', 'discription', 'language', 'locale_translations', 'content_code')
            ->get()
            ->keyBy('id');

        // Fix N+1: Fetch all week counts in one query
        $programSubIds = $programs->pluck('id')->toArray();
        $weekCounts = WeekWiseProgram::whereIn('program_sub_id', $programSubIds)
            ->select('program_sub_id', DB::raw('count(*) as weeks'))
            ->groupBy('program_sub_id')
            ->pluck('weeks', 'program_sub_id')
            ->toArray();

        foreach ($programs as $prog) {
            $program = $programsData->get($prog->program_id);
            if ($program) {
                ContentLocaleResolver::overlayProgram($program, $userLang);
                $prog->name = $program->title;
                $prog->image = $program->image;
                $prog->description = $program->discription ?? '';
                $prog->content_code = $program->content_code;
            } else {
                $prog->name = 'Unknown Program';
                $prog->image = null;
                $prog->description = '';
                $prog->content_code = null;
            }
            $weeks = $weekCounts[$prog->id] ?? 0;
            $prog->duration = $weeks . ' Weeks';
        }
        
        return $this->success($programs);
    }

    public function removeWorkout(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
        ]);
        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors()->all()[0]
            ]);
        }
        ProgramPhaseWorkout::destroy($request->ids);
        return response()->json([
            'status' => true,
            'message' => 'Workout Removed from Program'
        ]);
    }

    function removeProgramPhase($id)
    {       //program phase id
        $progPhase = ProgramPhase::find($id);
        if (is_null($progPhase))
            return $this->notFound('Invalid Id');
        $progPhase->delete();
        return $this->success(null, 'Deleted.');
    }

    function clientsListForProgram($id)
    {
        $alreadySubscribed = ProgramSub::where('program_id', $id)->pluck('user_id')->toArray();
        $subscribedUserIds = \App\Models\UserDetail::where('subscription_status','active')->pluck('user_id')->toArray();
        $clients = User::where('role', 1)->whereIn('id', $subscribedUserIds)->whereNotIn('id', $alreadySubscribed)->get(['id', 'name']);
        
        if ($clients->isEmpty()) {
            return $this->error('No Client Found With Paid Subscription', 404);
        }

        // Fix N+1: Fetch all user details in one query
        $userIds = $clients->pluck('id')->toArray();
        $userDetails = \App\Models\UserDetail::whereIn('user_id', $userIds)
            ->select('user_id', 'name', 'Lastname', 'picture', 'subscription')
            ->get()
            ->keyBy('user_id');

        // Fix N+1: Fetch all subscriptions in one query
        $subscriptionIds = $userDetails->pluck('subscription')->filter()->unique()->toArray();
        $subscriptions = \App\Models\Subscription::whereIn('id', $subscriptionIds)
            ->select('id', 'name')
            ->get()
            ->keyBy('id');

        // Fix N+1: Fetch all user settings in one query
        $userSettings = \App\Models\UserSetting::whereIn('user_id', $userIds)
            ->select('user_id', 'language')
            ->get()
            ->keyBy('user_id');

        foreach ($clients as $client) {
            $userDetail = $userDetails->get($client->id);
            if ($userDetail) {
                $client->full_name = $userDetail->name . ' ' . $userDetail->Lastname;
                $client->image = $userDetail->picture;
                
                $subscription = $subscriptions->get($userDetail->subscription);
                $client->subscription = $subscription ? $subscription->name : null;
            } else {
                $client->full_name = $client->name;
                $client->image = null;
                $client->subscription = null;
            }
            
            $setting = $userSettings->get($client->id);
            $client->language = ($setting && $setting->language === 'en') ? 'English' : 'Arabic';
        }
        
        return $this->success($clients);
    }
}