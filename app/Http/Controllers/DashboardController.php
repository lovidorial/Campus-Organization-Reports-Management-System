<?php

namespace App\Http\Controllers;

use App\Models\ActivityRequest;
use App\Models\Gpoa;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        if (Auth::user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $user = auth()->user();
        $term = $user->term ?? '1st Term';
        $schoolYear = $user->school_year ?? (date('Y') . '-' . (date('Y') + 1));

        $gpoa = Gpoa::where('user_id', auth()->id())
            ->where('term', $term)
            ->where('school_year', $schoolYear)
            ->with('activities')
            ->latest()
            ->first();

        $activities = ActivityRequest::where('user_id', auth()->id())
            ->with(['gpoaActivity', 'report'])
            ->latest()
            ->paginate(10);

        $stats = [
            'total'    => ActivityRequest::where('user_id', auth()->id())->count(),
            'pending'  => ActivityRequest::where('user_id', auth()->id())->where('status', 'pending')->count(),
            'approved' => ActivityRequest::where('user_id', auth()->id())
                ->whereIn('status', ['approved', 'in_progress', 'awaiting_report', 'report_submitted', 'closed'])->count(),
            'rejected' => ActivityRequest::where('user_id', auth()->id())->where('status', 'rejected')->count(),
        ];

        $hasApprovedGpoa = $user->approvedGpoaForCurrentPeriod();

        return view('dashboard', compact('activities', 'stats', 'gpoa', 'hasApprovedGpoa', 'term', 'schoolYear'));
    }
}
