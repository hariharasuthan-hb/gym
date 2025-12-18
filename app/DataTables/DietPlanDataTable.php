<?php

namespace App\DataTables;

use App\Models\DietPlan;
use Yajra\DataTables\Html\Column;

class DietPlanDataTable extends BaseDataTable
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
            ->addColumn('member_name', function ($plan) {
                return $plan->member->name ?? '-';
            })
            ->addColumn('trainer_name', function ($plan) {
                return $plan->trainer->name ?? '-';
            })
            ->addColumn('status_badge', function ($plan) {
                $statusColors = [
                    'active' => 'bg-green-100 text-green-800',
                    'completed' => 'bg-blue-100 text-blue-800',
                    'paused' => 'bg-yellow-100 text-yellow-800',
                    'cancelled' => 'bg-red-100 text-red-800',
                ];
                $color = $statusColors[$plan->status] ?? 'bg-gray-100 text-gray-800';
                return '<span class="px-2 py-1 text-xs font-semibold rounded-full ' . $color . '">' . ucfirst($plan->status) . '</span>';
            })
            ->addColumn('target_calories_formatted', function ($plan) {
                return $plan->target_calories ? number_format($plan->target_calories) . ' cal' : '-';
            })
            ->addColumn('action', function ($plan) {
                $showUrl = route('admin.diet-plans.show', $plan->id);
                $editUrl = route('admin.diet-plans.edit', $plan->id);
                $deleteUrl = route('admin.diet-plans.destroy', $plan->id);
                
                $html = '<div class="flex justify-center space-x-2">';
                $html .= '<a href="' . $showUrl . '" class="text-blue-600 hover:text-blue-900" title="View">';
                $html .= '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>';
                $html .= '</a>';
                
                if (auth()->user()->can('edit diet plans') && 
                    (!auth()->user()->hasRole('trainer') || $plan->trainer_id === auth()->id())) {
                    $html .= '<a href="' . $editUrl . '" class="text-indigo-600 hover:text-indigo-900" title="Edit">';
                    $html .= '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>';
                    $html .= '</a>';
                }
                
                if (auth()->user()->can('delete diet plans') && 
                    (!auth()->user()->hasRole('trainer') || $plan->trainer_id === auth()->id())) {
                    $html .= '<form action="' . $deleteUrl . '" method="POST" class="inline" data-confirm="true" data-confirm-title="Delete Diet Plan" data-confirm-message="Deleting ' . e($plan->plan_name) . ' will remove it from the member timeline. Continue?" data-confirm-button="Delete Plan" data-confirm-tone="danger">';
                    $html .= '<input type="hidden" name="_token" value="' . csrf_token() . '">';
                    $html .= '<input type="hidden" name="_method" value="DELETE">';
                    $html .= '<button type="submit" class="text-red-600 hover:text-red-900" title="Delete">';
                    $html .= '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>';
                    $html .= '</button></form>';
                }
                
                $html .= '</div>';
                
                return $html;
            })
            ->rawColumns(['status_badge', 'action']);
    }

    /**
     * Get query source of dataTable.
     */
    public function query(DietPlan $model)
    {
        $query = $model->newQuery()->with(['member', 'trainer']);
        
        // If user is a trainer, filter by their plans
        if (auth()->user()->hasRole('trainer')) {
            $query->where('trainer_id', auth()->id());
        }
        
        return $query;
    }

    /**
     * Get table ID
     */
    protected function getTableId(): string
    {
        return 'diet-plans-table';
    }

    /**
     * Get columns definition
     */
    protected function getColumns(): array
    {
        $columns = [
            Column::make('id')->title('ID')->width('5%')->addClass('text-right'),
            Column::make('plan_name')->title('Plan Name')->width('15%')->addClass('text-left'),
            Column::make('member_name')->title('Member')->width('15%')->orderable(false)->searchable(false)->addClass('text-left'),
        ];

        // Only show trainer column for admins
        if (auth()->user()->hasRole('admin')) {
            $columns[] = Column::make('trainer_name')->title('Trainer')->width('15%')->orderable(false)->searchable(false)->addClass('text-left');
        }

        $columns = array_merge($columns, [
            Column::make('status_badge')->title('Status')->width('10%')->orderable(false)->searchable(false)->addClass('text-center'),
            Column::make('target_calories_formatted')->title('Target Calories')->width('12%')->orderable(false)->searchable(false)->addClass('text-right'),
            Column::make('start_date')->title('Start Date')->width('12%')->addClass('text-right'),
            Column::make('end_date')->title('End Date')->width('12%')->addClass('text-right'),
            Column::make('created_at')->title('Created At')->width('11%')->addClass('text-right'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width('15%')
                ->addClass('text-center')
                ->title('Actions'),
        ]);

        return $columns;
    }
}

