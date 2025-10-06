<?php

namespace App\Http\Controllers;

use App\Models\SpecialistAppointment;
use Illuminate\Http\Request;

class ClinicAppointmentController extends Controller
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

        $pageTitle = __('message.list_form_title', ['form' => __('message.appointment')]);
        $appointments = SpecialistAppointment::with(['user', 'specialist.branch'])
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->paginate(20);

        return view('clinic.appointments.index', compact('pageTitle', 'appointments'));
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
}
