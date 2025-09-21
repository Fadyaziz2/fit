<x-app-layout :assets="$assets ?? []">
    <div>
        <?php $id = $id ?? null;?>
        @if(isset($id))
            {{ html()->modelForm($data, 'PATCH', route('ingredient.update', $id) )->open() }}
        @else
            {{ html()->form('POST', route('ingredient.store'))->open() }}
        @endif
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                        <div class="card-action">
                            <a href="{{ route('ingredient.index') }} " class="btn btn-sm btn-primary" role="button">{{ __('message.back') }}</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="form-group col-md-6">
                                {{ html()->label(__('message.title') . ' <span class="text-danger">*</span>', 'title')->class('form-control-label') }}
                                {{ html()->text('title')->placeholder(__('message.title'))->class('form-control')->attribute('required','required') }}
                            </div>
                            <div class="form-group col-md-6">
                                {{ html()->label(__('message.protein') . ' <span class="text-danger">*</span>', 'protein')->class('form-control-label') }}
                                {{ html()->text('protein')->placeholder(__('message.protein')." (".__('message.grams').")")->class('form-control')->attribute('required','required')->attribute('inputmode','decimal') }}
                            </div>
                            <div class="form-group col-md-6">
                                {{ html()->label(__('message.fat') . ' <span class="text-danger">*</span>', 'fat')->class('form-control-label') }}
                                {{ html()->text('fat')->placeholder(__('message.fat')." (".__('message.grams').")")->class('form-control')->attribute('required','required')->attribute('inputmode','decimal') }}
                            </div>
                            <div class="form-group col-md-6">
                                {{ html()->label(__('message.carbs') . ' <span class="text-danger">*</span>', 'carbs')->class('form-control-label') }}
                                {{ html()->text('carbs')->placeholder(__('message.carbs')." (".__('message.grams').")")->class('form-control')->attribute('required','required')->attribute('inputmode','decimal') }}
                            </div>
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
</x-app-layout>
