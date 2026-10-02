<?php

namespace App\Http\Controllers;

use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\OutstandingActivityReportService;
use App\Services\VenueAvailabilityService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
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
            'gpoaActivity.monitoringResult.admin',
            'gpoaActivity',
            'report.photos',
            'report.reviewer',
            'monitoringResult.admin',
            'monitoringResult',
            'programFlows',
            'venueRecord' => fn ($query) => $query->withCount(['scheduledRequests', 'futureReservationRequests']),
        ]);

        $gpoaActivity = $activityRequest->gpoaActivity;
    $reviewerRemarks = $activityRequest->monitoringResult ?? $gpoaActivity?->monitoringResult;
        $activityNumber = null;

        if ($gpoaActivity?->date && $gpoaActivity->gpoa) {
            $activityNumber = $gpoaActivity->gpoa->activities()
                ->orderBy('date')
                ->orderBy('id')
                ->pluck('id')
                ->search($gpoaActivity->id);
            $activityNumber = $activityNumber === false ? null : $activityNumber + 1;
        }

        return view('users.activity-request-show', compact('activityRequest', 'activityNumber', 'reviewerRemarks'));
    }

    public function downloadPdf(ActivityRequest $activityRequest)
    {
        $this->authorize('view', $activityRequest);

        $activityRequest->loadMissing(['user.organization', 'gpoa', 'gpoaActivity.gpoa', 'programFlows']);
        $organization = $activityRequest->user?->organization;
        $organizationName = $organization?->name ?? $activityRequest->user?->org_name ?? '';
        $imageDataUri = static function (array $paths): ?string {
            foreach ($paths as $path) {
                $fullPath = public_path($path);
                if (! is_file($fullPath)) {
                    continue;
                }

                $mimeType = mime_content_type($fullPath);
                $contents = file_get_contents($fullPath);
                if ($mimeType && str_starts_with($mimeType, 'image/') && $contents !== false) {
                    return 'data:' . $mimeType . ';base64,' . base64_encode($contents);
                }
            }

            return null;
        };
        $templateImages = [
            'csuLogo' => $imageDataUri(['images/pdf-template/csu-logo.png', 'images/osdw.logo.jpg']),
            'campusBuilding' => $imageDataUri([
                'images/pdf-template/campus-building.png',
                'images/pdfTemplateimg/9404d1ab-7999-4b20-ac10-da93a79681bf.jpg',
            ]),
            'footerLogos' => $imageDataUri([
                'images/pdf-template/footer-logos.png',
                'images/pdfTemplateimg/377479f2-fc78-4c12-add2-8429cf284be7.jpg',
            ]),
        ];
        $organizationCollege = $organization?->college
            ?? $activityRequest->gpoa?->college
            ?? $activityRequest->user?->college;

        return Pdf::loadView('users.activity-request-pdf', [
            'activityRequest' => $activityRequest,
            'organizationName' => $organizationName,
            'organizationCollege' => $organizationCollege,
            'templateImages' => $templateImages,
        ])
            ->setPaper('legal', 'portrait')
            ->download('activity-request-' . $activityRequest->id . '.pdf');
    }

    public function monitor(Request $request, OutstandingActivityReportService $outstandingReportService)
    {
        $user = auth()->user();
        $outstandingReports = $outstandingReportService->forUser($user)
            ->map(fn (ActivityRequest $activityRequest) => [
                'title' => $activityRequest->title,
                'url' => route('activity-reports.create', $activityRequest),
            ])
            ->values();
        $term = $user->term ?? '1st Term';
        $schoolYear = $user->school_year ?? (date('Y') . '-' . (date('Y') + 1));

        $gpoas = Gpoa::where('user_id', $user->id)
            ->where('term', $term)
            ->where('school_year', $schoolYear)
            ->with([
                'activities' => fn ($query) => $query->orderBy('date')->orderBy('id')->withMonitoringData(),
            ])
            ->get();

        $allActivities = $gpoas->flatMap(fn ($gpoa) => $gpoa->activities->values()->map(function ($activity, $index) use ($gpoa) {
            $status = $activity->monitoringStatus();
            $activityRequest = $activity->activityRequest;
            $letterSubmittedAt = filled($activityRequest?->communication_letter)
                ? ($activityRequest->communication_letter_signed_at ?? $activityRequest->updated_at)
                : null;
            $reportSubmittedAt = $activityRequest?->report?->submitted_at;
            $requestUpdatedAt = $activityRequest?->updated_at;
            $lastSubmittedAt = collect([$letterSubmittedAt, $reportSubmittedAt, $requestUpdatedAt])
                ->filter()
                ->sortByDesc(fn ($submittedAt) => $submittedAt->timestamp)
                ->first();
            $activity->monitor_status = $status['status'];
            $activity->monitor_late = $status['late'];
            $activity->last_submitted_at = $lastSubmittedAt;
            $activity->monitor_gpoa = $gpoa;
            $activity->activity_number = $index + 1;
            return $activity;
        }));

        $tab = $request->query('tab', 'submitted') === 'todo' ? 'todo' : 'submitted';
        $sort = $request->query('sort', 'latest');
        $statusFilter = (string) $request->query('status', '');
        $search = mb_strtolower(trim((string) $request->query('search', '')));
        $searchMatches = fn ($activity) => $search === '' || str_contains(mb_strtolower(implode(' ', array_filter([
            $activity->title,
            $activity->venue,
            $activity->date?->toDateString(),
        ]))), $search);
        $submittedActivities = $allActivities->filter(fn ($activity) => $activity->last_submitted_at !== null && ! $activity->archived_at && $searchMatches($activity))->values();
        $archivedActivities = $allActivities->filter(fn ($activity) => $activity->monitor_status === 'Archived' && $searchMatches($activity))->values();
        $statusCounts = [
            'All' => $submittedActivities->count(),
            'Pending' => $submittedActivities->where('monitor_status', 'Pending')->count(),
            'Ongoing' => $submittedActivities->where('monitor_status', 'Ongoing')->count(),
            'Completed' => $submittedActivities->where('monitor_status', 'Completed')->count(),
            'Late' => $submittedActivities->where('monitor_late', true)->count(),
            'Archived' => $archivedActivities->count(),
        ];
        $tabActivities = $tab === 'todo'
            ? $allActivities->filter(fn ($activity) => $activity->last_submitted_at === null && ! $activity->archived_at && $searchMatches($activity))->values()
            : ($statusFilter === 'Archived'
                ? $archivedActivities
                : $submittedActivities);

        if ($tab === 'todo') {
            $tabActivities = $tabActivities->sortBy(fn ($activity) => $activity->date?->timestamp ?? PHP_INT_MAX)->values();
        } elseif ($sort === 'activity_date') {
            $tabActivities = $tabActivities->sortByDesc(fn ($activity) => $activity->date?->timestamp ?? 0)->values();
        } else {
            $tabActivities = $tabActivities->sortByDesc(fn ($activity) => $activity->last_submitted_at?->timestamp ?? 0)->values();
        }

        if ($statusFilter !== '' && $statusFilter !== 'Archived' && $tab === 'submitted') {
            $tabActivities = $statusFilter === 'Late'
                ? $tabActivities->where('monitor_late', true)->values()
                : $tabActivities->where('monitor_status', $statusFilter)->values();
        }

        $page = LengthAwarePaginator::resolveCurrentPage();
        $activities = new LengthAwarePaginator(
            $tabActivities->forPage($page, 10)->values(),
            $tabActivities->count(),
            10,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $pendingActivities = $allActivities
            ->filter(fn ($activity) => $activity->last_submitted_at === null && ! $activity->archived_at && $searchMatches($activity))
            ->values();

        $completedCount = $statusCounts['Completed'];
        $ongoingCount = $statusCounts['Ongoing'];
        $pendingCount = $statusCounts['Pending'];
        $archivedCount = $statusCounts['Archived'];
        $progressPercent = $activities->isEmpty() ? 0 : (int) round(($completedCount / $activities->count()) * 100);

        return view('users.activity-monitor', compact('activities', 'pendingActivities', 'completedCount', 'ongoingCount', 'pendingCount', 'archivedCount', 'progressPercent', 'term', 'schoolYear', 'statusFilter', 'statusCounts', 'tab', 'sort', 'search', 'outstandingReports'));
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
                'status' => $request->gpoaActivity?->monitoringStatus()['status'] ?? 'Pending',
                'monitoring_compliance_status' => $request->monitoringResult?->compliance_status,
            ])->values(),
        ]);
    }

    public function create(Request $request, OutstandingActivityReportService $outstandingReportService)
    {
        $user = auth()->user();
        $outstandingReports = $outstandingReportService->forUser($user);
        if ($outstandingReports->isNotEmpty()) {
            return $this->outstandingReportRedirect($outstandingReports);
        }

        if ($request->filled('gpoa') && $request->filled('activity')) {
            $requestedActivity = GpoaActivity::with('gpoa')->find($request->query('activity'));
            if ($requestedActivity
                && (string) $requestedActivity->gpoa_id === (string) $request->query('gpoa')
                && (int) $requestedActivity->gpoa?->user_id === (int) $user->id
                && ! $requestedActivity->activity_request_id
                && ! $requestedActivity->archived_at) {
                return redirect()->route('activity-requests.create-from-activity', $requestedActivity);
            }
        }

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
        $selectedActivityId = null;
        $requestedActivityId = $request->query('activity');
        if ($requestedActivityId !== null) {
            $plannedActivity = $gpoa->activities->firstWhere('id', $requestedActivityId);
            if ($plannedActivity && ! $plannedActivity->activityRequest()->exists()) {
                $selectedActivityId = $plannedActivity->id;
            }
        }

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
            'selectedActivityId',
            'activityLimits',
            'categoryCounts',
            'usedCount',
            'limitCount',
            'atCap',
            'activityLimitTemplate'
        ));
    }

    public function createFromActivity(GpoaActivity $gpoaActivity, OutstandingActivityReportService $outstandingReportService)
    {
        $gpoaActivity->load(['gpoa.user', 'gpoa.activities', 'activityRequest', 'activityRequest.programFlows']);
        abort_unless((int) $gpoaActivity->gpoa?->user_id === (int) auth()->id(), 403);

        $outstandingReports = $outstandingReportService->forUser(auth()->user());
        if ($outstandingReports->isNotEmpty()) {
            return $this->outstandingReportRedirect($outstandingReports);
        }

        if ($gpoaActivity->activityRequest) {
            return redirect()->route('activity-requests.show', $gpoaActivity->activityRequest)
                ->with('info', 'An activity request already exists. Continue editing its documents from the request details.');
        }

        if ($gpoaActivity->archived_at) {
            return redirect()->route('activity-monitor.index')
                ->with('error', 'Archived planned activities cannot be requested.');
        }

        $user = auth()->user();
        $gpoa = $gpoaActivity->gpoa;
        $availableGpoas = Gpoa::where('user_id', $user->id)
            ->with('activities')
            ->orderByDesc('school_year')
            ->orderByRaw("CASE WHEN term = '1st Term' THEN 0 ELSE 1 END")
            ->get();
        $selectedGpoaId = $gpoa->id;
        $selectedActivityId = $gpoaActivity->id;
        $fromPlannedActivity = true;
        $prefill = [
            'title' => $gpoaActivity->title,
            'category' => $gpoaActivity->category,
            'sdgs' => $gpoaActivity->sdgs ?? [],
            'date' => $gpoaActivity->date
                ? ($gpoaActivity->date_is_month_only ? $gpoaActivity->date->format('Y-m-01') : $gpoaActivity->date->toDateString())
                : '',
            'end_date' => $gpoaActivity->end_date?->toDateString() ?? '',
            'start_time' => $gpoaActivity->start_time ? substr((string) $gpoaActivity->start_time, 0, 5) : '',
            'end_time' => $gpoaActivity->end_time ? substr((string) $gpoaActivity->end_time, 0, 5) : '',
            'venue' => $gpoaActivity->venue,
            'objectives' => $gpoaActivity->objectives,
            'expected_outcome' => $gpoaActivity->expected_outcome,
            'plan_key_strategy' => $gpoaActivity->plan_key_strategy,
            'target_participants' => $gpoaActivity->target_participants,
            'person_in_charge' => $gpoaActivity->person_in_charge,
            'facilities_materials' => $gpoaActivity->facilities_materials,
            'estimated_budget' => $gpoaActivity->estimated_budget,
            'source_of_funds' => $gpoaActivity->source_of_funds,
        ];
        $programFlows = collect($gpoaActivity->getAttribute('program_flows') ?? [])
            ->map(fn ($flow) => [
                'time' => data_get($flow, 'time', ''),
                'flow' => data_get($flow, 'flow', ''),
                'person_in_charge' => data_get($flow, 'person_in_charge', ''),
            ])
            ->values()
            ->all();
        $categoryCounts = ActivityRequest::where('user_id', $user->id)
            ->where('gpoa_id', $gpoa->id)
            ->selectRaw('category, count(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();
        $activityLimits = config('gpoa_activity_limits', []);
        $usedCount = array_sum($categoryCounts);
        $limitCount = array_sum($activityLimits);
        $atCap = $usedCount >= $limitCount;
        $activityLimitTemplate = (object) ['used' => $usedCount, 'limit' => $limitCount];

        return view('users.create-request', compact(
            'availableGpoas', 'gpoa', 'selectedGpoaId', 'selectedActivityId', 'activityLimits',
            'categoryCounts', 'usedCount', 'limitCount', 'atCap', 'activityLimitTemplate',
            'fromPlannedActivity', 'prefill', 'programFlows', 'gpoaActivity'
        ));
    }

    public function store(Request $request, VenueAvailabilityService $venueAvailability, OutstandingActivityReportService $outstandingReportService)
    {
        $outstandingReports = $outstandingReportService->forUser(auth()->user());
        if ($outstandingReports->isNotEmpty()) {
            return $this->outstandingReportRedirect($outstandingReports, true);
        }

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

    private function outstandingReportRedirect(Collection $outstandingReports, bool $withValidationErrors = false)
    {
        $firstRequest = $outstandingReports->first();
        $message = "Submit the pending report for {$firstRequest->title} before requesting a new activity.";
        $reportLinks = $outstandingReports->map(fn (ActivityRequest $activityRequest) => [
            'title' => $activityRequest->title,
            'url' => route('activity-reports.create', $activityRequest),
        ])->values()->all();
        $redirect = redirect()->route('activity-monitor.index')
            ->with('outstandingReports', $reportLinks);

        return $withValidationErrors
            ? $redirect->withErrors(['activity_request' => $message])
            : $redirect->with('error', $message);
    }

}
