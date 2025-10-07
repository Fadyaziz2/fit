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
        (function ($) {
            'use strict';

            const manualModal = document.getElementById('manualAppointmentModal');
            const convertModal = document.getElementById('convertManualModal');
            const manualForm = document.getElementById('manual-appointment-form');
            const appointmentUrl = "{{ route('clinic.appointments.available_slots') }}";

            const manualUser = manualForm ? manualForm.querySelector('#manual-user') : null;
            const manualName = manualForm ? manualForm.querySelector('#manual-name') : null;
            const manualPhone = manualForm ? manualForm.querySelector('#manual-phone') : null;
            const manualBranch = manualForm ? manualForm.querySelector('#manual-branch') : null;
            const specialistSelect = manualForm ? manualForm.querySelector('#manual-specialist') : null;
            const dateInput = manualForm ? manualForm.querySelector('#manual-date') : null;
            const timeSelect = manualForm ? manualForm.querySelector('#manual-time') : null;
            const helper = manualForm ? manualForm.querySelector('#manual-time-helper') : null;

            const messages = {
                placeholder: "{{ __('message.select_name', ['select' => __('message.start_time')]) }}",
                loading: "{{ __('message.loading') }}",
                noSlots: "{{ __('message.no_slots_available') }}",
                noSchedule: "{{ __('message.no_schedule_for_day') }}",
                error: "{{ __('message.error_fetching_slots') }}",
                workingHours: "{{ __('message.working_hours_for_day', ['range' => '__RANGE__']) }}",
            };

            function resetManualFreeFields() {
                if (manualName) {
                    manualName.value = '';
                    manualName.removeAttribute('required');
                }

                if (manualPhone) {
                    manualPhone.value = '';
                    manualPhone.removeAttribute('required');
                }

                if (manualBranch) {
                    manualBranch.value = '';
                    manualBranch.removeAttribute('required');
                    manualBranch.setAttribute('disabled', 'disabled');
                }
            }

            function toggleManualSections(type) {
                if (!manualForm) {
                    return;
                }

                const isManualFree = type === 'manual_free';
                const regularSections = manualForm.querySelectorAll('.manual-regular-section');
                const freeSections = manualForm.querySelectorAll('.manual-free-section');

                regularSections.forEach(function (section) {
                    section.classList.toggle('d-none', isManualFree);
                });

                freeSections.forEach(function (section) {
                    section.classList.toggle('d-none', !isManualFree);
                });

                if (manualUser) {
                    const $manualUser = $(manualUser);

                    if (isManualFree) {
                        $manualUser.val('').trigger('change');
                        $manualUser.prop('disabled', true);
                    } else {
                        $manualUser.prop('disabled', false).trigger('change');
                    }

                    const select2Container = $manualUser.next('.select2-container, .select2');
                    if (select2Container.length) {
                        select2Container.toggleClass('d-none', isManualFree);
                    }
                }

                if (isManualFree) {
                    if (manualName) {
                        manualName.setAttribute('required', 'required');
                    }
                    if (manualPhone) {
                        manualPhone.setAttribute('required', 'required');
                    }
                    if (manualBranch) {
                        manualBranch.removeAttribute('disabled');
                        manualBranch.setAttribute('required', 'required');
                    }
                } else {
                    resetManualFreeFields();
                }

                filterSpecialists();
            }

            function filterSpecialists() {
                if (!manualForm || !specialistSelect) {
                    return;
                }

                const typeInput = manualForm.querySelector('input[name="type"]:checked');
                const branchId = typeInput && typeInput.value === 'manual_free' && manualBranch ? manualBranch.value : '';

                Array.from(specialistSelect.options).forEach(function (option) {
                    if (!option.value) {
                        option.hidden = false;
                        return;
                    }

                    const matchesBranch = !branchId || option.getAttribute('data-branch') === branchId;
                    option.hidden = !matchesBranch;

                    if (option.hidden && option.selected) {
                        specialistSelect.value = '';
                        $('#manual-specialist').trigger('change');
                    }
                });
            }

            function setTimeMessage(message, helperText) {
                if (helperText === undefined) {
                    helperText = '';
                }

                if (!timeSelect) {
                    return;
                }

                timeSelect.innerHTML = '<option value="">' + message + '</option>';
                timeSelect.disabled = message === messages.loading;

                if (helper) {
                    helper.textContent = helperText;
                }
            }

            function formatWorkingRanges(ranges) {
                if (!Array.isArray(ranges) || !ranges.length) {
                    return '';
                }

                const formatted = ranges
                    .map(function (range) {
                        if (!range || !range.start || !range.end) {
                            return null;
                        }

                        return range.start + ' - ' + range.end;
                    })
                    .filter(function (value) {
                        return Boolean(value);
                    });

                if (!formatted.length) {
                    return '';
                }

                return messages.workingHours.replace('__RANGE__', formatted.join(' | '));
            }

            function slotIsAvailable(slot) {
                if (!slot || typeof slot !== 'object') {
                    return true;
                }

                const marker = slot.available !== undefined ? slot.available
                    : (slot.is_available !== undefined ? slot.is_available : slot.isAvailable);

                if (marker === undefined || marker === null) {
                    return true;
                }

                if (typeof marker === 'string') {
                    return ['1', 'true', 'yes'].indexOf(marker.toLowerCase()) !== -1;
                }

                return Boolean(marker);
            }

            function optionTime(slot) {
                if (!slot) {
                    return '';
                }

                if (typeof slot === 'string') {
                    return slot;
                }

                return slot.time || slot.start_time || slot.startTime || '';
            }

            function populateSlots(slots, helperText) {
                if (helperText === undefined) {
                    helperText = '';
                }

                if (!timeSelect) {
                    return;
                }

                timeSelect.disabled = false;
                timeSelect.innerHTML = '<option value="">' + messages.placeholder + '</option>';

                slots.forEach(function (slot) {
                    const value = optionTime(slot);
                    if (!value) {
                        return;
                    }

                    const option = document.createElement('option');
                    option.value = value;
                    option.textContent = value;

                    if (!slotIsAvailable(slot)) {
                        option.disabled = true;
                    }

                    timeSelect.appendChild(option);
                });

                if (helper) {
                    helper.textContent = helperText;
                }
            }

            function normaliseSlots(rawSlots) {
                if (!rawSlots) {
                    return [];
                }

                if (Array.isArray(rawSlots)) {
                    return rawSlots;
                }

                return Object.values(rawSlots);
            }

            function fetchSlots() {
                if (!specialistSelect || !dateInput) {
                    return;
                }

                const specialistId = specialistSelect.value;
                const appointmentDate = dateInput.value;

                if (!specialistId || !appointmentDate) {
                    setTimeMessage(messages.placeholder);
                    return;
                }

                setTimeMessage(messages.loading);

                $.get(appointmentUrl, { specialist_id: specialistId, date: appointmentDate })
                    .done(function (response) {
                        const slots = normaliseSlots(response && response.slots);
                        const workingRanges = response && response.meta && Array.isArray(response.meta.working_ranges)
                            ? response.meta.working_ranges
                            : [];
                        const helperText = formatWorkingRanges(workingRanges);

                        if (!slots.length) {
                            setTimeMessage(messages.noSchedule, helperText);
                            return;
                        }

                        const available = slots.filter(function (slot) {
                            return slotIsAvailable(slot);
                        });

                        if (!available.length) {
                            populateSlots(slots, helperText ? helperText + ' — ' + messages.noSlots : messages.noSlots);
                            return;
                        }

                        populateSlots(available, helperText);
                    })
                    .fail(function () {
                        setTimeMessage(messages.error);
                    });
            }

            if (manualForm) {
                $(manualForm).on('submit', function (event) {
                    const typeInput = manualForm.querySelector('input[name="type"]:checked');
                    const selectedType = typeInput ? typeInput.value : 'regular';

                    if (selectedType === 'manual_free' && manualBranch && !manualBranch.value) {
                        event.preventDefault();
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __('message.opps') }}',
                            text: '{{ __('message.manual_branch_required') }}',
                            confirmButtonColor: 'var(--bs-primary)'
                        });
                        return false;
                    }

                    return true;
                });

                manualForm.querySelectorAll('input[name="type"]').forEach(function (input) {
                    input.addEventListener('change', function (event) {
                        toggleManualSections(event.target.value);
                        setTimeMessage(messages.placeholder);
                        fetchSlots();
                    });
                });
            }

            if (manualBranch) {
                $(manualBranch).on('change', function () {
                    filterSpecialists();
                    setTimeMessage(messages.placeholder);
                    fetchSlots();
                });
            }

            if (specialistSelect) {
                $(specialistSelect).on('change', function () {
                    setTimeMessage(messages.placeholder);
                    fetchSlots();
                });
            }

            if (dateInput) {
                $(dateInput).on('change', fetchSlots);
            }

            if (timeSelect) {
                $(timeSelect).on('change', function () {
                    if (helper) {
                        helper.textContent = '';
                    }
                });
            }

            if (manualModal) {
                manualModal.addEventListener('shown.bs.modal', function () {
                    const typeInput = manualForm ? manualForm.querySelector('input[name="type"]:checked') : null;
                    const selectedType = typeInput ? typeInput.value : 'regular';
                    toggleManualSections(selectedType);
                    filterSpecialists();
                    setTimeMessage(messages.placeholder);
                });

                manualModal.addEventListener('hidden.bs.modal', function () {
                    if (manualForm) {
                        manualForm.reset();
                    }

                    if (manualUser) {
                        $(manualUser).val('').trigger('change');
                        $(manualUser).prop('disabled', false);
                        const select2Container = $(manualUser).next('.select2-container, .select2');
                        if (select2Container.length) {
                            select2Container.removeClass('d-none');
                        }
                    }

                    resetManualFreeFields();
                    setTimeMessage(messages.placeholder);

                    if (helper) {
                        helper.textContent = '';
                    }
                });
            }

            document.querySelectorAll('.convert-manual-free').forEach(function (button) {
                button.addEventListener('click', function () {
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
                convertModal.addEventListener('hidden.bs.modal', function () {
                    document.getElementById('convert-manual-form').reset();
                });
            }
        })(jQuery);
    </script>
@endpush
