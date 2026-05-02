<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExerciseWeightTracking;
use App\Models\UserAchievement;
use App\Models\Exercise;
use App\Models\User;
use App\Traits\NotificationsTrait;
use App\Traits\ActivitiesTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class WeightTrackingController extends Controller
{
    use NotificationsTrait, ActivitiesTrait;
    /**
     * Track exercise weight
     */
    public function trackWeight(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exercise_id' => 'required|exists:exercises,id',
            'weight' => 'required|numeric|min:0',
            'sets' => 'nullable|integer',
            'reps' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            DB::beginTransaction();
            
            $user = Auth::user();
            
            // Get previous max weight for this exercise
            $previousMax = ExerciseWeightTracking::where('user_id', $user->id)
                ->where('exercise_id', $request->exercise_id)
                ->max('weight');
            
            $isPersonalBest = false;
            if (!$previousMax || $request->weight > $previousMax) {
                $isPersonalBest = true;
                
                // Update all previous records to false
                ExerciseWeightTracking::where('user_id', $user->id)
                    ->where('exercise_id', $request->exercise_id)
                    ->update(['is_personal_best' => false]);
            }
            
            $tracking = ExerciseWeightTracking::create([
                'user_id' => $user->id,
                'exercise_id' => $request->exercise_id,
                'weight' => $request->weight,
                'sets' => $request->sets,
                'reps' => $request->reps,
                'is_personal_best' => $isPersonalBest,
            ]);

            // If personal best, create achievement and notify admin
            if ($isPersonalBest) {
                $exercise = Exercise::find($request->exercise_id);
                
                // Create achievement with star
                UserAchievement::create([
                    'user_id' => $user->id,
                    'achievement_type' => 'personal_best',
                    'achievement_data' => [
                        'exercise_id' => $request->exercise_id,
                        'exercise_name' => $exercise->title,
                        'weight' => $request->weight,
                    ],
                    'stars' => 1,
                ]);
                
                // Notify admin (Dina) about personal best
                $adminId = User::where('role', 2)->pluck('id')->first();
                if ($adminId) {
                    $userName = $user->name;
                    $exerciseName = $exercise->title;
                    $weightUnit = \App\Models\UserSetting::where('user_id', $user->id)->pluck('weight_unit')->first() ?? 'kg';
                    $notiTitle = 'Personal Best Achieved! ⭐';
                    $notiTitleAr = 'تم تحقيق أفضل أداء شخصي! ⭐';
                    $notiContent = "{$userName} achieved a personal best in {$exerciseName} with {$request->weight} {$weightUnit} - They earned a star!";
                    $notiContentAr = "حققت {$userName} أفضل أداء شخصي في {$exerciseName} بـ {$request->weight} {$weightUnit} - لقد حصلوا على نجمة!";
                    
                    $this->storeNotification($adminId, $notiTitle, $notiTitleAr, $notiContent, $notiContentAr, $user->id);
                    
                    // Generate activity for admin
                    $this->generateActivityForAdmin(
                        'Personal Best ⭐',
                        $notiContent,
                        4, // Goals Hit category
                        $user->id,
                        'personal_best',
                        $exercise->id
                    );
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => $isPersonalBest ? 'Weight tracked! Personal best achieved! ⭐ You earned a star!' : 'Weight tracked successfully',
                'data' => [
                    'tracking' => $tracking,
                    'is_personal_best' => $isPersonalBest,
                    'star_earned' => $isPersonalBest,
                    'achievement' => $isPersonalBest ? [
                        'type' => 'personal_best',
                        'message' => "Congratulations! You've set a new personal best!"
                    ] : null,
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Error tracking weight',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get weight history for exercise
     */
    public function getWeightHistory($exerciseId)
    {
        $user = Auth::user();
        
        $history = ExerciseWeightTracking::where('user_id', $user->id)
            ->where('exercise_id', $exerciseId)
            ->orderBy('created_at', 'desc')
            ->with('exercise')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $history
        ], 200);
    }

    /**
     * Get all personal bests
     */
    public function getPersonalBests()
    {
        $user = Auth::user();
        
        $personalBests = ExerciseWeightTracking::where('user_id', $user->id)
            ->where('is_personal_best', true)
            ->with('exercise')
            ->orderBy('weight', 'desc')
            ->get();

        $totalStars = UserAchievement::where('user_id', $user->id)
            ->sum('stars');

        return response()->json([
            'status' => true,
            'data' => [
                'personal_bests' => $personalBests,
                'total_stars' => $totalStars
            ]
        ], 200);
    }

    /**
     * Get last weight lifted for an exercise
     * Users can see what weight they last lifted to know if they should go heavier
     */
    public function getLastWeight($exerciseId)
    {
        $user = Auth::user();
        
        $lastWeight = ExerciseWeightTracking::where('user_id', $user->id)
            ->where('exercise_id', $exerciseId)
            ->orderBy('created_at', 'desc')
            ->first();
        
        $personalBest = ExerciseWeightTracking::where('user_id', $user->id)
            ->where('exercise_id', $exerciseId)
            ->where('is_personal_best', true)
            ->first();
        
        // Get user's weight unit
        $weightUnit = \App\Models\UserSetting::where('user_id', $user->id)->pluck('weight_unit')->first() ?? 'kg';
        
        return response()->json([
            'status' => true,
            'data' => [
                'last_weight' => $lastWeight ? [
                    'weight' => $lastWeight->weight,
                    'sets' => $lastWeight->sets,
                    'reps' => $lastWeight->reps,
                    'date' => $lastWeight->created_at->format('Y-m-d'),
                    'date_human' => $lastWeight->created_at->diffForHumans(),
                ] : null,
                'personal_best' => $personalBest ? [
                    'weight' => $personalBest->weight,
                    'sets' => $personalBest->sets,
                    'reps' => $personalBest->reps,
                    'date' => $personalBest->created_at->format('Y-m-d'),
                    'date_human' => $personalBest->created_at->diffForHumans(),
                ] : null,
                'weight_unit' => $weightUnit,
                'suggestion' => $lastWeight ? 'Consider increasing weight if you completed all sets and reps comfortably' : 'No previous weight recorded for this exercise',
            ]
        ], 200);
    }
}

