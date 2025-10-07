@php
    $dislikedIngredientIds = collect($dislikedIngredientIds ?? [])
        ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
        ->map(fn ($id) => (int) $id)
        ->unique()
        ->values()
        ->all();

    $normalizedCustomPlan = \App\Support\MealPlan::normalizePlan($customPlan ?? [], false, true);
    $normalizedCustomPlan = \App\Support\MealPlan::reindexPlan($normalizedCustomPlan);

    $formatQuantity = static function ($value): string {
        if ($value === null || $value === '') {
            return '';
        }

        if (! is_numeric($value)) {
            return '';
        }

        $number = (float) $value;

        if (floor($number) === $number) {
            return (string) (int) $number;
        }

        return rtrim(rtrim(number_format($number, 4, '.', ''), '0'), '.');
    };
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
                                            $defaultEntries = collect($dayMeals[$mealIndex] ?? [])
                                                ->map(function ($entry) {
                                                    if (is_array($entry)) {
                                                        $id = $entry['id'] ?? $entry['ingredient_id'] ?? $entry['ingredient'] ?? null;
                                                        $quantity = $entry['quantity'] ?? $entry['qty'] ?? $entry['amount'] ?? null;
                                                    } elseif (is_numeric($entry)) {
                                                        $id = (int) $entry;
                                                        $quantity = 1;
                                                    } else {
                                                        $id = null;
                                                        $quantity = null;
                                                    }

                                                    if (! is_numeric($id) || (int) $id <= 0) {
                                                        return null;
                                                    }

                                                    $id = (int) $id;
                                                    $quantity = is_numeric($quantity) ? (float) $quantity : null;

                                                    if ($quantity === null) {
                                                        $quantity = 1.0;
                                                    }

                                                    return [
                                                        'id' => $id,
                                                        'quantity' => $quantity,
                                                    ];
                                                })
                                                ->filter()
                                                ->values();

                                            $customEntries = collect($normalizedCustomPlan[$dayIndex][$mealIndex] ?? [])
                                                ->map(function ($entry) {
                                                    if (! is_array($entry)) {
                                                        return null;
                                                    }

                                                    $id = $entry['id'] ?? $entry['ingredient_id'] ?? $entry['ingredient'] ?? null;
                                                    $quantity = $entry['quantity'] ?? $entry['qty'] ?? $entry['amount'] ?? null;

                                                    if (! is_numeric($id) || (int) $id <= 0) {
                                                        return null;
                                                    }

                                                    $id = (int) $id;
                                                    $quantity = is_numeric($quantity) ? (float) $quantity : null;

                                                    if ($quantity === null) {
                                                        $quantity = 1.0;
                                                    }

                                                    return [
                                                        'id' => $id,
                                                        'quantity' => $quantity,
                                                    ];
                                                })
                                                ->filter()
                                                ->values();

                                            $hasCustomOverride = $customEntries->isNotEmpty();

                                            $selectedEntries = $hasCustomOverride ? $customEntries : $defaultEntries;

                                            $defaultIngredientDetails = $defaultEntries
                                                ->map(function ($entry) use ($ingredientsMap, $dislikedIngredientIds, $formatQuantity) {
                                                    $ingredient = $ingredientsMap->get($entry['id']);

                                                    if (! $ingredient) {
                                                        return null;
                                                    }

                                                    $formattedQuantity = $formatQuantity($entry['quantity'] ?? null);

                                                    return [
                                                        'id' => $entry['id'],
                                                        'title' => $ingredient->title,
                                                        'disliked' => in_array($entry['id'], $dislikedIngredientIds, true),
                                                        'quantity' => $formattedQuantity,
                                                    ];
                                                })
                                                ->filter()
                                                ->values();

                                            $editorEntries = $selectedEntries->map(function ($entry) use ($formatQuantity) {
                                                return [
                                                    'id' => $entry['id'],
                                                    'quantity' => $formatQuantity($entry['quantity'] ?? null),
                                                ];
                                            });
                                        @endphp
                                        <td>
                                            <div class="d-flex flex-column gap-3">
                                                <div>
                                                    <small class="text-muted d-block">{{ __('message.default_meal_label') }}</small>
                                                    @if($defaultIngredientDetails->isNotEmpty())
                                                        <div class="d-flex flex-column gap-1">
                                                            @foreach($defaultIngredientDetails as $detail)
                                                                <span class="fw-semibold {{ $detail['disliked'] ? 'text-danger' : '' }}">
                                                                    {{ $detail['title'] }}
                                                                    @if($detail['quantity'] !== '')
                                                                        <span class="text-muted">({{ __('message.quantity') }}: {{ $detail['quantity'] }})</span>
                                                                    @endif
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <span class="fw-semibold">{{ __('message.no_ingredients_selected') }}</span>
                                                    @endif
                                                    @if($hasCustomOverride)
                                                        <span class="badge bg-primary ms-1">{{ __('message.custom_meal_plan_badge') }}</span>
                                                    @endif
                                                </div>
                                                <div class="meal-ingredient-editor"
                                                    data-day-index="{{ $dayIndex }}"
                                                    data-meal-index="{{ $mealIndex }}"
                                                    data-next-index="{{ $editorEntries->count() }}">
                                                    <div class="meal-ingredient-entries">
                                                        @foreach($editorEntries as $entryIndex => $entry)
                                                            <div class="meal-ingredient-entry input-group input-group-sm mb-2" data-entry-index="{{ $entryIndex }}">
                                                                <select name="plan[{{ $dayIndex }}][{{ $mealIndex }}][{{ $entryIndex }}][id]" class="form-select form-select-sm">
                                                                    <option value="">{{ __('message.select_name', ['select' => __('message.ingredient')]) }}</option>
                                                                    @foreach($ingredients as $ingredient)
                                                                        @php
                                                                            $isDisliked = in_array($ingredient->id, $dislikedIngredientIds, true);
                                                                        @endphp
                                                                        <option value="{{ $ingredient->id }}"
                                                                            @class(['text-danger fw-semibold' => $isDisliked])
                                                                            {{ $isDisliked ? 'data-disliked="1"' : '' }}
                                                                            {{ (int) $ingredient->id === (int) $entry['id'] ? 'selected' : '' }}>
                                                                            {{ $ingredient->title }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                                <input type="number"
                                                                    class="form-control form-control-sm"
                                                                    name="plan[{{ $dayIndex }}][{{ $mealIndex }}][{{ $entryIndex }}][quantity]"
                                                                    value="{{ $entry['quantity'] }}"
                                                                    min="0"
                                                                    step="0.01"
                                                                    placeholder="{{ __('message.quantity') }}">
                                                                <button type="button" class="btn btn-outline-danger btn-sm" data-action="remove-meal-ingredient" aria-label="{{ __('message.remove') }}">
                                                                    &times;
                                                                </button>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    <p class="text-muted small mb-2 meal-ingredient-empty {{ $editorEntries->isEmpty() ? '' : 'd-none' }}">{{ __('message.no_ingredients_selected') }}</p>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" data-action="add-meal-ingredient">
                                                        {{ __('message.add_meal_ingredient') }}
                                                    </button>
                                                </div>
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
<script>
    window.dietMealIngredientOptions = @json($ingredients->map(function ($ingredient) use ($dislikedIngredientIds) {
        return [
            'id' => (int) $ingredient->id,
            'title' => $ingredient->title,
            'disliked' => in_array($ingredient->id, $dislikedIngredientIds, true),
        ];
    })->values());
    window.dietMealIngredientPlaceholder = @json(__('message.select_name', ['select' => __('message.ingredient')]));
    window.dietMealIngredientQuantityLabel = @json(__('message.quantity'));
    window.dietMealIngredientRemoveLabel = @json(__('message.remove'));

    (function ($) {
        if (window.assignDietMealEditorInitialized) {
            return;
        }

        window.assignDietMealEditorInitialized = true;

        const createIngredientRow = (day, meal, entryIndex, selectedId = null, quantity = '') => {
            const options = Array.isArray(window.dietMealIngredientOptions) ? window.dietMealIngredientOptions : [];
            const placeholder = window.dietMealIngredientPlaceholder || '';
            const quantityLabel = window.dietMealIngredientQuantityLabel || '';

            const wrapper = $('<div>', {
                class: 'meal-ingredient-entry input-group input-group-sm mb-2',
                'data-entry-index': entryIndex,
            });

            const select = $('<select>', {
                class: 'form-select form-select-sm',
                name: `plan[${day}][${meal}][${entryIndex}][id]`,
            });

            select.append($('<option>', { value: '', text: placeholder }));

            options.forEach((option) => {
                const optionElement = $('<option>', {
                    value: option.id,
                    text: option.title,
                });

                if (option.disliked) {
                    optionElement.addClass('text-danger fw-semibold');
                    optionElement.attr('data-disliked', '1');
                }

                if (Number(option.id) === Number(selectedId)) {
                    optionElement.prop('selected', true);
                }

                select.append(optionElement);
            });

            const quantityInput = $('<input>', {
                type: 'number',
                class: 'form-control form-control-sm',
                name: `plan[${day}][${meal}][${entryIndex}][quantity]`,
                step: '0.01',
                min: '0',
                placeholder: quantityLabel,
            }).val(quantity || '');

            const removeButton = $('<button>', {
                type: 'button',
                class: 'btn btn-outline-danger btn-sm',
                'data-action': 'remove-meal-ingredient',
                'aria-label': window.dietMealIngredientRemoveLabel || '×',
            }).html('&times;');

            wrapper.append(select, quantityInput, removeButton);

            return wrapper;
        };

        const ensurePlaceholderVisibility = (editor) => {
            const entriesContainer = editor.find('.meal-ingredient-entries');
            const placeholder = editor.find('.meal-ingredient-empty');
            if (! placeholder.length) {
                return;
            }

            if (entriesContainer.children('.meal-ingredient-entry').length === 0) {
                placeholder.removeClass('d-none');
            } else {
                placeholder.addClass('d-none');
            }
        };

        $(document).on('click', '[data-action="add-meal-ingredient"]', function (event) {
            event.preventDefault();

            const button = $(this);
            const editor = button.closest('.meal-ingredient-editor');
            const entriesContainer = editor.find('.meal-ingredient-entries');
            const day = editor.data('dayIndex');
            const meal = editor.data('mealIndex');
            const nextIndex = Number(editor.data('nextIndex')) || 0;

            const newRow = createIngredientRow(day, meal, nextIndex);
            entriesContainer.append(newRow);

            editor.data('nextIndex', nextIndex + 1);

            ensurePlaceholderVisibility(editor);
        });

        $(document).on('click', '[data-action="remove-meal-ingredient"]', function (event) {
            event.preventDefault();

            const button = $(this);
            const editor = button.closest('.meal-ingredient-editor');
            const entry = button.closest('.meal-ingredient-entry');

            entry.remove();

            ensurePlaceholderVisibility(editor);
        });

        $('.meal-ingredient-editor').each(function () {
            const editor = $(this);
            const entries = editor.find('.meal-ingredient-entry');

            if (!entries.length) {
                editor.data('nextIndex', 0);
            }

            ensurePlaceholderVisibility(editor);
        });
    })(jQuery);
</script>
