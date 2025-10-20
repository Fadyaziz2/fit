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

        #ingredient-modal-selected {
            min-height: 48px;
        }

        .selected-ingredient-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            background-color: var(--bs-primary-bg-subtle, rgba(13, 110, 253, 0.1));
            color: var(--bs-primary, #0d6efd);
            font-weight: 600;
        }

        .meal-ingredients-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .meal-ingredients-list .meal-info {
            align-items: flex-start;
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
                    noResults: "{{ __('message.no_results_found') }}",
                    selectedIngredients: "{{ __('message.selected_ingredients') }}",
                    noIngredientsSelected: "{{ __('message.no_ingredients_selected') }}",
                    mealTotals: "{{ __('message.meal_totals') }}",
                    quantity: "{{ __('message.quantity') }}",
                    unit: "{{ __('message.unit') }}"
                };

                const unitDatalistId = 'diet-unit-options';
                let unitDatalist = null;
                const unitOptions = new Set();

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
                const saveSelectionButton = $('#save-meal-selection');
                const selectionSummary = $('#ingredient-modal-selected');

                let mealPlan = [];
                let currentSelection = { day: null, meal: null };
                let modalSelection = [];
                let currentQuantities = {};
                let currentUnits = {};

                function sanitizeCount(value) {
                    const number = parseInt(value, 10);
                    return Number.isFinite(number) && number > 0 ? number : 0;
                }

                function sanitizeQuantity(value) {
                    if (value === null || value === undefined || value === '') {
                        return 1;
                    }

                    if (typeof value === 'string') {
                        value = value.replace(',', '.');
                    }

                    const number = parseFloat(value);

                    if (!Number.isFinite(number) || number <= 0) {
                        return 1;
                    }

                    return Math.round(number * 100) / 100;
                }

                function sanitizeUnitLabel(value) {
                    if (value === null || value === undefined) {
                        return '';
                    }

                    if (typeof value !== 'string') {
                        value = String(value);
                    }

                    const trimmed = value.trim();

                    if (!trimmed) {
                        return '';
                    }

                    return trimmed.length > 50 ? trimmed.substring(0, 50) : trimmed;
                }

                function ensureUnitDatalist() {
                    if (unitDatalist && unitDatalist.length) {
                        return unitDatalist;
                    }

                    const existing = document.getElementById(unitDatalistId);

                    if (existing) {
                        unitDatalist = $(existing);
                        return unitDatalist;
                    }

                    unitDatalist = $('<datalist />').attr('id', unitDatalistId).appendTo('body');

                    return unitDatalist;
                }

                function updateUnitDatalist() {
                    const datalist = ensureUnitDatalist();
                    datalist.empty();

                    Array.from(unitOptions)
                        .sort(function (a, b) {
                            return a.localeCompare(b);
                        })
                        .forEach(function (unit) {
                            datalist.append($('<option />').attr('value', unit));
                        });
                }

                function registerUnit(unit) {
                    const sanitized = sanitizeUnitLabel(unit);

                    if (!sanitized || unitOptions.has(sanitized)) {
                        return;
                    }

                    unitOptions.add(sanitized);
                    updateUnitDatalist();
                }

                function extractEntryId(source) {
                    if (!source || typeof source !== 'object') {
                        return NaN;
                    }

                    if (Object.prototype.hasOwnProperty.call(source, 'id')) {
                        return parseInt(source.id, 10);
                    }

                    if (Object.prototype.hasOwnProperty.call(source, 'ingredient_id')) {
                        return parseInt(source.ingredient_id, 10);
                    }

                    if (Object.prototype.hasOwnProperty.call(source, 'ingredient')) {
                        return parseInt(source.ingredient, 10);
                    }

                    return NaN;
                }

                function extractEntryQuantity(source) {
                    if (!source || typeof source !== 'object') {
                        return 1;
                    }

                    if (Object.prototype.hasOwnProperty.call(source, 'quantity')) {
                        return source.quantity;
                    }

                    if (Object.prototype.hasOwnProperty.call(source, 'qty')) {
                        return source.qty;
                    }

                    if (Object.prototype.hasOwnProperty.call(source, 'amount')) {
                        return source.amount;
                    }

                    return 1;
                }

                function extractEntryUnit(source) {
                    if (!source || typeof source !== 'object') {
                        return '';
                    }

                    if (Object.prototype.hasOwnProperty.call(source, 'unit')) {
                        return source.unit;
                    }

                    if (Object.prototype.hasOwnProperty.call(source, 'measurement_unit')) {
                        return source.measurement_unit;
                    }

                    if (Object.prototype.hasOwnProperty.call(source, 'measure')) {
                        return source.measure;
                    }

                    return '';
                }

                function normalizeMealValue(value) {
                    const normalized = [];
                    const seen = new Set();

                    function addEntry(id, quantity, unit) {
                        if (!Number.isInteger(id) || id <= 0 || seen.has(id)) {
                            return;
                        }

                        const sanitizedQuantity = sanitizeQuantity(quantity);
                        const sanitizedUnit = sanitizeUnitLabel(unit);

                        seen.add(id);
                        normalized.push({ id: id, quantity: sanitizedQuantity, unit: sanitizedUnit });

                        if (sanitizedUnit) {
                            registerUnit(sanitizedUnit);
                        }
                    }

                    if (Array.isArray(value)) {
                        value.forEach(function (item) {
                            if (item === null || item === undefined) {
                                return;
                            }

                            if (typeof item === 'object') {
                                const id = extractEntryId(item);
                                const quantity = extractEntryQuantity(item);
                                const unit = extractEntryUnit(item);
                                addEntry(id, quantity, unit);
                                return;
                            }

                            const id = parseInt(item, 10);
                            addEntry(id, 1, null);
                        });

                        return normalized;
                    }

                    if (value && typeof value === 'object') {
                        const id = extractEntryId(value);
                        const quantity = extractEntryQuantity(value);
                        const unit = extractEntryUnit(value);
                        addEntry(id, quantity, unit);

                        return normalized;
                    }

                    const id = parseInt(value, 10);
                    addEntry(id, 1, null);

                    return normalized;
                }

                function normalizePlanStructure(plan) {
                    if (!Array.isArray(plan)) {
                        return [];
                    }

                    return plan.map(function (dayMeals) {
                        if (!Array.isArray(dayMeals)) {
                            return [];
                        }

                        return dayMeals.map(function (meal) {
                            return normalizeMealValue(meal);
                        });
                    });
                }

                function parseInitialPlan() {
                    const raw = planInput.val();
                    unitOptions.clear();
                    updateUnitDatalist();
                    if (!raw) {
                        mealPlan = [];
                        return;
                    }

                    try {
                        const parsed = JSON.parse(raw);

                        if (Array.isArray(parsed)) {
                            mealPlan = normalizePlanStructure(parsed);
                            return;
                        }

                        if (parsed && Array.isArray(parsed.plan)) {
                            mealPlan = normalizePlanStructure(parsed.plan);
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
                            mealPlan[day][meal] = normalizeMealValue(mealPlan[day][meal]);
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

                function computeMealTotals(ingredientIds) {
                    const totals = { protein: 0, carbs: 0, fat: 0, calories: 0 };
                    const normalized = normalizeMealValue(ingredientIds);

                    normalized.forEach(function (entry) {
                        const ingredient = ingredientMap[entry.id];

                        if (!ingredient) {
                            return;
                        }

                        const quantity = Number(entry.quantity) || 0;

                        if (!Number.isFinite(quantity) || quantity <= 0) {
                            return;
                        }

                        totals.protein += (Number(ingredient.protein) || 0) * quantity;
                        totals.carbs += (Number(ingredient.carbs) || 0) * quantity;
                        totals.fat += (Number(ingredient.fat) || 0) * quantity;
                        totals.calories += (Number(ingredient.calories) || 0) * quantity;
                    });

                    return totals;
                }

                function computeDayTotals(dayMeals) {
                    const totals = { protein: 0, carbs: 0, fat: 0, calories: 0 };

                    if (!Array.isArray(dayMeals)) {
                        return totals;
                    }

                    dayMeals.forEach(function (meal) {
                        const mealTotals = computeMealTotals(meal);
                        totals.protein += mealTotals.protein;
                        totals.carbs += mealTotals.carbs;
                        totals.fat += mealTotals.fat;
                        totals.calories += mealTotals.calories;
                    });

                    return totals;
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

                function getMealSelection(dayIndex, mealIndex) {
                    if (!Array.isArray(mealPlan[dayIndex])) {
                        return [];
                    }

                    return normalizeMealValue(mealPlan[dayIndex][mealIndex]);
                }

                function setMealSelection(dayIndex, mealIndex, ingredientIds) {
                    const days = sanitizeCount(daysField.val());
                    const meals = sanitizeCount(servingsField.val());
                    ensurePlanSize(days, meals);

                    if (!Array.isArray(mealPlan[dayIndex])) {
                        mealPlan[dayIndex] = [];
                    }

                    mealPlan[dayIndex][mealIndex] = normalizeMealValue(ingredientIds);
                }

                function removeMeal(dayIndex, mealIndex) {
                    setMealSelection(dayIndex, mealIndex, []);
                    renderTable();
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

                    const mealEntries = getMealSelection(dayIndex, mealIndex).map(function (entry) {
                        const quantityValue = sanitizeQuantity(entry.quantity);
                        const unitValue = sanitizeUnitLabel(entry.unit);

                        if (unitValue) {
                            registerUnit(unitValue);
                        }

                        return {
                            id: entry.id,
                            quantity: quantityValue,
                            unit: unitValue,
                        };
                    });

                    mealPlan[dayIndex][mealIndex] = mealEntries;

                    if (mealEntries.length) {
                        selectButton.addClass('d-none');
                        content.removeClass('d-none');

                        const list = $('<div />').addClass('meal-ingredients-list');

                        mealEntries.forEach(function (entry, entryIndex) {
                            const ingredient = ingredientMap[entry.id];
                            if (!ingredient) {
                                return;
                            }

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

                            const quantityValue = sanitizeQuantity(entry.quantity);
                            const unitValue = sanitizeUnitLabel(entry.unit);
                            mealPlan[dayIndex][mealIndex][entryIndex].quantity = quantityValue;
                            mealPlan[dayIndex][mealIndex][entryIndex].unit = unitValue;

                            const ingredientProtein = (Number(ingredient.protein) || 0) * quantityValue;
                            const ingredientCarbs = (Number(ingredient.carbs) || 0) * quantityValue;
                            const ingredientFat = (Number(ingredient.fat) || 0) * quantityValue;
                            const ingredientCalories = (Number(ingredient.calories) || 0) * quantityValue;

                            const macros = [
                                translations.protein + ': ' + formatNumber(ingredientProtein) + ' ' + translations.grams,
                                translations.carbs + ': ' + formatNumber(ingredientCarbs) + ' ' + translations.grams,
                                translations.fat + ': ' + formatNumber(ingredientFat) + ' ' + translations.grams,
                                translations.calories + ': ' + formatNumber(ingredientCalories)
                            ].join(' | ');

                            textWrapper.append($('<div />').addClass('meal-macros').text(macros));

                            const quantityGroup = $('<div />').addClass('d-flex align-items-center gap-2 mt-2 flex-wrap');
                            quantityGroup.append($('<span />').addClass('text-muted small').text(translations.quantity));

                            const quantityInput = $('<input />')
                                .attr({
                                    type: 'number',
                                    step: '0.01',
                                    min: '0.01',
                                })
                                .addClass('form-control form-control-sm')
                                .css('max-width', '120px')
                                .val(formatNumber(quantityValue))
                                .on('change', function () {
                                    const sanitized = sanitizeQuantity($(this).val());
                                    mealPlan[dayIndex][mealIndex][entryIndex].quantity = sanitized;
                                    updatePlanInput();
                                    renderTable();
                                });

                            quantityGroup.append(quantityInput);
                            quantityGroup.append($('<span />').addClass('text-muted small').text(translations.unit));

                            const unitInput = $('<input />')
                                .attr({
                                    type: 'text',
                                    list: unitDatalistId,
                                })
                                .addClass('form-control form-control-sm')
                                .css('max-width', '140px')
                                .val(unitValue)
                                .on('change blur', function () {
                                    const sanitizedUnit = sanitizeUnitLabel($(this).val());
                                    $(this).val(sanitizedUnit);
                                    mealPlan[dayIndex][mealIndex][entryIndex].unit = sanitizedUnit;
                                    registerUnit(sanitizedUnit);
                                    updatePlanInput();
                                });

                            quantityGroup.append(unitInput);
                            textWrapper.append(quantityGroup);
                            info.append(textWrapper);
                            list.append(info);
                        });

                        content.append(list);

                        const mealTotals = computeMealTotals(mealEntries);
                        content.append(
                            $('<div />')
                                .addClass('meal-macros mt-2 fw-semibold')
                                .text(
                                    translations.mealTotals + ': '
                                    + translations.protein + ' ' + formatNumber(mealTotals.protein) + ' ' + translations.grams + ' | '
                                    + translations.carbs + ' ' + formatNumber(mealTotals.carbs) + ' ' + translations.grams + ' | '
                                    + translations.fat + ' ' + formatNumber(mealTotals.fat) + ' ' + translations.grams + ' | '
                                    + translations.calories + ' ' + formatNumber(mealTotals.calories)
                                )
                        );

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
                                    removeMeal(dayIndex, mealIndex);
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

                function updateModalSelection(ingredientId, shouldSelect) {
                    const index = modalSelection.indexOf(ingredientId);

                    if (shouldSelect) {
                        if (index === -1) {
                            modalSelection.push(ingredientId);
                        }

                        if (!Object.prototype.hasOwnProperty.call(currentQuantities, ingredientId)) {
                            currentQuantities[ingredientId] = 1;
                        }

                        if (!Object.prototype.hasOwnProperty.call(currentUnits, ingredientId)) {
                            currentUnits[ingredientId] = '';
                        }
                    } else if (index !== -1) {
                        modalSelection.splice(index, 1);
                        delete currentQuantities[ingredientId];
                        delete currentUnits[ingredientId];
                    }
                }

                function updateSelectionSummary() {
                    if (!selectionSummary.length) {
                        return;
                    }

                    selectionSummary.empty();

                    if (!modalSelection.length) {
                        selectionSummary.append(
                            $('<span />')
                                .addClass('text-muted')
                                .text(translations.noIngredientsSelected)
                        );

                        return;
                    }

                    modalSelection.forEach(function (ingredientId) {
                        const ingredient = ingredientMap[ingredientId];
                        const badge = $('<span />').addClass('selected-ingredient-badge');
                        const title = ingredient ? ingredient.title : '#' + ingredientId;
                        badge.append($('<span />').text(title));

                        const quantity = Object.prototype.hasOwnProperty.call(currentQuantities, ingredientId)
                            ? currentQuantities[ingredientId]
                            : 1;
                        const unit = Object.prototype.hasOwnProperty.call(currentUnits, ingredientId)
                            ? sanitizeUnitLabel(currentUnits[ingredientId])
                            : '';

                        currentUnits[ingredientId] = unit;

                        badge.append(
                            $('<small />')
                                .addClass('d-block text-muted')
                                .text(
                                    translations.quantity + ': ' + formatNumber(quantity)
                                    + (unit ? ' | ' + translations.unit + ': ' + unit : '')
                                )
                        );

                        selectionSummary.append(badge);
                    });
                }

                function updateRemoveButtonState() {
                    removeSelectionButton.prop('disabled', modalSelection.length === 0);
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
                        const ingredientId = parseInt(ingredient.id, 10);
                        const isSelected = modalSelection.includes(ingredientId);

                        const item = $('<label />')
                            .addClass('list-group-item list-group-item-action d-flex align-items-center gap-3');

                        const checkbox = $('<input />')
                            .attr('type', 'checkbox')
                            .addClass('form-check-input me-2')
                            .prop('checked', isSelected)
                            .on('change', function () {
                                updateModalSelection(ingredientId, this.checked);
                                updateSelectionSummary();
                                updateRemoveButtonState();
                            });

                        item.append(checkbox);

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

                        ingredientList.append(item);
                    });
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
                    const currentEntries = getMealSelection(dayIndex, mealIndex);
                    modalSelection = currentEntries.map(function (entry) {
                        return entry.id;
                    });

                    currentQuantities = {};
                    currentUnits = {};
                    currentEntries.forEach(function (entry) {
                        currentQuantities[entry.id] = sanitizeQuantity(entry.quantity);
                        currentUnits[entry.id] = sanitizeUnitLabel(entry.unit);
                    });
                    ingredientSearch.val('');
                    renderIngredientList('');
                    updateSelectionSummary();
                    updateRemoveButtonState();

                    if (ingredientModal) {
                        ingredientModal.show();
                    } else {
                        ingredientModalEl.modal('show');
                    }
                }

                removeSelectionButton.on('click', function () {
                    if (currentSelection.day === null || currentSelection.meal === null) {
                        return;
                    }

                    modalSelection = [];
                    currentQuantities = {};
                    currentUnits = {};
                    setMealSelection(currentSelection.day, currentSelection.meal, []);
                    renderTable();
                    closeModal();
                });

                saveSelectionButton.on('click', function () {
                    if (currentSelection.day === null || currentSelection.meal === null) {
                        return;
                    }

                    const entries = modalSelection.map(function (ingredientId) {
                        const quantity = Object.prototype.hasOwnProperty.call(currentQuantities, ingredientId)
                            ? currentQuantities[ingredientId]
                            : 1;
                        const unit = Object.prototype.hasOwnProperty.call(currentUnits, ingredientId)
                            ? sanitizeUnitLabel(currentUnits[ingredientId])
                            : '';

                        currentUnits[ingredientId] = unit;

                        return {
                            id: ingredientId,
                            quantity: sanitizeQuantity(quantity),
                            unit: unit,
                        };
                    });

                    setMealSelection(currentSelection.day, currentSelection.meal, entries);
                    renderTable();
                    closeModal();
                });

                ingredientSearch.on('input', function () {
                    renderIngredientList($(this).val());
                });

                if (ingredientModalEl.length) {
                    ingredientModalEl.on('hidden.bs.modal', function () {
                        currentSelection = { day: null, meal: null };
                        ingredientSearch.val('');
                        modalSelection = [];
                        currentQuantities = {};
                        currentUnits = {};
                        if (selectionSummary.length) {
                            selectionSummary.empty();
                        }
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
                        <label class="form-label">{{ __('message.selected_ingredients') }}</label>
                        <div id="ingredient-modal-selected" class="d-flex flex-wrap gap-2"></div>
                    </div>
                    <div class="mb-3">
                        <label for="ingredient-search" class="form-label">{{ __('message.search') }}</label>
                        <input type="text" class="form-control" id="ingredient-search" placeholder="{{ __('message.search') }}">
                    </div>
                    <div id="ingredient-modal-list" class="list-group"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('message.close') }}</button>
                    <button type="button" class="btn btn-outline-danger" id="clear-meal-selection">{{ __('message.remove_meal') }}</button>
                    <button type="button" class="btn btn-primary" id="save-meal-selection">{{ __('message.save_selection') }}</button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
