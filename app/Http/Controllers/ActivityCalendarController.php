<?php

namespace App\Http\Controllers;

use App\Models\ActivityRequest;
use App\Models\GpoaActivity;
use App\Models\User;
use App\Models\Venue;
use App\Services\VenueAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Support\Carbon;

class ActivityCalendarController extends Controller
{
    public function index(Request $request, VenueAvailabilityService $venueAvailability): View
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'venue' => ['nullable', 'integer', Rule::exists('venues', 'id')],
            'organization' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'college' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['Pending', 'Ongoing', 'Completed'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $month = isset($validated['month'])
            ? Carbon::createFromFormat('!Y-m', $validated['month'])
            : Carbon::now()->startOfMonth();
        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        $gpoaActivities = GpoaActivity::query()
            ->whereDate('date', '<=', $endOfMonth->toDateString())
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $startOfMonth->toDateString()))
            ->when(! $request->user()->isAdmin(), fn ($query) => $query->whereHas(
                'gpoa',
                fn ($gpoaQuery) => $gpoaQuery->where('user_id', $request->user()->id)
            ))
            ->with(['gpoa.user.organization', 'activityRequest.user.organization', 'activityRequest.gpoa', 'activityRequest.venueRecord:id,name'])
            ->orderBy('date')
            ->orderBy('title')
            ->get([
                'id', 'gpoa_id', 'activity_request_id', 'title', 'category', 'date', 'venue',
            ]);

        $directRequests = ActivityRequest::query()
            ->whereDate('date', '<=', $endOfMonth->toDateString())
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $startOfMonth->toDateString()))
            ->with(['user.organization', 'gpoa.user.organization', 'venueRecord:id,name'])
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
                    'rejected' => 'Pending',
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
                $activity->setAttribute('organization_name', $activity->gpoa?->user?->org_name ?: $activity->user?->org_name ?: $activity->gpoa?->user?->name ?: $activity->user?->name ?: 'Organization not set');
                $activity->setAttribute('planned_date', null);
                $activity->setAttribute('planned_end_date', null);
                $activity->setAttribute('actual_date', $activity->date);
                $activity->setAttribute('actual_end_date', $activity->end_date);
                return true;
            }

            $activity->setAttribute('monitoring_status', $activity->monitoringStatus()['status']);
            $activity->setAttribute('status', $activity->monitoring_status);
            $activity->setAttribute('start_time', $request?->start_time ?? $activity->start_time);
            $activity->setAttribute('end_time', $request?->end_time ?? $activity->end_time);
            $activity->setRelation('venueRecord', $request?->venueRecord);
            $activity->setAttribute('organization_name', $activity->gpoa?->user?->org_name ?: $activity->gpoa?->user?->name ?: 'Organization not set');
            $activity->setAttribute('planned_date', $activity->date);
            $activity->setAttribute('planned_end_date', $activity->end_date);
            $activity->setAttribute('actual_date', $request?->date);
            $activity->setAttribute('actual_end_date', $request?->end_date);
            return in_array($activity->monitoring_status, ['Pending', 'Ongoing', 'Completed'], true);
        })->values();

        $visibleActivities = $activities;
        $selectedOrganization = $request->user()->isAdmin() ? ($validated['organization'] ?? null) : null;
        $rangeStart = isset($validated['date_from']) ? Carbon::parse($validated['date_from'])->startOfDay() : $startOfMonth;
        $rangeEnd = isset($validated['date_to']) ? Carbon::parse($validated['date_to'])->endOfDay() : $endOfMonth;
        $activities = $activities->filter(function ($activity) use ($validated, $selectedOrganization, $rangeStart, $rangeEnd, $request) {
            $ownerId = $activity instanceof ActivityRequest ? $activity->user_id : $activity->gpoa?->user_id;
            $college = $activity->gpoa?->college
                ?? $activity->user?->college
                ?? $activity->user?->organization?->college
                ?? $activity->gpoa?->user?->college
                ?? $activity->gpoa?->user?->organization?->college;
            $activityEnd = $activity->end_date ?? $activity->date;

            return (! $selectedOrganization || (string) $ownerId === (string) $selectedOrganization)
                && (! filled($validated['college'] ?? null) || mb_strtolower(trim((string) $college)) === mb_strtolower(trim($validated['college'])))
                && (! filled($validated['status'] ?? null) || $activity->status === $validated['status'])
                && $activity->date?->lte($rangeEnd)
                && $activityEnd?->gte($rangeStart);
        })->values();

        if (! empty($validated['venue'])) {
            $venue = Venue::findOrFail($validated['venue']);
            $activities = $activities->filter(fn ($activity) => mb_strtolower(trim((string) ($activity->venue ?? $activity->venueRecord?->name))) === mb_strtolower(trim($venue->name)))->values();
        }

        foreach ($activities as $activity) {
            $activityRequest = $activity instanceof ActivityRequest ? $activity : $activity->activityRequest;
            $conflictDate = $activityRequest?->date ?? $activity->date;
            $conflictEndDate = $activityRequest?->end_date ?? $activity->end_date ?? $conflictDate;
            $conflict = $venueAvailability->conflictingRequest([
                'venue_id' => $activityRequest?->venue_id,
                'venue' => $activity->venue ?: $activity->venueRecord?->name,
                'date' => $conflictDate?->toDateString(),
                'end_date' => $conflictEndDate?->toDateString(),
                'start_time' => $activityRequest?->start_time ?? $activity->start_time,
                'end_time' => $activityRequest?->end_time ?? $activity->end_time,
            ], $activityRequest?->id);

            if ($conflict) {
                $conflict->loadMissing(['user', 'gpoaActivity.gpoa.user']);
            }

            $activity->setAttribute('conflicting_activity', $conflict);
            $activity->setAttribute('conflict_details_visible', $request->user()->isAdmin() || $conflict?->user_id === $request->user()->id);
        }

        $eventsByDay = [];
        foreach ($activities as $activity) {
            $activityStart = $activity->date->copy()->max($startOfMonth);
            $activityEnd = ($activity->end_date ?? $activity->date)->copy()->min($endOfMonth);

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

        $venueNames = $visibleActivities->pluck('venue')->filter()->map(fn ($name) => mb_strtolower(trim($name)))->unique()->values();
        $venues = Venue::query()->orderBy('name')->get(['id', 'name'])
            ->filter(fn (Venue $venue) => $venueNames->contains(mb_strtolower(trim($venue->name))))
            ->values();
        $organizations = $request->user()->isAdmin()
            ? User::query()->whereIn('id', $visibleActivities->map(fn ($activity) => $activity instanceof ActivityRequest ? $activity->user_id : $activity->gpoa?->user_id)->filter()->unique())->orderBy('org_name')->get(['id', 'org_name', 'name'])
            : collect();
        $colleges = $visibleActivities->map(fn ($activity) => $activity->gpoa?->college
            ?? $activity->user?->college
            ?? $activity->user?->organization?->college
            ?? $activity->gpoa?->user?->college
            ?? $activity->gpoa?->user?->organization?->college)->filter()->unique()->sort()->values();

        return view('activities.calendar', [
            'month' => $month,
            'calendarCells' => $calendarCells,
            'eventsByDay' => $eventsByDay,
            'venues' => $venues,
            'selectedVenue' => $validated['venue'] ?? null,
            'organizations' => $organizations,
            'colleges' => $colleges,
            'filters' => array_merge(['organization' => '', 'college' => '', 'status' => '', 'date_from' => '', 'date_to' => ''], $validated),
            'today' => Carbon::today(),
        ]);
    }
}