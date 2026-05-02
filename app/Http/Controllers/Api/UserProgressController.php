<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExercisesTracking;
use App\Models\Program;
use App\Models\ProgramPhase;
use App\Models\ProgramPhaseWorkout;
use App\Models\ProgramsTracking;
use App\Models\ProgramSubscriber;
use App\Models\User;
use App\Models\WeeksTracking;
use App\Models\WorkoutExercise;
use App\Models\WorkoutsTracking;
use App\Traits\ActivitiesTrait;
use App\Traits\NotificationsTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use stdClass;

class UserProgressController extends Controller
{
    use ActivitiesTrait,NotificationsTrait;
    //
    function generateUserProgramProgress($id){

        $trackingExists = ProgramsTracking::where('user_id',Auth::id())->where('program_id',$id)->first();
        if($trackingExists)
        ProgramsTracking::destroy($trackingExists->id);
        //counting duration
        $duration = array_sum(ProgramPhase::where('program_id',$id)->pluck('weeks')->toArray());

        // generate program tracking
        $programTrack = new ProgramsTracking();
        $programTrack->user_id = Auth::id();
        $programTrack->program_id = $id;
        $programTrack->status = 0;
        $programTrack->save();

        // genrate weeks progress
        $phaseNo = 1;
        for ($i=1; $i <= $duration; $i++) {
            $weekTrack = new WeeksTracking();
            $weekTrack->program_track_id = $programTrack->id;
            $weekTrack->week_no = $i;
            $weekTrack->status = 0;
            $weekTrack->save();


            //getting phase id and workout id
            $weeksCount = ProgramPhase::where('phase_no',$phaseNo)->where('program_id',$id)->pluck('weeks')->first();
            if($i>($weeksCount*$phaseNo))
                $phaseNo++;
            $phaseId = ProgramPhase::where('phase_no',$phaseNo)->where('program_id',$id)->pluck('id')->first();

            // generate workout progress
            $workoutIds = ProgramPhaseWorkout::where('program_phase_id',$phaseId)->pluck('workout_id')->toArray();
            foreach ($workoutIds as $wId) {
                $workoutTrack = new WorkoutsTracking();
                $workoutTrack->week_track_id = $weekTrack->id;
                $workoutTrack->workout_id = $wId;
                $workoutTrack->status = 0;
                $workoutTrack->save();

                //genrate exercise progress
                $exerciseIds = WorkoutExercise::where('workout_id',$workoutTrack->workout_id)->pluck('exercise_id')->toArray();
                foreach ($exerciseIds as $exId) {
                    if(!$exId==null){
                        $exerciseTrack = new ExercisesTracking();
                        $exerciseTrack->workout_track_id = $workoutTrack->id;
                        $exerciseTrack->exercise_id = $exId;
                        $exerciseTrack->status = 0;
                        $exerciseTrack->save();
                    } else {
                        $exerciseTrack = new ExercisesTracking();
                        $exerciseTrack->workout_track_id = $workoutTrack->id;
                        $exerciseTrack->exercise_id = null;
                        $exerciseTrack->status = 1;
                        $exerciseTrack->save();
                    }
                }
            }
        }
        return [
            'status' => true,
            'message' => 'Progress Generated Successfully.'
        ];
    }

    function markExerciseDone(Request $request){
        $validate = Validator::make($request->all(),[
			'exercise_id' => 'required|numeric',
            'workout_id' => 'required|numeric',
            'week_no' => 'required|numeric',
            'program_id' => 'required|numeric'
		]);
		if($validate->fails()){
			return response()->json([
				'status' => false,
				'message' => $validate->errors()->all()[0]
			]);
		}
        $programStatus = ProgramSubscriber::where('user_id',Auth::id())->where('program_id',$request->program_id)->pluck('status')->first();
        if($programStatus==null)
        return response()->json([
            'status' => false,
            'message' => 'Please subscribe program first.'
        ]);
        if($programStatus=='Assigned')
        return response()->json([
            'status' => false,
            'message' => 'Please start program first.'
        ]);
        if($programStatus=='Paused')
        return response()->json([
            'status' => false,
            'message' => 'Please resume program first.'
        ]);
        //find current exercise row
        $programTrackId = ProgramsTracking::where('user_id',Auth::id())
        ->where('program_id',$request->program_id)->pluck('id')->first();
        $weekTrackId = WeeksTracking::where('program_track_id',$programTrackId)
        ->where('week_no',$request->week_no)->pluck('id')->first();
        $workoutTrackId = WorkoutsTracking::where('week_track_id',$weekTrackId)
        ->where('workout_id',$request->workout_id)->pluck('id')->first();
        $exerciseTrackRow = ExercisesTracking::where('workout_track_id',$workoutTrackId)
        ->where('exercise_id',$request->exercise_id)->first();
        if($exerciseTrackRow==null)
        return response()->json([
            'status' => false,
            'message' => 'Any of given data is not correct.'
        ]);
        $exerciseTrackRow->status = 1;
        $exerciseTrackRow->update();        // update current exercise status
        

        //check if all exercises of current workout are done
        $y = ExercisesTracking::where('workout_track_id',$workoutTrackId)->pluck('status')->toArray();
        if($this->arrayAllOne($y)){
            $x = WorkoutsTracking::find($workoutTrackId);
            $x->status = 1;
            $x->update(); // update current workout track

            $actTitle = 'Workout Completed.';
            $actContent = Auth::user()->name.' completed Workout.';
            $actCategory = 1;
            $actSource = Auth::id();
            $this->generateActivityForAdmin($actTitle,$actContent,$actCategory,$actSource,'workout',$x->workout_id);
        }
        //check if all workouts of current week are done
        $y = WorkoutsTracking::where('week_track_id',$weekTrackId)->pluck('status')->toArray();
        if($this->arrayAllOne($y)){
            $x = WeeksTracking::find($weekTrackId);
            $x->status = 1;
            $x->update(); // update current week track
        }
        //check if all weeks of current program are done
        $y = WeeksTracking::where('program_track_id',$programTrackId)->pluck('status')->toArray();
        if($this->arrayAllOne($y)){
            $x = ProgramsTracking::find($programTrackId);
            $x->status = 1;
            $x->update(); // update current program track
        }


        //check for achievement
        BadgesAchievementsController::weeklyAchievementCheck($weekTrackId,$request->program_id,Auth::id(),$request->week_no);
        return response()->json([
            'status' => true,
            'message' => 'Progress Updated Successfully.'
        ]);
    }

