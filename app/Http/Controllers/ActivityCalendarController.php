<?php

namespace App\Http\Controllers;

use App\Models\ActivityRequest;
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

        $query = ActivityRequest::query()
            ->whereIn('status', [
                ActivityRequest::STATUS_APPROVED,
                ActivityRequest::STATUS_IN_PROGRESS,
                ActivityRequest::STATUS_CLOSED,
            ])
            ->whereDate('date', '<=', $endOfMonth->toDateString())
            ->where(function ($query) use ($startOfMonth) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $startOfMonth->toDateString());
            })
            ->with('venueRecord:id,name')
            ->orderBy('date')
            ->orderBy('start_time');

        if (! $request->user()->isAdmin()) {
            $query->where('user_id', $request->user()->id);
        }

        if (! empty($validated['venue'])) {
            $query->where('venue_id', $validated['venue']);
        }

        $activities = $query->get([
            'id', 'user_id', 'venue_id', 'title', 'category', 'date', 'end_date',
            'start_time', 'end_time', 'venue', 'status',
        ]);

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

        $venuesQuery = Venue::query()
            ->whereHas('activityRequests', function ($query) use ($request, $startOfMonth, $endOfMonth) {
                $query->whereIn('status', [
                    ActivityRequest::STATUS_APPROVED,
                    ActivityRequest::STATUS_IN_PROGRESS,
                    ActivityRequest::STATUS_CLOSED,
                ])
                    ->whereDate('date', '<=', $endOfMonth->toDateString())
                    ->where(function ($query) use ($startOfMonth) {
                        $query->whereNull('end_date')
                            ->orWhereDate('end_date', '>=', $startOfMonth->toDateString());
                    });

                if (! $request->user()->isAdmin()) {
                    $query->where('user_id', $request->user()->id);
                }
            })
            ->orderBy('name');

        $venues = $venuesQuery->get(['id', 'name']);

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