<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class SmsService
{
    protected string $endpoint = 'https://smsapi.theblunet.com:8441/websmpp/websms';
    protected string $username = 'microjo';
    protected string $password = 'Mic0@25!';
    protected string $senderId = 'micro jo';

    public function send(string $message, ?string $phoneNumber): bool
    {
        $normalizedPhone = $this->normalizeJordanPhone($phoneNumber);

        if (! $normalizedPhone) {
            return false;
        }

        $text = trim(preg_replace("/\r\n?/, "\n", $message));

        if ($text === '') {
            return false;
        }

        try {
            Http::timeout(10)->get($this->endpoint, [
                'user' => $this->username,
                'pass' => $this->password,
                'sid' => $this->senderId,
                'mno' => $normalizedPhone,
                'type' => 4,
                'text' => $text,
            ]);

            return true;
        } catch (Throwable $exception) {
            report($exception);
        }

        return false;
    }

    public function sendMany(string $message, iterable $phoneNumbers): array
    {
        $sent = 0;
        $failed = [];

        foreach ($phoneNumbers as $key => $phoneNumber) {
            if ($this->send($message, $phoneNumber)) {
                $sent++;
            } else {
                $failed[] = $key;
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
        ];
    }

    public function normalizeJordanPhone(?string $phone): ?string
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
}
