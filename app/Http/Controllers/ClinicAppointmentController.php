<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Package;
use App\Models\Specialist;
use App\Models\SpecialistAppointment;
use App\Models\SpecialistSchedule;
use App\Models\Subscription;
use App\Models\User;
use App\Traits\SubscriptionTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ClinicAppointmentController extends Controller
{
    use SubscriptionTrait;

    protected function authorizeAccess()
    {
        if (auth()->user()?->user_type !== 'admin') {
            abort(403, __('message.permission_denied_for_account'));
        }
    }

    public function index(Request $request)
    {
        $this->authorizeAccess();

        $request->validate([
            'type' => 'nullable|in:regular,free,manual_free',
        ]);

        $pageTitle = __('message.list_form_title', ['form' => __('message.appointment')]);
        $appointments = SpecialistAppointment::with(['user', 'specialist.branch'])
            ->when($request->filled('type'), function ($query) use ($request) {
                $query->where('type', $request->input('type'));
            })
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->paginate(20)
            ->appends($request->only('type'));

        $users = User::where('user_type', 'user')
            ->orderBy('display_name')
            ->get();

        $branches = Branch::orderBy('name')->get();
        $specialists = Specialist::with(['branch', 'branches'])->orderBy('name')->get();
        $packages = Package::where('status', 'active')->orderBy('name')->get();

        return view('clinic.appointments.index', compact('pageTitle', 'appointments', 'users', 'branches', 'specialists', 'packages'));
    }

    public function edit(SpecialistAppointment $appointment)
    {
        $this->authorizeAccess();

        $pageTitle = __('message.update_form_title', ['form' => __('message.appointment')]);

        return view('clinic.appointments.form', compact('pageTitle', 'appointment'));
    }

    public function update(Request $request, SpecialistAppointment $appointment)
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'status' => 'required|in:pending,confirmed,completed,cancelled',
            'admin_comment' => 'nullable|string',
        ]);

        $appointment->update($data);

        return redirect()->route('clinic.appointments.index')->withSuccess(__('message.update_form', ['form' => __('message.appointment')]));
    }

    public function availableSlots(Request $request)
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'specialist_id' => 'required|exists:specialists,id',
            'date' => 'required|date_format:Y-m-d',
        ]);

        $specialist = Specialist::with('schedules')->findOrFail($data['specialist_id']);
        $date = Carbon::createFromFormat('Y-m-d', $data['date']);
        $weekStart = Carbon::create()->startOfWeek();

        $schedules = $specialist->schedules
            ->filter(function ($schedule) use ($date, $weekStart) {
                $storedDay = (int) $schedule->day_of_week;
                $normalizedDay = $weekStart->copy()->addDays($storedDay)->dayOfWeek;

                return $normalizedDay === $date->dayOfWeek;
            })
            ->sortBy('start_time');

        $slots = [];
        $totalSlots = 0;
        $availableSlots = 0;
        $workingRanges = [];
        $now = Carbon::now();

        foreach ($schedules as $schedule) {
            if ($schedule->slot_duration <= 0) {
                continue;
            }

            $start = Carbon::parse($schedule->start_time);
            $end = Carbon::parse($schedule->end_time);

            if ($end->lessThanOrEqualTo($start)) {
                continue;
            }

            $workingRanges[] = [
                'start' => $start->format('H:i'),
                'end' => $end->format('H:i'),
            ];

            $current = $start->copy();

            while ($current->lt($end)) {
                $slotEnd = $current->copy()->addMinutes($schedule->slot_duration);

                if ($slotEnd->gt($end)) {
                    break;
                }

                if ($date->isToday() && $slotEnd->lte($now)) {
                    $current = $slotEnd;
                    continue;
                }

                $slotTime = $current->format('H:i:s');
                $isBooked = SpecialistAppointment::where('specialist_id', $specialist->id)
                    ->where('appointment_date', $date->toDateString())
                    ->where('appointment_time', $slotTime)
                    ->whereIn('status', ['pending', 'confirmed', 'completed'])
                    ->exists();

                $available = ! $isBooked;

                $slots[] = [
                    'time' => $current->format('H:i'),
                    'available' => $available,
                    'is_available' => $available,
                ];

                $totalSlots++;

                if ($available) {
                    $availableSlots++;
                }

                $current = $slotEnd;
            }
        }

        $slots = collect($slots)
            ->unique('time')
            ->sortBy('time')
            ->values()
            ->all();

        $workingRanges = collect($workingRanges)
            ->unique(function ($range) {
                return $range['start'] . '-' . $range['end'];
            })
            ->values()
            ->all();

        return response()->json([
            'date' => $date->toDateString(),
            'slots' => $slots,
            'meta' => [
                'total_slots' => $totalSlots,
                'available_slots' => $availableSlots,
                'working_ranges' => $workingRanges,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'type' => 'required|in:regular,manual_free',
            'user_id' => 'required_if:type,regular|nullable|exists:users,id',
            'manual_name' => 'required_if:type,manual_free|nullable|string|max:255',
            'manual_phone' => 'required_if:type,manual_free|nullable|string|max:20',
            'manual_branch' => 'required_if:type,manual_free|nullable|exists:branches,id',
            'specialist_id' => 'required|exists:specialists,id',
            'appointment_date' => 'required|date_format:Y-m-d',
            'appointment_time' => 'required|date_format:H:i',
        ]);

        $specialist = Specialist::with(['branch', 'branches'])->findOrFail($data['specialist_id']);
        $date = Carbon::createFromFormat('Y-m-d', $data['appointment_date']);
        $time = Carbon::createFromFormat('H:i', $data['appointment_time']);
        $timeString = $time->format('H:i:s');

        $scheduleDay = ($date->dayOfWeek + 6) % 7;

        $scheduleExists = SpecialistSchedule::where('specialist_id', $specialist->id)
            ->where('day_of_week', $scheduleDay)
            ->where('start_time', '<=', $timeString)
            ->where('end_time', '>', $timeString)
            ->exists();

        if (! $scheduleExists) {
            return back()->withErrors(__('message.slot_not_available'))->withInput();
        }

        $alreadyBooked = SpecialistAppointment::where('specialist_id', $specialist->id)
            ->where('appointment_date', $date->toDateString())
            ->where('appointment_time', $timeString)
            ->whereIn('status', ['pending', 'confirmed', 'completed'])
            ->exists();

        if ($alreadyBooked) {
            return back()->withErrors(__('message.slot_already_booked'))->withInput();
        }

        $userId = $data['user_id'] ?? null;

        $branchId = null;
        $user = null;

        if ($data['type'] === 'manual_free') {
            $user = $this->createManualUser($data['manual_name'], $data['manual_phone']);
            $userId = $user->id;
            $branchId = (int) $data['manual_branch'];

            if (! $specialist->branches->pluck('id')->contains($branchId)) {
                return back()->withErrors(__('message.specialist_not_in_branch'))->withInput();
            }
        }

        if ($data['type'] !== 'manual_free' && $userId) {
            $user = User::find($userId);
        }

        $branchId = $branchId ?? (int) $specialist->branch_id;

        if (! $branchId) {
            return back()->withErrors(__('message.specialist_not_in_branch'))->withInput();
        }

        SpecialistAppointment::create([
            'user_id' => $userId,
            'specialist_id' => $specialist->id,
            'branch_id' => $branchId,
            'appointment_date' => $date->toDateString(),
            'appointment_time' => $timeString,
            'status' => 'pending',
            'type' => $data['type'] === 'manual_free' ? 'manual_free' : 'regular',
            'notes' => $data['type'] === 'manual_free' ? __('message.manual_free_notes', ['name' => $data['manual_name'], 'phone' => $data['manual_phone']]) : null,
        ]);

        $phoneNumber = $data['type'] === 'manual_free'
            ? ($data['manual_phone'] ?? null)
            : ($user?->phone_number ?? null);

        $this->sendAppointmentConfirmationSms($phoneNumber, $date, $time);

        return redirect()->route('clinic.appointments.index')->withSuccess(__('message.save_form', ['form' => __('message.appointment')]));
    }

    public function convertManualFree(Request $request, SpecialistAppointment $appointment)
    {
        $this->authorizeAccess();

        abort_unless($appointment->type === 'manual_free', 404);

        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $appointment->user_id,
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6|confirmed',
            'package_id' => 'required|exists:packages,id',
        ]);

        $user = $appointment->user;

        if (! $user) {
            $user = $this->createManualUser($data['full_name'], $data['phone']);
            $appointment->user_id = $user->id;
        }

        $nameParts = preg_split('/\s+/', $data['full_name'], 2);
        $firstName = $nameParts[0] ?? null;
        $lastName = $nameParts[1] ?? null;

        DB::transaction(function () use ($user, $appointment, $data, $firstName, $lastName) {
            $user->update([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'display_name' => $data['full_name'],
                'email' => $data['email'],
                'phone_number' => $data['phone'],
                'password' => Hash::make($data['password']),
                'status' => 'active',
            ]);

            $package = Package::findOrFail($data['package_id']);
            $startDate = now()->format('Y-m-d H:i:s');
            $endDate = $this->get_plan_expiration_date($startDate, $package->duration_unit, 0, $package->duration);

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'package_id' => $package->id,
                'total_amount' => $package->price,
                'payment_type' => 'manual',
                'payment_status' => 'paid',
                'status' => config('constant.SUBSCRIPTION_STATUS.ACTIVE'),
                'subscription_start_date' => $startDate,
                'subscription_end_date' => $endDate,
                'package_data' => $package->toArray(),
                'transaction_detail' => [
                    'added_by' => auth()->id(),
                    'name' => auth()->user()->display_name,
                ],
            ]);

            $user->update(['is_subscribe' => 1]);

            $appointment->update([
                'status' => 'completed',
                'notes' => __('message.manual_free_converted_notes', ['subscription' => $subscription->id]),
            ]);
        });

        return redirect()->route('clinic.appointments.index')->withSuccess(__('message.manual_free_converted'));
    }

    protected function createManualUser(?string $name, ?string $phone): User
    {
        $safeName = $name ?: __('message.manual_free_guest');
        $username = Str::slug(mb_substr($safeName, 0, 20), '.') ?: 'manual-user';
        $username .= '.' . Str::lower(Str::random(6));

        $email = $username . '@manual.local';

        $user = User::create([
            'username' => $username,
            'first_name' => $safeName,
            'last_name' => null,
            'display_name' => $safeName,
            'email' => $email,
            'password' => Hash::make(Str::random(16)),
            'user_type' => 'user',
            'status' => 'pending',
            'phone_number' => $phone,
        ]);

        $user->assignRole('user');

        return $user;
    }

    protected function sendAppointmentConfirmationSms(?string $phoneNumber, Carbon $date, Carbon $time): void
    {
        $normalizedPhone = $this->normalizeJordanPhone($phoneNumber);

        if (! $normalizedPhone) {
            return;
        }

        $dayName = $date->copy()->locale('ar')->translatedFormat('l');
        $formattedDate = $this->convertToArabicNumerals($date->format('j-n-Y'));

        $message = sprintf(
            "تم تأكيد موعدك في مركز مايكرو\n📅 التاريخ: %s الموافق %s\n🕒 الساعة: %s\n\nيرجى الحضور قبل الموعد بـ10 دقائق.\n📞 للاستفسارات: 0780922854",
            $dayName,
            $formattedDate,
            $time->format('H:i')
        );

        try {
            Http::timeout(10)
                ->get('https://smsapi.theblunet.com:8441/websmpp/websms', [
                    'user' => 'microjo',
                    'pass' => 'Mic0@25!',
                    'sid' => 'micro jo',
                    'mno' => $normalizedPhone,
                    'type' => 4,
                    'text' => $message,
                ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    protected function normalizeJordanPhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === '') {
            return null;
        }

        if (Str::startsWith($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (Str::startsWith($digits, '962')) {
            return $digits;
        }

        if (Str::startsWith($digits, '0')) {
            return '962' . substr($digits, 1);
        }

        if (Str::startsWith($digits, '7') && strlen($digits) === 9) {
            return '962' . $digits;
        }

        return $digits;
    }

    protected function convertToArabicNumerals(string $value): string
    {
        return strtr($value, [
            '0' => '٠',
            '1' => '١',
            '2' => '٢',
            '3' => '٣',
            '4' => '٤',
            '5' => '٥',
            '6' => '٦',
            '7' => '٧',
            '8' => '٨',
            '9' => '٩',
        ]);
    }
}
