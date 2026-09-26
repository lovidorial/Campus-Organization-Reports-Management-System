<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity as ActivityLog;

class AdminActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'actor' => $request->query('actor'),
            'action' => $request->query('action'),
            'subject' => $request->query('subject'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ];

        $logs = ActivityLog::query()
            ->with(['causer', 'subject'])
            ->when($filters['actor'], function ($query, $actor) {
                $query->where(function ($actorQuery) use ($actor) {
                    $actorQuery->whereHasMorph('causer', [User::class], function ($userQuery) use ($actor) {
                        $userQuery->where('name', 'like', "%{$actor}%")
                            ->orWhere('email', 'like', "%{$actor}%");
                    });

                    if (is_numeric($actor)) {
                        $actorQuery->orWhere('causer_id', (int) $actor);
                    }

                    $actorQuery->orWhere('causer_type', 'like', "%{$actor}%");
                });
            })
            ->when($filters['action'], function ($query, $action) {
                $query->where(function ($actionQuery) use ($action) {
                    $actionQuery->where('event', 'like', "%{$action}%")
                        ->orWhere('description', 'like', "%{$action}%");
                });
            })
            ->when($filters['subject'], function ($query, $subject) {
                $query->where(function ($subjectQuery) use ($subject) {
                    $subjectQuery->where('subject_type', 'like', "%{$subject}%")
                        ->orWhere('subject_id', 'like', "%{$subject}%");
                });
            })
            ->when($filters['date_from'], fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'], fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.activity-logs.index', compact('logs', 'filters'));
    }
}