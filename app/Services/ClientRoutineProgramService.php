<?php

namespace App\Services;

use App\Models\ClientRoutineAssignment;
use App\Models\Program;
use App\Models\ProgramPhase;
use App\Models\ProgramPhaseWorkout;
use App\Models\ProgramSub;
use App\Models\UserDetail;
use App\Models\WeeklyWorkout;
use App\Models\WeekWiseProgram;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ClientRoutineProgramService
{
    public function createProgramFromAssignments(int $userId, ?int $createdBy = null): array
    {
        $assignments = ClientRoutineAssignment::query()
            ->where('user_id', $userId)
            ->where('status', 'assigned')
            ->with('workout:id,title,content_code,language,equipment_category,fitness_level,workout_type,routine_status')
            ->orderBy('assigned_at')
            ->orderBy('id')
            ->get()
            ->filter(fn ($assignment) => $assignment->workout && $assignment->workout->routine_status === 'approved')
            ->values();

        if ($assignments->isEmpty()) {
            throw new RuntimeException('Assign at least one approved routine before creating a client program.');
        }

        return DB::transaction(function () use ($userId, $createdBy, $assignments) {
            $clientName = trim((string) optional(UserDetail::where('user_id', $userId)->first())->name);
            $code = 'AI-CLIENT-' . $userId . '-' . now()->format('YmdHis');

            $program = new Program();
            $program->content_code = $code;
            $program->title = ($clientName ? $clientName . ' ' : '') . 'AI Recommended Program';
            $program->type = 'routine_library';
            $program->level = optional($assignments->first()->workout)->fitness_level ?: 'beginner';
            $program->phases = 1;
            $program->language = optional($assignments->first()->workout)->language ?: 'en';
            $program->discription = 'Generated from coach-approved AI routine recommendations.';
            $program->save();

            $phase = new ProgramPhase();
            $phase->program_id = $program->id;
            $phase->phase_no = 1;
            $phase->weeks = 4;
            $phase->name = 'AI Recommended Phase';
            $phase->summary = 'Four-week plan assembled from selected recommended routines.';
            $phase->save();

            foreach ($assignments as $index => $assignment) {
                ProgramPhaseWorkout::create([
                    'program_phase_id' => $phase->id,
                    'workout_id' => $assignment->workout_id,
                    'display_name' => optional($assignment->workout)->title,
                    'section_tag' => 'workout_routine',
                    'sort_order' => $index,
                ]);
            }

            ProgramSub::where('user_id', $userId)
                ->whereIn('status', ['subscribed', 'in-progress', 'resumed', 'paused'])
                ->update(['status' => 'switched', 'complete_date' => Carbon::today()]);

            $programSub = new ProgramSub();
            $programSub->user_id = $userId;
            $programSub->program_id = $program->id;
            $programSub->subscribe_date = Carbon::today();
            $programSub->status = 'subscribed';
            $programSub->save();

            for ($weekNo = 1; $weekNo <= 4; $weekNo++) {
                $week = new WeekWiseProgram();
                $week->program_sub_id = $programSub->id;
                $week->week_no = $weekNo;
                $week->status = 0;
                $week->save();

                foreach ($assignments as $index => $assignment) {
                    WeeklyWorkout::create([
                        'week_id' => $week->id,
                        'workout_id' => $assignment->workout_id,
                        'display_name' => optional($assignment->workout)->title,
                        'section_tag' => 'workout_routine',
                        'sort_order' => $index,
                        'status' => 0,
                    ]);
                }
            }

            ClientRoutineAssignment::whereIn('id', $assignments->pluck('id'))
                ->update([
                    'status' => 'packaged',
                    'notes' => DB::raw("CONCAT(COALESCE(notes, ''), ' Packaged into program {$program->id}.')"),
                ]);

            return [
                'program' => $program,
                'program_subscription' => $programSub,
                'assigned_routines' => $assignments->count(),
                'weeks' => 4,
            ];
        });
    }
}
