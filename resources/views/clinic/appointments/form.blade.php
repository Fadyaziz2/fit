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
                                    <select name="status" class="form-select" id="appointment-status" required>
                                        @foreach($statusOptions as $value => $label)
                                            <option value="{{ $value }}" @selected(old('status', $appointment->status) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('status')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            @php
                                $showStatusComment = old('status', $appointment->status) === 'other';
                            @endphp
                            <div class="mb-3 {{ $showStatusComment ? '' : 'd-none' }}" data-status-comment>
                                <label class="form-label">{{ __('message.notes') }}</label>
                                <textarea name="admin_comment" class="form-control" rows="3" placeholder="{{ __('message.appointment_status_other_placeholder') }}">{{ old('admin_comment', $appointment->admin_comment) }}</textarea>
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

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const statusSelect = document.getElementById('appointment-status');
            const commentWrapper = document.querySelector('[data-status-comment]');
            const commentField = commentWrapper ? commentWrapper.querySelector('textarea[name="admin_comment"]') : null;

            if (!statusSelect || !commentWrapper || !commentField) {
                return;
            }

            const toggleComment = () => {
                const isOther = statusSelect.value === 'other';
                commentWrapper.classList.toggle('d-none', !isOther);
                commentField.toggleAttribute('required', isOther);
            };

            toggleComment();
            statusSelect.addEventListener('change', toggleComment);
        });
    </script>
@endpush
