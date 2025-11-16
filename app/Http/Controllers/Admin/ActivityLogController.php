<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\DataTables\ActivityLogDataTable;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of activity logs.
     * Accessible by both admin and trainer (filtered by permission).
     */
    public function index(ActivityLogDataTable $dataTable)
    {
        if (request()->ajax() || request()->wantsJson()) {
            return $dataTable->dataTable($dataTable->query(new \App\Models\ActivityLog))->toJson();
        }
        
        return view('admin.activities.index', [
            'dataTable' => $dataTable
        ]);
    }
}

