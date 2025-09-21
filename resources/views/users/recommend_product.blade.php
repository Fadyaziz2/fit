<!-- Modal -->
{{ html()->form('POST', route('save.recommendproduct'))->attribute('data-toggle', 'validator')->open() }}
    <div class="row">
        {{ html()->hidden('user_id', $user_id) }}
        <div class="form-group col-md-12">
            {{ html()->label(__('message.product').' <span class="text-danger">*</span>')->class('form-control-label') }}
            {{
                html()->select('product_ids[]', [], null)
                    ->attribute('id', 'product_ids')
                    ->class('select2js form-group')
                    ->attribute('data-placeholder', __('message.select_name', ['select' => __('message.product')]))
                    ->attribute('data-ajax--url', route('ajax-list', ['type' => 'product']))
                    ->attribute('required', 'required')
                    ->attribute('multiple', 'multiple')
            }}
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-md btn-secondary" data-bs-dismiss="modal">{{ __('message.close') }}</button>
        <button type="submit" class="btn btn-md btn-primary" id="btn_submit" data-form="ajax">{{ __('message.save') }}</button>
    </div>
    @if(isset($id))
        {{ html()->closeModelForm() }}
    @else
        {{ html()->form()->close() }}
    @endif
<script>
    $('#product_ids').select2({
        dropdownParent: $('#formModal'),
        width: '100%',
        placeholder: "{{ __('message.select_name',['select' => __('message.product')]) }}",
    });
</script>
