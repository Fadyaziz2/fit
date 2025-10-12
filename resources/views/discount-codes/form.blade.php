<x-app-layout :assets="$assets ?? []">
    <div>
        <?php $id = $id ?? null; ?>
        @if(isset($id))
            {{ html()->modelForm($data, 'PATCH', route('discount-codes.update', $id))->open() }}
        @else
            {{ html()->form('POST', route('discount-codes.store'))->open() }}
        @endif
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                        <div class="card-action">
                            <a href="{{ route('discount-codes.index') }}" class="btn btn-sm btn-primary" role="button">{{ __('message.back') }}</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="form-group col-md-4">
                                {{ html()->label(__('message.discount_code') . ' <span class="text-danger">*</span>', 'code')->class('form-control-label') }}
                                {{ html()->text('code')->placeholder(__('message.enter_discount_code'))->class('form-control')->attribute('required', 'required') }}
                            </div>
                            <div class="form-group col-md-4">
                                {{ html()->label(__('message.name'), 'name')->class('form-control-label') }}
                                {{ html()->text('name')->placeholder(__('message.name'))->class('form-control') }}
                            </div>
                            <div class="form-group col-md-4">
                                {{ html()->label(__('message.discount_type') . ' <span class="text-danger">*</span>', 'discount_type')->class('form-control-label') }}
                                {{ html()->select('discount_type', ['percentage' => __('message.discount_percentage'), 'fixed' => __('message.discount_fixed_amount')], old('discount_type', $data->discount_type ?? 'percentage'))->class('form-select')->attribute('required', 'required') }}
                            </div>
                            <div class="form-group col-md-4">
                                {{ html()->label(__('message.discount_value') . ' <span class="text-danger">*</span>', 'discount_value')->class('form-control-label') }}
                                {{ html()->number('discount_value')->step('0.01')->placeholder(__('message.discount_value'))->class('form-control')->attribute('required', 'required') }}
                                <small class="form-text text-muted">{{ __('message.discount_value_hint') }}</small>
                            </div>
                            <div class="form-group col-md-4">
                                {{ html()->label(__('message.is_one_time_per_user'), 'is_one_time_per_user')->class('form-control-label') }}
                                {{ html()->select('is_one_time_per_user', [1 => __('message.yes'), 0 => __('message.no')], old('is_one_time_per_user', $data->is_one_time_per_user ?? 0))->class('form-select') }}
                            </div>
                            <div class="form-group col-md-4">
                                {{ html()->label(__('message.status'), 'is_active')->class('form-control-label') }}
                                {{ html()->select('is_active', [1 => __('message.active'), 0 => __('message.inactive')], old('is_active', $data->is_active ?? 1))->class('form-select') }}
                            </div>
                            <div class="form-group col-md-4">
                                {{ html()->label(__('message.max_redemptions'), 'max_redemptions')->class('form-control-label') }}
                                {{ html()->number('max_redemptions')->step('1')->min('1')->placeholder(__('message.max_redemptions_hint'))->class('form-control') }}
                                <small class="form-text text-muted">{{ __('message.max_redemptions_hint') }}</small>
                            </div>
                            <div class="form-group col-md-4">
                                {{ html()->label(__('message.starts_at'), 'starts_at')->class('form-control-label') }}
                                {{ html()->input('datetime-local', 'starts_at', old('starts_at', isset($data) && $data->starts_at ? $data->starts_at->format('Y-m-d\TH:i') : null))->class('form-control') }}
                            </div>
                            <div class="form-group col-md-4">
                                {{ html()->label(__('message.expires_at'), 'expires_at')->class('form-control-label') }}
                                {{ html()->input('datetime-local', 'expires_at', old('expires_at', isset($data) && $data->expires_at ? $data->expires_at->format('Y-m-d\TH:i') : null))->class('form-control') }}
                            </div>
                            <div class="form-group col-md-12">
                                {{ html()->label(__('message.description'), 'description')->class('form-control-label') }}
                                {{ html()->textarea('description')->placeholder(__('message.description'))->class('form-control')->rows(3) }}
                            </div>
                        </div>
                        <hr>
                        {{ html()->submit(__('message.save'))->class('btn btn-md btn-primary float-end') }}
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
</x-app-layout>
