<?php

namespace App\Http\Controllers;

use App\Services\StorageUsageService;
use Illuminate\View\View;

class AdminMaintenanceController extends Controller
{
    public function index(StorageUsageService $storageUsage): View
    {
        return view('admin.maintenance.index', $storageUsage->overview());
    }
}