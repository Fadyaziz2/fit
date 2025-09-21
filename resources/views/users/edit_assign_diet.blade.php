@php
    $dislikedIngredientIds = collect($dislikedIngredientIds ?? [])
        ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
        ->map(fn ($id) => (int) $id)
        ->unique()
        ->values()
        ->all();
@endphp
<!-- Modal -->
{{ html()->form('POST', route('update.assigndiet.meals'))->attribute('data-toggle', 'validator')->open() }}
    {{ html()->hidden('user_id', $assignment->user_id) }}
    {{ html()->hidden('diet_id', $assignment->diet_id) }}
    <div class="row g-3">
        <div class="col-12">
            <div class="alert alert-info" role="alert">
                {{ __('message.custom_diet_meal_description') }}
            </div>
        </div>
        @php
            $hasMeals = $maxMeals > 0 && count($plan) > 0;
        @endphp
        <div class="col-12">
            @if($hasMeals)
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th class="text-nowrap">{{ __('message.day') }}</th>
                                @for($mealIndex = 0; $mealIndex < $maxMeals; $mealIndex++)
                                    <th>{{ __('message.meal_time_number', ['number' => $mealIndex + 1]) }}</th>
                                @endfor
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($plan as $dayIndex => $dayMeals)
                                <tr>
                                    <th class="text-nowrap">{{ __('message.day') }} {{ $dayIndex + 1 }}</th>
                                    @for($mealIndex = 0; $mealIndex < $maxMeals; $mealIndex++)
                                        @php
                                            $defaultIngredientIds = $dayMeals[$mealIndex] ?? [];
                                            if (!is_array($defaultIngredientIds)) {
                                                $defaultIngredientIds = is_numeric($defaultIngredientIds) ? [(int) $defaultIngredientIds] : [];
                                            }

                                            $customDayMeals = $customPlan[$dayIndex] ?? [];
                                            $hasCustomOverride = is_array($customDayMeals) && array_key_exists($mealIndex, $customDayMeals);
                                            $customIngredientIds = $hasCustomOverride ? $customDayMeals[$mealIndex] : [];

                                            if (!is_array($customIngredientIds)) {
                                                $customIngredientIds = is_numeric($customIngredientIds) ? [(int) $customIngredientIds] : [];
                                            }

                                            $selectedIngredientIds = $hasCustomOverride ? $customIngredientIds : $defaultIngredientIds;

                                            $selectedIngredientIds = collect($selectedIngredientIds)
                                                ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
                                                ->map(fn ($id) => (int) $id)
                                                ->unique()
                                                ->values()
                                                ->all();

                                            $customIngredientIds = collect($customIngredientIds)
                                                ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
                                                ->map(fn ($id) => (int) $id)
                                                ->unique()
                                                ->values()
                                                ->all();

                                            $defaultIngredientDetails = collect($defaultIngredientIds)
                                                ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
                                                ->map(function ($id) use ($ingredientsMap, $dislikedIngredientIds) {
                                                    $ingredient = $ingredientsMap->get($id);

                                                    if (!$ingredient) {
                                                        return null;
                                                    }

                                                    $ingredientId = (int) $id;

                                                    return [
                                                        'id' => $ingredientId,
                                                        'title' => $ingredient->title,
                                                        'disliked' => in_array($ingredientId, $dislikedIngredientIds, true),
                                                    ];
                                                })
                                                ->filter()
                                                ->values();
                                        @endphp
                                        <td>
                                            <div class="d-flex flex-column gap-2">
                                                <div>
                                                    <small class="text-muted d-block">{{ __('message.default_meal_label') }}</small>
                                                    @if($defaultIngredientDetails->isNotEmpty())
                                                        <div class="d-flex flex-wrap gap-1">
                                                            @foreach($defaultIngredientDetails as $detail)
                                                                <span class="fw-semibold {{ $detail['disliked'] ? 'text-danger' : '' }}">
                                                                    {{ $detail['title'] }}
                                                                </span>@if(!$loop->last)<span class="text-muted">, </span>@endif
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <span class="fw-semibold">{{ __('message.no_ingredients_selected') }}</span>
                                                    @endif
                                                    @if($hasCustomOverride)
                                                        <span class="badge bg-primary ms-1">{{ __('message.custom_meal_plan_badge') }}</span>
                                                    @endif
                                                </div>
                                                <select name="plan[{{ $dayIndex }}][{{ $mealIndex }}][]" class="form-select" multiple>
                                                    @foreach($ingredients as $ingredient)
                                                        @php
                                                            $isDisliked = in_array($ingredient->id, $dislikedIngredientIds, true);
                                                            $optionStyle = $isDisliked ? 'color: var(--bs-danger, #dc3545); font-weight: 600;' : null;
                                                        @endphp
                                                        <option value="{{ $ingredient->id }}"
                                                            @class(['text-danger fw-semibold' => $isDisliked])
                                                            {{ $isDisliked ? 'data-disliked="1"' : '' }}
                                                            {{ $optionStyle ? 'style="' . $optionStyle . '"' : '' }}
                                                            {{ in_array($ingredient->id, $selectedIngredientIds, true) ? 'selected' : '' }}>
                                                            {{ $ingredient->title }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <small class="text-muted">{{ __('message.multi_select_helper') }}</small>
                                            </div>
                                        </td>
                                    @endfor
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-warning mb-0" role="alert">
                    {{ __('message.no_meal_plan_available') }}
                </div>
            @endif
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-md btn-secondary" data-bs-dismiss="modal">{{ __('message.close') }}</button>
        <button type="submit" class="btn btn-md btn-primary" data-form="ajax">{{ __('message.save') }}</button>
    </div>
    @if(isset($id))
        {{ html()->closeModelForm() }}
    @else
        {{ html()->form()->close() }}
    @endif
