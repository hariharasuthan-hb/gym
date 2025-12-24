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
        $dataTable = datatables()->of($query);

        $this->autoFormatDates($dataTable, ['created_at', 'updated_at']);

        return $dataTable
            ->addColumn('title', function ($notification) {
                $data = json_decode($notification->data, true);
                return $data['title'] ?? 'Notification';
            })
            ->addColumn('type', function ($notification) {
                $data = json_decode($notification->data, true);
                return isset($data['type']) ? ucfirst(str_replace('_', ' ', $data['type'])) : '—';
            })
            ->addColumn('message', function ($notification) {
                $data = json_decode($notification->data, true);
                return \Illuminate\Support\Str::limit($data['message'] ?? '', 50);
            })
            ->addColumn('status', function ($notification) {
                return $notification->read_at ? 'Read' : 'Unread';
            })
            ->addColumn('user_name', function ($notification) {
                $user = \App\Models\User::find($notification->notifiable_id);
                return $user ? $user->name : 'Unknown';
            })
            ->addColumn('action', function ($notification) {
                // Notifications from notifications table are read-only, no edit/delete
                return '<div class="flex justify-end space-x-2">
                    <span class="text-xs text-gray-500">System Notification</span>
                </div>';
            })
            ->rawColumns(['action']);
    }

    public function query($model = null)
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
            Column::make('title')->title('Title')->orderable(false)->searchable(false)->width('20%')->addClass('text-left'),
            Column::make('type')->title('Type')->orderable(false)->searchable(false)->width('15%')->addClass('text-left'),
            Column::make('message')->title('Message')->orderable(false)->searchable(false)->width('25%')->addClass('text-left'),
            Column::make('user_name')->title('User')->orderable(false)->searchable(false)->width('15%')->addClass('text-left'),
            Column::make('status')->title('Status')->orderable(false)->searchable(false)->width('10%')->addClass('text-center'),
            Column::make('created_at')->title('Created At')->width('15%')->addClass('text-right'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->title('Actions')
                ->width('10%')
                ->addClass('text-right'),
        ];
    }
}

