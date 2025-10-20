<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;

class SmsController extends Controller
{
    public function __construct(protected SmsService $smsService)
    {
    }

    public function index()
    {
        if (! auth()->user()->hasRole('admin')) {
            $message = __('message.permission_denied_for_account');

            return redirect()->back()->withErrors($message);
        }

        $pageTitle = __('message.sms_center');
        $assets = ['select2'];
        $users = User::where('user_type', 'user')
            ->whereNotNull('phone_number')
            ->where('phone_number', '!=', '')
            ->orderBy('display_name')
            ->get()
            ->pluck('display_name', 'id');

        return view('sms.form', compact('pageTitle', 'assets', 'users'));
    }

    public function send(Request $request)
    {
        if (! auth()->user()->hasRole('admin')) {
            $message = __('message.permission_denied_for_account');

            return redirect()->back()->withErrors($message);
        }

        $data = $request->validate([
            'target' => 'required|in:all,selected',
            'message' => 'required|string|max:612',
            'users' => 'array',
            'users.*' => 'exists:users,id',
        ]);

        $query = User::query()
            ->where('user_type', 'user')
            ->whereNotNull('phone_number')
            ->where('phone_number', '!=', '');

        if ($data['target'] === 'selected') {
            $selectedUsers = $data['users'] ?? [];

            if (empty($selectedUsers)) {
                return redirect()->back()->withInput()->withErrors(__('message.sms_select_recipients'));
            }

            $query->whereIn('id', $selectedUsers);
        }

        $recipients = $query->get(['id', 'display_name', 'phone_number']);

        if ($recipients->isEmpty()) {
            return redirect()->back()->withInput()->withErrors(__('message.sms_no_recipients'));
        }

        $sent = 0;
        $failed = [];

        foreach ($recipients as $recipient) {
            if ($this->smsService->send($data['message'], $recipient->phone_number)) {
                $sent++;
            } else {
                $failed[] = $recipient->display_name ?: $recipient->phone_number;
            }
        }

        $total = $recipients->count();

        if ($sent === 0) {
            return redirect()->back()->withInput()->withErrors(__('message.sms_send_error'));
        }

        if ($sent < $total) {
            return redirect()->route('sms.index')->withSuccess(
                __('message.sms_send_partial', [
                    'sent' => $sent,
                    'total' => $total,
                    'failed' => implode(', ', $failed),
                ])
            );
        }

        return redirect()->route('sms.index')->withSuccess(
            __('message.sms_send_success', ['count' => $sent])
        );
    }
}
