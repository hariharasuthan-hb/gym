<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\DataTables\ActivityLogDataTable;
use Illuminate\View\View;

/**
 * Controller for managing activity logs in the admin panel.
 * 
 * Handles viewing activity logs which record member check-ins and gym
 * attendance. Activity logs show when members visit the gym and can be
 * filtered by date and member. Accessible by both admin and trainer roles
 * with 'view activities' permission.
 */
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

