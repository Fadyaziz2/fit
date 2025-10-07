<?php

namespace App\Http\Controllers;

use App\Models\FreeBookingRequest;
use App\Models\Specialist;
use App\Models\SpecialistAppointment;
use App\Models\SpecialistSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClinicFreeBookingRequestController extends Controller
{
    protected function authorizeAccess()
    {
        if (auth()->user()?->user_type !== 'admin') {
            abort(403, __('message.permission_denied_for_account'));
        }
    }

    public function index()
    {
        $this->authorizeAccess();

        $pageTitle = __('message.list_form_title', ['form' => __('message.free_booking_request')]);
        $requests = FreeBookingRequest::with(['user', 'branch', 'specialist'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('clinic.free_requests.index', compact('pageTitle', 'requests'));
    }

    public function edit(FreeBookingRequest $freeRequest)
    {
        $this->authorizeAccess();

        $pageTitle = __('message.update_form_title', ['form' => __('message.free_booking_request')]);
        $specialists = Specialist::with(['branch', 'branches'])->orderBy('name')->get();

        $freeRequest->load('appointment');

        return view('clinic.free_requests.form', compact('pageTitle', 'freeRequest', 'specialists'));
    }

    public function update(Request $request, FreeBookingRequest $freeRequest)
    {
        $this->authorizeAccess();

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
            $date = Carbon::createFromFormat('Y-m-d', $request->appointment_date);
            $time = Carbon::createFromFormat('H:i', $request->appointment_time)->format('H:i:s');

            $scheduleExists = SpecialistSchedule::where('specialist_id', $request->specialist_id)
                ->where('day_of_week', $date->dayOfWeek)
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
                ->whereIn('status', ['pending', 'confirmed', 'completed']);

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
        $this->authorizeAccess();

        $validator = Validator::make($request->all(), [
            'specialist_id' => 'required|exists:specialists,id',
            'date' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $specialist = Specialist::with('schedules')->findOrFail($request->specialist_id);
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
                    ->whereIn('status', ['pending', 'confirmed', 'completed'])
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
