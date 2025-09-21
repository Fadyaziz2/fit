<!-- Modal -->
{{ html()->form('POST', route('save.assignworkout'))->attribute('data-toggle', 'validator')->open() }} 
    <div class="row">
    {{ html()->hidden('user_id',$user_id) }}
        <div class="form-group col-md-12">
            {{ html()->label(__('message.workout').' <span class="text-danger">*</span>') 
            ->class('form-control-label') }}
            {{ html()->select('workout_id', [], old('workout_id'))
                ->class('select2js form-group diet')
                ->attribute('data-placeholder', __('message.select_name', ['select' => __('message.workout')]))
                ->attribute('data-ajax--url', route('ajax-list', ['type' => 'workout']))
                ->attribute('required', 'required') }}
        </div>
    </div>
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
    $('#workout_id').select2({
        dropdownParent: $('#formModal'),
        width: '100%',
        placeholder: "{{ __('message.select_name',['select' => __('message.parent_permission')]) }}",
    });
</script>

