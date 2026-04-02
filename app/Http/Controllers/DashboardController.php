<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        // Redirect admins to admin dashboard
        if (Auth::user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $activities = Activity::where('user_id', auth()->id())->latest()->paginate(10);

        $stats = [
            'total'    => Activity::count(),
            'pending'  => Activity::where('status', 'pending')->count(),
            'approved' => Activity::where('status', 'approved')->count(),
            'rejected' => Activity::where('status', 'rejected')->count(),
        ];

        return view('dashboard', compact('activities', 'stats'));
    }
}
