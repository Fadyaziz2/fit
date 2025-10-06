<x-app-layout>
    <div>
        <form method="POST" action="{{ route('clinic.free_requests.update', $freeRequest) }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div class="header-title">
                                <h4 class="card-title">{{ $pageTitle }}</h4>
                            </div>
                            <div class="card-action">
                                <a href="{{ route('clinic.free_requests.index') }}" class="btn btn-sm btn-primary">{{ __('message.back') }}</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">{{ __('message.user') }}</label>
                                <input type="text" class="form-control" value="{{ $freeRequest->user?->display_name ?? $freeRequest->user?->email }}" disabled>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('message.branch') }}</label>
                                    <input type="text" class="form-control" value="{{ $freeRequest->branch?->name }}" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('message.contact_number') }}</label>
                                    <input type="text" class="form-control" value="{{ $freeRequest->phone }}" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('message.status') }}</label>
                                    <select name="status" class="form-select" required>
                                        <option value="pending" {{ old('status', $freeRequest->status) === 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="converted" {{ old('status', $freeRequest->status) === 'converted' ? 'selected' : '' }}>Converted</option>
                                        <option value="cancelled" {{ old('status', $freeRequest->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                    </select>
                                    @error('status')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('message.specialist') }}</label>
                                    <select name="specialist_id" class="form-select">
                                        <option value="">{{ __('message.select_name', ['select' => __('message.specialist')]) }}</option>
                                        @foreach($specialists as $specialist)
                                            <option value="{{ $specialist->id }}" {{ (string) $specialist->id === (string) old('specialist_id', $freeRequest->specialist_id) ? 'selected' : '' }}>
                                                {{ $specialist->name }} @if($specialist->branch) ({{ $specialist->branch->name }}) @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('specialist_id')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('message.day') }}</label>
                                    <input type="date" name="appointment_date" class="form-control" value="{{ old('appointment_date', optional($freeRequest->appointment)->appointment_date) }}">
                                    @error('appointment_date')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('message.start_time') }}</label>
                                    <input type="time" name="appointment_time" class="form-control" value="{{ old('appointment_time', optional($freeRequest->appointment)->appointment_time ? substr(optional($freeRequest->appointment)->appointment_time,0,5) : '') }}">
                                    @error('appointment_time')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('message.notes') }}</label>
                                <textarea name="admin_notes" class="form-control" rows="3">{{ old('admin_notes', $freeRequest->admin_notes) }}</textarea>
                                @error('admin_notes')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">{{ __('message.update') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
