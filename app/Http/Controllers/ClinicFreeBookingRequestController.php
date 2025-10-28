<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\FreeBookingRequest;
use App\Models\Specialist;
use App\Models\SpecialistAppointment;
use App\Models\SpecialistSchedule;
use App\Traits\CreatesManualUsers;
use App\Traits\HandlesBranchAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ClinicFreeBookingRequestController extends Controller
{
    use HandlesBranchAccess, CreatesManualUsers;

    protected function authorizeAccess()
    {
        return $this->authorizeBranchAccess();
    }

    public function index()
    {
        $user = $this->authorizeAccess();
        $branchIds = $this->getAccessibleBranchIds($user);

        $pageTitle = __('message.list_form_title', ['form' => __('message.free_booking_request')]);
        $requests = FreeBookingRequest::with(['user', 'branch', 'specialist'])
            ->when($branchIds !== null, function ($query) use ($branchIds) {
                $query->where(function ($innerQuery) use ($branchIds) {
                    $innerQuery->whereIn('branch_id', $branchIds)
                        ->orWhereNull('branch_id');
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20);

        $branches = Branch::query()
            ->orderBy('name')
            ->when($branchIds !== null, function ($query) use ($branchIds) {
                $query->whereIn('id', $branchIds);
            })
            ->get();

        $defaultBranchId = null;
        if ($branchIds !== null && count($branchIds) === 1) {
            $defaultBranchId = $branchIds[0];
        }

        return view('clinic.free_requests.index', compact('pageTitle', 'requests', 'branches', 'defaultBranchId'));
    }

    public function store(Request $request)
    {
        $user = $this->authorizeAccess();
        $branchIds = $this->getAccessibleBranchIds($user);

        $rules = [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
        ];

        if ($branchIds === null) {
            $rules['branch_id'] = ['required', 'exists:branches,id'];
        } else {
            $rules['branch_id'] = [Rule::requiredIf(count($branchIds) > 1), 'nullable', 'exists:branches,id'];
        }

        $data = $request->validate($rules);

        $branchId = isset($data['branch_id']) ? (int) $data['branch_id'] : null;

        if ($branchIds !== null) {
            if (! $branchId && count($branchIds) === 1) {
                $branchId = $branchIds[0];
            }

            $this->assertBranchAccessible($branchId, $branchIds);
        }

        $manualUser = $this->createManualUser($data['full_name'], $data['phone']);

        FreeBookingRequest::create([
            'user_id' => $manualUser->id,
            'branch_id' => $branchId,
            'phone' => $data['phone'],
            'status' => 'pending',
        ]);

        return redirect()
            ->route('clinic.free_requests.index')
            ->withSuccess(__('message.save_form', ['form' => __('message.free_booking_request')]));
    }

    public function edit(FreeBookingRequest $freeRequest)
    {
        $user = $this->authorizeAccess();
        $branchIds = $this->getAccessibleBranchIds($user);

        if ($branchIds !== null && $freeRequest->branch_id && ! in_array((int) $freeRequest->branch_id, $branchIds, true)) {
            abort(403, __('message.permission_denied_for_account'));
        }

        $pageTitle = __('message.update_form_title', ['form' => __('message.free_booking_request')]);
        $specialists = Specialist::with(['branch', 'branches'])
            ->when($branchIds !== null, function ($query) use ($branchIds) {
                $query->where(function ($innerQuery) use ($branchIds) {
                    $innerQuery->whereIn('branch_id', $branchIds)
                        ->orWhereHas('branches', function ($branchQuery) use ($branchIds) {
                            $branchQuery->whereIn('branches.id', $branchIds);
                        });
                });
            })
            ->orderBy('name')
            ->get();

        $freeRequest->load('appointment', 'specialist.branches');

        if ($freeRequest->specialist) {
            $this->ensureSpecialistAccessible($freeRequest->specialist, $branchIds);
        }

        return view('clinic.free_requests.form', compact('pageTitle', 'freeRequest', 'specialists'));
    }

    public function update(Request $request, FreeBookingRequest $freeRequest)
    {
        $user = $this->authorizeAccess();
        $branchIds = $this->getAccessibleBranchIds($user);

        if ($branchIds !== null && $freeRequest->branch_id && ! in_array((int) $freeRequest->branch_id, $branchIds, true)) {
            abort(403, __('message.permission_denied_for_account'));
        }

        $freeRequest->load('specialist.branches', 'appointment');
        if ($freeRequest->specialist) {
            $this->ensureSpecialistAccessible($freeRequest->specialist, $branchIds);
        }

        $data = $request->validate([
            'status' => 'required|in:pending,converted,cancelled',
            'specialist_id' => 'nullable|exists:specialists,id',
            'appointment_date' => 'nullable|date_format:Y-m-d',
            'appointment_time' => 'nullable|date_format:H:i',
            'admin_notes' => 'nullable|string',
        ]);

        $previousSpecialistId = $freeRequest->specialist_id;

        if ($data['status'] === 'converted') {
            $request->validate([
                'specialist_id' => 'required|exists:specialists,id',
                'appointment_date' => 'required|date_format:Y-m-d',
                'appointment_time' => 'required|date_format:H:i',
            ]);

            $specialist = Specialist::with('branches')->findOrFail($request->specialist_id);
            $this->ensureSpecialistAccessible($specialist, $branchIds);
            $date = Carbon::createFromFormat('Y-m-d', $request->appointment_date);
            $time = Carbon::createFromFormat('H:i', $request->appointment_time)->format('H:i:s');

            $scheduleDay = ($date->dayOfWeek + 6) % 7;

            $scheduleExists = SpecialistSchedule::where('specialist_id', $request->specialist_id)
                ->where('day_of_week', $scheduleDay)
                ->where('start_time', '<=', $time)
                ->where('end_time', '>', $time)
                ->exists();

            if (! $scheduleExists) {
                return back()->withErrors(__('message.slot_not_available'))->withInput();
            }

            if ($freeRequest->branch_id && ! $specialist->branches->pluck('id')->contains($freeRequest->branch_id)) {
                return back()->withErrors(__('message.specialist_not_in_branch'))->withInput();
            }

            $alreadyBookedQuery = SpecialistAppointment::where('specialist_id', $request->specialist_id)
                ->where('appointment_date', $date->toDateString())
                ->where('appointment_time', $time)
                ->whereIn('status', SpecialistAppointment::BLOCKING_STATUSES);

            if ($freeRequest->appointment_id) {
                $alreadyBookedQuery->where('id', '!=', $freeRequest->appointment_id);
            }

            $alreadyBooked = $alreadyBookedQuery->exists();

            if ($alreadyBooked) {
                return back()->withErrors(__('message.slot_already_booked'))->withInput();
            }

            $appointmentData = [
                'user_id' => $freeRequest->user_id,
                'specialist_id' => $request->specialist_id,
                'branch_id' => $freeRequest->branch_id,
                'appointment_date' => $date->toDateString(),
                'appointment_time' => $time,
                'status' => $freeRequest->appointment?->status ?? 'pending',
                'type' => 'free',
                'notes' => $request->admin_notes,
            ];

            if ($freeRequest->appointment) {
                $freeRequest->appointment->fill($appointmentData);
                $freeRequest->appointment->save();
                $appointment = $freeRequest->appointment;
            } else {
                $appointment = SpecialistAppointment::create($appointmentData);
            }

            $freeRequest->appointment_id = $appointment->id;
            $freeRequest->specialist_id = $request->specialist_id;

            $userProfile = optional($freeRequest->user)->userProfile;
            if ($userProfile) {
                $userProfile->specialist_id = $request->specialist_id;
                $userProfile->save();
            }
        } else {
            if ($freeRequest->appointment) {
                $freeRequest->appointment->delete();
            }
            $freeRequest->appointment_id = null;
            $freeRequest->specialist_id = null;

            $userProfile = optional($freeRequest->user)->userProfile;
            if ($userProfile && $userProfile->specialist_id === $previousSpecialistId) {
                $userProfile->specialist_id = null;
                $userProfile->save();
            }
        }

        $freeRequest->status = $data['status'];
        $freeRequest->admin_notes = $request->admin_notes;
        $freeRequest->save();

        return redirect()->route('clinic.free_requests.index')->withSuccess(__('message.update_form', ['form' => __('message.free_booking_request')]));
    }

    public function availableSlots(Request $request)
    {
        $user = $this->authorizeAccess();
        $branchIds = $this->getAccessibleBranchIds($user);

        $validator = Validator::make($request->all(), [
            'specialist_id' => 'required|exists:specialists,id',
            'date' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $specialist = Specialist::with(['schedules', 'branches'])->findOrFail($request->specialist_id);
        $this->ensureSpecialistAccessible($specialist, $branchIds);
        $date = Carbon::parse($request->date);

        $weekStart = Carbon::create()->startOfWeek();

        $schedules = $specialist->schedules
            ->filter(function ($schedule) use ($date, $weekStart) {
                $storedDay = (int) $schedule->day_of_week;
                $normalizedDay = $weekStart->copy()->addDays($storedDay)->dayOfWeek;

                return $normalizedDay === $date->dayOfWeek;
            })
            ->sortBy('start_time');

        $slots = [];

        foreach ($schedules as $schedule) {
            $start = Carbon::parse($schedule->start_time);
            $end = Carbon::parse($schedule->end_time);

            if ($end->lessThanOrEqualTo($start)) {
                continue;
            }

            $current = $start->copy();

            while ($current->lt($end)) {
                $slotEnd = $current->copy()->addMinutes($schedule->slot_duration);

                if ($slotEnd->gt($end)) {
                    break;
                }

                $slotTime = $current->format('H:i:s');

                $isBooked = SpecialistAppointment::where('specialist_id', $specialist->id)
                    ->where('appointment_date', $date->toDateString())
                    ->where('appointment_time', $slotTime)
                    ->whereIn('status', SpecialistAppointment::BLOCKING_STATUSES)
                    ->exists();

                $slots[] = [
                    'time' => $current->format('H:i'),
                    'is_available' => ! $isBooked,
                ];

                $current = $slotEnd;
            }
        }

        $slots = collect($slots)
            ->unique('time')
            ->sortBy('time')
            ->values()
            ->all();

        return response()->json([
            'date' => $date->toDateString(),
            'slots' => $slots,
        ]);
    }
}
