<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\BranchResource;
use App\Http\Resources\SpecialistAppointmentResource;
use App\Models\Branch;
use App\Models\FreeBookingRequest;
use App\Models\Specialist;
use App\Models\SpecialistAppointment;
use App\Models\SpecialistSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    public function branches()
    {
        $branches = Branch::orderBy('name')->get();

        return json_custom_response([
            'data' => BranchResource::collection($branches),
        ]);
    }

    public function myBookings(Request $request)
    {
        $user = $request->user();

        $bookings = SpecialistAppointment::with(['branch', 'specialist.branch', 'specialist.branches'])
            ->where('user_id', $user->id)
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->get();

        $upcoming = $bookings->filter(function ($booking) {
            $dateTime = Carbon::parse($booking->appointment_date.' '.$booking->appointment_time);
            return $dateTime->isFuture() || $dateTime->isSameDay(Carbon::today());
        });

        $past = $bookings->filter(function ($booking) {
            $dateTime = Carbon::parse($booking->appointment_date.' '.$booking->appointment_time);
            return $dateTime->isPast() && !$dateTime->isSameDay(Carbon::today());
        });

        return json_custom_response([
            'upcoming' => SpecialistAppointmentResource::collection($upcoming->values()),
            'past' => SpecialistAppointmentResource::collection($past->values()),
        ]);
    }

    public function availableSlots(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'specialist_id' => 'required|exists:specialists,id',
            'date' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return json_message_response($validator->errors()->first(), 422);
        }

        $specialist = Specialist::with('schedules')->findOrFail($request->specialist_id);
        $date = Carbon::parse($request->date);
        $day = $date->dayOfWeek; // 0 (Sun) - 6 (Sat)

        $schedules = $specialist->schedules->where('day_of_week', $day);
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
                $exists = SpecialistAppointment::where('specialist_id', $specialist->id)
                    ->where('appointment_date', $date->toDateString())
                    ->where('appointment_time', $slotTime)
                    ->whereIn('status', ['pending', 'confirmed', 'completed'])
                    ->exists();

                $slots[] = [
                    'time' => $current->format('H:i'),
                    'is_available' => !$exists,
                ];

                $current = $slotEnd;
            }
        }

        return json_custom_response([
            'date' => $date->toDateString(),
            'slots' => $slots,
        ]);
    }

    public function book(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'specialist_id' => 'required|exists:specialists,id',
            'branch_id' => 'required|exists:branches,id',
            'date' => 'required|date_format:Y-m-d',
            'time' => 'required|date_format:H:i',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return json_message_response($validator->errors()->first(), 422);
        }

        $user = $request->user();
        $date = Carbon::createFromFormat('Y-m-d', $request->date);
        $time = Carbon::createFromFormat('H:i', $request->time)->format('H:i:s');

        $scheduleExists = SpecialistSchedule::where('specialist_id', $request->specialist_id)
            ->where('day_of_week', $date->dayOfWeek)
            ->where(function ($query) use ($time) {
                $query->where(function ($inner) use ($time) {
                    $inner->where('start_time', '<=', $time)
                        ->where('end_time', '>', $time);
                });
            })
            ->exists();

        if (! $scheduleExists) {
            return json_message_response(__('message.slot_not_available'), 400);
        }

        $alreadyBooked = SpecialistAppointment::where('specialist_id', $request->specialist_id)
            ->where('appointment_date', $date->toDateString())
            ->where('appointment_time', $time)
            ->whereIn('status', ['pending', 'confirmed', 'completed'])
            ->exists();

        if ($alreadyBooked) {
            return json_message_response(__('message.slot_already_booked'), 409);
        }

        $specialist = Specialist::with('branches')->findOrFail($request->specialist_id);

        if (! $specialist->branches->pluck('id')->contains((int) $request->branch_id)) {
            return json_message_response(__('message.specialist_not_in_branch'), 422);
        }

        $appointment = SpecialistAppointment::create([
            'user_id' => $user->id,
            'specialist_id' => $request->specialist_id,
            'branch_id' => $request->branch_id,
            'appointment_date' => $date->toDateString(),
            'appointment_time' => $time,
            'status' => 'pending',
            'type' => 'regular',
            'notes' => $request->notes,
        ]);

        $user->loadMissing('userProfile');
        if ($user->userProfile) {
            $user->userProfile->specialist_id = $request->specialist_id;
            $user->userProfile->save();
        }

        return json_custom_response([
            'message' => __('message.appointment_booked_successfully'),
            'appointment' => new SpecialistAppointmentResource($appointment->fresh(['branch', 'specialist.branch', 'specialist.branches'])),
        ]);
    }

    public function requestFree(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'phone' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return json_message_response($validator->errors()->first(), 422);
        }

        $user = $request->user();
        $user->loadMissing('userProfile');

        if ($user->userProfile && $user->userProfile->free_booking_used_at) {
            return json_message_response(__('message.free_booking_already_used'), 409);
        }

        $requestRecord = FreeBookingRequest::create([
            'user_id' => $user->id,
            'branch_id' => $request->branch_id,
            'phone' => $request->phone,
            'status' => 'pending',
        ]);

        if ($user->userProfile) {
            $user->userProfile->update([
                'free_booking_used_at' => now(),
                'specialist_id' => null,
            ]);
        }

        return json_custom_response([
            'message' => __('message.free_booking_request_sent'),
            'request' => [
                'id' => $requestRecord->id,
                'status' => $requestRecord->status,
            ],
        ]);
    }
}
