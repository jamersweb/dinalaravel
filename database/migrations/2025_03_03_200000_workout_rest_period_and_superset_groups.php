<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class WorkoutRestPeriodAndSupersetGroups extends Migration
{
    /**
     * Run the migrations.
     * 1) Rest period: store as INT seconds (0-240), migrate existing string data
     * 2) Superset grouping: add group_id, group_type, group_order for multiple separate supersets
     */
    public function up()
    {
        Schema::table('workout_exercises', function (Blueprint $table) {
            // Rest period: add new INT column
            $table->unsignedSmallInteger('rest_period_seconds')->default(0)->after('time');
            // Superset grouping: unique group_id per superset block
            $table->string('group_id', 36)->nullable()->after('category');
            $table->string('group_type', 50)->nullable()->after('group_id');
            $table->unsignedInteger('group_order')->default(0)->after('group_type');
        });

        // Migrate existing rest_period (string) to rest_period_seconds (int)
        $rows = DB::table('workout_exercises')->get();
        foreach ($rows as $row) {
            $seconds = $this->parseRestPeriodToSeconds($row->rest_period);
            DB::table('workout_exercises')->where('id', $row->id)->update(['rest_period_seconds' => $seconds]);
        }

        Schema::table('workout_exercises', function (Blueprint $table) {
            $table->dropColumn('rest_period');
        });

        DB::statement('ALTER TABLE workout_exercises CHANGE rest_period_seconds rest_period SMALLINT UNSIGNED DEFAULT 0');
    }

    /**
     * Parse legacy rest_period string to seconds.
     * Handles: "5 min", "10 min", "5 sec", "30", etc.
     */
    private function parseRestPeriodToSeconds($value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        $val = trim((string) $value);
        if (stripos($val, 'min') !== false) {
            $num = (int) preg_replace('/[^0-9]/', '', $val);
            return min(240, $num * 60);
        }
        if (stripos($val, 'sec') !== false) {
            $num = (int) preg_replace('/[^0-9]/', '', $val);
            return min(240, $num);
        }
        $num = (int) $val;
        return min(240, max(0, $num));
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('workout_exercises', function (Blueprint $table) {
            $table->string('rest_period_legacy')->nullable()->after('time');
        });

        $rows = DB::table('workout_exercises')->get();
        foreach ($rows as $row) {
            $label = ($row->rest_period ?? 0) >= 60
                ? (intval($row->rest_period) / 60) . ' min'
                : intval($row->rest_period) . ' sec';
            DB::table('workout_exercises')->where('id', $row->id)->update(['rest_period_legacy' => $label]);
        }

        Schema::table('workout_exercises', function (Blueprint $table) {
            $table->dropColumn('rest_period');
        });
        DB::statement('ALTER TABLE workout_exercises CHANGE rest_period_legacy rest_period VARCHAR(255) NULL');

        Schema::table('workout_exercises', function (Blueprint $table) {
            $table->dropColumn(['group_id', 'group_type', 'group_order']);
        });
    }
}
