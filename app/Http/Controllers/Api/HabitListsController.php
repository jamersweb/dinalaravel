<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HabitList;
use App\Models\HabitListItem;
use App\Models\UserHabitAssignment;
use App\Models\UserHabitCompletion;
use App\Traits\NotificationsTrait;
use App\Traits\ActivitiesTrait;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class HabitListsController extends Controller
{
    use NotificationsTrait, ActivitiesTrait;
    
    /**
     * Get user's assigned habit lists
     */
    public function myHabitLists()
    {
        $user = Auth::user();
        
        $assignments = UserHabitAssignment::where('user_id', $user->id)
            ->with(['habitList.items' => function($q) {
                $q->orderBy('order', 'asc');
            }])
            ->get();

        $habitLists = $assignments->map(function ($assignment) use ($user) {
            $list = $assignment->habitList;
            if (!$list) {
                return null;
            }

            $list->items = $list->items->map(function ($item) use ($user) {
                $item->is_completed_today = UserHabitCompletion::where('user_id', $user->id)
                    ->where('habit_list_item_id', $item->id)
                    ->where('completed_date', Carbon::today())
                    ->exists();
                return $item;
            });
            
            // Calculate today's completion stats
            $totalItems = $list->items->count();
            $completedToday = $list->items->where('is_completed_today', true)->count();
            $list->completion_stats = [
                'total' => $totalItems,
                'completed' => $completedToday,
                'percentage' => $totalItems > 0 ? round(($completedToday / $totalItems) * 100) : 0,
            ];
            
            return $list;
        })->filter()->values();

        return response()->json([
            'status' => true,
            'data' => $habitLists
        ], 200);
    }

    /**
     * Mark habit as complete
     */
    public function markHabitComplete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'habit_list_item_id' => 'required|exists:habit_list_items,id',
            'completed_date' => 'nullable|date',
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
            $completedDate = $request->completed_date ? Carbon::parse($request->completed_date) : Carbon::today();
            
            // Check if already completed for this date
            $existing = UserHabitCompletion::where('user_id', $user->id)
                ->where('habit_list_item_id', $request->habit_list_item_id)
                ->where('completed_date', $completedDate->toDateString())
                ->first();
            
            if ($existing) {
                return response()->json([
                    'status' => false,
                    'message' => 'Habit already marked as complete for this date'
                ], 400);
            }
            
            $completion = UserHabitCompletion::create([
                'user_id' => $user->id,
                'habit_list_item_id' => $request->habit_list_item_id,
                'completed_date' => $completedDate,
            ]);

            // Check if user completed all habits for today - send positive notification
            $habitListItem = HabitListItem::find($request->habit_list_item_id);
            $habitListId = $habitListItem->habit_list_id;
            
            $assignment = UserHabitAssignment::where('user_id', $user->id)
                ->where('habit_list_id', $habitListId)
                ->first();
            
            if ($assignment) {
                $allItems = HabitListItem::where('habit_list_id', $habitListId)->pluck('id');
                $completedToday = UserHabitCompletion::where('user_id', $user->id)
                    ->whereIn('habit_list_item_id', $allItems)
                    ->where('completed_date', Carbon::today())
                    ->count();
                
                // If all habits completed today, send positive notification
                if ($completedToday >= $allItems->count()) {
                    $habitListName = HabitList::find($habitListId)->name;
                    $userLang = $this->userSelecetdLanguage($user->id);
                    $notiTitle = 'Great Job!';
                    $notiTitleAr = 'عمل رائع!';
                    $notiContent = "You've completed all habits in '{$habitListName}' today! Keep up the excellent work!";
                    $notiContentAr = "لقد أكملت جميع العادات في '{$habitListName}' اليوم! استمر في العمل الرائع!";
                    $this->storeNotification($user->id, $notiTitle, $notiTitleAr, $notiContent, $notiContentAr);
                    if (!empty($user->fcm_token)) {
                        if ($userLang === 'en') {
                            $this->sendFirebaseNotification([$user->fcm_token], $notiTitle, $notiContent);
                        } else {
                            $this->sendFirebaseNotification([$user->fcm_token], $notiTitleAr, $notiContentAr);
                        }
                    }
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Habit marked as complete',
                'data' => $completion
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error marking habit complete',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get habit progress
     */
    public function getHabitProgress(Request $request)
    {
        $user = Auth::user();
        $habitListId = $request->get('habit_list_id');
        
        $query = UserHabitCompletion::where('user_id', $user->id);
        
        if ($habitListId) {
            $itemIds = HabitListItem::where('habit_list_id', $habitListId)->pluck('id');
            $query->whereIn('habit_list_item_id', $itemIds);
        }
        
        $completions = $query->with('habitListItem.habitList')
            ->orderBy('completed_date', 'desc')
            ->get();

        // Calculate statistics
        $totalHabits = $habitListId 
            ? HabitListItem::where('habit_list_id', $habitListId)->count()
            : HabitListItem::count();
        
        $completedToday = $completions->where('completed_date', Carbon::today()->toDateString())->count();
        $completedThisWeek = $completions->where('completed_date', '>=', Carbon::now()->startOfWeek())->count();
        
        // Calculate streak
        $streak = 0;
        $date = Carbon::today();
        while ($completions->where('completed_date', $date->toDateString())->count() > 0) {
            $streak++;
            $date->subDay();
        }

        return response()->json([
            'status' => true,
            'data' => [
                'completions' => $completions,
                'statistics' => [
                    'total_habits' => $totalHabits,
                    'completed_today' => $completedToday,
                    'completed_this_week' => $completedThisWeek,
                    'current_streak' => $streak,
                ]
            ]
        ], 200);
    }

    // ============ CMS/Admin Methods ============

    /**
     * Create a new habit list (Admin)
     */
    public function createHabitList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.habit_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $habitList = HabitList::create([
                'name' => $request->name,
                'description' => $request->description,
                'created_by' => Auth::id(),
            ]);

            // Create habit list items
            foreach ($request->items as $index => $item) {
                HabitListItem::create([
                    'habit_list_id' => $habitList->id,
                    'habit_name' => $item['habit_name'],
                    'order' => $index,
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Habit list created successfully',
                'data' => $habitList->load('items')
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error creating habit list',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update habit list (Admin)
     */
    public function updateHabitList(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.habit_name' => 'required|string',
            'items.*.id' => 'nullable|exists:habit_list_items,id', // For existing items
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $habitList = HabitList::findOrFail($id);
            $habitList->name = $request->name;
            $habitList->description = $request->description;
            $habitList->save();

            // Get existing item IDs
            $existingItemIds = collect($request->items)->pluck('id')->filter()->toArray();
            
            // Delete removed items
            HabitListItem::where('habit_list_id', $habitList->id)
                ->whereNotIn('id', $existingItemIds)
                ->delete();

            // Update or create items
            foreach ($request->items as $index => $item) {
                if (isset($item['id'])) {
                    // Update existing item
                    HabitListItem::where('id', $item['id'])
                        ->update([
                            'habit_name' => $item['habit_name'],
                            'order' => $index,
                        ]);
                } else {
                    // Create new item
                    HabitListItem::create([
                        'habit_list_id' => $habitList->id,
                        'habit_name' => $item['habit_name'],
                        'order' => $index,
                    ]);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Habit list updated successfully',
                'data' => $habitList->load('items')
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error updating habit list',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete habit list (Admin)
     */
    public function deleteHabitList($id)
    {
        try {
            $habitList = HabitList::findOrFail($id);
            $habitList->delete(); // Cascade will delete items and assignments

            return response()->json([
                'status' => true,
                'message' => 'Habit list deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error deleting habit list',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all habit lists (Admin)
     */
    public function getAllHabitLists()
    {
        $habitLists = HabitList::with(['items' => function($q) {
            $q->orderBy('order', 'asc');
        }, 'creator'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Add stats for each list
        foreach ($habitLists as $list) {
            $list->total_items = $list->items->count();
            $list->total_assignments = UserHabitAssignment::where('habit_list_id', $list->id)->count();
        }

        return response()->json([
            'status' => true,
            'data' => $habitLists
        ], 200);
    }

    /**
     * Get habit list detail (Admin)
     */
    public function getHabitListDetail($id)
    {
        $habitList = HabitList::with(['items' => function($q) {
            $q->orderBy('order', 'asc');
        }, 'creator', 'userAssignments.user'])
            ->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $habitList
        ], 200);
    }

    /**
     * Assign habit list to users (Admin)
     */
    public function assignHabitListToUsers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'habit_list_id' => 'required|exists:habit_lists,id',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $habitList = HabitList::findOrFail($request->habit_list_id);
            $assignedUsers = [];
            $alreadyAssigned = [];
            $notiReceivers = [];
            $notiReceiversAr = [];

            foreach ($request->user_ids as $userId) {
                // Check if already assigned
                $existing = UserHabitAssignment::where('user_id', $userId)
                    ->where('habit_list_id', $request->habit_list_id)
                    ->first();

                if ($existing) {
                    $alreadyAssigned[] = $userId;
                    continue;
                }

                // Create assignment
                UserHabitAssignment::create([
                    'user_id' => $userId,
                    'habit_list_id' => $request->habit_list_id,
                    'assigned_at' => Carbon::now(),
                ]);

                $assignedUsers[] = $userId;

                // Send notification to user
                $user = User::find($userId);
                if ($user) {
                    $userLang = $this->userSelecetdLanguage($userId);
                    $notiTitle = 'New Habit List Assigned!';
                    $notiTitleAr = 'تم تعيين قائمة عادات جديدة!';
                    $notiContent = "A new habit list '{$habitList->name}' has been assigned to you. Check it out and start tracking your habits daily!";
                    $notiContentAr = "تم تعيين قائمة عادات جديدة '{$habitList->name}' لك. تحقق منها وابدأ بتتبع عاداتك اليومية!";
                    
                    $this->storeNotification($userId, $notiTitle, $notiTitleAr, $notiContent, $notiContentAr);
                    
                    if ($user->fcm_token) {
                        if ($userLang === 'en') {
                            $this->sendFirebaseNotification([$user->fcm_token], $notiTitle, $notiContent);
                        } else {
                            $this->sendFirebaseNotification([$user->fcm_token], $notiTitleAr, $notiContentAr);
                        }
                    }
                }

                // Generate activity for admin
                $this->generateActivityForAdmin(
                    'Habit List Assigned',
                    Auth::user()->name . " assigned habit list '{$habitList->name}' to " . $user->name,
                    3, // Habits category
                    $userId,
                    'habit_list_assignment',
                    $habitList->id
                );
            }

            return response()->json([
                'status' => true,
                'message' => 'Habit list assigned successfully',
                'assigned_count' => count($assignedUsers),
                'already_assigned_count' => count($alreadyAssigned),
                'assigned_users' => $assignedUsers,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error assigning habit list',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unassign habit list from users (Admin)
     */
    public function unassignHabitListFromUsers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'habit_list_id' => 'required|exists:habit_lists,id',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            UserHabitAssignment::where('habit_list_id', $request->habit_list_id)
                ->whereIn('user_id', $request->user_ids)
                ->delete();

            return response()->json([
                'status' => true,
                'message' => 'Habit list unassigned successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error unassigning habit list',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get users assigned to a habit list (Admin)
     */
    public function getHabitListUsers($id)
    {
        $assignments = UserHabitAssignment::where('habit_list_id', $id)
            ->with('user.userdetails')
            ->get();

        $users = $assignments->map(function($assignment) {
            $user = $assignment->user;
            if ($user && $user->userdetails) {
                $user->full_name = $user->userdetails->name . ' ' . $user->userdetails->Lastname;
                $user->picture = $user->userdetails->picture;
            }
            return $user;
        });

        return response()->json([
            'status' => true,
            'data' => $users
        ], 200);
    }
}
