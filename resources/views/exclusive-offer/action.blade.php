<?php
    $auth_user = auth()->user();
?>

<div class="d-flex align-items-center">
    @if($auth_user && ($auth_user->hasRole('admin') || $auth_user->can('exclusive-offer-edit') || $auth_user->can('exclusive-offer')))
        <a class="btn btn-sm btn-icon btn-success me-2" href="{{ route('exclusive-offer.edit', $id) }}" data-bs-toggle="tooltip"
           title="{{ __('message.update_form_title',['form' => __('message.exclusive_offer') ]) }}">
            <span class="btn-inner">
                <svg width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path opacity="0.4" d="M14.6209 3.51003L5.99989 12.13C5.68989 12.44 5.3899 13.05 5.3299 13.49L5.00989 16.21C4.87989 17.3 5.6799 18.1 6.7699 17.97L9.4899 17.65C9.9299 17.59 10.5399 17.29 10.8399 16.98L19.4609 8.36003C21.0209 6.80003 21.7609 4.99003 19.4609 2.69003C17.1609 0.390031 15.3509 1.13003 13.7909 2.69003Z" fill="currentColor"></path>
                    <path d="M12.3398 5.28998C12.6098 7.04998 13.7798 8.37998 15.5498 8.63998" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    <path opacity="0.4" d="M11 3H5C3 3 2 4 2 6V19C2 21 3 22 5 22H18C20 22 21 21 21 19V13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </span>
        </a>
    @endif
    @if($auth_user && ($auth_user->hasRole('admin') || $auth_user->can('exclusive-offer-delete') || $auth_user->can('exclusive-offer')))
        {{ html()->form('POST', route('exclusive-offer.destroy', $id))->attribute('data--submit', 'exclusive-offer-delete'.$id)->open() }}
        {{ html()->hidden('_method', 'DELETE') }}
            <a class="btn btn-sm btn-icon btn-danger" href="javascript:void(0)" data-bs-toggle="tooltip" data--submit="exclusive-offer-delete{{$id}}"
               data--confirmation="true" data-title="{{ __('message.delete_form_title',['form' => __('message.exclusive_offer') ]) }}"
               data-message="{{ __('message.delete_msg') }}"
               title="{{ __('message.delete_form_title',['form' => __('message.exclusive_offer') ]) }}">
                <span class="btn-inner">
                    <svg width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path opacity="0.4" d="M19.3203 5.76965C19.3203 5.76965 18.1103 5.65965 17.5603 5.59965C17.2003 5.55965 16.8603 5.38965 16.6303 5.10965C16.5403 4.99965 16.4503 4.86965 16.3903 4.73965C16.3203 4.56965 16.2703 4.38965 16.2503 4.19965C16.2103 3.87965 16.1803 3.54965 16.1403 3.21965C16.0303 2.20965 15.2003 1.42965 14.1803 1.42965H9.82027C8.80027 1.42965 7.97027 2.21965 7.86027 3.21965C7.82027 3.54965 7.79027 3.87965 7.75027 4.19965C7.73027 4.37965 7.68027 4.55965 7.60027 4.71965C7.54027 4.85965 7.45027 4.99965 7.35027 5.11965C7.12027 5.38965 6.78027 5.55965 6.43027 5.59965C5.89027 5.65965 4.68027 5.76965 4.68027 5.76965C4.30027 5.80965 4.02027 6.12965 4.06027 6.50965C4.09027 6.84965 4.37027 7.09965 4.72027 7.09965H19.2803C19.6303 7.09965 19.9103 6.84965 19.9403 6.50965C19.9803 6.12965 19.7003 5.80965 19.3203 5.76965Z" fill="currentColor"></path>
                        <path d="M18.0087 8.5C17.6887 8.5 17.4287 8.76 17.4087 9.08L16.9287 15.98C16.8287 17.26 16.6887 19.04 13.4787 19.04H10.5087C7.29874 19.04 7.15874 17.26 7.05874 15.98L6.57874 9.08C6.55874 8.76 6.29874 8.5 5.97874 8.5C5.65874 8.52 5.39874 8.79 5.41874 9.11L5.89874 16.02C6.01874 17.5 6.21874 20 10.5087 20H13.4787C17.7687 20 17.9687 17.5 18.0887 16.02L18.5687 9.11C18.5987 8.79 18.3487 8.52 18.0087 8.5Z" fill="currentColor"></path>
                        <path d="M13.6599 14.75C13.6599 15.16 13.9899 15.5 14.4099 15.5C14.8199 15.5 15.1599 15.16 15.1599 14.75V11.25C15.1599 10.84 14.8199 10.5 14.4099 10.5C13.9899 10.5 13.6599 10.84 13.6599 11.25V14.75Z" fill="currentColor"></path>
                        <path d="M8.82996 14.75C8.82996 15.16 9.16996 15.5 9.57996 15.5C10 15.5 10.33 15.16 10.33 14.75V11.25C10.33 10.84 9.98996 10.5 9.57996 10.5C9.16996 10.5 8.82996 10.84 8.82996 11.25V14.75Z" fill="currentColor"></path>
                    </svg>
                </span>
            </a>
        {{ html()->form()->close() }}
    @endif
</div>
