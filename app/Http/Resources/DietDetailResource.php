<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DietDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id'                  => $this->id,
            'title'               => $this->title,
            'calories'            => $this->calories,
            'carbs'               => $this->carbs,
            'protein'             => $this->protein,
            'fat'                 => $this->fat,
            'servings'            => $this->servings,
            'days'                => $this->days,
            'total_time'          => $this->total_time,
            'is_featured'         => $this->is_featured,
            'status'              => $this->status,
            'ingredients'         => $this->normalizePlan($this->ingredients),
            'description'         => $this->description,
            'diet_image'          => getSingleMedia($this, 'diet_image', null),
            'is_premium'          => $this->is_premium,
            'categorydiet_id'     => $this->categorydiet_id,
            'categorydiet_title'  => optional($this->categorydiet)->title,
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }

    protected function normalizePlan($plan): array
    {
        if (!is_array($plan)) {
            return [];
        }

        $normalized = [];

        foreach ($plan as $dayIndex => $dayMeals) {
            if (!is_array($dayMeals)) {
                $normalized[] = [];
                continue;
            }

            $normalized[] = array_values(array_map(function ($meal) {
                return $this->normalizeMealSelection($meal);
            }, $dayMeals));
        }

        return $normalized;
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
