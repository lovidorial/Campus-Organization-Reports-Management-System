<?php

namespace App\Http\Controllers;

use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\VenueAvailabilityService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ActivityRequestController extends Controller
{
    public function index()
    {
        return redirect()->route('activity-monitor.index');
    }

    public function show(ActivityRequest $activityRequest)
    {
        $this->authorize('view', $activityRequest);
        $activityRequest->load([
            'user',
            'gpoa',
            'gpoaActivity',
            'report',
            'monitoringResult',
            'programFlows',
            'venueRecord' => fn ($query) => $query->withCount(['scheduledRequests', 'futureReservationRequests']),
        ]);

        return view('users.activity-request-show', compact('activityRequest'));
    }

    public function downloadPdf(ActivityRequest $activityRequest)
    {
        $this->authorize('view', $activityRequest);

        $activityRequest->loadMissing(['user.organization', 'gpoa', 'programFlows']);
        $organization = $activityRequest->user?->organization;
        $organizationName = $organization?->name ?? $activityRequest->user?->org_name ?? 'Campus Organization';
        $logoDataUri = null;
        $logoPath = $organization?->logo_path;

        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            $imageInfo = @getimagesize(Storage::disk('public')->path($logoPath));
            $mimeType = $imageInfo['mime'] ?? null;
            if ($mimeType && str_starts_with($mimeType, 'image/')) {
                $logoDataUri = 'data:' . $mimeType . ';base64,' . base64_encode(Storage::disk('public')->get($logoPath));
            }
        }

        return Pdf::loadView('users.activity-request-pdf', [
            'activityRequest' => $activityRequest,
            'organizationName' => $organizationName,
            'logoDataUri' => $logoDataUri,
        ])
            ->setPaper('letter', 'portrait')
            ->download('activity-request-' . $activityRequest->id . '.pdf');
    }

    public function monitor()
    {
        $user = auth()->user();
        $term = $user->term ?? '1st Term';
        $schoolYear = $user->school_year ?? (date('Y') . '-' . (date('Y') + 1));

        $gpoas = Gpoa::where('user_id', $user->id)
            ->where('term', $term)
            ->where('school_year', $schoolYear)
            ->with([
                'activities' => fn ($query) => $query->orderBy('date')->withMonitoringData(),
            ])
            ->get();

        $activities = $gpoas->flatMap(fn ($gpoa) => $gpoa->activities->map(function ($activity) use ($gpoa) {
            $status = $activity->monitoringStatus();
            $activity->monitor_status = $status['status'];
            $activity->monitor_late = $status['late'];
            $activity->monitor_gpoa = $gpoa;
            return $activity;
        }));

        $completedCount = $activities->filter(fn ($activity) => $activity->monitor_status === 'Completed')->count();
        $progressPercent = $activities->isEmpty() ? 0 : (int) round(($completedCount / $activities->count()) * 100);

        return view('users.activity-monitor', compact('activities', 'completedCount', 'progressPercent', 'term', 'schoolYear'));
    }

    public function statuses()
    {
        $requests = ActivityRequest::where('user_id', auth()->id())
            ->with(['gpoaActivity' => fn ($query) => $query->withMonitoringData(), 'report', 'monitoringResult'])
            ->latest()
            ->get();

        return response()->json([
            'requests' => $requests->map(fn ($request) => [
                'id' => $request->id,
                'status' => $request->gpoaActivity?->monitoringStatus()['status'] ?? 'Not Started',
                'monitoring_compliance_status' => $request->monitoringResult?->compliance_status,
            ])->values(),
        ]);
    }

    public function create(Request $request)
    {
        $user = auth()->user();
        $term = $user->term ?? '1st Term';
        $schoolYear = $user->school_year ?? (date('Y') . '-' . (date('Y') + 1));

        $availableGpoas = Gpoa::where('user_id', auth()->id())
            ->where('term', $term)
            ->where('school_year', $schoolYear)
            ->with('activities')
            ->orderBy('school_year', 'desc')
            ->orderByRaw("CASE WHEN term = '1st Term' THEN 0 ELSE 1 END")
            ->get();

        if ($availableGpoas->isEmpty()) {
            return redirect()->route('gpoa.create')
                ->with('error', 'Create your GPOA first, then add planned activities to begin monitoring.');
        }

        $selectedGpoaId = $request->query('gpoa') ?: $availableGpoas->first()->id;
        $gpoa = $availableGpoas->firstWhere('id', $selectedGpoaId) ?: $availableGpoas->first();

        $categoryCounts = ActivityRequest::where('user_id', auth()->id())
            ->where('gpoa_id', $gpoa->id)
            ->selectRaw('category, count(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();

        $activityLimits = config('gpoa_activity_limits', []);
        $usedCount = array_sum($categoryCounts);
        $limitCount = array_sum($activityLimits);
        $atCap = $usedCount >= $limitCount;
        $activityLimitTemplate = (object) [
            'used' => $usedCount,
            'limit' => $limitCount,
        ];

        return view('users.create-request', compact(
            'availableGpoas',
            'gpoa',
            'selectedGpoaId',
            'activityLimits',
            'categoryCounts',
            'usedCount',
            'limitCount',
            'atCap',
            'activityLimitTemplate'
        ));
    }

    public function store(Request $request, VenueAvailabilityService $venueAvailability)
    {
        $programFlows = collect($request->input('program_flows', []))
            ->filter(fn ($row) => is_array($row) && (
                filled($row['time'] ?? null)
                || filled($row['flow'] ?? null)
                || filled($row['person_in_charge'] ?? null)
            ))
            ->values()
            ->all();
        $request->merge(['program_flows' => $programFlows]);

        $validated = $request->validate([
            'gpoa_id' => [
                'required',
                Rule::exists('gpoas', 'id')->where(function ($q) {
                    $q->where('user_id', auth()->id());
                }),
            ],
            'gpoa_activity_id' => [
                'required',
                Rule::exists('gpoa_activities', 'id')->where(function ($q) use ($request) {
                    $q->where('gpoa_id', $request->input('gpoa_id'));
                }),
            ],
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'sdgs' => 'required|array|min:1|max:8',
            'sdgs.*' => 'integer|between:1,17',
            'objectives' => 'required|string',
            'expected_outcome' => 'required|string',
            'plan_key_strategy' => 'required|string',
            'date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after_or_equal:date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'venue' => 'required|string|max:255',
            'target_participants' => 'required|string|max:255',
            'person_in_charge' => 'required|string|max:255',
            'facilities_materials' => 'required|string|max:255',
            'estimated_budget' => 'required|numeric|min:0',
            'source_of_funds' => 'required|string|max:100',
            'program_flows' => 'nullable|array',
            'program_flows.*.time' => 'required|string|max:50',
            'program_flows.*.flow' => 'required|string|max:255',
            'program_flows.*.person_in_charge' => 'required|string|max:255',
        ], [
            'date.after_or_equal' => 'Activity date cannot be in the past.',
        ]);

        $gpoa = Gpoa::findOrFail($validated['gpoa_id']);

        $linkedActivity = GpoaActivity::where('id', $validated['gpoa_activity_id'])
            ->where('gpoa_id', $gpoa->id)
            ->first();

        if (!$linkedActivity) {
            return back()->withErrors(['gpoa_activity_id' => 'The selected planned GPOA activity could not be found.'])->withInput();
        }

        $categoryLimit = config('gpoa_activity_limits.' . $validated['category']);
        if ($categoryLimit !== null) {
            $existingCount = ActivityRequest::where('user_id', auth()->id())
                ->where('gpoa_id', $gpoa->id)
                ->where('category', $validated['category'])
                ->count();

            if ($existingCount >= $categoryLimit) {
                return back()->withErrors(['category' => "You have reached the limit of {$categoryLimit} requests for {$validated['category']} under this GPOA."])->withInput();
            }
        }

        $existing = ActivityRequest::where('gpoa_id', $gpoa->id)
            ->where('title', $validated['title'])
            ->where('date', $validated['date'])
            ->where('venue', $validated['venue'])
            ->whereNotIn('status', ['cancelled', 'deleted'])
            ->exists();

        if ($existing) {
            return back()->withErrors(['title' => 'An activity request with the same title, date, and venue already exists for this GPOA.'])->withInput();
        }

        $venue = $venueAvailability->resolveVenue($validated['venue']);
        $validated['venue_id'] = $venue->id;
        $conflict = $venueAvailability->conflictingRequest($validated);

        if ($conflict) {
            return back()->withErrors([
                'venue' => "Another activity is already scheduled at this venue and time ({$conflict->title}). Choose a different time or venue.",
            ])->withInput();
        }

        $activityRequest = ActivityRequest::create([
            'user_id' => auth()->id(),
            'gpoa_id' => $gpoa->id,
            'gpoa_activity_id' => $validated['gpoa_activity_id'] ?? null,
            'title' => $validated['title'],
            'date' => $validated['date'],
            'end_date' => $validated['end_date'] ?? null,
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'venue' => $validated['venue'],
            'venue_id' => $venue->id,
            'category' => $validated['category'],
            'sdgs' => $validated['sdgs'],
            'objectives' => $validated['objectives'],
            'expected_outcome' => $validated['expected_outcome'],
            'plan_key_strategy' => $validated['plan_key_strategy'],
            'target_participants' => $validated['target_participants'],
            'person_in_charge' => $validated['person_in_charge'],
            'facilities_materials' => $validated['facilities_materials'],
            'estimated_budget' => $validated['estimated_budget'],
            'source_of_funds' => $validated['source_of_funds'],
            'preceding_activity' => $linkedActivity->preceding_activity,
            'participants_count' => $linkedActivity->participants_count,
        ]);

        $activityRequest->replaceProgramFlows($validated['program_flows'] ?? []);

        if ($activityRequest->gpoa_activity_id) {
            $linkedActivity->update([
                'activity_request_id' => $activityRequest->id,
                'objectives' => $validated['objectives'],
                'expected_outcome' => $validated['expected_outcome'],
                'plan_key_strategy' => $validated['plan_key_strategy'],
                'target_participants' => $validated['target_participants'],
                'person_in_charge' => $validated['person_in_charge'],
                'facilities_materials' => $validated['facilities_materials'],
                'estimated_budget' => $validated['estimated_budget'],
                'source_of_funds' => $validated['source_of_funds'],
            ]);
        }

        User::where('role', 'admin')->each(function (User $admin) use ($activityRequest) {
            UserNotification::create([
                'user_id' => $admin->id,
                'type' => 'activity_request_submitted',
                'title' => 'New Activity Request',
                'message' => "{$activityRequest->title} was submitted by " . auth()->user()->org_name . '.',
            ]);
        });

        return redirect()->route('activity-requests.show', $activityRequest)
            ->with('success', 'Activity request added.');
    }

}
