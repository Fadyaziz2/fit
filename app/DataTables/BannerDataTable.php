<?php

namespace App\DataTables;

use App\Models\Banner;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use App\Traits\DataTableTrait;

class BannerDataTable extends DataTable
{
    use DataTableTrait;

    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->editColumn('status', function ($banner) {
                $status = $banner->status === 'active' ? 'primary' : 'warning';
                return '<span class="text-capitalize badge bg-' . $status . '">' . $banner->status . '</span>';
            })
            ->editColumn('created_at', function ($banner) {
                return dateAgoFormate($banner->created_at, true);
            })
            ->editColumn('updated_at', function ($banner) {
                return dateAgoFormate($banner->updated_at, true);
            })
            ->addColumn('action', function ($banner) {
                $id = $banner->id;
                return view('banner.action', compact('banner', 'id'))->render();
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
                        $direction = $order['dir'];
                    }
                    $query->orderBy($columnName, $direction);
                }
            })
            ->rawColumns(['status', 'action']);
    }

    public function query(Banner $model)
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
            ['data' => 'title', 'name' => 'title', 'title' => __('message.title')],
            ['data' => 'subtitle', 'name' => 'subtitle', 'title' => __('message.subtitle')],
            ['data' => 'button_text', 'name' => 'button_text', 'title' => __('message.button_text')],
            ['data' => 'redirect_url', 'name' => 'redirect_url', 'title' => __('message.redirect_url')],
            ['data' => 'display_order', 'name' => 'display_order', 'title' => __('message.display_order')],
            ['data' => 'status', 'name' => 'status', 'title' => __('message.status')],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('message.created_at')],
            ['data' => 'updated_at', 'name' => 'updated_at', 'title' => __('message.updated_at')],
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->title(__('message.action'))
                ->width(60)
                ->addClass('text-center hide-search'),
        ];
    }
}
