<?php

namespace App\Http\Controllers;

use App\DataTables\DiscountCodeDataTable;
use App\Helpers\AuthHelper;
use App\Models\DiscountCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DiscountCodeController extends Controller
{
    public function index(DiscountCodeDataTable $dataTable)
    {
        $authUser = AuthHelper::authSession();

        if (!$authUser->hasRole('admin') && !$authUser->hasAnyPermission(['discount-code-list', 'discount-code'])) {
            return redirect()->back()->withErrors(__('message.permission_denied_for_account'));
        }

        $pageTitle = __('message.list_form_title', ['form' => __('message.discount_codes')]);
        $assets = ['data-table'];

        return $dataTable->render('global.datatable', compact('pageTitle', 'assets', 'authUser'));
    }

    public function create(): View|RedirectResponse
    {
        $authUser = AuthHelper::authSession();

        if (!$authUser->hasRole('admin') && !$authUser->hasAnyPermission(['discount-code-add', 'discount-code'])) {
            return redirect()->back()->withErrors(__('message.permission_denied_for_account'));
        }

        $pageTitle = __('message.add_form_title', ['form' => __('message.discount_code')]);

        return view('discount-codes.form', compact('pageTitle'));
    }

    public function store(Request $request): RedirectResponse
    {
        $authUser = AuthHelper::authSession();

        if (!$authUser->hasRole('admin') && !$authUser->hasAnyPermission(['discount-code-add', 'discount-code'])) {
            return redirect()->back()->withErrors(__('message.permission_denied_for_account'));
        }

        $data = $this->validateRequest($request);

        DiscountCode::create($data);

        return redirect()->route('discount-codes.index')
            ->withSuccess(__('message.save_form', ['form' => __('message.discount_code')]));
    }

    public function edit(DiscountCode $discountCode): View|RedirectResponse
    {
        $authUser = AuthHelper::authSession();

        if (!$authUser->hasRole('admin') && !$authUser->hasAnyPermission(['discount-code-edit', 'discount-code'])) {
            return redirect()->back()->withErrors(__('message.permission_denied_for_account'));
        }

        $pageTitle = __('message.update_form_title', ['form' => __('message.discount_code')]);
        $data = $discountCode;
        $id = $discountCode->id;

        return view('discount-codes.form', compact('pageTitle', 'data', 'id'));
    }

    public function update(Request $request, DiscountCode $discountCode): RedirectResponse
    {
        $authUser = AuthHelper::authSession();

        if (!$authUser->hasRole('admin') && !$authUser->hasAnyPermission(['discount-code-edit', 'discount-code'])) {
            return redirect()->back()->withErrors(__('message.permission_denied_for_account'));
        }

        $data = $this->validateRequest($request, $discountCode->id);

        $discountCode->update($data);

        return redirect()->route('discount-codes.index')
            ->withSuccess(__('message.update_form', ['form' => __('message.discount_code')]));
    }

    public function destroy(DiscountCode $discountCode): RedirectResponse
    {
        $authUser = AuthHelper::authSession();

        if (!$authUser->hasRole('admin') && !$authUser->hasAnyPermission(['discount-code-delete', 'discount-code'])) {
            return redirect()->back()->withErrors(__('message.permission_denied_for_account'));
        }

        $discountCode->delete();

        return redirect()->route('discount-codes.index')
            ->withSuccess(__('message.delete_form', ['form' => __('message.discount_code')]));
    }

    protected function validateRequest(Request $request, ?int $id = null): array
    {
        $rules = [
            'code' => [
                'required',
                'string',
                'max:191',
                Rule::unique('discount_codes', 'code')->ignore($id),
            ],
            'name' => 'nullable|string|max:191',
            'description' => 'nullable|string|max:1000',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0.01',
            'is_active' => 'sometimes|boolean',
            'is_one_time_per_user' => 'sometimes|boolean',
            'max_redemptions' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
        ];

        if ($request->input('discount_type') === 'percentage') {
            $rules['discount_value'] .= '|max:100';
        }

        $validated = $request->validate($rules);

        $validated['code'] = mb_strtoupper($validated['code']);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_one_time_per_user'] = $request->boolean('is_one_time_per_user');
        $validated['max_redemptions'] = $validated['max_redemptions'] ?? null;
        $validated['starts_at'] = $validated['starts_at'] ? Carbon::parse($validated['starts_at']) : null;
        $validated['expires_at'] = $validated['expires_at'] ? Carbon::parse($validated['expires_at']) : null;

        if ($validated['discount_type'] === 'percentage') {
            $validated['discount_value'] = min($validated['discount_value'], 100);
        }

        return $validated;
    }
}
