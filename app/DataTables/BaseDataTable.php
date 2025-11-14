<?php

namespace App\DataTables;

use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Html\Column;

abstract class BaseDataTable
{
    /**
     * Build DataTable html.
     */
    public function html(): Builder
    {
        return $this->builder()
            ->setTableId($this->getTableId())
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0, 'asc')
            ->parameters([
                'layout' => [
                    'topStart' => ['pageLength', 'search', 'buttons'],
                    'bottomStart' => ['info'],
                    'bottomEnd' => ['paging'],
                ],
                'buttons' => $this->getButtons(),
                'language' => [
                    'search' => '',
                    'searchPlaceholder' => 'Search...',
                ],
                'responsive' => true,
                'autoWidth' => false,
                'pageLength' => 25,
                'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            ]);
    }

    /**
     * Get HTML builder instance.
     */
    protected function builder(): Builder
    {
        return app(Builder::class);
    }

    /**
     * Get the table ID for this DataTable
     */
    abstract protected function getTableId(): string;

    /**
     * Public method to get table ID
     */
    public function getTableIdPublic(): string
    {
        return $this->getTableId();
    }

    /**
     * Get columns definition
     */
    abstract protected function getColumns(): array;

    /**
     * Get buttons configuration
     */
    protected function getButtons(): array
    {
        return [
            'excel',
            'csv',
            'pdf',
            'reload',
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return $this->getTableId() . '_' . date('YmdHis');
    }

    /**
     * Get scripts for DataTable
     * Uses Yajra's built-in scripts() method
     */
    public function scripts(): string
    {
        return $this->html()->scripts();
    }
}
