<x-app-layout :assets="$assets ?? []">
    <div>
        <?php $id = $id ?? null; ?>
        @if(isset($id))
            {{ html()->modelForm($data, 'PATCH', route('exclusive-offer.update', $id))->attribute('enctype', 'multipart/form-data')->open() }}
        @else
            {{ html()->form('POST', route('exclusive-offer.store'))->attribute('enctype', 'multipart/form-data')->open() }}
        @endif
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                        <div class="card-action">
                            <a href="{{ route('exclusive-offer.index') }}" class="btn btn-sm btn-primary" role="button">{{ __('message.back') }}</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning" role="alert">
                            <div class="d-flex">
                                <svg class="me-2" width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.4" d="M11.723 2.02291C5.94301 2.02291 1.27502 6.70091 1.27502 12.4809C1.27502 18.2609 5.94301 22.9389 11.723 22.9389C17.513 22.9389 22.191 18.2609 22.191 12.4809C22.191 6.70091 17.513 2.02291 11.723 2.02291Z" fill="currentColor"/>
                                    <path d="M11.7124 17.0435C12.3148 17.0435 12.8054 16.5528 12.8054 15.9504C12.8054 15.3481 12.3148 14.8574 11.7124 14.8574C11.11 14.8574 10.6194 15.3481 10.6194 15.9504C10.6194 16.5528 11.11 17.0435 11.7124 17.0435Z" fill="currentColor"/>
                                    <path d="M11.7083 12.8194C12.2093 12.8194 12.6174 12.4113 12.6174 11.9104V7.83643C12.6174 7.33544 12.2093 6.92737 11.7083 6.92737C11.2073 6.92737 10.7993 7.33544 10.7993 7.83643V11.9104C10.7993 12.4113 11.2073 12.8194 11.7083 12.8194Z" fill="currentColor"/>
                                </svg>
                                <div>{{ __('message.exclusive_offer_single_active_notice') }}</div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                {{ html()->label(__('message.title') . ' <span class="text-danger">*</span>', 'title')->class('form-control-label') }}
                                {{ html()->text('title')->placeholder(__('message.title'))->class('form-control')->attribute('required', 'required') }}
                            </div>
                            <div class="form-group col-md-6">
                                {{ html()->label(__('message.button_text'), 'button_text')->class('form-control-label') }}
                                {{ html()->text('button_text')->placeholder(__('message.button_text'))->class('form-control') }}
                            </div>
                            <div class="form-group col-md-12">
                                {{ html()->label(__('message.description'), 'description')->class('form-control-label') }}
                                {{ html()->textarea('description')->placeholder(__('message.description'))->class('form-control')->rows(4) }}
                            </div>
                            <div class="form-group col-md-6">
                                {{ html()->label(__('message.redirect_url'), 'button_url')->class('form-control-label') }}
                                {{ html()->input('url', 'button_url')->placeholder(__('message.redirect_url'))->class('form-control') }}
                            </div>
                            <div class="form-group col-md-6">
                                {{ html()->label(__('message.status') . ' <span class="text-danger">*</span>', 'status')->class('form-control-label') }}
                                {{ html()->select('status', ['active' => __('message.active'), 'inactive' => __('message.inactive')], old('status'))->class('form-control select2js')->attribute('required', 'required') }}
                            </div>
                            <div class="form-group col-md-6">
                                <label class="form-control-label" for="offer_image">{{ __('message.exclusive_offer_image') }} <span class="text-danger">*</span></label>
                                <div>
                                    <input class="form-control file-input" type="file" name="offer_image" accept="image/*">
                                </div>
                                <small class="form-text text-muted">{{ __('message.image_png_jpg') }}</small>
                            </div>
                            @if(isset($id) && getMediaFileExit($data, 'exclusive_offer_image'))
                                <div class="col-md-2 mb-2 position-relative">
                                    <img id="offer_image_preview" src="{{ getSingleMedia($data, 'exclusive_offer_image') }}" alt="exclusive-offer-image" class="avatar-100 mt-1">
                                    <a class="text-danger remove-file"
                                       href="{{ route('remove.file', ['id' => $data->id, 'type' => 'exclusive_offer_image']) }}"
                                       data--submit='confirm_form'
                                       data--confirmation='true'
                                       data--ajax='true'
                                       data-toggle='tooltip'
                                       title='{{ __("message.remove_file_title", ["name" => __("message.image")]) }}'
                                       data-title='{{ __("message.remove_file_title", ["name" => __("message.image")]) }}'
                                       data-message='{{ __("message.remove_file_msg") }}'>
                                        <svg width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path opacity="0.4" d="M16.34 1.99976H7.67C4.28 1.99976 2 4.37976 2 7.91976V16.0898C2 19.6198 4.28 21.9998 7.67 21.9998H16.34C19.73 21.9998 22 19.6198 22 16.0898V7.91976C22 4.37976 19.73 1.99976 16.34 1.99976Z" fill="currentColor"></path>
                                            <path d="M15.0158 13.7703L13.2368 11.9923L15.0148 10.2143C15.3568 9.87326 15.3568 9.31826 15.0148 8.97726C14.6728 8.63326 14.1198 8.63426 13.7778 8.97626L11.9988 10.7543L10.2198 8.97426C9.87782 8.63226 9.32382 8.63426 8.98182 8.97426C8.64082 9.31626 8.64082 9.87126 8.98182 10.2123L10.7618 11.9923L8.98582 13.7673C8.64382 14.1093 8.64382 14.6643 8.98582 15.0043C9.15682 15.1763 9.37982 15.2613 9.60382 15.2613C9.82882 15.2613 10.0518 15.1763 10.2228 15.0053L11.9988 13.2293L13.7788 15.0083C13.9498 15.1793 14.1728 15.2643 14.3968 15.2643C14.6208 15.2643 14.8448 15.1783 15.0158 15.0083C15.3578 14.6663 15.3578 14.1123 15.0158 13.7703Z" fill="currentColor"></path>
                                        </svg>
                                    </a>
                                </div>
                            @endif
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
