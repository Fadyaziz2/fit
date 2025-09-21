<!-- Modal -->
{{ html()->form('POST', route('save.assigndiet'))->attribute('data-toggle', 'validator')->open() }} 
    <div class="row">
        {{ html()->hidden('user_id',$user_id) }}
        <div class="form-group col-md-12">
            {{ html()->label(__('message.diet').' <span class="text-danger">*</span>')
            ->class('form-control-label') }}
            {{ html()->select('diet_id', [], old('diet_id'))
                ->class('select2js form-group diet')
                ->attribute('data-placeholder', __('message.select_name', ['select' => __('message.diet')]))
                ->attribute('data-ajax--url', route('ajax-list', ['type' => 'diet']))
                ->attribute('required', 'required') }}
        </div>
    </div>
    <div class="row g-3 d-none" id="serve-times-container"></div>
    <div class="modal-footer">
        <button type="button" class="btn btn-md btn-secondary" data-bs-dismiss="modal">{{ __('message.close') }}</button>
        <button type="submit" class="btn btn-md btn-primary" id="btn_submit" data-form="ajax" >{{ __('message.save') }}</button>
    </div>
    @if(isset($id))
        {{ html()->closeModelForm() }}
    @else
        {{ html()->form()->close() }}
    @endif
<script>
    (function ($) {
        const dietSelect = $('#diet_id').select2({
            dropdownParent: $('#formModal'),
            width: '100%',
            placeholder: "{{ __('message.select_name',['select' => __('message.parent_permission')]) }}",
        });

        const serveTimesContainer = $('#serve-times-container');
        const userId = @json($user_id);
        const mealTimeLabelTemplate = @json(__('message.meal_time_number', ['number' => ':number']));
        const dietServingUrlTemplate = @json(route('diet.servings', ['diet' => ':id']));

        const renderServeTimeInputs = (servings, times) => {
            serveTimesContainer.empty();

            if (!servings) {
                serveTimesContainer.addClass('d-none');
                return;
            }

            serveTimesContainer.removeClass('d-none');

            const normalizedTimes = Array.isArray(times)
                ? times
                : Object.values(times || {});

            for (let index = 0; index < servings; index += 1) {
                const timeValue = normalizedTimes[index] ?? '';
                const labelText = mealTimeLabelTemplate.replace(':number', index + 1);

                const column = $('<div>', { class: 'form-group col-md-6' });
                const label = $('<label>', { class: 'form-control-label', text: labelText });
                label.append($('<span>', { class: 'text-danger', text: ' *' }));

                const input = $('<input>', {
                    type: 'time',
                    class: 'form-control',
                    name: 'serve_times[]',
                    required: true,
                }).val(timeValue);

                column.append(label, input);
                serveTimesContainer.append(column);
            }
        };

        const fetchDietServings = (dietId) => {
            if (!dietId) {
                renderServeTimeInputs(0, []);
                return;
            }

            serveTimesContainer.removeClass('d-none').html('<div class="col-12 text-center py-2"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span></div>');

            const url = dietServingUrlTemplate.replace(':id', dietId);

            $.get(url, { user_id: userId })
                .done((response) => {
                    const servings = parseInt(response.servings, 10) || 0;
                    renderServeTimeInputs(servings, response.serve_times || []);
                })
                .fail(() => {
                    renderServeTimeInputs(0, []);
                });
        };

        dietSelect.on('change', function () {
            fetchDietServings($(this).val());
        });
    })(jQuery);
</script>

