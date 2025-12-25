<?php

namespace App\DataTables;

use App\Repositories\Interfaces\OrphanedVideoRepositoryInterface;
use Yajra\DataTables\Html\Column;

class OrphanedVideoDataTable extends BaseDataTable
{
    protected OrphanedVideoRepositoryInterface $orphanedVideoRepository;
    protected array $data = [];

    public function __construct(OrphanedVideoRepositoryInterface $orphanedVideoRepository)
    {
        $this->orphanedVideoRepository = $orphanedVideoRepository;
    }

    /**
     * Set data for the DataTable
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Build DataTable class.
     */
    public function dataTable($query = null)
    {
        $collection = empty($this->data) 
            ? $this->orphanedVideoRepository->scanOrphanedVideos() 
            : collect($this->data);

        return datatables()
            ->collection($collection)
            ->addColumn('action', function ($video) {
                $deleteUrl = route('admin.orphaned-videos.destroy');

                $html = '<div class="flex justify-center">';
                $html .= '<form action="' . $deleteUrl . '" method="POST" class="inline" data-confirm="true" data-confirm-title="Delete Video" data-confirm-message="Are you sure you want to delete this orphaned video? This action cannot be undone." data-confirm-button="Delete Video" data-confirm-tone="danger">';
                $html .= '<input type="hidden" name="_token" value="' . csrf_token() . '">';
                $html .= '<input type="hidden" name="_method" value="DELETE">';
                $html .= '<input type="hidden" name="video_path" value="' . e($video['path']) . '">';
                $html .= '<button type="submit" class="text-red-600 hover:text-red-900" title="Delete">';
                $html .= '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>';
                $html .= '</button></form>';
                $html .= '</div>';

                return $html;
            })
            ->editColumn('modified_at', function ($video) {
                if ($video['modified_at']) {
                    return \Carbon\Carbon::createFromTimestamp($video['modified_at'])->format('Y-m-d H:i:s');
                }
                return '-';
            })
            ->rawColumns(['action']);
    }

    /**
     * Get table ID
     */
    protected function getTableId(): string
    {
        return 'orphaned-videos-table';
    }

