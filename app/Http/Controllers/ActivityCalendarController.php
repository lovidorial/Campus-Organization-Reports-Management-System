<?php

namespace App\Http\Controllers;

use App\Models\ActivityRequest;
use App\Models\GpoaActivity;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Support\Carbon;

class ActivityCalendarController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'venue' => ['nullable', 'integer', Rule::exists('venues', 'id')],
        ]);

        $month = isset($validated['month'])
            ? Carbon::createFromFormat('!Y-m', $validated['month'])
            : Carbon::now()->startOfMonth();
        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        $gpoaActivities = GpoaActivity::query()
            ->whereDate('date', '<=', $endOfMonth->toDateString())
            ->whereDate('date', '>=', $startOfMonth->toDateString())
            ->with(['gpoa.user', 'activityRequest.venueRecord:id,name'])
            ->orderBy('date')
            ->orderBy('title')
            ->get([
                'id', 'gpoa_id', 'activity_request_id', 'title', 'category', 'date', 'venue',
            ]);

        if (! $request->user()->isAdmin()) {
            $gpoaActivities = $gpoaActivities->filter(fn ($activity) => $activity->gpoa && $activity->gpoa->user_id === $request->user()->id);
        }

        $directRequests = ActivityRequest::query()
            ->whereDate('date', '<=', $endOfMonth->toDateString())
            ->whereDate('date', '>=', $startOfMonth->toDateString())
            ->with(['user', 'venueRecord:id,name'])
            ->when(! $request->user()->isAdmin(), fn ($query) => $query->where('user_id', $request->user()->id))
            ->whereNull('gpoa_activity_id')
            ->orderBy('date')
            ->orderBy('title')
            ->get();

        $activities = $gpoaActivities->merge($directRequests)->filter(function ($activity) {
            $request = $activity instanceof ActivityRequest ? $activity : $activity->activityRequest;
            if ($activity instanceof ActivityRequest) {
                $normalizedStatus = match ($activity->status) {
                    'approved', 'in_progress', 'awaiting_report', 'report_submitted' => 'Ongoing',
                    'closed' => 'Completed',
                    default => null,
                };

                if ($normalizedStatus === null) {
                    return false;
                }

                $activity->setAttribute('monitoring_status', $normalizedStatus);
                $activity->setAttribute('status', $normalizedStatus);
                $activity->setAttribute('start_time', $request?->start_time);
                $activity->setAttribute('end_time', $request?->end_time);
                $activity->setRelation('venueRecord', $request?->venueRecord);
                return true;
            }

            $activity->setAttribute('monitoring_status', $activity->monitoringStatus()['status']);
            $activity->setAttribute('status', $activity->monitoring_status);
            $activity->setAttribute('start_time', $request?->start_time);
            $activity->setAttribute('end_time', $request?->end_time);
            $activity->setRelation('venueRecord', $request?->venueRecord);
            return in_array($activity->monitoring_status, ['Pending', 'Ongoing', 'Completed'], true);
        })->values();

        if (! empty($validated['venue'])) {
            $venue = Venue::findOrFail($validated['venue']);
            $activities = $activities->filter(fn ($activity) => mb_strtolower(trim((string) ($activity->venue ?? $activity->venueRecord?->name))) === mb_strtolower(trim($venue->name)))->values();
        }

        $eventsByDay = [];
        foreach ($activities as $activity) {
            $activityStart = $activity->date->copy()->max($startOfMonth);
            $activityEnd = $activity->date->copy()->min($endOfMonth);

            for ($day = $activityStart->copy(); $day->lte($activityEnd); $day->addDay()) {
                $eventsByDay[$day->day][] = $activity;
            }
        }

        $leadingBlankCount = $startOfMonth->dayOfWeek;
        $trailingBlankCount = (7 - (($leadingBlankCount + $startOfMonth->daysInMonth) % 7)) % 7;
        $calendarCells = collect(array_fill(0, $leadingBlankCount, null))
            ->concat(range(1, $startOfMonth->daysInMonth))
            ->concat(array_fill(0, $trailingBlankCount, null))
            ->values()
            ->chunk(7);

        $venueNames = $activities->pluck('venue')->filter()->map(fn ($name) => mb_strtolower(trim($name)))->unique()->values();
        $venues = Venue::query()->orderBy('name')->get(['id', 'name'])
            ->filter(fn (Venue $venue) => $venueNames->contains(mb_strtolower(trim($venue->name))))
            ->values();

        return view('activities.calendar', [
            'month' => $month,
            'calendarCells' => $calendarCells,
            'eventsByDay' => $eventsByDay,
            'venues' => $venues,
            'selectedVenue' => $validated['venue'] ?? null,
            'today' => Carbon::today(),
        ]);
    }
}