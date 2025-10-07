<x-app-layout>
    <div>
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                        <div class="card-action">
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#manualAppointmentModal">
                                {{ __('message.add_manual_appointment') }}
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('message.user') }}</th>
                                        <th>{{ __('message.specialist') }}</th>
                                        <th>{{ __('message.branch') }}</th>
                                        <th>{{ __('message.day') }}</th>
                                        <th>{{ __('message.start_time') }}</th>
                                        <th>{{ __('message.type') }}</th>
                                        <th>{{ __('message.status') }}</th>
                                        <th class="text-end">{{ __('message.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($appointments as $appointment)
                                        <tr>
                                            <td>{{ $appointment->id }}</td>
                                            <td>{{ $appointment->user?->display_name ?? $appointment->user?->email }}</td>
                                            <td>{{ $appointment->specialist?->name ?? '-' }}</td>
                                            <td>{{ $appointment->specialist?->branch?->name ?? '-' }}</td>
                                            <td>{{ $appointment->appointment_date }}</td>
                                            <td>{{ substr($appointment->appointment_time, 0, 5) }}</td>
                                            <td>
                                                @php
                                                    $typeLabels = [
                                                        'regular' => __('message.regular_appointment'),
                                                        'free' => __('message.free_appointment'),
                                                        'manual_free' => __('message.manual_free_appointment'),
                                                    ];
                                                @endphp
                                                <span class="badge bg-info text-dark">{{ $typeLabels[$appointment->type] ?? ucfirst($appointment->type) }}</span>
                                            </td>
                                            <td>
                                                <span class="badge {{ $appointment->status === 'completed' ? 'bg-success' : ($appointment->status === 'cancelled' ? 'bg-danger' : 'bg-secondary') }}">
                                                    {{ ucfirst($appointment->status) }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('clinic.appointments.edit', $appointment) }}" class="btn btn-sm btn-outline-primary">{{ __('message.update') }}</a>
                                                @if($appointment->type === 'manual_free' && $appointment->status !== 'completed')
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-success mt-1 convert-manual-free"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#convertManualModal"
                                                        data-appointment-id="{{ $appointment->id }}"
                                                        data-name="{{ $appointment->user?->display_name }}"
                                                        data-phone="{{ $appointment->user?->phone_number }}"
                                                        data-email="{{ $appointment->user?->email }}">
                                                        {{ __('message.convert_to_member') }}
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center">{{ __('message.no_results_found') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $appointments->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="manualAppointmentModal" tabindex="-1" aria-labelledby="manualAppointmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('clinic.appointments.store') }}" id="manual-appointment-form">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="manualAppointmentModalLabel">{{ __('message.add_manual_appointment') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('message.close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">{{ __('message.appointment_type') }} <span class="text-danger">*</span></label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="type" id="manual-type-regular" value="regular" checked>
                                        <label class="form-check-label" for="manual-type-regular">{{ __('message.regular_appointment') }}</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="type" id="manual-type-free" value="manual_free">
                                        <label class="form-check-label" for="manual-type-free">{{ __('message.manual_free_appointment') }}</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 manual-regular-section">
                                <label class="form-label">{{ __('message.user') }} <span class="text-danger">*</span></label>
                                <select name="user_id" id="manual-user" class="form-select select2js" data-placeholder="{{ __('message.select_name', ['select' => __('message.user')]) }}" data-dropdown-parent="#manualAppointmentModal">
                                    <option value="">{{ __('message.select_name', ['select' => __('message.user')]) }}</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->display_name ?? $user->email }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 manual-free-section d-none">
                                <label class="form-label">{{ __('message.full_name') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="manual_name" id="manual-name" placeholder="{{ __('message.full_name') }}">
                            </div>
                            <div class="col-md-6 manual-free-section d-none">
                                <label class="form-label">{{ __('message.contact_number') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="manual_phone" id="manual-phone" placeholder="{{ __('message.contact_number') }}">
                            </div>
                            <div class="col-md-6 manual-free-section d-none">
                                <label class="form-label">{{ __('message.branch') }} <span class="text-danger">*</span></label>
                                <select class="form-select" id="manual-branch">
                                    <option value="">{{ __('message.select_name', ['select' => __('message.branch')]) }}</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('message.specialist') }} <span class="text-danger">*</span></label>
                                <select name="specialist_id" id="manual-specialist" class="form-select" data-placeholder="{{ __('message.select_name', ['select' => __('message.specialist')]) }}">
                                    <option value="">{{ __('message.select_name', ['select' => __('message.specialist')]) }}</option>
                                    @foreach($specialists as $specialist)
                                        <option value="{{ $specialist->id }}" data-branch="{{ $specialist->branch_id }}">{{ $specialist->name }} - {{ $specialist->branch?->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('message.day') }} <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="appointment_date" id="manual-date" min="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('message.start_time') }} <span class="text-danger">*</span></label>
                                <select name="appointment_time" id="manual-time" class="form-select">
                                    <option value="">{{ __('message.select_name', ['select' => __('message.start_time')]) }}</option>
                                </select>
                                <small class="text-muted d-block" id="manual-time-helper"></small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('message.cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('message.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="convertManualModal" tabindex="-1" aria-labelledby="convertManualModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="" id="convert-manual-form">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="convertManualModalLabel">{{ __('message.convert_to_member') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('message.close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="appointment_id" id="convert-appointment-id">
                        <div class="mb-3">
                            <label class="form-label">{{ __('message.full_name') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="full_name" id="convert-name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('message.email') }} <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" id="convert-email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('message.contact_number') }}</label>
                            <input type="text" class="form-control" name="phone" id="convert-phone">
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('message.password') }} <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="password" id="convert-password" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('message.confirm_password') }} <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="password_confirmation" id="convert-password-confirm" required>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label">{{ __('message.package') }} <span class="text-danger">*</span></label>
                            <select name="package_id" id="convert-package" class="form-select" required>
                                <option value="">{{ __('message.select_name', ['select' => __('message.package')]) }}</option>
                                @foreach($packages as $package)
                                    <option value="{{ $package->id }}">{{ $package->name }} - {{ number_format($package->price, 2) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('message.cancel') }}</button>
                        <button type="submit" class="btn btn-success">{{ __('message.convert_to_member') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

@push('scripts')
    <script>
        (function($) {
            'use strict';

            const manualModal = document.getElementById('manualAppointmentModal');
            const convertModal = document.getElementById('convertManualModal');
            const appointmentUrl = "{{ route('clinic.appointments.available_slots') }}";

            function toggleManualSections(type) {
                const freeSections = document.querySelectorAll('.manual-free-section');
                const regularSections = document.querySelectorAll('.manual-regular-section');

                freeSections.forEach(section => section.classList.toggle('d-none', type !== 'manual_free'));
                regularSections.forEach(section => section.classList.toggle('d-none', type === 'manual_free'));

                if (type === 'manual_free') {
                    document.getElementById('manual-user').value = '';
                } else {
                    document.getElementById('manual-name').value = '';
                    document.getElementById('manual-phone').value = '';
                    document.getElementById('manual-branch').value = '';
                }
            }

            function filterSpecialists() {
                const branchId = document.getElementById('manual-branch').value;
                const specialistSelect = document.getElementById('manual-specialist');

                Array.from(specialistSelect.options).forEach(option => {
                    if (!option.value) {
                        option.hidden = false;
                        return;
                    }
                    const optionBranch = option.getAttribute('data-branch');
                    option.hidden = branchId && optionBranch !== branchId;
                    if (option.hidden && option.selected) {
                        specialistSelect.value = '';
                        $('#manual-specialist').trigger('change');
                    }
                });
            }

            const timeSelect = document.getElementById('manual-time');
            const helper = document.getElementById('manual-time-helper');
            const messages = {
                placeholder: "{{ __('message.select_name', ['select' => __('message.start_time')]) }}",
                loading: "{{ __('message.loading') }}",
                noSlots: "{{ __('message.no_slots_available') }}",
                noSchedule: "{{ __('message.no_schedule_for_day') }}",
                error: "{{ __('message.error_fetching_slots') }}",
                workingHours: "{{ __('message.working_hours_for_day', ['range' => '__RANGE__']) }}",
            };

            function formatWorkingRanges(ranges) {
                if (!Array.isArray(ranges) || !ranges.length) {
                    return '';
                }

                const formatted = ranges
                    .map(range => {
                        if (!range || !range.start || !range.end) {
                            return null;
                        }
                        return `${range.start} - ${range.end}`;
                    })
                    .filter(Boolean);

                if (!formatted.length) {
                    return '';
                }

                return messages.workingHours.replace('__RANGE__', formatted.join(' | '));
            }

            function setTimeMessage(message, helperText = '') {
                if (!timeSelect) {
                    return;
                }

                timeSelect.innerHTML = `<option value="">${message}</option>`;
                if (helper) {
                    helper.textContent = helperText;
                }
            }

            function setTimeOptions(slots, helperText = '') {
                if (!timeSelect) {
                    return;
                }

                timeSelect.innerHTML = `<option value="">${messages.placeholder}</option>`;

                slots.forEach(function(slot) {
                    const option = document.createElement('option');
                    option.value = slot.time;
                    option.textContent = slot.time;
                    timeSelect.appendChild(option);
                });

                if (helper) {
                    helper.textContent = helperText;
                }
            }

            function resetTimeSelect(message = messages.placeholder, helperText = '') {
                setTimeMessage(message, helperText);
            }

            function fetchSlots() {
                const specialistId = document.getElementById('manual-specialist').value;
                const date = document.getElementById('manual-date').value;

                if (!specialistId || !date) {
                    resetTimeSelect("{{ __('message.select_name', ['select' => __('message.start_time')]) }}");
                    return;
                }

                $('#manual-time').prop('disabled', true);
                $('#manual-time-helper').text(messages.loading);

                $.get(appointmentUrl, { specialist_id: specialistId, date: date })
                    .done(function(response) {
                        const slots = response && Array.isArray(response.slots) ? response.slots : [];
                        const availableSlots = slots.filter(function(slot) {
                            return slot && slot.available;
                        });
                        const select = document.getElementById('manual-time');
                        select.innerHTML = '';

                        if (!availableSlots.length) {
                            resetTimeSelect(slots.length ? "{{ __('message.no_slots_available') }}" : "{{ __('message.error_fetching_slots') }}");
                            return;
                        }

                        select.innerHTML = `<option value="">{{ __('message.select_name', ['select' => __('message.start_time')]) }}</option>`;
                        availableSlots.forEach(function(slot) {
                            const option = document.createElement('option');
                            option.value = slot.time;
                            option.textContent = slot.time;
                            select.appendChild(option);
                        });
                        document.getElementById('manual-time-helper').textContent = '';
                    })
                    .fail(function() {
                        setTimeMessage(messages.error);
                    })
                    .always(function() {
                        $('#manual-time').prop('disabled', false);
                    });
            }

            if (manualModal) {
                manualModal.addEventListener('shown.bs.modal', function() {
                    $('#manual-user').trigger('change');
                    toggleManualSections(document.querySelector('input[name="type"]:checked').value);
                    resetTimeSelect("{{ __('message.select_name', ['select' => __('message.start_time')]) }}");
                });
            }

            document.querySelectorAll('input[name="type"]').forEach(function(input) {
                input.addEventListener('change', function() {
                    toggleManualSections(this.value);
                    fetchSlots();
                });
            });

            $('#manual-branch').on('change', function() {
                filterSpecialists();
                resetTimeSelect();
                fetchSlots();
            });

            $('#manual-specialist').on('change', function() {
                fetchSlots();
            });

            $('#manual-date').on('change', function() {
                fetchSlots();
            });

            $('#manual-time').on('change', function() {
                document.getElementById('manual-time-helper').textContent = '';
            });

            $('#manual-appointment-form').on('submit', function(event) {
                if (document.querySelector('input[name="type"]:checked').value === 'manual_free') {
                    if (!$('#manual-branch').val()) {
                        event.preventDefault();
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __('message.opps') }}',
                            text: '{{ __('message.manual_branch_required') }}',
                            confirmButtonColor: 'var(--bs-primary)'
                        });
                        return false;
                    }
                }
                return true;
            });

            document.querySelectorAll('.convert-manual-free').forEach(function(button) {
                button.addEventListener('click', function() {
                    const appointmentId = this.dataset.appointmentId;
                    const action = `{{ route('clinic.appointments.convert', ['appointment' => '__id__']) }}`.replace('__id__', appointmentId);

                    document.getElementById('convert-manual-form').setAttribute('action', action);
                    document.getElementById('convert-appointment-id').value = appointmentId;
                    document.getElementById('convert-name').value = this.dataset.name || '';
                    document.getElementById('convert-phone').value = this.dataset.phone || '';
                    const email = this.dataset.email || '';
                    document.getElementById('convert-email').value = email.endsWith('@manual.local') ? '' : email;
                });
            });

            if (convertModal) {
                convertModal.addEventListener('hidden.bs.modal', function() {
                    document.getElementById('convert-manual-form').reset();
                });
            }
        })(jQuery);
    </script>
@endpush
