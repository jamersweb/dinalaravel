<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddIndexesToFrequentlyQueriedTables extends Migration
{
    /**
     * Run the migrations.
     * Add indexes to frequently queried columns to improve database performance
     *
     * @return void
     */
    public function up()
    {
        // Users table indexes
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                // Email is frequently used for lookups
                if (!$this->hasIndex('users', 'users_email_index')) {
                    $table->index('email', 'users_email_index');
                }
                // Skipping api_token index: column type can be TEXT and fail on MySQL without key length.
                // Role filtering
                if (Schema::hasColumn('users', 'role') && !$this->hasIndex('users', 'users_role_index')) {
                    $table->index('role', 'users_role_index');
                }
            });
        }

        // User details table indexes
        if (Schema::hasTable('user_details')) {
            Schema::table('user_details', function (Blueprint $table) {
                // Foreign key index for user_id
                if (!$this->hasIndex('user_details', 'user_details_user_id_index')) {
                    $table->index('user_id', 'user_details_user_id_index');
                }
                // Subscription status filtering
                if (Schema::hasColumn('user_details', 'subscription_status') && !$this->hasIndex('user_details', 'user_details_subscription_status_index')) {
                    $table->index('subscription_status', 'user_details_subscription_status_index');
                }
            });
        }

        // User subscriptions table indexes
        if (Schema::hasTable('user_subs')) {
            Schema::table('user_subs', function (Blueprint $table) {
                if (!$this->hasIndex('user_subs', 'user_subs_user_id_index')) {
                    $table->index('user_id', 'user_subs_user_id_index');
                }
                if (Schema::hasColumn('user_subs', 'status') && !$this->hasIndex('user_subs', 'user_subs_status_index')) {
                    $table->index('status', 'user_subs_status_index');
                }
                // Composite index for common query pattern
                if (!$this->hasIndex('user_subs', 'user_subs_user_status_index')) {
                    $table->index(['user_id', 'status'], 'user_subs_user_status_index');
                }
            });
        }

        // Programs tracking table indexes
        if (Schema::hasTable('programs_tracking')) {
            Schema::table('programs_tracking', function (Blueprint $table) {
                if (!$this->hasIndex('programs_tracking', 'programs_tracking_user_id_index')) {
                    $table->index('user_id', 'programs_tracking_user_id_index');
                }
                if (Schema::hasColumn('programs_tracking', 'program_id') && !$this->hasIndex('programs_tracking', 'programs_tracking_program_id_index')) {
                    $table->index('program_id', 'programs_tracking_program_id_index');
                }
            });
        }

        // Program subscriptions table indexes
        if (Schema::hasTable('program_subs')) {
            Schema::table('program_subs', function (Blueprint $table) {
                if (!$this->hasIndex('program_subs', 'program_subs_user_id_index')) {
                    $table->index('user_id', 'program_subs_user_id_index');
                }
                if (Schema::hasColumn('program_subs', 'program_id') && !$this->hasIndex('program_subs', 'program_subs_program_id_index')) {
                    $table->index('program_id', 'program_subs_program_id_index');
                }
                if (Schema::hasColumn('program_subs', 'status') && !$this->hasIndex('program_subs', 'program_subs_status_index')) {
                    $table->index('status', 'program_subs_status_index');
                }
            });
        }

        // Program phases table indexes
        if (Schema::hasTable('program_phases')) {
            Schema::table('program_phases', function (Blueprint $table) {
                if (Schema::hasColumn('program_phases', 'program_id') && !$this->hasIndex('program_phases', 'program_phases_program_id_index')) {
                    $table->index('program_id', 'program_phases_program_id_index');
                }
            });
        }

        // Body stats and related tables
        $bodyStatsTables = ['body_stats', 'user_steps', 'user_sleep', 'user_calories_burn', 'user_heart_rate', 'user_blood_pressure'];
        foreach ($bodyStatsTables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!$this->hasIndex($tableName, $tableName . '_user_id_index')) {
                        $table->index('user_id', $tableName . '_user_id_index');
                    }
                    if (Schema::hasColumn($tableName, 'date') && !$this->hasIndex($tableName, $tableName . '_date_index')) {
                        $table->index('date', $tableName . '_date_index');
                    }
                    // Composite index for user_id + date queries
                    if (!$this->hasIndex($tableName, $tableName . '_user_date_index')) {
                        $table->index(['user_id', 'date'], $tableName . '_user_date_index');
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop indexes in reverse order
        $bodyStatsTables = ['user_blood_pressure', 'user_heart_rate', 'user_calories_burn', 'user_sleep', 'user_steps', 'body_stats'];
        foreach ($bodyStatsTables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->dropIndex($tableName . '_user_date_index');
                    if (Schema::hasColumn($tableName, 'date')) {
                        $table->dropIndex($tableName . '_date_index');
                    }
                    $table->dropIndex($tableName . '_user_id_index');
                });
            }
        }

        if (Schema::hasTable('program_phases')) {
            Schema::table('program_phases', function (Blueprint $table) {
                if (Schema::hasColumn('program_phases', 'program_id')) {
                    $table->dropIndex('program_phases_program_id_index');
                }
            });
        }

        if (Schema::hasTable('program_subs')) {
            Schema::table('program_subs', function (Blueprint $table) {
                if (Schema::hasColumn('program_subs', 'status')) {
                    $table->dropIndex('program_subs_status_index');
                }
                if (Schema::hasColumn('program_subs', 'program_id')) {
                    $table->dropIndex('program_subs_program_id_index');
                }
                $table->dropIndex('program_subs_user_id_index');
            });
        }

        if (Schema::hasTable('programs_tracking')) {
            Schema::table('programs_tracking', function (Blueprint $table) {
                if (Schema::hasColumn('programs_tracking', 'program_id')) {
                    $table->dropIndex('programs_tracking_program_id_index');
                }
                $table->dropIndex('programs_tracking_user_id_index');
            });
        }

        if (Schema::hasTable('user_subs')) {
            Schema::table('user_subs', function (Blueprint $table) {
                $table->dropIndex('user_subs_user_status_index');
                if (Schema::hasColumn('user_subs', 'status')) {
                    $table->dropIndex('user_subs_status_index');
                }
                $table->dropIndex('user_subs_user_id_index');
            });
        }

        if (Schema::hasTable('user_details')) {
            Schema::table('user_details', function (Blueprint $table) {
                if (Schema::hasColumn('user_details', 'subscription_status')) {
                    $table->dropIndex('user_details_subscription_status_index');
                }
                $table->dropIndex('user_details_user_id_index');
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'role')) {
                    $table->dropIndex('users_role_index');
                }
                // users_api_token_index intentionally not dropped (not created in up()).
                $table->dropIndex('users_email_index');
            });
        }
    }

    /**
     * Check if an index exists on a table
     *
     * @param string $table
     * @param string $indexName
     * @return bool
     */
    private function hasIndex($table, $indexName)
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return !empty($indexes);
    }
}
