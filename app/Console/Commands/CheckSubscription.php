<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Subscription;
use App\Models\SubscriptionFreeze;
use Carbon\Carbon;

class CheckSubscription extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:subscription';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check subscription';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $now = Carbon::now();

        $scheduledFreezes = SubscriptionFreeze::where('status', SubscriptionFreeze::STATUS_SCHEDULED)
            ->where('freeze_start_date', '<=', $now)
            ->get();

        foreach ($scheduledFreezes as $freeze) {
            $subscription = $freeze->subscription;
            if (!$subscription) {
                continue;
            }

            $freeze->status = SubscriptionFreeze::STATUS_ACTIVE;
            $freeze->save();

            $subscription->status = config('constant.SUBSCRIPTION_STATUS.PAUSED');
            $subscription->save();

            if ($subscription->user) {
                $subscription->user->is_subscribe = 0;
                $subscription->user->save();
            }
        }

        $completedFreezes = SubscriptionFreeze::where('status', SubscriptionFreeze::STATUS_ACTIVE)
            ->where('freeze_end_date', '<=', $now)
            ->get();

        foreach ($completedFreezes as $freeze) {
            $subscription = $freeze->subscription;
            if (!$subscription) {
                continue;
            }

            $freezeDuration = $freeze->freeze_start_date && $freeze->freeze_end_date
                ? $freeze->freeze_start_date->diffInSeconds($freeze->freeze_end_date)
                : 0;

            if ($subscription->subscription_end_date) {
                $endDate = Carbon::parse($subscription->subscription_end_date)->addSeconds($freezeDuration);
                $subscription->subscription_end_date = $endDate->format('Y-m-d H:i:s');
            }

            $subscription->status = config('constant.SUBSCRIPTION_STATUS.ACTIVE');
            $subscription->save();

            if ($subscription->user) {
                $user = $subscription->user;
                if ($subscription->subscription_end_date && Carbon::parse($subscription->subscription_end_date)->isFuture()) {
                    $user->is_subscribe = 1;
                } else {
                    $user->is_subscribe = 0;
                    $subscription->status = config('constant.SUBSCRIPTION_STATUS.INACTIVE');
                    $subscription->save();
                }
                $user->save();
            }

            $freeze->status = SubscriptionFreeze::STATUS_COMPLETED;
            $freeze->processed_at = $now;
            $freeze->save();
        }

        $user_list = User::where('is_subscribe', 1)->with('subscriptionPackage')->get();
        foreach ($user_list as $key => $user) {
            $subscription = Subscription::where('user_id', $user->id)->where('status', config('constant.SUBSCRIPTION_STATUS.ACTIVE'))->first();
            $subscription_end_at = date('Y-m-d', strtotime(optional($user->subscriptionPackage)->subscription_end_date));
            $today_date = date('Y-m-d');
            if(strtotime($subscription_end_at) < strtotime($today_date)) {
                // \Log::info('subscription-expire:-'.$user->id);

                $user->is_subscribe = 0;
                $user->save();

                $subscription->status = config('constant.SUBSCRIPTION_STATUS.INACTIVE');
                $subscription->save();
            }
            // \Log::info('No subscriber');
        }
    }
}
