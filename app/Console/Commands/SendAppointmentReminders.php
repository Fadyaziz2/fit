<?php

namespace App\Console\Commands;

use App\Models\SpecialistAppointment;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendAppointmentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send SMS reminders for next-day appointments';

    public function __construct(protected SmsService $smsService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $ammanNow = Carbon::now('Asia/Amman');
        $tomorrowDate = $ammanNow->copy()->addDay()->toDateString();

        $query = SpecialistAppointment::query()
            ->with('user')
            ->whereDate('appointment_date', $tomorrowDate)
            ->whereIn('status', SpecialistAppointment::BLOCKING_STATUSES)
            ->orderBy('id');

        $sent = 0;
        $failed = 0;

        $query->chunkById(100, function ($appointments) use (&$sent, &$failed) {
            foreach ($appointments as $appointment) {
                $user = $appointment->user;

                if (! $user || ! $user->phone_number) {
                    $failed++;
                    continue;
                }

                $name = $this->resolveUserName($user->display_name, $user->first_name, $user->last_name);

                $date = Carbon::parse($appointment->appointment_date, 'Asia/Amman');
                $time = Carbon::parse($appointment->appointment_time, 'Asia/Amman');

                $dayName = $date->locale('ar')->translatedFormat('l');
                $formattedDate = $this->convertToArabicNumerals($date->format('d/m/Y'));
                $formattedTime = $this->convertToArabicNumerals($time->format('H:i'));

                $message = sprintf(
                    'السلام عليكم %s نود تذكيرك ان موعدك غدا %s %s في مركز مايكرو %s الرجاء الحضور قبل الموعد 10 دقائق',
                    $name,
                    $dayName,
                    $formattedDate,
                    $formattedTime
                );

                if ($this->smsService->send($message, $user->phone_number)) {
                    $sent++;
                } else {
                    $failed++;
                }
            }
        });

        $this->info("Reminders sent: {$sent}");
        $this->info("Failed deliveries: {$failed}");

        return self::SUCCESS;
    }

    protected function resolveUserName(?string $displayName, ?string $firstName, ?string $lastName): string
    {
        if ($displayName) {
            return $displayName;
        }

        $nameParts = array_filter([$firstName, $lastName], fn ($value) => filled($value));

        if (! empty($nameParts)) {
            return implode(' ', $nameParts);
        }

        return 'عميلنا العزيز';
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
