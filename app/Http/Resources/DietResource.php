<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DietResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $user_id = auth()->id() ?? null;
        $plan = $this->ingredients ?? [];
        $customPlan = [];

        if ($user_id) {
            $assignment = $this->whenLoaded('userAssignDiet')
                ? $this->userAssignDiet->firstWhere('user_id', $user_id)
                : null;

            if (!$assignment) {
                $assignment = $this->userAssignDiet()
                    ->where('user_id', $user_id)
                    ->first();
            }

            if ($assignment && is_array($assignment->custom_plan)) {
                $customPlan = $assignment->custom_plan;
                $plan = $this->mergeCustomPlan($plan, $customPlan);
            }
        }

        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'calories'         => $this->calories,
            'carbs'            => $this->carbs,
            'protein'          => $this->protein,
            'fat'              => $this->fat,
            'servings'         => $this->servings,
            'days'             => $this->days,
            'total_time'       => $this->total_time,
            'is_featured'      => $this->is_featured,
            'status'           => $this->status,
            'ingredients'      => $plan,
            'custom_plan'      => $customPlan,
            'description'      => $this->description,
            'diet_image'       => getSingleMedia($this, 'diet_image',null),
            'is_premium'       => $this->is_premium,
            'categorydiet_id'  => $this->categorydiet_id,
            'categorydiet_title'  => optional($this->categorydiet)->title,
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,
            'is_favourite'     => $this->userFavouriteDiet->where('user_id',$user_id)->first() ? 1 : 0,
        ];
    }

    protected function mergeCustomPlan($plan, array $customPlan): array
    {
        if (!is_array($plan)) {
            $plan = [];
        }

        foreach ($customPlan as $dayIndex => $dayMeals) {
            if (!is_array($dayMeals)) {
                continue;
            }

            if (!isset($plan[$dayIndex]) || !is_array($plan[$dayIndex])) {
                $plan[$dayIndex] = [];
            }

            foreach ($dayMeals as $mealIndex => $ingredientId) {
                if ($ingredientId === null) {
                    continue;
                }

                $plan[$dayIndex][$mealIndex] = $ingredientId;
            }
        }

        return array_values(array_map(function ($dayMeals) {
            return array_values(is_array($dayMeals) ? $dayMeals : []);
        }, $plan));
    }
}
