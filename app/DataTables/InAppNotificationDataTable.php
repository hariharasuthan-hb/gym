<?php

namespace App\DataTables;

use App\Models\InAppNotification;
use App\Repositories\Interfaces\InAppNotificationRepositoryInterface;
use Yajra\DataTables\Html\Column;

class InAppNotificationDataTable extends BaseDataTable
{
    public function __construct(
        private readonly InAppNotificationRepositoryInterface $notificationRepository
    ) {
    }

    public function dataTable($query)
    {
        $dataTable = datatables()->eloquent($query);

        $this->autoFormatDates($dataTable, ['created_at', 'updated_at', 'published_at', 'scheduled_for']);

        return $dataTable
            ->editColumn('audience_type', fn ($notification) => ucfirst($notification->audience_type))
            ->editColumn('status', fn ($notification) => ucfirst($notification->status))
            ->addColumn('target', function ($notification) {
                return $notification->audience_type === InAppNotification::AUDIENCE_USER
                    ? ($notification->targetUser?->name ?? 'Unknown')
                    : '—';
            })
            ->addColumn('creator_name', fn ($notification) => $notification->creator?->name ?? 'System')
            ->addColumn('action', function ($notification) {
                $editUrl = route('admin.notifications.edit', $notification);
                $deleteUrl = route('admin.notifications.destroy', $notification);

                $html = '<div class="flex justify-end space-x-2">';
                if (auth()->user()->can('edit notifications')) {
                    $html .= '<a href="' . $editUrl . '" class="btn btn-xs btn-secondary">Edit</a>';
                }
                if (auth()->user()->can('delete notifications')) {
                    $html .= '<form action="' . $deleteUrl . '" method="POST" onsubmit="return confirm(\'Delete this notification?\');">';
                    $html .= csrf_field();
                    $html .= method_field('DELETE');
                    $html .= '<button type="submit" class="btn btn-xs btn-danger">Delete</button>';
                    $html .= '</form>';
                }
                $html .= '</div>';

                return $html;
            })
            ->rawColumns(['action']);
    }

    public function query(InAppNotification $model)
    {
        $filters = request()->only([
            'status',
            'audience_type',
            'requires_acknowledgement',
            'scheduled_from',
            'scheduled_to',
            'published_from',
            'published_to',
            'search',
        ]);

        return $this->notificationRepository->queryForDataTable($filters);
    }

    protected function getTableId(): string
    {
        return 'notifications-table';
    }

    protected function getFilterFormId(): string
    {
        return 'notifications-filter-form';
    }

    protected function getColumns(): array
    {
        return [
            Column::make('id')->title('ID')->width('5%')->addClass('text-right'),
            Column::make('title')->title('Title')->width('20%')->addClass('text-left'),
            Column::make('audience_type')->title('Audience')->width('10%')->addClass('text-left'),
            Column::make('target')->title('Target User')->orderable(false)->searchable(false)->width('15%')->addClass('text-left'),
            Column::make('status')->title('Status')->width('10%')->addClass('text-center'),
            Column::make('scheduled_for')->title('Scheduled For')->width('15%')->addClass('text-right'),
            Column::make('published_at')->title('Published At')->width('15%')->addClass('text-right'),
            Column::make('creator_name')->title('Created By')->orderable(false)->searchable(false)->width('10%')->addClass('text-left'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->title('Actions')
                ->width('15%')
                ->addClass('text-right'),
        ];
    }
}

