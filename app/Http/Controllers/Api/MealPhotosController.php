<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MealPhoto;
use App\Models\MealPhotoComment;
use App\Traits\ActivitiesTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class MealPhotosController extends Controller
{
    use ActivitiesTrait;
    
    /**
     * Upload meal photo
     */
    public function uploadMealPhoto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'meal_details' => 'nullable|string',
            'meal_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $user = Auth::user();
            
            // Handle file upload (similar to posture picture upload)
            $filename = Auth::id() . "_meal_photo_" . time() . '_' . uniqid() . '.' . $request->file('photo')->getClientOriginalExtension();
            $photoPath = $request->file('photo')->storeAs('meal_photos', $filename, 'fwd_media');
            
            $mealPhoto = MealPhoto::create([
                'user_id' => $user->id,
                'photo_url' => $filename, // Store only filename, not full path
                'meal_details' => $request->meal_details,
                'meal_date' => $request->meal_date ?? Carbon::today(),
            ]);

            // Generate activity for admin to see in overview/recent activities
            $userName = $user->name;
            $title = 'Meal Photo Uploaded';
            $content = $userName . ' uploaded a meal photo';
            $category = 8; // Meals category (from activities_categories: 1=Workouts, 2=Cardios, 3=Habits, 4=Goals Hit, 5=Body Stats, 6=Photos, 7=Payments, 8=Meals)
            $this->generateActivityForAdmin($title, $content, $category, $user->id, 'meal_photo', $mealPhoto->id);

            return response()->json([
                'status' => true,
                'message' => 'Meal photo uploaded successfully',
                'data' => $mealPhoto
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error uploading meal photo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get meal photos (last 30 days for tracking progress)
     * Users can review their meal photos from the last 30 days to track progress
     */
    public function getMealPhotos(Request $request)
    {
        $user = Auth::user();
        // Default to last 30 days to track progress
        $days = $request->get('days', 30);
        
        $startDate = Carbon::today()->subDays($days);
        
        $mealPhotos = MealPhoto::where('user_id', $user->id)
            ->where('meal_date', '>=', $startDate)
            ->orderBy('meal_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->with(['comments.user', 'user'])
            ->get();

        // Format photo URLs and ensure meal_details and comments are included
        foreach ($mealPhotos as $photo) {
            // Ensure photo_url is properly formatted for display
            if ($photo->photo_url) {
                if (strpos($photo->photo_url, 'http') !== 0) {
                    $photo->photo_url = url('/media/meal_photos/' . $photo->photo_url);
                }
            }
            // Ensure meal_details is included (details about the meal written by user)
            // Comments are already loaded via ->with(['comments.user', 'user'])
        }

        return response()->json([
            'status' => true,
            'data' => $mealPhotos,
            'days' => $days,
            'start_date' => $startDate->format('Y-m-d')
        ], 200);
    }

    /**
     * Get single meal photo with comments and meal_details
     * Returns the meal photo with:
     * - photo_url (formatted image URL)
     * - meal_details (details about the meal written by the user)
     * - comments (all comments from users and admin)
     */
    public function getMealPhoto($id)
    {
        $mealPhoto = MealPhoto::with(['comments.user', 'user'])
            ->find($id);

        if (!$mealPhoto) {
            return response()->json([
                'status' => false,
                'message' => 'Meal photo not found'
            ], 404);
        }

        // Format photo URL for display (picture should show)
        if ($mealPhoto->photo_url && strpos($mealPhoto->photo_url, 'http') !== 0) {
            $mealPhoto->photo_url = url('/media/meal_photos/' . $mealPhoto->photo_url);
        }
        
        // meal_details is already included in the model (from fillable fields)
        // comments are already loaded via ->with(['comments.user', 'user'])

        return response()->json([
            'status' => true,
            'data' => $mealPhoto
        ], 200);
    }

    /**
     * Add comment to meal photo
     */
    public function addComment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'meal_photo_id' => 'required|exists:meal_photos,id',
            'comment' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $user = Auth::user();
            
            $comment = MealPhotoComment::create([
                'meal_photo_id' => $request->meal_photo_id,
                'user_id' => $user->id,
                'comment' => $request->comment,
            ]);

            $comment->load('user');

            return response()->json([
                'status' => true,
                'message' => 'Comment added successfully',
                'data' => $comment
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error adding comment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get comments for meal photo
     */
    public function getComments($id)
    {
        $comments = MealPhotoComment::where('meal_photo_id', $id)
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $comments
        ], 200);
    }
}