    /**
     * Get columns definition
     */
    protected function getColumns(): array
    {
        return [
            Column::make('path')->title('Video Path')->width('40%')->addClass('text-left'),
            Column::make('directory')->title('Directory')->width('20%')->addClass('text-left'),
            Column::make('size_formatted')->title('Size')->width('15%')->addClass('text-right'),
            Column::make('modified_at')->title('Modified At')->width('15%')->addClass('text-center'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width('10%')
                ->addClass('text-center')
                ->title('Actions'),
        ];
    }

    /**
     * Override HTML builder to disable server-side processing and AJAX
     */
    public function html(): \Yajra\DataTables\Html\Builder
    {
        return $this->builder()
            ->setTableId($this->getTableId())
            ->columns($this->getColumns())
            ->serverSide(false)
            ->processing(false)
            ->ajax(false)
            ->orderBy(2, 'desc')
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
                'pageLength' => 10,
                'lengthMenu' => [[5, 10, 25, 50, 100, -1], [5, 10, 25, 50, 100, "All"]],
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
     * Override filter form ID to disable AJAX
     */
    protected function getFilterFormId(): string
    {
        return '';
    }

    /**
     * Get buttons for DataTable
     */
    protected function getButtons(): array
    {
        return [];
    }

    /**
     * Process and format data for DataTables
     */
    protected function processDataForTable(): array
    {
        $rawData = empty($this->data) 
            ? $this->orphanedVideoRepository->scanOrphanedVideos() 
            : $this->data;
        
        $processedData = [];
        
        foreach ($rawData as $video) {
            $modifiedAt = '-';
            if (isset($video['modified_at']) && $video['modified_at']) {
                try {
                    $modifiedAt = \Carbon\Carbon::createFromTimestamp($video['modified_at'])->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    $modifiedAt = '-';
                }
            }
            
            $processedData[] = [
                'path' => $video['path'] ?? '',
                'directory' => $video['directory'] ?? '',
                'size_formatted' => $video['size_formatted'] ?? '0 B',
                'modified_at' => $modifiedAt,
                'action' => $this->generateActionHtml($video),
            ];
        }
        
        return $processedData;
    }

    /**
     * Generate action HTML for a video
     */
    protected function generateActionHtml(array $video): string
    {
        $deleteUrl = route('admin.orphaned-videos.destroy');
        $path = e($video['path'] ?? '');

        $html = '<div class="flex justify-center">';
        $html .= '<form action="' . $deleteUrl . '" method="POST" class="inline" data-confirm="true" data-confirm-title="Delete Video" data-confirm-message="Are you sure you want to delete this orphaned video? This action cannot be undone." data-confirm-button="Delete Video" data-confirm-tone="danger">';
        $html .= '<input type="hidden" name="_token" value="' . csrf_token() . '">';
        $html .= '<input type="hidden" name="_method" value="DELETE">';
        $html .= '<input type="hidden" name="video_path" value="' . $path . '">';
        $html .= '<button type="submit" class="text-red-600 hover:text-red-900" title="Delete">';
        $html .= '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>';
        $html .= '</button></form>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Generate scripts with data initialization
     */
    public function scripts(): string
    {
        $tableId = $this->getTableId();
        $processedData = $this->processDataForTable();
        
        if (empty($processedData)) {
            return "
            <script>
            (function() {
                function initTable() {
                    if (typeof window.$ === 'undefined' || typeof window.$.fn.DataTable === 'undefined') {
                        setTimeout(initTable, 100);
                        return;
                    }
                    console.log('No orphaned videos data to display');
                }
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initTable);
                } else {
                    initTable();
                }
            })();
            </script>
            ";
        }
        
        $jsonData = json_encode($processedData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES);

        return "
        <script>
        (function() {
            function initDataTable() {
                if (typeof window.$ === 'undefined' || typeof window.$.fn.DataTable === 'undefined') {
                    setTimeout(initDataTable, 100);
                    return;
                }
                
                try {
                    var \$ = window.$;
                    var tableData = {$jsonData};
                    console.log('Orphaned videos data loaded:', tableData.length, 'videos');
                    
                    if (!tableData || tableData.length === 0) {
                        console.warn('No data to display in DataTable');
                        return;
                    }
                    
                    var table = \$('#{$tableId}').DataTable({
                        data: tableData,
                        columns: [
                            { data: 'path', name: 'path' },
                            { data: 'directory', name: 'directory' },
                            { data: 'size_formatted', name: 'size_formatted' },
                            { data: 'modified_at', name: 'modified_at' },
                            { data: 'action', name: 'action', orderable: false, searchable: false }
                        ],
                        serverSide: false,
                        processing: false,
                        order: [[2, 'desc']],
                        dom: \"<'dt-toolbar flex flex-col md:flex-row md:items-center md:justify-between gap-4'<'dt-toolbar-left flex items-center gap-3'l><'dt-toolbar-right'f>>\" +
                            \"<'dt-table'rt>\" +
                            \"<'dt-footer flex flex-col md:flex-row md:items-center md:justify-between gap-4'<'dt-info'i><'dt-pagination'p>>\",
                        language: {
                            search: '',
                            searchPlaceholder: 'Search...',
                            lengthMenu: '_MENU_'
                        },
                        responsive: true,
                        autoWidth: false,
                        pageLength: 10,
                        lengthMenu: [[5, 10, 25, 50, 100, -1], [5, 10, 25, 50, 100, 'All']],
                        columnDefs: [{
                            targets: '_all',
                            createdCell: function(td, cellData, rowData, row, col) {
                                var api = this.api();
                                var header = \$(api.column(col).header());
                                if (header.hasClass('text-right')) {
                                    \$(td).addClass('text-right').css('text-align', 'right');
                                } else if (header.hasClass('text-center')) {
                                    \$(td).addClass('text-center').css('text-align', 'center');
                                } else {
                                    \$(td).addClass('text-left').css('text-align', 'left');
                                }
                            }
                        }]
                    });
                    
                    console.log('DataTable initialized successfully with', table.rows().count(), 'rows');
                } catch (error) {
                    console.error('Error initializing DataTable:', error);
                }
            }
            
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initDataTable);
            } else {
                initDataTable();
            }
        })();
        </script>
        ";
    }
}
