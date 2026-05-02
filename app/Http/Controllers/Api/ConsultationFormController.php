<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConsultationForm;
use App\Models\Tag;
use App\Models\UserTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ConsultationFormController extends Controller
{
    /**
     * Submit consultation form
     */
    public function submitConsultation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'health_background' => 'required|string',
            'injuries' => 'nullable|string',
            'goals' => 'required|string',
            'lifestyle_habits' => 'nullable|string',
            'preferred_training_style' => 'nullable|string',
            'fitness_level' => 'required|string',
            'medical_concerns' => 'nullable|string',
            'training_experience' => 'nullable|string',
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
            
            // Check if consultation already exists
            $consultation = ConsultationForm::where('user_id', $user->id)->first();
            
            if ($consultation) {
                // Update existing consultation
                $consultation->update($request->all());
                $consultation->completed_at = now();
                $consultation->save();
            } else {
                // Create new consultation
                $consultation = ConsultationForm::create([
                    'user_id' => $user->id,
                    'health_background' => $request->health_background,
                    'injuries' => $request->injuries,
                    'goals' => $request->goals,
                    'lifestyle_habits' => $request->lifestyle_habits,
                    'preferred_training_style' => $request->preferred_training_style,
                    'fitness_level' => $request->fitness_level,
                    'medical_concerns' => $request->medical_concerns,
                    'training_experience' => $request->training_experience,
                    'completed_at' => now(),
                ]);
            }

            // Generate tags from consultation answers
            $this->generateTagsFromConsultation($user->id, $request->all());

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Consultation form submitted successfully',
                'data' => $consultation
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Error submitting consultation form',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if consultation form is completed
     */
    public function checkConsultationStatus()
    {
        $user = Auth::user();
        $consultation = ConsultationForm::where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->first();

        return response()->json([
            'status' => true,
            'completed' => $consultation ? true : false,
            'data' => $consultation
        ], 200);
    }

    /**
     * Get consultation form data
     */
    public function getConsultation()
    {
        $user = Auth::user();
        $consultation = ConsultationForm::where('user_id', $user->id)->first();

        return response()->json([
            'status' => true,
            'data' => $consultation
        ], 200);
    }

    /**
     * Generate tags from consultation answers
     */
    private function generateTagsFromConsultation($userId, $answers)
    {
        $tagMappings = [
            // Injuries
            'back injury' => 'Back Injury',
            'knee injury' => 'Knee Injury',
            'shoulder injury' => 'Shoulder Injury',
            
            // Goals
            'lose weight' => 'Weight Loss',
            'weight loss' => 'Weight Loss',
            'muscle gain' => 'Muscle Gain',
            'toning' => 'Toning',
            'strength' => 'Strength',
            
            // Medical
            'pcos' => 'PCOS',
            'diabetes' => 'Diabetes',
            'insulin resistance' => 'Insulin Resistance',
            
            // Fitness Level
            'beginner' => 'Beginner Level',
            'intermediate' => 'Intermediate Level',
            'advanced' => 'Advanced Level',
        ];

        $tagsToAssign = [];

        // Check each answer for keywords
        foreach ($answers as $answer) {
            if (is_string($answer)) {
                $lowerAnswer = strtolower($answer);
                foreach ($tagMappings as $keyword => $tagName) {
                    if (strpos($lowerAnswer, $keyword) !== false) {
                        $tagsToAssign[] = $tagName;
                    }
                }
            }
        }

        // Get or create tags and assign to user
        foreach ($tagsToAssign as $tagName) {
            $tag = Tag::firstOrCreate(
                ['name' => $tagName],
                ['type' => 'consultation', 'category' => 'user']
            );

            // Assign tag to user if not already assigned
            UserTag::firstOrCreate([
                'user_id' => $userId,
                'tag_id' => $tag->id
            ]);
        }
    }
}

