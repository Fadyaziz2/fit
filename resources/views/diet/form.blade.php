@push('styles')
    <style>
        .meal-plan-table thead th,
        .meal-plan-table tbody td {
            vertical-align: middle;
        }

        .meal-plan-table tbody th {
            min-width: 110px;
        }

        .meal-cell {
            min-width: 200px;
        }

        .meal-cell .meal-cell-placeholder {
            border-style: dashed;
            font-weight: 600;
        }

        .meal-cell .meal-cell-placeholder:hover,
        .meal-cell .meal-cell-placeholder:focus {
            opacity: 0.9;
        }

        .meal-cell-content {
            border: 1px solid var(--bs-border-color, #dee2e6);
            border-radius: 0.75rem;
            padding: 0.75rem;
            background-color: var(--bs-body-bg, #fff);
        }

        .meal-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .meal-info img {
            width: 56px;
            height: 56px;
            object-fit: cover;
            border-radius: 0.75rem;
        }

        .meal-macros {
            font-size: 0.75rem;
            color: var(--bs-secondary-color, #6c757d);
        }

        #meal-plan-empty {
            color: var(--bs-secondary-color, #6c757d);
        }

        #ingredient-modal-list .list-group-item img {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 0.75rem;
        }

        #ingredient-modal-list .list-group-item {
            cursor: pointer;
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function ($) {
            'use strict';

            $(document).ready(function () {
                tinymceEditor('.tinymce-description', ' ', function () {
                }, 450);

                const ingredientsData = @json($ingredients ?? []);
                const ingredientMap = {};
                ingredientsData.forEach(function (ingredient) {
                    ingredientMap[ingredient.id] = ingredient;
                });

                const translations = {
                    day: "{{ __('message.day') }}",
                    servings: "{{ __('message.servings') }}",
                    selectMeal: "{{ __('message.select_meal') }}",
                    changeMeal: "{{ __('message.change_meal') }}",
                    removeMeal: "{{ __('message.remove_meal') }}",
                    noMealSelected: "{{ __('message.no_meal_selected') }}",
                    protein: "{{ __('message.protein') }}",
                    carbs: "{{ __('message.carbs') }}",
                    fat: "{{ __('message.fat') }}",
                    calories: "{{ __('message.calories') }}",
                    grams: "{{ __('message.grams') }}",
                    mealPlanHelper: "{{ __('message.meal_plan_helper') }}",
                    noResults: "{{ __('message.no_results_found') }}"
                };

                const planInput = $('#meal-plan-input');
                const daysField = $('#days');
                const servingsField = $('#servings');
                const macroFields = {
                    protein: $('#protein'),
                    carbs: $('#carbs'),
                    fat: $('#fat'),
                    calories: $('#calories'),
                };
                const tableHead = $('#meal-plan-table thead');
                const tableBody = $('#meal-plan-table tbody');
                const placeholder = $('#meal-plan-empty');
                const ingredientModalElement = document.getElementById('ingredient-modal');
                const ingredientModal = ingredientModalElement && typeof bootstrap !== 'undefined' && bootstrap.Modal
                    ? new bootstrap.Modal(ingredientModalElement)
                    : null;
                const ingredientModalEl = $('#ingredient-modal');
                const ingredientSearch = $('#ingredient-search');
                const ingredientList = $('#ingredient-modal-list');
                const removeSelectionButton = $('#clear-meal-selection');

                let mealPlan = [];
                let currentSelection = { day: null, meal: null };

                function sanitizeCount(value) {
                    const number = parseInt(value, 10);
                    return Number.isFinite(number) && number > 0 ? number : 0;
                }

                function parseInitialPlan() {
                    const raw = planInput.val();
                    if (!raw) {
                        mealPlan = [];
                        return;
                    }

                    try {
                        const parsed = JSON.parse(raw);

                        if (Array.isArray(parsed)) {
                            mealPlan = parsed.map(function (day) {
                                return Array.isArray(day) ? day : [];
                            });
                            return;
                        }

                        if (parsed && Array.isArray(parsed.plan)) {
                            mealPlan = parsed.plan.map(function (day) {
                                return Array.isArray(day) ? day : [];
                            });
                            return;
                        }

                        mealPlan = [];
                    } catch (error) {
                        mealPlan = [];
                    }
                }

                function ensurePlanSize(days, meals) {
                    if (mealPlan.length > days) {
                        mealPlan.length = days;
                    }

                    for (let day = 0; day < days; day++) {
                        if (!Array.isArray(mealPlan[day])) {
                            mealPlan[day] = [];
                        }

                        if (mealPlan[day].length > meals) {
                            mealPlan[day].length = meals;
                        }

                        for (let meal = 0; meal < meals; meal++) {
                            if (typeof mealPlan[day][meal] === 'undefined') {
                                mealPlan[day][meal] = null;
                            }
                        }
                    }
                }

                function updatePlanInput() {
                    planInput.val(JSON.stringify(mealPlan));
                }

                function formatNumber(value) {
                    const number = Number(value || 0);
                    if (!Number.isFinite(number) || number === 0) {
                        return '0';
                    }

                    return Number.isInteger(number) ? number.toString() : number.toFixed(2);
                }

                function updateMacroFields() {
                    const totals = { protein: 0, carbs: 0, fat: 0, calories: 0 };
                    const days = sanitizeCount(daysField.val());
                    const dayLimit = days > 0 ? days : mealPlan.length;
                    let countedDays = 0;

                    for (let dayIndex = 0; dayIndex < dayLimit; dayIndex++) {
                        const dayTotals = computeDayTotals(mealPlan[dayIndex]);
                        totals.protein += dayTotals.protein;
                        totals.carbs += dayTotals.carbs;
                        totals.fat += dayTotals.fat;
                        totals.calories += dayTotals.calories;
                        countedDays++;
                    }

                    if (countedDays === 0 && mealPlan.length > 0) {
                        mealPlan.forEach(function (dayMeals) {
                            const dayTotals = computeDayTotals(dayMeals);
                            totals.protein += dayTotals.protein;
                            totals.carbs += dayTotals.carbs;
                            totals.fat += dayTotals.fat;
                            totals.calories += dayTotals.calories;
                            countedDays++;
                        });
                    }

                    const averages = {
                        protein: 0,
                        carbs: 0,
                        fat: 0,
                        calories: 0,
                    };

                    if (countedDays > 0) {
                        averages.protein = totals.protein / countedDays;
                        averages.carbs = totals.carbs / countedDays;
                        averages.fat = totals.fat / countedDays;
                        averages.calories = totals.calories / countedDays;
                    }

                    Object.keys(macroFields).forEach(function (key) {
                        const field = macroFields[key];
                        if (!field || !field.length) {
                            return;
                        }

                        field.val(formatNumber(averages[key]));
                    });
                }

                function computeDayTotals(dayMeals) {
                    const totals = { protein: 0, carbs: 0, fat: 0, calories: 0 };

                    if (!Array.isArray(dayMeals)) {
                        return totals;
                    }

                    dayMeals.forEach(function (mealId) {
                        if (!mealId) {
                            return;
                        }

                        const ingredient = ingredientMap[mealId];
                        if (!ingredient) {
                            return;
                        }

                        totals.protein += Number(ingredient.protein) || 0;
                        totals.carbs += Number(ingredient.carbs) || 0;
                        totals.fat += Number(ingredient.fat) || 0;
                        totals.calories += Number(ingredient.calories) || 0;
                    });

                    return totals;
                }

                function clearMeal(dayIndex, mealIndex) {
                    if (!Array.isArray(mealPlan[dayIndex])) {
                        return;
                    }

                    mealPlan[dayIndex][mealIndex] = null;
                    updatePlanInput();
                    renderTable();
                }

                function closeModal() {
                    if (ingredientModal) {
                        ingredientModal.hide();
                    } else {
                        ingredientModalEl.modal('hide');
                    }
                }

                function openIngredientModal(dayIndex, mealIndex) {
                    currentSelection = { day: dayIndex, meal: mealIndex };
                    ingredientSearch.val('');
                    renderIngredientList('');
                    updateRemoveButtonState();

                    if (ingredientModal) {
                        ingredientModal.show();
                    } else {
                        ingredientModalEl.modal('show');
                    }
                }

                function updateRemoveButtonState() {
                    const hasValue = currentSelection.day !== null && currentSelection.meal !== null &&
                        Array.isArray(mealPlan[currentSelection.day]) &&
                        mealPlan[currentSelection.day][currentSelection.meal];

                    removeSelectionButton.prop('disabled', !hasValue);
                }

                function selectIngredient(ingredientId) {
                    if (currentSelection.day === null || currentSelection.meal === null) {
                        return;
                    }

                    const days = sanitizeCount(daysField.val());
                    const meals = sanitizeCount(servingsField.val());
                    ensurePlanSize(days, meals);

                    mealPlan[currentSelection.day][currentSelection.meal] = ingredientId;
                    updatePlanInput();
                    renderTable();
                    closeModal();
                }

                function renderIngredientList(filterValue) {
                    const filter = (filterValue || '').toString().toLowerCase();
                    ingredientList.empty();

                    const filtered = ingredientsData.filter(function (ingredient) {
                        return ingredient.title && ingredient.title.toLowerCase().includes(filter);
                    });

                    if (!filtered.length) {
                        ingredientList.append(
                            $('<div />')
                                .addClass('text-center text-muted py-3')
                                .text(translations.noResults)
                        );
                        return;
                    }

                    filtered.forEach(function (ingredient) {
                        const item = $('<button />')
                            .attr('type', 'button')
                            .addClass('list-group-item list-group-item-action d-flex align-items-center gap-3');

                        if (ingredient.image) {
                            item.append(
                                $('<img />')
                                    .attr('src', ingredient.image)
                                    .attr('alt', ingredient.title)
                            );
                        }

                        const info = $('<div />').addClass('flex-grow-1 text-start');
                        info.append($('<div />').addClass('fw-semibold').text(ingredient.title));

                        const macrosText = [
                            translations.protein + ': ' + formatNumber(ingredient.protein) + ' ' + translations.grams,
                            translations.carbs + ': ' + formatNumber(ingredient.carbs) + ' ' + translations.grams,
                            translations.fat + ': ' + formatNumber(ingredient.fat) + ' ' + translations.grams,
                            translations.calories + ': ' + formatNumber(ingredient.calories)
                        ].join(' | ');

                        info.append($('<div />').addClass('meal-macros').text(macrosText));
                        item.append(info);

                        item.on('click', function () {
                            selectIngredient(ingredient.id);
                        });

                        ingredientList.append(item);
                    });
                }

                function renderMealCell(dayIndex, mealIndex) {
                    const container = $('<div />').addClass('meal-cell');
                    const selectButton = $('<button />')
                        .attr('type', 'button')
                        .addClass('btn btn-outline-primary meal-cell-placeholder w-100')
                        .text(translations.selectMeal)
                        .on('click', function () {
                            openIngredientModal(dayIndex, mealIndex);
                        });

                    const content = $('<div />').addClass('meal-cell-content d-none');

                    container.append(selectButton, content);

                    const mealId = Array.isArray(mealPlan[dayIndex]) ? mealPlan[dayIndex][mealIndex] : null;
                    const ingredient = mealId ? ingredientMap[mealId] : null;

                    if (ingredient) {
                        selectButton.addClass('d-none');
                        content.removeClass('d-none');

                        const info = $('<div />').addClass('meal-info');

                        if (ingredient.image) {
                            info.append(
                                $('<img />')
                                    .attr('src', ingredient.image)
                                    .attr('alt', ingredient.title)
                            );
                        }

                        const textWrapper = $('<div />').addClass('flex-grow-1');
                        textWrapper.append($('<div />').addClass('fw-semibold').text(ingredient.title));

                        const macros = [
                            translations.protein + ': ' + formatNumber(ingredient.protein) + ' ' + translations.grams,
                            translations.carbs + ': ' + formatNumber(ingredient.carbs) + ' ' + translations.grams,
                            translations.fat + ': ' + formatNumber(ingredient.fat) + ' ' + translations.grams,
                            translations.calories + ': ' + formatNumber(ingredient.calories)
                        ].join(' | ');

                        textWrapper.append($('<div />').addClass('meal-macros').text(macros));
                        info.append(textWrapper);
                        content.append(info);

                        const actions = $('<div />').addClass('d-flex flex-wrap gap-2 mt-2');

                        actions.append(
                            $('<button />')
                                .attr('type', 'button')
                                .addClass('btn btn-sm btn-outline-primary')
                                .text(translations.changeMeal)
                                .on('click', function () {
                                    openIngredientModal(dayIndex, mealIndex);
                                })
                        );

                        actions.append(
                            $('<button />')
                                .attr('type', 'button')
                                .addClass('btn btn-sm btn-outline-danger')
                                .text(translations.removeMeal)
                                .on('click', function () {
                                    clearMeal(dayIndex, mealIndex);
                                })
                        );

                        content.append(actions);
                    }

                    return container;
                }

                function renderTable() {
                    const days = sanitizeCount(daysField.val());
                    const meals = sanitizeCount(servingsField.val());

                    tableHead.empty();
                    tableBody.empty();

                    if (!days || !meals) {
                        placeholder.removeClass('d-none').text(translations.mealPlanHelper);
                        mealPlan = [];
                        updatePlanInput();
                        updateMacroFields();
                        return;
                    }

                    placeholder.addClass('d-none');

                    ensurePlanSize(days, meals);
                    updatePlanInput();

                    const headerRow = $('<tr />');
                    headerRow.append($('<th />').text(translations.day));

                    for (let mealIndex = 0; mealIndex < meals; mealIndex++) {
                        headerRow.append(
                            $('<th />').text(translations.servings + ' ' + (mealIndex + 1))
                        );
                    }

                    headerRow.append($('<th class="text-center" />').text(translations.protein + ' (' + translations.grams + ')'));
                    headerRow.append($('<th class="text-center" />').text(translations.carbs + ' (' + translations.grams + ')'));
                    headerRow.append($('<th class="text-center" />').text(translations.fat + ' (' + translations.grams + ')'));
                    headerRow.append($('<th class="text-center" />').text(translations.calories));

                    tableHead.append(headerRow);

                    for (let dayIndex = 0; dayIndex < days; dayIndex++) {
                        const row = $('<tr />');
                        row.append(
                            $('<th scope="row" />').text(translations.day + ' ' + (dayIndex + 1))
                        );

                        for (let mealIndex = 0; mealIndex < meals; mealIndex++) {
                            row.append(
                                $('<td />').append(renderMealCell(dayIndex, mealIndex))
                            );
                        }

                        const totals = computeDayTotals(mealPlan[dayIndex]);
                        row.append($('<td class="text-center fw-semibold" />').text(formatNumber(totals.protein)));
                        row.append($('<td class="text-center fw-semibold" />').text(formatNumber(totals.carbs)));
                        row.append($('<td class="text-center fw-semibold" />').text(formatNumber(totals.fat)));
                        row.append($('<td class="text-center fw-semibold" />').text(formatNumber(totals.calories)));

                        tableBody.append(row);
                    }

                    updateMacroFields();
                }

                removeSelectionButton.on('click', function () {
                    if (currentSelection.day === null || currentSelection.meal === null) {
                        return;
                    }

                    clearMeal(currentSelection.day, currentSelection.meal);
                    closeModal();
                });

                ingredientSearch.on('input', function () {
                    renderIngredientList($(this).val());
                });

                if (ingredientModalEl.length) {
                    ingredientModalEl.on('hidden.bs.modal', function () {
                        currentSelection = { day: null, meal: null };
                        ingredientSearch.val('');
                    });
                }

                daysField.on('change input', function () {
                    renderTable();
                });

                servingsField.on('change input', function () {
                    renderTable();
                });

                parseInitialPlan();
                renderTable();
                updateMacroFields();
            });
        })(jQuery);
    </script>
@endpush
<x-app-layout :assets="$assets ?? []">
    <div>
        <?php $id = $id ?? null;?>
        @if(isset($id))
            {{ html()->modelForm($data, 'PATCH', route('diet.update', $id) )->attribute('enctype', 'multipart/form-data')->open() }}
        @else
            {{ html()->form('POST', route('diet.store'))->attribute('enctype', 'multipart/form-data')->open() }} 
        @endif
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                        <div class="card-action">
                            <a href="{{ route('diet.index') }} " class="btn btn-sm btn-primary" role="button">{{ __('message.back') }}</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="form-group col-md-4">
                                {{ html()->label(__('message.title') . ' <span class="text-danger">*</span>', 'title')->class('form-control-label') }}
                                {{ html()->text('title')->placeholder(__('message.title'))->class('form-control')->attribute('required','required') }}
                            </div>
                            <div class="form-group col-md-4">
                                {{ html()->label(__('message.categorydiet').' <span class="text-danger">*</span>')->class('form-control-label') }}
                                {{ html()->select('categorydiet_id', isset($id) ? [ optional($data->categorydiet)->id => optional($data->categorydiet)->title ] : [], old('equipment_id'))
                                    ->class('select2js form-group categorydiet')
                                    ->attribute('data-placeholder', __('message.select_name', ['select' => __('message.categorydiet')]))
                                    ->attribute('data-ajax--url', route('ajax-list', ['type' => 'categorydiet']))
                                    ->attribute('required', 'required') 
                                }}
                            </div>
                            <div class="form-group col-md-4">
                                {{ html()->label(__('message.calories') . ' <span class="text-danger">*</span>', 'calories')->class('form-control-label') }}
                                {{ html()->text('calories')->placeholder(__('message.calories'))->class('form-control')->attribute('required','required')->attribute('readonly', 'readonly')->id('calories') }}
                            </div>
                             <div class="form-group col-md-4">
                                {{ html()->label(__('message.carbs').' <span class="text-danger">*</span>')->class('form-control-label') }}
                                {{ html()->text('carbs', old('carbs'))->placeholder(__('message.carbs')." (". __('message.grams') .")")->class('form-control')->attribute('required', 'required')->attribute('readonly', 'readonly')->id('carbs') }}
                            </div>
                            <div class="form-group col-md-4">
                                {{ html()->label(__('message.protein').' <span class="text-danger">*</span>')->class('form-control-label') }}
                                {{ html()->text('protein', old('protein'))->placeholder(__('message.protein')." (". __('message.grams') .")")->class('form-control')->attribute('required', 'required')->attribute('readonly', 'readonly')->id('protein') }}
                            </div>
                            <div class="form-group col-md-4">
                                {{ html()->label(__('message.fat').' <span class="text-danger">*</span>')->class('form-control-label') }}
                                {{ html()->text('fat', old('fat'))->placeholder(__('message.fat')." (". __('message.grams') .")")->class('form-control')->attribute('required', 'required')->attribute('readonly', 'readonly')->id('fat') }}
                            </div>
                            <div class="form-group col-md-4">
                                {{ html()->label(__('message.servings') . ' <span class="text-danger">*</span>', 'servings')->class('form-control-label') }}
                                {{ html()->text('servings')->placeholder(__('message.servings'))->class('form-control')->attribute('required','required')->attribute('type','number')->attribute('min','1')->id('servings') }}
                            </div>
                            <div class="form-group col-md-4">
                                {{ html()->label(__('message.days') . ' <span class="text-danger">*</span>', 'days')->class('form-control-label') }}
                                {{ html()->text('days')->placeholder(__('message.days'))->class('form-control')->attribute('required','required')->attribute('type','number')->attribute('min','1')->id('days') }}
                            </div>
                            <div class="form-group col-md-4">
                                {{ html()->label(__('message.status') . ' <span class="text-danger">*</span>', 'status')->class('form-control-label') }}
                                {{ html()->select('status',[ 'active' => __('message.active'), 'inactive' => __('message.inactive') ], old('status'))->class('form-control select2js')->attribute('required', 'required') }}
                            </div>
                            <div class="form-group col-md-4">
                                {{ html()->label(__('message.featured'))->class('form-control-label') }}
                                <div class="form-check">
                                    <div class="custom-control custom-radio d-inline-block col-4">
                                        <label class="form-check-label" for="is_featured-yes">{{ __('message.yes') }}</label>
                                        {{ html()->radio('is_featured', old('is_featured') == 'yes' || true, 'yes')->class('form-check-input')->id('is_featured-yes')}}
                                    </div>
                                    <div class="custom-control custom-radio d-inline-block col-4">
                                        <label class="form-check-label" for="is_featured-no">{{ __('message.no') }}</label>
                                        {{ html()->radio('is_featured', old('is_featured') == 'no', 'no')->class('form-check-input')->id('is_featured-no') }}
                                    </div>
                                </div> 
                            </div>
                            <div class="form-group col-md-4">
                                {{ html()->label(__('message.is_premium'))->class('form-control-label') }}
                                <div class="">
                                    {!! html()->hidden('is_premium', 0)->class('form-check-input') !!}
                                    {!! html()->checkbox('is_premium', null, 1)->class('form-check-input') !!}
                                    <label class="custom-control-label" for="is_premium"></label>
                                </div>
                            </div>
                        </div>
                        
                        @php
                            $existingPlan = isset($data) ? ($data->ingredients ?? []) : [];
                            $planValue = old('ingredients', json_encode($existingPlan ?: []));
                        @endphp

                        <div class="row">
                            <div class="form-group col-md-12">
                                <label class="form-control-label" for="meal-plan-table">{{ __('message.meal_plan') }}</label>
                                <p class="text-muted small mb-3">{{ __('message.meal_plan_helper') }}</p>
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle meal-plan-table" id="meal-plan-table">
                                        <thead></thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                                <div id="meal-plan-empty" class="text-center py-3">
                                    {{ __('message.meal_plan_helper') }}
                                </div>
                                <input type="hidden" name="ingredients" id="meal-plan-input" value='{{ e($planValue) }}'>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-12">
                                {{ html()->label(__('message.description'))->class('form-control-label') }}
                                {{ html()->textarea('description', null)->class('form-control tinymce-description')->placeholder(__('message.description')) }}
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="form-control-label" for="diet_image">{{ __('message.image') }} </label>
                                <div class="">
                                    <input class="form-control file-input" type="file" name="diet_image" accept="image/*" id="diet_image" />
                                </div>
                            </div>
                            @if( isset($id) && getMediaFileExit($data, 'diet_image'))
                                <div class="col-md-2 mb-2 position-relative">
                                    <img id="diet_image_preview" src="{{ getSingleMedia($data,'diet_image') }}" alt="diet-image" class="avatar-100 mt-1">                
                                    <a class="text-danger remove-file" href="{{ route('remove.file', ['id' => $data->id, 'type' => 'diet_image']) }}"
                                        data--submit='confirm_form'
                                        data--confirmation='true'
                                        data--ajax='true'
                                        data-toggle='tooltip'
                                        title='{{ __("message.remove_file_title" , ["name" =>  __("message.image") ]) }}'
                                        data-title='{{ __("message.remove_file_title" , ["name" =>  __("message.image") ]) }}'
                                        data-message='{{ __("message.remove_file_msg") }}'
                                    >
                                        <svg width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path opacity="0.4" d="M16.34 1.99976H7.67C4.28 1.99976 2 4.37976 2 7.91976V16.0898C2 19.6198 4.28 21.9998 7.67 21.9998H16.34C19.73 21.9998 22 19.6198 22 16.0898V7.91976C22 4.37976 19.73 1.99976 16.34 1.99976Z" fill="currentColor"></path>
                                            <path d="M15.0158 13.7703L13.2368 11.9923L15.0148 10.2143C15.3568 9.87326 15.3568 9.31826 15.0148 8.97726C14.6728 8.63326 14.1198 8.63426 13.7778 8.97626L11.9988 10.7543L10.2198 8.97426C9.87782 8.63226 9.32382 8.63426 8.98182 8.97426C8.64082 9.31626 8.64082 9.87126 8.98182 10.2123L10.7618 11.9923L8.98582 13.7673C8.64382 14.1093 8.64382 14.6643 8.98582 15.0043C9.15682 15.1763 9.37982 15.2613 9.60382 15.2613C9.82882 15.2613 10.0518 15.1763 10.2228 15.0053L11.9988 13.2293L13.7788 15.0083C13.9498 15.1793 14.1728 15.2643 14.3968 15.2643C14.6208 15.2643 14.8448 15.1783 15.0158 15.0083C15.3578 14.6663 15.3578 14.1123 15.0158 13.7703Z" fill="currentColor"></path>
                                        </svg>
                                    </a>
                                </div>
                            @endif
                        </div>
                        <hr>
                        {{ html()->submit( __('message.save') )->class('btn btn-md btn-primary float-end') }}
                    </div>
                </div>
            </div>
        </div>
        @if(isset($id))
            {{ html()->closeModelForm() }}
        @else
            {{ html()->form()->close() }}
        @endif
    </div>
    <div class="modal fade" id="ingredient-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('message.select_meal') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('message.close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="ingredient-search" class="form-label">{{ __('message.search') }}</label>
                        <input type="text" class="form-control" id="ingredient-search" placeholder="{{ __('message.search') }}">
                    </div>
                    <div id="ingredient-modal-list" class="list-group"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('message.close') }}</button>
                    <button type="button" class="btn btn-outline-danger" id="clear-meal-selection">{{ __('message.remove_meal') }}</button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
