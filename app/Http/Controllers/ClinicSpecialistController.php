<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Specialist;
use Illuminate\Http\Request;

class ClinicSpecialistController extends Controller
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

        $pageTitle = __('message.list_form_title', ['form' => __('message.specialist')]);
        $specialists = Specialist::with(['branch', 'branches'])->orderBy('name')->paginate(15);

        return view('clinic.specialists.index', compact('pageTitle', 'specialists'));
    }

    public function create()
    {
        $this->authorizeAccess();

        $pageTitle = __('message.add_form_title', ['form' => __('message.specialist')]);
        $branches = Branch::orderBy('name')->pluck('name', 'id');

        return view('clinic.specialists.form', compact('pageTitle', 'branches'));
    }

    public function store(Request $request)
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'name' => 'required|string|max:191',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:191',
            'specialty' => 'nullable|string|max:191',
            'branch_ids' => 'required|array|min:1',
            'branch_ids.*' => 'exists:branches,id',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $branchIds = collect($request->input('branch_ids'))->filter()->unique()->values()->all();
        $primaryBranchId = collect($branchIds)->first();
        $primaryBranchId = $primaryBranchId !== null ? (int) $primaryBranchId : null;

        $specialist = Specialist::create(array_merge(
            $data,
            ['branch_id' => $primaryBranchId]
        ));

        $specialist->branches()->sync($branchIds);

        return redirect()->route('clinic.specialists.index')->withSuccess(__('message.save_form', ['form' => __('message.specialist')]));
    }

    public function edit(Specialist $specialist)
    {
        $this->authorizeAccess();

        $pageTitle = __('message.update_form_title', ['form' => __('message.specialist')]);
        $branches = Branch::orderBy('name')->pluck('name', 'id');

        $specialist->load('branches');

        return view('clinic.specialists.form', compact('pageTitle', 'branches', 'specialist'));
    }

    public function update(Request $request, Specialist $specialist)
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'name' => 'required|string|max:191',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:191',
            'specialty' => 'nullable|string|max:191',
            'branch_ids' => 'required|array|min:1',
            'branch_ids.*' => 'exists:branches,id',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $branchIds = collect($request->input('branch_ids'))->filter()->unique()->values()->all();
        $primaryBranchId = collect($branchIds)->first();
        $primaryBranchId = $primaryBranchId !== null ? (int) $primaryBranchId : null;

        $specialist->update(array_merge(
            $data,
            ['branch_id' => $primaryBranchId]
        ));

        $specialist->branches()->sync($branchIds);

        return redirect()->route('clinic.specialists.index')->withSuccess(__('message.update_form', ['form' => __('message.specialist')]));
    }

    public function destroy(Specialist $specialist)
    {
        $this->authorizeAccess();

        $specialist->delete();

        return redirect()->route('clinic.specialists.index')->withSuccess(__('message.delete_form', ['form' => __('message.specialist')]));
    }
}
