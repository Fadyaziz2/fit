<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class ClinicBranchController extends Controller
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

        $pageTitle = __('message.list_form_title', ['form' => __('message.branch')]);
        $branches = Branch::orderBy('name')->paginate(15);

        return view('clinic.branches.index', compact('pageTitle', 'branches'));
    }

    public function create()
    {
        $this->authorizeAccess();

        $pageTitle = __('message.add_form_title', ['form' => __('message.branch')]);
        return view('clinic.branches.form', compact('pageTitle'));
    }

    public function store(Request $request)
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'name' => 'required|string|max:191',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:191',
            'address' => 'nullable|string',
        ]);

        Branch::create($data);

        return redirect()->route('clinic.branches.index')->withSuccess(__('message.save_form', ['form' => __('message.branch')]));
    }

    public function edit(Branch $branch)
    {
        $this->authorizeAccess();

        $pageTitle = __('message.update_form_title', ['form' => __('message.branch')]);
        return view('clinic.branches.form', compact('pageTitle', 'branch'));
    }

    public function update(Request $request, Branch $branch)
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'name' => 'required|string|max:191',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:191',
            'address' => 'nullable|string',
        ]);

        $branch->update($data);

        return redirect()->route('clinic.branches.index')->withSuccess(__('message.update_form', ['form' => __('message.branch')]));
    }

    public function destroy(Branch $branch)
    {
        $this->authorizeAccess();

        $branch->delete();

        return redirect()->route('clinic.branches.index')->withSuccess(__('message.delete_form', ['form' => __('message.branch')]));
    }
}
