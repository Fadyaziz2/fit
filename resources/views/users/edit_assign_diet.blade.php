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
                                            $defaultIngredientId = $dayMeals[$mealIndex] ?? null;
                                            $defaultIngredient = $ingredientsMap->get($defaultIngredientId);
                                            $customIngredientId = data_get($customPlan, $dayIndex . '.' . $mealIndex);
                                            $selectedIngredientId = $customIngredientId ?? $defaultIngredientId;
                                        @endphp
                                        <td>
                                            <div class="d-flex flex-column gap-2">
                                                <div>
                                                    <small class="text-muted d-block">{{ __('message.default_meal_label') }}</small>
                                                    <span class="fw-semibold">{{ $defaultIngredient?->title ?? __('message.no_meal_selected') }}</span>
                                                    @if($customIngredientId && $selectedIngredientId !== $defaultIngredientId)
                                                        <span class="badge bg-primary ms-1">{{ __('message.custom_meal_plan_badge') }}</span>
                                                    @endif
                                                </div>
                                                <select name="plan[{{ $dayIndex }}][{{ $mealIndex }}]" class="form-select">
                                                    <option value="">{{ __('message.keep_default_meal_option') }}</option>
                                                    @foreach($ingredients as $ingredient)
                                                        <option value="{{ $ingredient->id }}" {{ $selectedIngredientId === $ingredient->id ? 'selected' : '' }}>
                                                            {{ $ingredient->title }}
                                                        </option>
                                                    @endforeach
                                                </select>
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
