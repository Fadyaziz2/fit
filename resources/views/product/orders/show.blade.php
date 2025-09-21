<x-app-layout :assets="$assets ?? []">
    <div>
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                        <div class="card-action">
                            <a href="{{ route('product-orders.index') }}" class="btn btn-sm btn-primary" role="button">
                                {{ __('message.back') }}
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        @php
                            $product = $productOrder->product;
                            $user = $productOrder->user;
                            $statusLabel = __('message.' . $productOrder->status);
                            if ($statusLabel === 'message.' . $productOrder->status) {
                                $statusLabel = ucfirst(str_replace('_', ' ', $productOrder->status));
                            }

                            $statusClass = match ($productOrder->status) {
                                'completed', 'delivered' => 'primary',
                                'cancelled', 'canceled' => 'danger',
                                'processing' => 'info',
                                default => 'warning',
                            };
                        @endphp
                        <div class="row g-4">
                            <div class="col-md-6">
                                <h6 class="text-muted mb-1">{{ __('message.product') }}</h6>
                                <p class="mb-0 fw-semibold">{{ $product->title ?? '-' }}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-1">{{ __('message.user_name') }}</h6>
                                <p class="mb-0 fw-semibold">
                                    @if($user)
                                        {{ $user->display_name ?? trim(sprintf('%s %s', $user->first_name, $user->last_name)) ?: $user->username ?? $user->email }}
                                    @else
                                        -
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-1">{{ __('message.quantity') }}</h6>
                                <p class="mb-0 fw-semibold">{{ $productOrder->quantity }}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-1">{{ __('message.status') }}</h6>
                                <span class="badge text-capitalize bg-{{ $statusClass }}">{{ $statusLabel }}</span>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-1">{{ __('message.created_at') }}</h6>
                                <p class="mb-0 fw-semibold">{{ optional($productOrder->created_at)->format(config('app.date_time_format', 'd M Y H:i')) }}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-1">{{ __('message.updated_at') }}</h6>
                                <p class="mb-0 fw-semibold">{{ optional($productOrder->updated_at)->format(config('app.date_time_format', 'd M Y H:i')) }}</p>
                            </div>
                        </div>

                        @if($auth_user && $auth_user->can('product-edit'))
                            <hr>
                            {{ html()->modelForm($productOrder, 'PUT', route('product-orders.update', $productOrder->id))->open() }}
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-6">
                                        {{ html()->label(__('message.status') . ' <span class="text-danger">*</span>', 'status')->class('form-control-label') }}
                                        {{ html()->select('status', $statuses, $productOrder->status)->class('form-select')->attribute('required', 'required') }}
                                    </div>
                                    <div class="col-md-6">
                                        {{ html()->submit(__('message.update'))->class('btn btn-primary mt-4') }}
                                    </div>
                                </div>
                            {{ html()->closeModelForm() }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
