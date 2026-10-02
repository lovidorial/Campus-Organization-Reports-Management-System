<?php

namespace App\Http\Controllers;

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
        $submissionStatus = $user->gpoaSubmissionStatus();
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
            'Pending' => 0,
            'Ongoing' => 0,
            'Completed' => 0,
            'Archived' => 0,
            'Late' => 0,
        ];

        $visibleActivities = $activities->filter(fn ($activity) => ! $activity->archived_at)->values();
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
        $totalCount = $visibleActivities->count();
        $overallPercent = $visibleActivities->isEmpty() ? 0 : (int) round(($completedCount / $visibleActivities->count()) * 100);
        $unreadCount = $user->unreadNotificationsCount();
        $activities = $visibleActivities;
        $duplicateCurrentGpoa = $user->term && $user->school_year
            ? $user->gpoas()->where('term', $user->term)->where('school_year', $user->school_year)->exists()
            : false;
        $canSubmitGpoa = $user->isAdmin() || ($submissionStatus['allowed'] && ! $duplicateCurrentGpoa);
        $submissionBlockMessage = null;
        if (! $submissionStatus['allowed']) {
            $blockingGpoa = $submissionStatus['blockingGpoa'];
            $submissionBlockMessage = "Finish all activities in {$blockingGpoa->term} SY {$blockingGpoa->school_year} first. {$submissionStatus['unfinishedCount']} remaining.";
        } elseif ($duplicateCurrentGpoa) {
            $submissionBlockMessage = "A GPOA for {$user->term} / SY {$user->school_year} has already been submitted.";
        }

        return view('dashboard', compact(
            'gpoa',
            'activities',
            'counts',
            'totalCount',
            'completedCount',
            'overallPercent',
            'term',
            'schoolYear',
            'unreadCount',
            'canSubmitGpoa',
            'submissionBlockMessage'
        ));
    }
}
