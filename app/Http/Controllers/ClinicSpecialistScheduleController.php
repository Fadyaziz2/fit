<?php

namespace App\Http\Controllers;

use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use App\Traits\HandlesBranchAccess;
use Illuminate\Http\Request;

class ClinicSpecialistScheduleController extends Controller
{
    use HandlesBranchAccess;

    protected function authorizeAccess()
    {
        return $this->authorizeBranchAccess();
    }

    public function index()
    {
        $user = $this->authorizeAccess();
        $branchIds = $this->getAccessibleBranchIds($user);

        $pageTitle = __('message.list_form_title', ['form' => __('message.specialist_schedule')]);
        $schedules = SpecialistSchedule::with(['specialist.branch', 'specialist.branches'])
            ->when($branchIds !== null, function ($query) use ($branchIds) {
                $query->whereHas('specialist', function ($specialistQuery) use ($branchIds) {
                    $specialistQuery->whereIn('branch_id', $branchIds)
                        ->orWhereHas('branches', function ($branchQuery) use ($branchIds) {
                            $branchQuery->whereIn('branches.id', $branchIds);
                        });
                });
            })
            ->orderBy('day_of_week')
            ->paginate(20);

        return view('clinic.schedules.index', compact('pageTitle', 'schedules'));
    }

    public function create()
    {
        $user = $this->authorizeAccess();
        $branchIds = $this->getAccessibleBranchIds($user);

        $pageTitle = __('message.add_form_title', ['form' => __('message.specialist_schedule')]);
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

        return view('clinic.schedules.form', compact('pageTitle', 'specialists'));
    }

    public function store(Request $request)
    {
        $user = $this->authorizeAccess();
        $branchIds = $this->getAccessibleBranchIds($user);

        $data = $request->validate([
            'specialist_id' => 'required|exists:specialists,id',
            'day_of_week' => 'required|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'slot_duration' => 'required|integer|min:5',
        ]);

        $specialist = Specialist::with('branches')->findOrFail($data['specialist_id']);
        $this->ensureSpecialistAccessible($specialist, $branchIds);

        SpecialistSchedule::create($data);

        return redirect()->route('clinic.schedules.index')->withSuccess(__('message.save_form', ['form' => __('message.specialist_schedule')]));
    }

    public function edit(SpecialistSchedule $schedule)
    {
        $user = $this->authorizeAccess();
        $branchIds = $this->getAccessibleBranchIds($user);
        $schedule->load('specialist.branches');
        if ($schedule->specialist) {
            $this->ensureSpecialistAccessible($schedule->specialist, $branchIds);
        }

        $pageTitle = __('message.update_form_title', ['form' => __('message.specialist_schedule')]);
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

        return view('clinic.schedules.form', compact('pageTitle', 'specialists', 'schedule'));
    }

    public function update(Request $request, SpecialistSchedule $schedule)
    {
        $user = $this->authorizeAccess();
        $branchIds = $this->getAccessibleBranchIds($user);
        $schedule->load('specialist.branches');
        if ($schedule->specialist) {
            $this->ensureSpecialistAccessible($schedule->specialist, $branchIds);
        }

        $data = $request->validate([
            'specialist_id' => 'required|exists:specialists,id',
            'day_of_week' => 'required|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'slot_duration' => 'required|integer|min:5',
        ]);

        $specialist = Specialist::with('branches')->findOrFail($data['specialist_id']);
        $this->ensureSpecialistAccessible($specialist, $branchIds);

        $schedule->update($data);

        return redirect()->route('clinic.schedules.index')->withSuccess(__('message.update_form', ['form' => __('message.specialist_schedule')]));
    }

    public function destroy(SpecialistSchedule $schedule)
    {
        $user = $this->authorizeAccess();
        $branchIds = $this->getAccessibleBranchIds($user);
        $schedule->load('specialist.branches');
        if ($schedule->specialist) {
            $this->ensureSpecialistAccessible($schedule->specialist, $branchIds);
        }

        $schedule->delete();

        return redirect()->route('clinic.schedules.index')->withSuccess(__('message.delete_form', ['form' => __('message.specialist_schedule')]));
    }
}
