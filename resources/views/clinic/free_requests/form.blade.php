<x-app-layout>
    @php
        $initialAppointmentTime = old('appointment_time', optional($freeRequest->appointment)->appointment_time ? substr(optional($freeRequest->appointment)->appointment_time, 0, 5) : '');
    @endphp
    <style>
        .slot-button {
            min-width: 72px;
            min-height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            border: 1px solid var(--bs-primary);
            color: var(--bs-primary);
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            padding: 0.5rem 0.75rem;
            background-color: #fff;
        }

        .slot-button:hover {
            background-color: rgba(13, 110, 253, 0.08);
        }

        .slot-button.active,
        .slot-button:focus {
            background-color: var(--bs-primary);
            color: #fff;
        }

        .slot-button.slot-disabled,
        .slot-button.slot-disabled:hover {
            border-color: var(--bs-gray-400);
            color: var(--bs-gray-500);
            background-color: var(--bs-gray-200);
            cursor: not-allowed;
        }
    </style>
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
                                    <input type="hidden" name="appointment_time" id="appointment_time_input" value="{{ $initialAppointmentTime }}">
                                    <div id="available-slots" class="d-flex flex-wrap gap-2">
                                        <span class="text-muted small">{{ __('Select a specialist and date to view available slots.') }}</span>
                                    </div>
                                    <div class="mt-2">
                                        <span class="small text-muted">{{ __('Selected time:') }} <span id="selected-slot-label" class="fw-semibold">{{ $initialAppointmentTime ?: '—' }}</span></span>
                                    </div>
                                    @error('appointment_time')
                                        <small class="text-danger d-block mt-1">{{ $message }}</small>
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

@push('scripts')
    <script>
        (function () {
            const dateInput = document.querySelector('input[name="appointment_date"]');
            const specialistSelect = document.querySelector('select[name="specialist_id"]');
            const slotsContainer = document.getElementById('available-slots');
            const selectedTimeInput = document.getElementById('appointment_time_input');
            const selectedTimeLabel = document.getElementById('selected-slot-label');
            const fetchUrl = "{{ route('clinic.free_requests.available_slots', [], false) }}";

            const texts = {
                selectSpecialistAndDate: "{{ __('Select a specialist and date to view available slots.') }}",
                noSlots: "{{ __('No slots available for the selected date.') }}",
                loading: "{{ __('Loading available slots...') }}",
            };

            const setSelectedTime = (value) => {
                selectedTimeInput.value = value || '';
                if (selectedTimeLabel) {
                    selectedTimeLabel.textContent = value || '—';
                }
            };

            const clearSlots = (message) => {
                slotsContainer.innerHTML = '';
                const span = document.createElement('span');
                span.className = 'text-muted small';
                span.textContent = message;
                slotsContainer.appendChild(span);
            };

            const updateActiveState = () => {
                const buttons = slotsContainer.querySelectorAll('.slot-button');
                buttons.forEach((button) => {
                    if (button.dataset.time === selectedTimeInput.value) {
                        button.classList.add('active');
                    } else {
                        button.classList.remove('active');
                    }
                });
            };

            const renderSlots = (slots) => {
                slotsContainer.innerHTML = '';

                if (!slots.length) {
                    clearSlots(texts.noSlots);
                    return;
                }

                slots.forEach((slot) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'slot-button' + (slot.is_available ? '' : ' slot-disabled');
                    button.textContent = slot.time;
                    button.dataset.time = slot.time;

                    if (!slot.is_available) {
                        button.disabled = true;
                    }

                    button.addEventListener('click', () => {
                        if (button.disabled) {
                            return;
                        }
                        setSelectedTime(slot.time);
                        updateActiveState();
                    });

                    slotsContainer.appendChild(button);
                });

                updateActiveState();
            };

            const fetchSlots = () => {
                if (!dateInput.value || !specialistSelect.value) {
                    setSelectedTime(selectedTimeInput.value);
                    clearSlots(texts.selectSpecialistAndDate);
                    return;
                }

                clearSlots(texts.loading);

                const params = new URLSearchParams({
                    specialist_id: specialistSelect.value,
                    date: dateInput.value,
                });

                fetch(`${fetchUrl}?${params.toString()}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                })
                    .then((response) => {
                        if (!response.ok) {
                            throw new Error(`Request failed with status ${response.status}`);
                        }
                        return response.json();
                    })
                    .then((data) => {
                        renderSlots(data.slots || []);
                    })
                    .catch((error) => {
                        console.error('Failed to load available slots:', error);
                        clearSlots("{{ __('Unable to load slots. Please try again later.') }}");
                    });
            };

            if (dateInput && specialistSelect && slotsContainer) {
                dateInput.addEventListener('change', () => {
                    setSelectedTime('');
                    fetchSlots();
                });
                specialistSelect.addEventListener('change', () => {
                    setSelectedTime('');
                    fetchSlots();
                });

                if (selectedTimeInput.value) {
                    setSelectedTime(selectedTimeInput.value);
                }

                fetchSlots();
            }
        })();
    </script>
@endpush
