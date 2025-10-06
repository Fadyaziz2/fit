<x-app-layout>
    <div>
        <form method="POST" action="{{ route('clinic.appointments.update', $appointment) }}">
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
                                <a href="{{ route('clinic.appointments.index') }}" class="btn btn-sm btn-primary">{{ __('message.back') }}</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">{{ __('message.user') }}</label>
                                <input type="text" class="form-control" value="{{ $appointment->user?->display_name ?? $appointment->user?->email }}" disabled>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('message.specialist') }}</label>
                                    <input type="text" class="form-control" value="{{ $appointment->specialist?->name }}" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('message.day') }}</label>
                                    <input type="text" class="form-control" value="{{ $appointment->appointment_date }}" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('message.start_time') }}</label>
                                    <input type="text" class="form-control" value="{{ substr($appointment->appointment_time, 0, 5) }}" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('message.status') }}</label>
                                    <select name="status" class="form-select" required>
                                        <option value="pending" {{ old('status', $appointment->status) === 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="confirmed" {{ old('status', $appointment->status) === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                                        <option value="completed" {{ old('status', $appointment->status) === 'completed' ? 'selected' : '' }}>Completed</option>
                                        <option value="cancelled" {{ old('status', $appointment->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                    </select>
                                    @error('status')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('message.notes') }}</label>
                                <textarea name="admin_comment" class="form-control" rows="3">{{ old('admin_comment', $appointment->admin_comment) }}</textarea>
                                @error('admin_comment')
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
