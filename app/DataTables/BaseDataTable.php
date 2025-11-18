<?php

namespace App\DataTables;

use App\DataTables\Traits\AutoFormatsDates;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Html\Column;

abstract class BaseDataTable
{
    use AutoFormatsDates;
    /**
     * Build DataTable html.
     */
    public function html(): Builder
    {
        $url = request()->url();
        $formId = $this->getFilterFormId();
        
        return $this->builder()
            ->setTableId($this->getTableId())
            ->columns($this->getColumns())
            ->ajax([
                'url' => $url,
                'data' => "function(d) {
                    // Get filter form values and merge with DataTables parameters
                    var form = document.getElementById('{$formId}');
                    if (form) {
                        // Get all form inputs
                        var inputs = form.querySelectorAll('input, select, textarea');
                        for (var i = 0; i < inputs.length; i++) {
                            var input = inputs[i];
                            var name = input.name;
                            var value = input.value;
                            
                            // Skip if no name
                            if (!name) {
                                continue;
                            }
                            
                            // For date inputs, always include (even if empty) to clear filters
                            // For other inputs, skip empty values
                            if (input.type === 'date') {
                                d[name] = value || '';
                            } else if (value && value.toString().trim() !== '') {
                                d[name] = value;
                            }
                        }
                    }
                    return d;
                }"
            ])
            ->orderBy(0, 'asc')
            ->buttons($this->getButtons())
            ->parameters([
                'layout' => [
                    'topStart' => ['pageLength'],
                    'topEnd' => ['buttons', 'search'],
                    'bottomStart' => ['info'],
                    'bottomEnd' => ['paging'],
                ],
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
     * Get filter form ID for this DataTable.
     * Override in child classes if needed.
     */
    protected function getFilterFormId(): string
    {
        // Default pattern: {table-id}-filter-form
        return str_replace('_', '-', $this->getTableId()) . '-filter-form';
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