    function arrayAllOne($array){
        $result = array_search(0,$array);
        if($result===false)
            return 1;
        return 0;
    }

    function startProgram($id){
        $program = ProgramSubscriber::where('user_id',Auth::id())->where('program_id',$id)->first();
        if($program==null)
        return response()->json([
            'status' => false,
            'message' => 'Invalid Program Id.'
        ]);
        $program->status = 'Started';
        $program->start_date = now();
        $program->update();
        $res = $this->generateUserProgramProgress($id);
        if($res['status']){
            $notiReciever = User::where('role',2)->pluck('id')->first();
            $notiSource = Auth::id();
            $notiTitle = 'Program Started!';
            $notiContent = Auth::user()->name.' just started '.Program::where('id',$id)->pluck('title')->first().' program.';
            $this->storeNotification($notiReciever,$notiTitle,null,$notiContent,null,$notiSource);
            return response()->json([
                'status' => true,
                'message' => 'Program Started Successfully.'
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => 'Program Started, Progress not generated.'
        ]);
    }

    function pauseProgram($id){
        $program = ProgramSubscriber::where('user_id',Auth::id())->where('program_id',$id)->first();
        if($program==null)
        return response()->json([
            'status' => false,
            'message' => 'Invalid Program Id.'
        ]);
        $program->status = 'Paused';
        $program->pause_date = now();
        $program->update();
        $notiReciever = User::where('role',2)->pluck('id')->first();
        $notiSource = Auth::id();
        $notiTitle = 'Program Paused!';
        $notiContent = Auth::user()->name.' just paused '.Program::where('id',$id)->pluck('title')->first().' program.';
        $this->storeNotification($notiReciever,$notiTitle,null,$notiContent,null,$notiSource);
        return response()->json([
            'status' => true,
            'message' => 'Program Paused Successfully.'
        ]);
    }

    function resumeProgram($id){
        $program = ProgramSubscriber::where('user_id',Auth::id())->where('program_id',$id)->first();
        if($program==null)
        return response()->json([
            'status' => false,
            'message' => 'Invalid Program Id.'
        ]);
        $program->status = 'Resumed';
        $program->resume_date = now();
        $program->update();
        $notiReciever = User::where('role',2)->pluck('id')->first();
        $notiSource = Auth::id();
        $notiTitle = 'Program Resumed!';
        $notiContent = Auth::user()->name.' just resumed '.Program::where('id',$id)->pluck('title')->first().' program.';
        $this->storeNotification($notiReciever,$notiTitle,null,$notiContent,null,$notiSource);
        return response()->json([
            'status' => true,
            'message' => 'Program Resumed Successfully.'
        ]);
    }

    function resetProgram($id){
        $program = ProgramSubscriber::where('user_id',Auth::id())->where('program_id',$id)->first();
        if($program==null)
        return response()->json([
            'status' => false,
            'message' => 'Invalid Program Id.'
        ]);

        $programTrack = ProgramsTracking::where('user_id',Auth::id())->where('program_id',$id)->first();
        $programTrack->delete();

        $res = $this->generateUserProgramProgress($id);
        if($res['status'])
        return response()->json([
            'status' => true,
            'message' => 'Program Reseted Successfully.'
        ]);
        else
        return response($res);
    }

    function getProgramProgress($id){
        $programTrackId = ProgramsTracking::where('program_id',$id)->where('user_id',Auth::id())->pluck('id')->first();
        if($programTrackId==null)
        return response()->json([
            'status' => true,
            'message' => 'Program Not Started Yet.'
        ]);

        $progress = ProgramsTracking::where('id',$programTrackId)->first(['program_id','status']);
        $weeksProgress = WeeksTracking::where('program_track_id',$programTrackId)->get(['id','week_no','status']);
        foreach ($weeksProgress as $item) {
            $workoutsProgress = WorkoutsTracking::where('week_track_id',$item->id)->get(['id','workout_id','status']);
            foreach ($workoutsProgress as $item2) {
                $exercisesProgress = ExercisesTracking::where('workout_track_id',$item2->id)->get(['exercise_id','status']);
                $item2->exercises_progress = $exercisesProgress;
                unset($item2->id);
            }
            $item->workouts_progress = $workoutsProgress;
            unset($item->id);
        }
        $progress->weeks_progress = $weeksProgress;
        return response()->json([
            'status' => true,
            'data' => $progress
        ]);
    }
}
