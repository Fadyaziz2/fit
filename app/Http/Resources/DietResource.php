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
        $plan = $this->normalizePlan($this->ingredients ?? []);
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
                $customPlan = $this->normalizePlan($assignment->custom_plan, true);
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

    protected function mergeCustomPlan(array $plan, array $customPlan): array
    {
        foreach ($customPlan as $dayIndex => $dayMeals) {
            if (!isset($plan[$dayIndex])) {
                $plan[$dayIndex] = [];
            }

            foreach ($dayMeals as $mealIndex => $ingredients) {
                $plan[$dayIndex][$mealIndex] = $ingredients;
            }
        }

        return array_values(array_map(function ($dayMeals) {
            return array_values(is_array($dayMeals) ? $dayMeals : []);
        }, $plan));
    }

    protected function normalizePlan($plan, bool $preserveKeys = false): array
    {
        if (!is_array($plan)) {
            return [];
        }

        $normalized = [];

        foreach ($plan as $dayIndex => $dayMeals) {
            if (!is_array($dayMeals)) {
                $normalized[(int) $dayIndex] = [];
                continue;
            }

            $normalized[(int) $dayIndex] = array_values(array_map(function ($meal) {
                return $this->normalizeMealSelection($meal);
            }, $dayMeals));
        }

        ksort($normalized);

        if ($preserveKeys) {
            foreach ($normalized as $dayIndex => $dayMeals) {
                $normalized[$dayIndex] = array_values($dayMeals);
            }

            return $normalized;
        }

        return array_values($normalized);
    }

    protected function normalizeMealSelection($value): array
    {
        if (is_array($value)) {
            $normalized = [];

            foreach ($value as $item) {
                if (!is_numeric($item)) {
                    continue;
                }

                $id = (int) $item;

                if ($id <= 0 || in_array($id, $normalized, true)) {
                    continue;
                }

                $normalized[] = $id;
            }

            return $normalized;
        }

        if (is_numeric($value)) {
            $id = (int) $value;

            return $id > 0 ? [$id] : [];
        }

        return [];
    }
}
