@push('scripts')
    <script>
        $(document).ready(function () {
            function toggleRecipients() {
                const target = $('input[name="target"]:checked').val();
                if (target === 'selected') {
                    $('#specific-users').removeClass('d-none');
                } else {
                    $('#specific-users').addClass('d-none');
                }
            }

            $('input[name="target"]').on('change', toggleRecipients);
            toggleRecipients();
        });
    </script>
@endpush

<x-app-layout :assets="$assets ?? []">
    <div>
        {{ html()->form('POST', route('emails.send'))->open() }}
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="form-group col-md-12">
                                {{ html()->label(__('message.email_subject') . ' <span class="text-danger">*</span>', 'subject')->class('form-control-label') }}
                                {{ html()->text('subject')->class('form-control')->attribute('required', 'required')->placeholder(__('message.email_subject_placeholder'))->value(old('subject')) }}
                            </div>

                            <div class="form-group col-md-12">
                                {{ html()->label(__('message.message') . ' <span class="text-danger">*</span>', 'message')->class('form-control-label') }}
                                {{ html()->textarea('message')->class('form-control')->rows(6)->attribute('required', 'required')->placeholder(__('message.email_message_placeholder'))->value(old('message')) }}
                            </div>

                            <div class="form-group col-md-12">
                                {{ html()->label(__('message.email_recipients') . ' <span class="text-danger">*</span>', 'target')->class('form-control-label d-block') }}
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="target" id="email_target_all" value="all" {{ old('target', 'all') === 'all' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="email_target_all">{{ __('message.email_target_all') }}</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="target" id="email_target_selected" value="selected" {{ old('target') === 'selected' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="email_target_selected">{{ __('message.email_target_selected') }}</label>
                                </div>
                            </div>

                            <div class="form-group col-md-12 d-none" id="specific-users">
                                {{ html()->label(__('message.user') . ' <span class="text-danger">*</span>', 'users')->class('form-control-label') }}
                                {{ html()->select('users[]', $users, old('users', []))->class('select2js form-control')->attribute('multiple', 'multiple')->attribute('data-placeholder', __('message.email_users_placeholder')) }}
                            </div>
                        </div>
                        <hr>
                        {{ html()->submit(__('message.send'))->class('btn btn-md btn-primary float-end') }}
                    </div>
                </div>
            </div>
        </div>
        {{ html()->form()->close() }}
    </div>
</x-app-layout>
