<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Specialist;
use App\Models\User;
use App\Traits\HandlesBranchAccess;
use Illuminate\Http\Request;

class ClinicSpecialistController extends Controller
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

        $pageTitle = __('message.list_form_title', ['form' => __('message.specialist')]);
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
            ->paginate(15);

        return view('clinic.specialists.index', compact('pageTitle', 'specialists'));
    }

    public function create()
    {
        $user = $this->authorizeAccess();
        $branchIds = $this->getAccessibleBranchIds($user);

        $pageTitle = __('message.add_form_title', ['form' => __('message.specialist')]);
        $branches = Branch::orderBy('name')
            ->when($branchIds !== null, function ($query) use ($branchIds) {
                $query->whereIn('id', $branchIds);
            })
            ->pluck('name', 'id');

        $superUsers = User::query()
            ->where('user_type', '!=', 'user')
            ->orderBy('display_name')
            ->pluck('display_name', 'id');

        return view('clinic.specialists.form', compact('pageTitle', 'branches', 'superUsers'));
    }

    public function store(Request $request)
    {
        $user = $this->authorizeAccess();
        $branchIdsScope = $this->getAccessibleBranchIds($user);

        $data = $request->validate([
            'name' => 'required|string|max:191',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:191',
            'specialty' => 'nullable|string|max:191',
            'branch_ids' => 'required|array|min:1',
            'branch_ids.*' => 'exists:branches,id',
            'super_user_id' => 'nullable|exists:users,id',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $branchIds = collect($request->input('branch_ids'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($branchIdsScope !== null && array_diff($branchIds, $branchIdsScope)) {
            abort(403, __('message.permission_denied_for_account'));
        }
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
        $user = $this->authorizeAccess();
        $branchIds = $this->getAccessibleBranchIds($user);
        $this->ensureSpecialistAccessible($specialist, $branchIds);

        $pageTitle = __('message.update_form_title', ['form' => __('message.specialist')]);
        $branches = Branch::orderBy('name')
            ->when($branchIds !== null, function ($query) use ($branchIds) {
                $query->whereIn('id', $branchIds);
            })
            ->pluck('name', 'id');

        $specialist->load('branches');

        $superUsers = User::query()
            ->where('user_type', '!=', 'user')
            ->orderBy('display_name')
            ->pluck('display_name', 'id');

        return view('clinic.specialists.form', compact('pageTitle', 'branches', 'specialist', 'superUsers'));
    }

    public function update(Request $request, Specialist $specialist)
    {
        $user = $this->authorizeAccess();
        $branchIdsScope = $this->getAccessibleBranchIds($user);
        $this->ensureSpecialistAccessible($specialist, $branchIdsScope);

        $data = $request->validate([
            'name' => 'required|string|max:191',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:191',
            'specialty' => 'nullable|string|max:191',
            'branch_ids' => 'required|array|min:1',
            'branch_ids.*' => 'exists:branches,id',
            'super_user_id' => 'nullable|exists:users,id',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $branchIds = collect($request->input('branch_ids'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($branchIdsScope !== null && array_diff($branchIds, $branchIdsScope)) {
            abort(403, __('message.permission_denied_for_account'));
        }
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
        $user = $this->authorizeAccess();
        $branchIds = $this->getAccessibleBranchIds($user);
        $this->ensureSpecialistAccessible($specialist, $branchIds);

        $specialist->delete();

        return redirect()->route('clinic.specialists.index')->withSuccess(__('message.delete_form', ['form' => __('message.specialist')]));
    }
}
