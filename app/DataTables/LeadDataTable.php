<?php

namespace App\DataTables;

use App\Models\Lead;
use App\Models\User;
use App\Services\LeadAccessService;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Builder;

class LeadDataTable extends BaseDataTable
{
    /**
     * Build DataTable class.
     */
    public function dataTable($query)
    {
        $dataTable = datatables()
            ->eloquent($query);
        
        // Automatically format date columns
        $this->autoFormatDates($dataTable);
        
        return $dataTable
            ->addColumn('assigned_to', function ($lead) {
                return $lead->assignedTo ? $lead->assignedTo->name : '-';
            })
            ->editColumn('status', function ($lead) {
                $colors = [
                    'new' => 'bg-blue-100 text-blue-800',
                    'contacted' => 'bg-yellow-100 text-yellow-800',
                    'qualified' => 'bg-purple-100 text-purple-800',
                    'converted' => 'bg-green-100 text-green-800',
                    'lost' => 'bg-red-100 text-red-800',
                ];
                $color = $colors[$lead->status] ?? 'bg-gray-100 text-gray-800';
                return '<span class="px-2 py-1 text-xs font-semibold rounded-full ' . $color . '">' . $lead->readable_status . '</span>';
            })
            ->editColumn('source', function ($lead) {
                return '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">' . $lead->readable_source . '</span>';
            })
            ->editColumn('follow_up_date', function ($lead) {
                return $lead->follow_up_date ? $lead->follow_up_date->format('Y-m-d H:i') : '-';
            })
            ->addColumn('action', function ($lead) {
                $editUrl = route('admin.leads.edit', $lead->id);
                $showUrl = route('admin.leads.show', $lead->id);
                $deleteUrl = route('admin.leads.destroy', $lead->id);

                $html = '<div class="flex justify-center space-x-2">';
                $html .= '<a href="' . $showUrl . '" class="text-blue-600 hover:text-blue-900" title="View">';
                $html .= '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>';
                $html .= '</a>';
                
                if (auth()->user()->can('edit leads')) {
                    $html .= '<a href="' . $editUrl . '" class="text-indigo-600 hover:text-indigo-900" title="Edit">';
                    $html .= '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>';
                    $html .= '</a>';
                }
                
                if (auth()->user()->can('delete leads')) {
                    $html .= '<form action="' . $deleteUrl . '" method="POST" class="inline" data-confirm="true" data-confirm-title="Delete Lead" data-confirm-message="Are you sure you want to delete lead for ' . e($lead->name) . '? This action cannot be undone." data-confirm-button="Delete Lead" data-confirm-tone="danger">';
                    $html .= '<input type="hidden" name="_token" value="' . csrf_token() . '">';
                    $html .= '<input type="hidden" name="_method" value="DELETE">';
                    $html .= '<button type="submit" class="text-red-600 hover:text-red-900" title="Delete">';
                    $html .= '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>';
                    $html .= '</button></form>';
                }
                
                $html .= '</div>';
                
                return $html;
            })
            ->rawColumns(['action', 'status', 'source']);
    }

    public function html(): Builder
    {
        $formId = $this->getFilterFormId();

        $builder = $this->builder()
            ->setTableId($this->getTableId())
            ->columns($this->getColumns());

        // Add filter form data if form exists
        if ($formId) {
            $builder->ajax([
                'data' => "function(d) {
                    var form = document.getElementById('{$formId}');
                    if (form) {
                        var inputs = form.querySelectorAll('input, select, textarea');
                        for (var i = 0; i < inputs.length; i++) {
                            var input = inputs[i];
                            var name = input.name;
                            var value = input.value;
                            if (!name) continue;
                            if (input.type === 'date') {
                                d[name] = value || '';
                            } else if (value && value.toString().trim() !== '') {
                                d[name] = value;
                            }
                        }
                    }
                    return d;
                }"
            ]);
        }

        return $builder
            ->orderBy(0, 'desc') // ID column (index 0) descending - newest leads first
            ->buttons($this->getButtons())
            ->parameters([
                'dom' => "<'dt-toolbar flex flex-col md:flex-row md:items-center md:justify-between gap-4'<'dt-toolbar-left flex items-center gap-3'lB><'dt-toolbar-right'f>>" .
                    "<'dt-table'rt>" .
                    "<'dt-footer flex flex-col md:flex-row md:items-center md:justify-between gap-4'<'dt-info'i><'dt-pagination'p>>",
                'language' => [
                    'search' => '',
                    'searchPlaceholder' => 'Search...',
                    'lengthMenu' => '_MENU_',
                ],
                'responsive' => true,
                'autoWidth' => false,
                'pageLength' => 5,
                'lengthMenu' => [[5, 10, 25, 50, 100, -1], [5, 10, 25, 50, 100, "All"]],
                'order' => [[0, 'desc']],
                'stateSave' => false,
                'columnDefs' => [
                    [
                        'targets' => '_all',
                        'createdCell' => "function(td, cellData, rowData, row, col) {
                            var api = this.api();
                            var header = $(api.column(col).header());
                            if (header.hasClass('text-right')) {
                                $(td).addClass('text-right').css('text-align', 'right');
                            } else if (header.hasClass('text-center')) {
                                $(td).addClass('text-center').css('text-align', 'center');
                            } else {
                                $(td).addClass('text-left').css('text-align', 'left');
                            }
                        }"
                    ]
                ],
            ]);
    }

    /**
     * Get query source of dataTable.
     * Orders by ID DESC (newest leads first).
     * For trainers: only shows leads assigned to them
     * For admins: shows all leads
     */
    public function query(Lead $model)
    {
        $query = $model->newQuery()
            ->with(['assignedTo', 'createdBy']);
        
        // Apply trainer filter (reusable service)
        $query = LeadAccessService::applyTrainerFilter($query);
        
        // Default ordering: newest leads first (ID DESC)
        $query->orderByDesc('id');
        
        return $query;
    }

    /**
     * Get table ID
     */
    protected function getTableId(): string
    {
        return 'leads-table';
    }

    /**
     * Get columns definition
     */
    protected function getColumns(): array
    {
        return [
            Column::make('id')->title('ID')->width('5%')->addClass('text-right'),
            Column::make('name')->title('Name')->width('12%')->addClass('text-left'),
            Column::make('email')->title('Email')->width('15%')->addClass('text-left'),
            Column::make('phone')->title('Phone')->width('10%')->addClass('text-left'),
            Column::make('status')->title('Status')->width('10%')->orderable(false)->addClass('text-center'),
            Column::make('source')->title('Source')->width('10%')->orderable(false)->addClass('text-center'),
            Column::make('assigned_to')->title('Assigned To')->width('12%')->orderable(false)->searchable(false)->addClass('text-left'),
            Column::make('follow_up_date')->title('Follow Up Date')->width('12%')->addClass('text-right'),
            Column::make('created_at')->title('Created At')->width('12%')->addClass('text-right'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width('10%')
                ->addClass('text-center')
                ->title('Actions'),
        ];
    }
}
