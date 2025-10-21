<?php

namespace App\Http\Controllers;

use App\Mail\BulkEmail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EmailController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:email-center-list')->only('index');
        $this->middleware('permission:email-center-send')->only('send');
    }

    public function index()
    {
        $pageTitle = __('message.email_center');
        $assets = ['select2'];
        $users = User::where('user_type', 'user')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('display_name')
            ->get()
            ->pluck('display_name', 'id');

        return view('email.form', compact('pageTitle', 'assets', 'users'));
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'target' => 'required|in:all,selected',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'users' => 'array',
            'users.*' => 'exists:users,id',
        ]);

        $query = User::query()
            ->where('user_type', 'user')
            ->whereNotNull('email')
            ->where('email', '!=', '');

        if ($data['target'] === 'selected') {
            $selectedUsers = $data['users'] ?? [];

            if (empty($selectedUsers)) {
                return redirect()->back()->withInput()->withErrors(__('message.email_select_recipients'));
            }

            $query->whereIn('id', $selectedUsers);
        }

        $recipients = $query->get(['id', 'display_name', 'email']);

        if ($recipients->isEmpty()) {
            return redirect()->back()->withInput()->withErrors(__('message.email_no_recipients'));
        }

        $sent = 0;
        $failed = [];

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient->email)->send(new BulkEmail($data['subject'], $data['message'], $recipient));
                $sent++;
            } catch (\Throwable $exception) {
                report($exception);
                $failed[] = $recipient->display_name ?: $recipient->email;
            }
        }

        $total = $recipients->count();

        if ($sent === 0) {
            return redirect()->back()->withInput()->withErrors(__('message.email_send_error'));
        }

        if ($sent < $total) {
            return redirect()->route('emails.index')->withSuccess(
                __('message.email_send_partial', [
                    'sent' => $sent,
                    'total' => $total,
                    'failed' => implode(', ', $failed),
                ])
            );
        }

        return redirect()->route('emails.index')->withSuccess(
            __('message.email_send_success', ['count' => $sent])
        );
    }
}
