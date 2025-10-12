<?php

namespace App\DataTables;

use App\Models\DiscountCode;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use App\Traits\DataTableTrait;

class DiscountCodeDataTable extends DataTable
{
    use DataTableTrait;

    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->editColumn('discount_value', function (DiscountCode $code) {
                $value = number_format($code->discount_value, 2);

                return $code->discount_type === 'percentage'
                    ? $value . '%'
                    : $value;
            })
            ->editColumn('is_active', function (DiscountCode $code) {
                $label = $code->is_active ? __('message.active') : __('message.inactive');
                $class = $code->is_active ? 'success' : 'secondary';

                return '<span class="badge bg-' . $class . '">' . e($label) . '</span>';
            })
            ->editColumn('is_one_time_per_user', function (DiscountCode $code) {
                $label = $code->is_one_time_per_user ? __('message.yes') : __('message.no');

                return '<span class="badge bg-light text-dark">' . e($label) . '</span>';
            })
            ->editColumn('starts_at', function (DiscountCode $code) {
                return optional($code->starts_at)->format(config('app.date_time_format', 'd M Y H:i')) ?: '-';
            })
            ->editColumn('expires_at', function (DiscountCode $code) {
                return optional($code->expires_at)->format(config('app.date_time_format', 'd M Y H:i')) ?: '-';
            })
            ->addColumn('action', function (DiscountCode $code) {
                $id = $code->id;

                return view('discount-codes.action', compact('code', 'id'))->render();
            })
            ->addIndexColumn()
            ->rawColumns(['is_active', 'is_one_time_per_user', 'action']);
    }

    public function query(DiscountCode $model)
    {
        return $model->newQuery();
    }

    protected function getColumns()
    {
        return [
            Column::make('DT_RowIndex')
                ->searchable(false)
                ->title(__('message.srno'))
                ->orderable(false),
            ['data' => 'code', 'name' => 'code', 'title' => __('message.discount_code')],
            ['data' => 'name', 'name' => 'name', 'title' => __('message.name')],
            ['data' => 'discount_type', 'name' => 'discount_type', 'title' => __('message.discount_type')],
            ['data' => 'discount_value', 'name' => 'discount_value', 'title' => __('message.discount_value')],
            ['data' => 'is_one_time_per_user', 'name' => 'is_one_time_per_user', 'title' => __('message.is_one_time_per_user'), 'orderable' => false],
            ['data' => 'is_active', 'name' => 'is_active', 'title' => __('message.status'), 'orderable' => false],
            ['data' => 'redemption_count', 'name' => 'redemption_count', 'title' => __('message.redemption_count')],
            ['data' => 'max_redemptions', 'name' => 'max_redemptions', 'title' => __('message.max_redemptions')],
            ['data' => 'starts_at', 'name' => 'starts_at', 'title' => __('message.starts_at')],
            ['data' => 'expires_at', 'name' => 'expires_at', 'title' => __('message.expires_at')],
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->title(__('message.action'))
                ->width(80)
                ->addClass('text-center hide-search'),
        ];
    }
}
