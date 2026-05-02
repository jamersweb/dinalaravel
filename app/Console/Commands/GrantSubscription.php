<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\UserSub;
use Illuminate\Console\Command;

class GrantSubscription extends Command
{
    protected $signature = 'subscription:grant {user_id : The user ID (or email) to grant subscription to} {--months=12 : Duration in months}';

    protected $description = 'Manually grant Pro subscription to a user (for testing when webhook has wrong app_user_id)';

    public function handle(): int
    {
        $userIdOrEmail = $this->argument('user_id');
        $months = (int) $this->option('months');

        $user = is_numeric($userIdOrEmail)
            ? User::find($userIdOrEmail)
            : User::where('email', $userIdOrEmail)->first();

        if (! $user) {
            $this->error("User not found: {$userIdOrEmail}");
            return 1;
        }

        $userId = $user->id;

        $fullAccessSub = Subscription::where('access_type', 'full_access')
            ->where('status', 1)
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $fullAccessSub) {
            $this->error('No full_access subscription found in database.');
            return 1;
        }

        UserSub::where('user_id', $userId)->where('status', 'active')->update(['status' => 'replaced']);

        $subStartDate = now();
        $subExpireDate = now()->addMonths($months);

        $userSub = new UserSub;
        $userSub->user_id = $userId;
        $userSub->sub_id = $fullAccessSub->id;
        $userSub->payment_id = null;
        $userSub->status = 'active';
        $userSub->sub_start_date = $subStartDate;
        $userSub->sub_expire_date = $subExpireDate;
        $userSub->save();

        $updated = UserDetail::where('user_id', $userId)->update([
            'subscription' => $fullAccessSub->id,
            'subscription_status' => 'active',
        ]);

        if ($updated === 0) {
            $this->warn('user_details row not found for this user - subscription_status may not be set. User may need to complete profile first.');
        }

        $this->info("Subscription granted for user {$userId} ({$user->email}) until {$subExpireDate->format('Y-m-d')}");
        return 0;
    }
}
