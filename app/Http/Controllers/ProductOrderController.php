<?php

namespace App\Http\Controllers;

use App\DataTables\ProductOrderDataTable;
use App\Helpers\AuthHelper;
use App\Models\ProductOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductOrderController extends Controller
{
    /**
     * Display a listing of the product orders.
     */
    public function index(ProductOrderDataTable $dataTable)
    {
        $auth_user = AuthHelper::authSession();

        if (!$auth_user->can('product-list')) {
            $message = __('message.permission_denied_for_account');

            return redirect()->back()->withErrors($message);
        }

        $pageTitle = __('message.list_form_title', ['form' => __('message.product_order')]);
        $assets = ['data-table'];

        return $dataTable->render('global.datatable', compact('pageTitle', 'assets', 'auth_user'));
    }

    /**
     * Display the specified product order.
     */
    public function show(ProductOrder $productOrder): View|RedirectResponse
    {
        $auth_user = AuthHelper::authSession();

        if (!$auth_user->can('product-list')) {
            $message = __('message.permission_denied_for_account');

            return redirect()->back()->withErrors($message);
        }

        $productOrder->load(['product', 'user']);

        $pageTitle = __('message.detail_form_title', ['form' => __('message.product_order')]);
        $statuses = $this->availableStatuses();

        return view('product.orders.show', compact('pageTitle', 'productOrder', 'statuses', 'auth_user'));
    }

    /**
     * Update the specified product order in storage.
     */
    public function update(Request $request, ProductOrder $productOrder): RedirectResponse
    {
        $auth_user = AuthHelper::authSession();

        if (!$auth_user->can('product-edit')) {
            $message = __('message.permission_denied_for_account');

            return redirect()->back()->withErrors($message);
        }

        $request->validate([
            'status' => 'required|string|in:' . implode(',', array_keys($this->availableStatuses())),
        ]);

        $productOrder->update([
            'status' => $request->input('status'),
        ]);

        return redirect()
            ->route('product-orders.show', $productOrder)
            ->withSuccess(__('message.update_form', ['form' => __('message.product_order')]));
    }

    /**
     * Get available order statuses.
     */
    protected function availableStatuses(): array
    {
        return [
            'pending' => __('message.pending'),
            'processing' => __('message.processing'),
            'completed' => __('message.completed'),
            'delivered' => __('message.delivered'),
            'cancelled' => __('message.cancelled'),
        ];
    }
}
