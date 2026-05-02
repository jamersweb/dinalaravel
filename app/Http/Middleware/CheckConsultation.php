<?php

namespace App\Http\Middleware;

use App\Models\ConsultationForm;
use App\Models\UserAnswer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckConsultation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // Check if consultation form is completed
        $consultation = ConsultationForm::where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->first();

        // If no ConsultationForm entry, check if user has submitted answers (backward compatibility)
        if (!$consultation) {
            $hasAnswers = UserAnswer::where('user_id', $user->id)->count() > 0;
            
            if ($hasAnswers) {
                // User has answers but no ConsultationForm entry - create one for backward compatibility
                ConsultationForm::create([
                    'user_id' => $user->id,
                    'completed_at' => now(),
                ]);
            } else {
                // No answers and no consultation form - user needs to complete consultation
                return response()->json([
                    'status' => false,
                    'message' => 'Please complete the consultation form first',
                    'consultation_required' => true
                ], 403);
            }
        }

        return $next($request);
    }
}

