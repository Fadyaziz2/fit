<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Support\MealPlan;
use App\Traits\BuildsMealPlanDetails;

class DietResource extends JsonResource
{
    use BuildsMealPlanDetails;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $user_id = auth()->id() ?? null;
        $basePlan = MealPlan::normalizePlan($this->ingredients ?? [], false, true);
        $mergedPlan = MealPlan::reindexPlan($basePlan);
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
                $normalizedCustomPlan = MealPlan::normalizePlan($assignment->custom_plan, true, true);
                $customPlan = MealPlan::reindexPlan($normalizedCustomPlan);
                $mergedPlan = MealPlan::mergeNormalizedPlans($basePlan, $normalizedCustomPlan);
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
            'ingredients'      => $mergedPlan,
            'custom_plan'      => $customPlan,
            'has_custom_plan'  => !empty($customPlan),
            'meal_plan'        => $this->buildMealPlanDetails($mergedPlan),
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
}
