<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\Package;
use App\Models\SubscriptionFreeze;
use App\Models\User;
use App\Http\Resources\SubscriptionFreezeResource;
use App\Http\Resources\SubscriptionResource;
use App\Traits\SubscriptionTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    use SubscriptionTrait;
    public function getList(Request $request)
    {
        $subscription = Subscription::mySubscription();

        $subscription->when(request('id'), function ($q) {
            return $q->where('id', 'LIKE', '%' . request('id') . '%');
        });
                
        $per_page = config('constant.PER_PAGE_LIMIT');
        if( $request->has('per_page') && !empty($request->per_page)){
            if(is_numeric($request->per_page))
            {
                $per_page = $request->per_page;
            }
            if($request->per_page == -1 ){
                $per_page = $subscription->count();
            }
        }

        $subscription = $subscription->orderBy('id', 'asc')->paginate($per_page);

        $items = SubscriptionResource::collection($subscription);

        $response = [
            'pagination'    => json_pagination_response($items),
            'data'          => $items,
        ];
        
        return json_custom_response($response);
    }

    public function subscriptionSave(Request $request)
    {
        $data = $request->all();

        $user_id = auth()->id();
        $user = User::where('id', $user_id)->first();
        $package_data = Package::where('id',$data['package_id'])->first();
        
        $get_existing_plan = $this->get_user_active_subscription_plan($user_id);

        $active_plan_left_days = 0;

        $data['user_id'] = $user_id;
        $data['status'] = config('constant.SUBSCRIPTION_STATUS.PENDING');
        $data['subscription_start_date'] = date('Y-m-d H:i:s');
        $data['total_amount'] = $package_data->price;

        if($get_existing_plan)
        {
            $active_plan_left_days = $this->check_days_left_plan($get_existing_plan, $data);
            if($package_data->id != $get_existing_plan->package_id)
            {
                $get_existing_plan->update([
                    'status' => config('constant.SUBSCRIPTION_STATUS.INACTIVE')
                ]);
                $get_existing_plan->save();
            }
        }
        $data['subscription_end_date'] = $this->get_plan_expiration_date( $data['subscription_start_date'], $package_data->duration_unit, $active_plan_left_days, $package_data->duration );

        $data['package_data'] = $package_data ?? null;

        $subscription = Subscription::create($data);

        if( $subscription->payment_status == 'paid' ) {
            $subscription->status = config('constant.SUBSCRIPTION_STATUS.ACTIVE');
            $subscription->save();
            $user->update([ 'is_subscribe' => 1 ]);
        }

        $message = __('message.save_form', ['form' => __('message.subscription')]);
        
        return response()->json(['status' => true, 'message' => $message ]);
    }

    public function cancelSubscription(Request $request)
    {
        $user_id = auth()->id();
        $id = $request->id;
        $user_subscription = Subscription::where('id', $id )->where('user_id', $user_id)->first();
        $user = User::where('id', $user_id)->first();

        $message = __('message.not_found_entry',['name' => __('message.subscription')] );
        if($user_subscription)
        {
            $user_subscription->status = config('constant.SUBSCRIPTION_STATUS.INACTIVE');
            $user_subscription->save();
            $user_subscription->freezes()
                ->whereIn('status', [
                    SubscriptionFreeze::STATUS_SCHEDULED,
                    SubscriptionFreeze::STATUS_ACTIVE,
                ])
                ->update([
                    'status' => SubscriptionFreeze::STATUS_CANCELLED,
                    'processed_at' => Carbon::now(),
                ]);
            $user->is_subscribe = 0;
            $user->save();
            $message = __('message.subscription_cancelled');
        }
        return json_message_response($message);
    }

    public function freezeSubscription(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subscription_id' => 'required|integer|exists:subscriptions,id',
            'freeze_start_date' => 'required|date_format:Y-m-d H:i:s',
            'freeze_end_date' => 'required|date_format:Y-m-d H:i:s|after:freeze_start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = auth()->user();
        $subscription = Subscription::where('id', $request->subscription_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$subscription || !in_array($subscription->status, [config('constant.SUBSCRIPTION_STATUS.ACTIVE'), config('constant.SUBSCRIPTION_STATUS.PAUSED')])) {
            return response()->json([
                'status' => false,
                'message' => __('message.subscription_freeze_not_allowed'),
            ], 422);
        }

        $start = Carbon::parse($request->freeze_start_date);
        $end = Carbon::parse($request->freeze_end_date);

        if ($start->lt(Carbon::now()->startOfDay())) {
            return response()->json([
                'status' => false,
                'message' => __('message.subscription_freeze_invalid_dates'),
            ], 422);
        }

        $hasOverlap = $subscription->freezes()
            ->whereIn('status', [SubscriptionFreeze::STATUS_SCHEDULED, SubscriptionFreeze::STATUS_ACTIVE])
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('freeze_start_date', [$start, $end])
                    ->orWhereBetween('freeze_end_date', [$start, $end])
                    ->orWhere(function ($inner) use ($start, $end) {
                        $inner->where('freeze_start_date', '<', $start)
                            ->where('freeze_end_date', '>', $end);
                    });
            })
            ->exists();

        if ($hasOverlap) {
            return response()->json([
                'status' => false,
                'message' => __('message.subscription_freeze_conflict'),
            ], 422);
        }

        $status = $start->lessThanOrEqualTo(Carbon::now())
            ? SubscriptionFreeze::STATUS_ACTIVE
            : SubscriptionFreeze::STATUS_SCHEDULED;

        $freeze = $subscription->freezes()->create([
            'user_id' => $user->id,
            'freeze_start_date' => $start,
            'freeze_end_date' => $end,
            'status' => $status,
        ]);

        if ($status === SubscriptionFreeze::STATUS_ACTIVE) {
            $subscription->status = config('constant.SUBSCRIPTION_STATUS.PAUSED');
            $subscription->save();

            $user->is_subscribe = 0;
            $user->save();
        }

        $messageKey = $status === SubscriptionFreeze::STATUS_ACTIVE
            ? 'message.subscription_freeze_active'
            : 'message.subscription_freeze_scheduled';

        $response = [
            'status' => true,
            'message' => __($messageKey, [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ]),
            'freeze' => new SubscriptionFreezeResource($freeze),
            'subscription_detail' => $this->subscriptionPlanDetail($user->id),
        ];

        return response()->json($response);
    }
}
