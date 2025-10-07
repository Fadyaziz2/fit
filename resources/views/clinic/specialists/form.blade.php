<x-app-layout>
    <div>
        <?php $specialist = $specialist ?? null; ?>
        <form method="POST" action="{{ $specialist ? route('clinic.specialists.update', $specialist) : route('clinic.specialists.store') }}">
            @csrf
            @if($specialist)
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
                                <a href="{{ route('clinic.specialists.index') }}" class="btn btn-sm btn-primary">{{ __('message.back') }}</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('message.name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" value="{{ old('name', $specialist->name ?? '') }}" required>
                                    @error('name')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('message.branch') }} <span class="text-danger">*</span></label>
                                    @php
                                        $branchSelection = old('branch_ids', $specialist?->branches?->pluck('id')->toArray() ?? []);
                                        $selectedBranches = is_array($branchSelection) ? array_map('strval', $branchSelection) : [];
                                    @endphp
                                    <select name="branch_ids[]" class="form-select" multiple required data-placeholder="{{ __('message.select_name', ['select' => __('message.branch')]) }}">
                                        @foreach($branches as $id => $name)
                                            <option value="{{ $id }}" {{ in_array((string) $id, $selectedBranches, true) ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">{{ __('message.specialist_branch_hint') }}</small>
                                    @error('branch_ids')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                    @error('branch_ids.*')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('message.contact_number') }}</label>
                                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $specialist->phone ?? '') }}">
                                    @error('phone')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('message.email') }}</label>
                                    <input type="email" name="email" class="form-control" value="{{ old('email', $specialist->email ?? '') }}">
                                    @error('email')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('message.specialist') }}</label>
                                    <input type="text" name="specialty" class="form-control" value="{{ old('specialty', $specialist->specialty ?? '') }}">
                                    @error('specialty')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">{{ __('message.notes') }}</label>
                                    <textarea name="notes" class="form-control" rows="3">{{ old('notes', $specialist->notes ?? '') }}</textarea>
                                    @error('notes')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1" name="is_active" id="is_active" {{ old('is_active', $specialist->is_active ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">{{ __('message.active') }}</label>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 text-end">
                                <button type="submit" class="btn btn-primary">{{ $specialist ? __('message.update') : __('message.save') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
