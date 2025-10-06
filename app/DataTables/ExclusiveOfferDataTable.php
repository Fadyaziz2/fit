<?php

namespace App\DataTables;

use App\Models\ExclusiveOffer;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use App\Traits\DataTableTrait;

class ExclusiveOfferDataTable extends DataTable
{
    use DataTableTrait;

    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->editColumn('status', function ($offer) {
                $status = $offer->status === 'active' ? 'primary' : 'warning';
                return '<span class="text-capitalize badge bg-' . $status . '">' . $offer->status . '</span>';
            })
            ->editColumn('activated_at', function ($offer) {
                return $offer->activated_at ? dateAgoFormate($offer->activated_at, true) : '-';
            })
            ->editColumn('created_at', function ($offer) {
                return dateAgoFormate($offer->created_at, true);
            })
            ->editColumn('updated_at', function ($offer) {
                return dateAgoFormate($offer->updated_at, true);
            })
            ->addColumn('action', function ($offer) {
                $id = $offer->id;
                return view('exclusive-offer.action', compact('offer', 'id'))->render();
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

    public function query(ExclusiveOffer $model)
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
            ['data' => 'status', 'name' => 'status', 'title' => __('message.status')],
            ['data' => 'activated_at', 'name' => 'activated_at', 'title' => __('message.activated_at')],
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
