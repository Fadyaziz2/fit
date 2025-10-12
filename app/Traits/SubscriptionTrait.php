<?php

namespace App\Traits;

use App\Http\Resources\SubscriptionResource;
use App\Http\Resources\SubscriptionFreezeResource;
use App\Models\Subscription;
use App\Models\SubscriptionFreeze;
use Carbon\Carbon;

trait SubscriptionTrait {

    public function get_user_active_subscription_plan($user_id)
    {
        $statuses = [
            config('constant.SUBSCRIPTION_STATUS.ACTIVE'),
            config('constant.SUBSCRIPTION_STATUS.PAUSED'),
        ];

        return Subscription::where('user_id', $user_id)
            ->whereIn('status', $statuses)
            ->orderByDesc('id')
            ->first();
    }

    public function is_subscribed_users($user_id)
    {
        return Subscription::where('user_id', $user_id)->where('status', config('constant.SUBSCRIPTION_STATUS.ACTIVE'))->exists();
    }

    public function get_plan_expiration_date($plan_start_date = '', $duration_unit = '', $left_days = 0, $plan_duration = 1)
    {
        $start_at = new Carbon($plan_start_date);
        $end_date = '';
        
        if($duration_unit === 'monthly'){
            $end_date =  $start_at->addMonths($plan_duration)->addDays($left_days);
        }
        if($duration_unit === 'yearly'){
            $end_date =  $start_at->addYears($plan_duration)->addDays($left_days);
        }
        return $end_date->format('Y-m-d H:i:s');
    }

    public function check_days_left_plan($previous_plan, $new_plan)
    {
        $previous_plan_end = new Carbon($previous_plan->subscription_end_date);
        $new_plan_start = new Carbon(date('Y-m-d H:i:s'));
        $left_days = $previous_plan_end->diffInDays($new_plan_start);
        return $left_days;
    }

    public function user_last_plan($user_id)
    {
        $user_subscribed = Subscription::where('user_id',$user_id)->where('status',config('constant.SUBSCRIPTION_STATUS.INACTIVE'))->orderBy('id','desc')->first();
        $inactivePlan = null;

        if(!empty($user_subscribed)) {
            $inactivePlan = new SubscriptionResource($user_subscribed);
        }
        return $inactivePlan;
    }

    public function is_plan_active($user_id){
        $is_subscribe = Subscription::where('user_id', $user_id)->where('status', config('constant.SUBSCRIPTION_STATUS.ACTIVE'))->exists();
        return $is_subscribe;
    }

    public function subscriptionPlanDetail($user_id)
    {
        $subscription_model = $this->get_user_active_subscription_plan($user_id);
        $subscription_plan = $subscription_model ? new SubscriptionResource($subscription_model) : null;

        $is_subscribed_users = $this->is_subscribed_users($user_id);
        $activeFreezeResource = null;
        $upcomingFreezeResources = SubscriptionFreezeResource::collection(collect());
        $canFreeze = false;

        if ($subscription_model) {
            $activeFreeze = $subscription_model->freezes()
                ->where('status', SubscriptionFreeze::STATUS_ACTIVE)
                ->orderByDesc('freeze_start_date')
                ->first();

            $scheduledFreezes = $subscription_model->freezes()
                ->where('status', SubscriptionFreeze::STATUS_SCHEDULED)
                ->orderBy('freeze_start_date')
                ->get();

            if ($activeFreeze) {
                $activeFreezeResource = new SubscriptionFreezeResource($activeFreeze);
                $is_subscribed_users = 0;
            }

            $upcomingFreezeResources = SubscriptionFreezeResource::collection($scheduledFreezes);

            $canFreeze = ! $subscription_model->freezes()
                ->whereIn('status', [
                    SubscriptionFreeze::STATUS_ACTIVE,
                    SubscriptionFreeze::STATUS_SCHEDULED,
                ])->exists();
        } elseif(!$this->is_plan_active($user_id) && !$is_subscribed_users) {
            $subscription_plan = $this->user_last_plan($user_id);
        }

        return [
            'is_subscribe' => (int) $is_subscribed_users,
            'subscription_plan' => $subscription_plan,
            'active_freeze' => $activeFreezeResource,
            'upcoming_freezes' => $upcomingFreezeResources,
            'can_freeze' => (bool) $canFreeze,
        ];
    }
}