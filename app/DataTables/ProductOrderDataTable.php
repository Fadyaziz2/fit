<?php

namespace App\DataTables;

use App\Models\ProductOrder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

use App\Traits\DataTableTrait;

class ProductOrderDataTable extends DataTable
{
    use DataTableTrait;

    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->editColumn('status', function ($order) {
                $statusClass = 'warning';
                switch ($order->status) {
                    case 'delivered':
                        $statusClass = 'success';
                        break;
                    case 'confirmed':
                        $statusClass = 'info';
                        break;
                    case 'shipped':
                        $statusClass = 'primary';
                        break;
                    case 'cancelled':
                    case 'canceled':
                    case 'returned':
                        $statusClass = 'danger';
                        break;
                    case 'placed':
                    default:
                        $statusClass = 'warning';
                        break;
                }

                $statusLabel = __('message.' . $order->status);
                if ($statusLabel === 'message.' . $order->status) {
                    $statusLabel = ucfirst(str_replace('_', ' ', $order->status));
                }

                return '<span class="badge bg-' . $statusClass . ' text-capitalize">' . $statusLabel . '</span>';
            })
            ->addColumn('product_title', function ($order) {
                return $order->product->title ?? '-';
            })
            ->addColumn('user_name', function ($order) {
                $user = $order->user;
                if (!$user) {
                    return '-';
                }

                if (!empty($user->display_name)) {
                    return $user->display_name;
                }

                $fullName = trim(sprintf('%s %s', $user->first_name, $user->last_name));
                if (!empty($fullName)) {
                    return $fullName;
                }

                if (!empty($user->username)) {
                    return $user->username;
                }

                return $user->email ?? '-';
            })
            ->addColumn('discount_code', function ($order) {
                return $order->discount_code ?? '-';
            })
            ->editColumn('discount_amount', function ($order) {
                return number_format($order->discount_amount ?? 0, 2);
            })
            ->addColumn('action', function ($order) {
                $id = $order->id;
                return view('product.orders.action', compact('order', 'id'))->render();
            })
            ->addIndexColumn()
            ->order(function ($query) {
                if (request()->has('order')) {
                    $order = request()->order[0];
                    $columnIndex = $order['column'];
                    $columnName = 'id';
                    $direction = 'desc';

                    if ($columnIndex != 0) {
                            $columnName = request()->columns[$columnIndex]['data'];
                            $columnName = match ($columnName) {
                                'product_title' => 'product_id',
                                'user_name' => 'user_id',
                                'status' => 'status',
                                'discount_code' => 'discount_code',
                                'discount_amount' => 'discount_amount',
                                default => $columnName,
                            };
                            $direction = $order['dir'];
                        }

                    $query->orderBy('product_orders.' . $columnName, $direction);
                }
            })
            ->rawColumns(['status', 'action']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\ProductOrder $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(ProductOrder $model)
    {
        $model = ProductOrder::query()->with(['product', 'user']);

        return $this->applyScopes($model);
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            Column::make('DT_RowIndex')
                ->searchable(false)
                ->title(__('message.srno'))
                ->orderable(false),
            ['data' => 'product_title', 'name' => 'product.title', 'title' => __('message.product'), 'orderable' => false, 'searchable' => false],
            ['data' => 'user_name', 'name' => 'user.display_name', 'title' => __('message.user_name'), 'orderable' => false, 'searchable' => false],
            ['data' => 'quantity', 'name' => 'quantity', 'title' => __('message.quantity')],
            ['data' => 'status', 'name' => 'status', 'title' => __('message.status')],
            ['data' => 'discount_code', 'name' => 'discount_code', 'title' => __('message.discount_code'), 'orderable' => false],
            ['data' => 'discount_amount', 'name' => 'discount_amount', 'title' => __('message.discount_amount')],
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->title(__('message.action'))
                ->width(60)
                ->addClass('text-center hide-search'),
        ];
    }
}
