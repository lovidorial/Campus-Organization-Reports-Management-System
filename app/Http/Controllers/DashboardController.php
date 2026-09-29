<?php

namespace App\Http\Controllers;

use App\Models\Gpoa;
use App\Models\GpoaActivity;
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

        $gpoa = Gpoa::where('user_id', $user->id)
            ->where('term', $term)
            ->where('school_year', $schoolYear)
            ->with([
                'activities' => fn ($query) => $query->withMonitoringData()->orderBy('date'),
            ])
            ->latest()
            ->first();

        $activities = $gpoa?->activities ?? collect();

        $counts = [
            'Not Started' => 0,
            'Ongoing' => 0,
            'Completed' => 0,
            'Late' => 0,
        ];

        foreach ($activities as $activity) {
            $status = $activity->monitoringStatus();
            $label = $status['status'];

            if (! isset($counts[$label])) {
                $counts[$label] = 0;
            }

            $counts[$label]++;

            if ($status['late']) {
                $counts['Late']++;
            }
        }

        $completedCount = $counts['Completed'];
        $overallPercent = $activities->isEmpty() ? 0 : (int) round(($completedCount / $activities->count()) * 100);
        $unreadCount = $user->unreadNotificationsCount();

        return view('dashboard', compact(
            'gpoa',
            'activities',
            'counts',
            'completedCount',
            'overallPercent',
            'term',
            'schoolYear',
            'unreadCount'
        ));
    }
}
