<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity as ActivityLog;

class AdminActivityLogController extends Controller
{
    private const ACTIVITY_TYPE_LABELS = [
        'backup_created' => 'Backup Created',
        'backup_deleted' => 'Backup Deleted',
        'backup_downloaded' => 'Backup Downloaded',
        'backup_restored' => 'Backup Restored',
        'backup_schedule_changed' => 'Backup Schedule Changed',
        'activity_report.approved' => 'Report Approved',
        'admin_user.created' => 'User Created',
        'organization.deactivated' => 'Organization Deactivated',
    ];

    public function index(Request $request): View
    {
        $filters = [
            'actor' => $request->query('actor'),
            'action' => $request->query('action'),
            'subject' => $request->query('subject'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ];

        $activityTypes = ActivityLog::query()
            ->whereNotNull('event')
            ->distinct()
            ->pluck('event')
            ->merge(
                ActivityLog::query()
                    ->whereNull('event')
                    ->whereNotNull('description')
                    ->distinct()
                    ->pluck('description')
                    ->filter(fn (string $description) => preg_match('/^[a-z_]+(\.[a-z_]+)*$/', $description) === 1)
            )
            ->unique()
            ->sort()
            ->mapWithKeys(fn (string $code) => [
                $code => self::ACTIVITY_TYPE_LABELS[$code] ?? ucwords(str_replace(['.', '_'], ' ', $code)),
            ]);

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
                    $actionQuery->where('event', $action)
                        ->orWhere('description', $action);
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

        return view('admin.activity-logs.index', compact('logs', 'filters', 'activityTypes'));
    }
}