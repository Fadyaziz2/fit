<?php
    $auth_user = auth()->user();
?>

<div class="d-flex align-items-center">
    @if($auth_user && $auth_user->can('product-list'))
        <a class="btn btn-sm btn-icon btn-primary" href="{{ route('product-orders.show', $id) }}" data-bs-toggle="tooltip"
            title="{{ __('message.view_form_title', ['form' => __('message.product_order')]) }}">
            <span class="btn-inner">
                <svg class="icon-20" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2 12C3.8 7.2 7.6 4 12 4C16.4 4 20.2 7.2 22 12C20.2 16.8 16.4 20 12 20C7.6 20 3.8 16.8 2 12Z"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    <circle cx="12" cy="12" r="3.5" stroke="currentColor" stroke-width="1.5" />
                </svg>
            </span>
        </a>
    @endif
</div>
