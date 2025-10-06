<?php

namespace App\Http\Controllers;

use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use Illuminate\Http\Request;

class ClinicSpecialistScheduleController extends Controller
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

        $pageTitle = __('message.list_form_title', ['form' => __('message.specialist_schedule')]);
        $schedules = SpecialistSchedule::with('specialist.branch')->orderBy('day_of_week')->paginate(20);

        return view('clinic.schedules.index', compact('pageTitle', 'schedules'));
    }

    public function create()
    {
        $this->authorizeAccess();

        $pageTitle = __('message.add_form_title', ['form' => __('message.specialist_schedule')]);
        $specialists = Specialist::with('branch')->orderBy('name')->get();

        return view('clinic.schedules.form', compact('pageTitle', 'specialists'));
    }

    public function store(Request $request)
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'specialist_id' => 'required|exists:specialists,id',
            'day_of_week' => 'required|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'slot_duration' => 'required|integer|min:5',
        ]);

        SpecialistSchedule::create($data);

        return redirect()->route('clinic.schedules.index')->withSuccess(__('message.save_form', ['form' => __('message.specialist_schedule')]));
    }

    public function edit(SpecialistSchedule $schedule)
    {
        $this->authorizeAccess();

        $pageTitle = __('message.update_form_title', ['form' => __('message.specialist_schedule')]);
        $specialists = Specialist::with('branch')->orderBy('name')->get();

        return view('clinic.schedules.form', compact('pageTitle', 'specialists', 'schedule'));
    }

    public function update(Request $request, SpecialistSchedule $schedule)
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'specialist_id' => 'required|exists:specialists,id',
            'day_of_week' => 'required|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'slot_duration' => 'required|integer|min:5',
        ]);

        $schedule->update($data);

        return redirect()->route('clinic.schedules.index')->withSuccess(__('message.update_form', ['form' => __('message.specialist_schedule')]));
    }

    public function destroy(SpecialistSchedule $schedule)
    {
        $this->authorizeAccess();

        $schedule->delete();

        return redirect()->route('clinic.schedules.index')->withSuccess(__('message.delete_form', ['form' => __('message.specialist_schedule')]));
    }
}
