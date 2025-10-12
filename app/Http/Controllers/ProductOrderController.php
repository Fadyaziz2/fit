<?php

namespace App\Http\Controllers;

use App\DataTables\ProductOrderDataTable;
use App\Helpers\AuthHelper;
use App\Models\ProductOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Notifications\DatabaseNotification;


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

        $productOrder->load(['product', 'user', 'discountCode']);

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
            'status_comment' => 'nullable|string|max:1000',
        ]);

        $status = $request->input('status');
        $comment = $request->input('status_comment');

        $originalStatus = $productOrder->status;
        $originalComment = $productOrder->status_comment;

        $productOrder->update([
            'status' => $status,
            'status_comment' => $comment,
        ]);

        $productOrder->loadMissing(['product', 'user']);

        if ($productOrder->user) {
            $statusChanged = $originalStatus !== $status;
            $commentChanged = $originalComment !== $comment && filled($comment);

            if ($statusChanged || $commentChanged) {
                $this->sendStatusNotification($productOrder);
            }
        }

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
            'placed' => __('message.placed'),
            'confirmed' => __('message.confirmed'),
            'shipped' => __('message.shipped'),
            'delivered' => __('message.delivered'),
            'cancelled' => __('message.cancelled'),
            'returned' => __('message.returned'),
        ];
    }

    /**
     * Notify the customer about the status update.
     */
    protected function sendStatusNotification(ProductOrder $productOrder): void
    {
        $user = $productOrder->user;

        if (!$user) {
            return;
        }

        $product = $productOrder->product;
        $statusKey = 'message.' . $productOrder->status;
        $statusLabel = __($statusKey);

        if ($statusLabel === $statusKey) {
            $statusLabel = ucfirst(str_replace('_', ' ', $productOrder->status));
        }

        $data = [
            'type' => 'product_order_status',
            'subject' => __('message.order_status_updated_subject'),
            'message' => __('message.order_status_updated_message', [
                'product' => $product->title ?? __('message.product'),
                'status' => $statusLabel,
            ]),
            'order_id' => $productOrder->id,
            'status' => $productOrder->status,
            'status_label' => $statusLabel,
            'comment' => $productOrder->status_comment,
        ];

        $user->notify(new DatabaseNotification($data));
    }
}
