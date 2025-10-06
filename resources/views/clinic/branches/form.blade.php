<x-app-layout>
    <div>
        <?php $branch = $branch ?? null; ?>
        <form method="POST" action="{{ $branch ? route('clinic.branches.update', $branch) : route('clinic.branches.store') }}">
            @csrf
            @if($branch)
                @method('PUT')
            @endif
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div class="header-title">
                                <h4 class="card-title">{{ $pageTitle }}</h4>
                            </div>
                            <div class="card-action">
                                <a href="{{ route('clinic.branches.index') }}" class="btn btn-sm btn-primary">{{ __('message.back') }}</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('message.name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" value="{{ old('name', $branch->name ?? '') }}" required>
                                    @error('name')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('message.contact_number') }}</label>
                                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $branch->phone ?? '') }}">
                                    @error('phone')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('message.email') }}</label>
                                    <input type="email" name="email" class="form-control" value="{{ old('email', $branch->email ?? '') }}">
                                    @error('email')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">{{ __('message.address') }}</label>
                                    <textarea name="address" class="form-control" rows="3">{{ old('address', $branch->address ?? '') }}</textarea>
                                    @error('address')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="mt-4 text-end">
                                <button type="submit" class="btn btn-primary">{{ $branch ? __('message.update') : __('message.save') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
