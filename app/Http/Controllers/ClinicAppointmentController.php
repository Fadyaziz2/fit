<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Package;
use App\Models\Specialist;
use App\Models\SpecialistAppointment;
use App\Models\SpecialistSchedule;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SmsService;
use App\Traits\CreatesManualUsers;
use App\Traits\HandlesBranchAccess;
use App\Traits\SubscriptionTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClinicAppointmentController extends Controller
{
    use SubscriptionTrait, HandlesBranchAccess, CreatesManualUsers;

    public function __construct(protected SmsService $smsService)
    {
    }

    protected function authorizeAccess(): User
    {
        return $this->authorizeBranchAccess();
    }

    public function index(Request $request)
    {
        $user = $this->authorizeAccess();
        $branchIds = $this->getAccessibleBranchIds($user);

        $request->validate([
            'type' => 'nullable|in:regular,free,manual_free',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'specialist_id' => 'nullable|integer|exists:specialists,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        if ($request->filled('branch_id')) {
            $this->assertBranchAccessible((int) $request->input('branch_id'), $branchIds);
        }

        $pageTitle = __('message.list_form_title', ['form' => __('message.appointment')]);
        $appointments = SpecialistAppointment::with(['user', 'specialist.branch', 'branch'])
            ->when($branchIds !== null, function ($query) use ($branchIds) {
                $query->whereIn('branch_id', $branchIds);
            })
            ->when($request->filled('from_date'), function ($query) use ($request) {
                $query->whereDate('appointment_date', '>=', $request->input('from_date'));
            })
            ->when($request->filled('to_date'), function ($query) use ($request) {
                $query->whereDate('appointment_date', '<=', $request->input('to_date'));
            })
            ->when($request->filled('branch_id'), function ($query) use ($request) {
                $branchId = (int) $request->input('branch_id');

                $query->where(function ($branchQuery) use ($branchId) {
                    $branchQuery->where('branch_id', $branchId)
                        ->orWhereHas('specialist', function ($specialistQuery) use ($branchId) {
                            $specialistQuery->where('branch_id', $branchId)
                                ->orWhereHas('branches', function ($relationQuery) use ($branchId) {
                                    $relationQuery->where('branches.id', $branchId);
                                });
                        });
                });
            })
            ->when($request->filled('specialist_id'), function ($query) use ($request) {
                $query->where('specialist_id', $request->input('specialist_id'));
            })
            ->when($request->filled('type'), function ($query) use ($request) {
                $type = $request->input('type');

                if ($type === 'free') {
                    $query->whereIn('type', ['free', 'manual_free']);
                } elseif ($type === 'manual_free') {
                    $query->where('type', 'manual_free');
                } else {
                    $query->where('type', $type);
                }
            })
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->paginate(20)
            ->appends($request->only('type', 'branch_id', 'specialist_id', 'from_date', 'to_date'));

        $users = User::where('user_type', 'user')
            ->orderBy('display_name')
            ->get();

        $branches = Branch::orderBy('name')
            ->when($branchIds !== null, function ($query) use ($branchIds) {
                $query->whereIn('id', $branchIds);
            })
            ->get();

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
        $packages = Package::where('status', 'active')->orderBy('name')->get();
        $statusLabels = SpecialistAppointment::statusLabels();
        $statusBadgeClasses = SpecialistAppointment::statusBadgeClasses();

        return view('clinic.appointments.index', compact('pageTitle', 'appointments', 'users', 'branches', 'specialists', 'packages', 'statusLabels', 'statusBadgeClasses'));
    }

    public function edit(SpecialistAppointment $appointment)
    {
        $this->authorizeAccess();

        $pageTitle = __('message.update_form_title', ['form' => __('message.appointment')]);
        $statusOptions = SpecialistAppointment::statusLabels();

        return view('clinic.appointments.form', compact('pageTitle', 'appointment', 'statusOptions'));
    }

    public function update(Request $request, SpecialistAppointment $appointment)
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'status' => 'required|' . SpecialistAppointment::statusValidationRule(),
            'admin_comment' => ['nullable', 'string', 'required_if:status,other'],
        ]);

        $appointment->update($data);

        return redirect()->route('clinic.appointments.index')->withSuccess(__('message.update_form', ['form' => __('message.appointment')]));
    }

    public function availableSlots(Request $request)
    {
        $user = $this->authorizeAccess();
        $branchIds = $this->getAccessibleBranchIds($user);

        $data = $request->validate([
            'specialist_id' => 'required|exists:specialists,id',
            'date' => 'required|date_format:Y-m-d',
        ]);

        $specialist = Specialist::with(['schedules', 'branches'])->findOrFail($data['specialist_id']);
        $this->ensureSpecialistAccessible($specialist, $branchIds);
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
                    ->whereIn('status', SpecialistAppointment::BLOCKING_STATUSES)
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
        $user = $this->authorizeAccess();
        $branchIds = $this->getAccessibleBranchIds($user);

        $data = $request->validate([
            'type' => 'required|in:regular,manual_free',
            'user_id' => 'required_if:type,regular|nullable|exists:users,id',
            'manual_name' => 'required_if:type,manual_free|nullable|string|max:255',
            'manual_phone' => 'required_if:type,manual_free|nullable|string|max:20',
            'manual_branch' => 'required_if:type,manual_free|nullable|exists:branches,id',
            'specialist_id' => 'required|exists:specialists,id',
            'branch_id' => 'nullable|exists:branches,id',
            'appointment_date' => 'required|date_format:Y-m-d',
            'appointment_time' => 'required|date_format:H:i',
        ]);

        $specialist = Specialist::with(['branch', 'branches'])->findOrFail($data['specialist_id']);
        $this->ensureSpecialistAccessible($specialist, $branchIds);
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
            ->whereIn('status', SpecialistAppointment::BLOCKING_STATUSES)
            ->exists();

        if ($alreadyBooked) {
            return back()->withErrors(__('message.slot_already_booked'))->withInput();
        }

        $userId = $data['user_id'] ?? null;

        $branchId = null;
        $user = null;
        $specialistBranchIds = $specialist->branches->pluck('id')->map(fn ($id) => (int) $id);

        if ($data['type'] === 'manual_free') {
            $user = $this->createManualUser($data['manual_name'], $data['manual_phone']);
            $userId = $user->id;
            $branchId = (int) $data['manual_branch'];
            $this->assertBranchAccessible($branchId, $branchIds);

            if (! $specialistBranchIds->contains($branchId)) {
                return back()->withErrors(__('message.specialist_not_in_branch'))->withInput();
            }
        } else {
            if ($request->filled('branch_id')) {
                $branchId = (int) $request->input('branch_id');
            }

            if ($specialistBranchIds->count() > 1 && ! $branchId) {
                return back()->withErrors(__('message.specialist_branch_selection_required'))->withInput();
            }

            if ($branchId && ! $specialistBranchIds->contains($branchId)) {
                return back()->withErrors(__('message.specialist_not_in_branch'))->withInput();
            }
        }

        if ($data['type'] !== 'manual_free' && $userId) {
            $user = User::find($userId);
        }

        if (! $branchId && $specialistBranchIds->count() === 1) {
            $branchId = $specialistBranchIds->first();
        }

        $branchId = $branchId ?? (int) $specialist->branch_id;
        $this->assertBranchAccessible($branchId, $branchIds);

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
        $user = $this->authorizeAccess();
        $branchIds = $this->getAccessibleBranchIds($user);

        abort_unless($appointment->type === 'manual_free', 404);
        $this->assertBranchAccessible($appointment->branch_id, $branchIds);
        $appointment->loadMissing('specialist.branches');
        if ($appointment->specialist) {
            $this->ensureSpecialistAccessible($appointment->specialist, $branchIds);
        }

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
                'status' => 'subscribed',
                'notes' => __('message.manual_free_converted_notes', ['subscription' => $subscription->id]),
            ]);
        });

        return redirect()->route('clinic.appointments.index')->withSuccess(__('message.manual_free_converted'));
    }

    protected function sendAppointmentConfirmationSms(?string $phoneNumber, Carbon $date, Carbon $time): void
    {
        $dayName = $date->copy()->locale('ar')->translatedFormat('l');
        $formattedDate = $this->convertToArabicNumerals($date->format('j-n-Y'));

        $message = sprintf(
            "تم تأكيد موعدك في مركز مايكرو\n📅 التاريخ: %s الموافق %s\n🕒 الساعة: %s\n\nيرجى الحضور قبل الموعد بـ10 دقائق.\n📞 للاستفسارات: 0780922854",
            $dayName,
            $formattedDate,
            $time->format('H:i')
        );

        $this->smsService->send($message, $phoneNumber);
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
