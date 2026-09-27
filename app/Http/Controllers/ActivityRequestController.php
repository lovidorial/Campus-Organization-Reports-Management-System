<?php

namespace App\Http\Controllers;

use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\OrganizationWorkflow;
use App\Models\WorkflowSubmission;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\GpoaMatchValidator;
use App\Services\VenueAvailabilityService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ActivityRequestController extends Controller
{
    public function index()
    {
        $requests = ActivityRequest::where('user_id', auth()->id())
            ->with([
                'gpoa',
                'gpoaActivity.gpoa',
                'report',
                'monitoringResult',
                'programFlows',
                'venueRecord' => fn ($query) => $query->withCount(['scheduledRequests', 'futureReservationRequests']),
            ])
            ->latest()
            ->get();

        foreach ($requests as $req) {
            $req->refreshLifecycleStatus();
        }

        $grouped = $requests->groupBy(fn ($request) => optional($request->gpoa ?? $request->gpoaActivity?->gpoa)->id ?: 'ungrouped');

        return view('users.activity-requests', compact('grouped'));
    }

    public function show(ActivityRequest $activityRequest)
    {
        $this->authorize('view', $activityRequest);
        $activityRequest->refreshLifecycleStatus();
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

        abort_unless(in_array($activityRequest->status, [
            ActivityRequest::STATUS_APPROVED,
            ActivityRequest::STATUS_IN_PROGRESS,
            ActivityRequest::STATUS_AWAITING_REPORT,
            ActivityRequest::STATUS_REPORT_SUBMITTED,
            ActivityRequest::STATUS_CLOSED,
        ], true), 403);

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
        $gpoas = Gpoa::approved()
            ->where('user_id', auth()->id())
            ->with(['activities.activityRequests' => fn ($query) => $query->latest()])
            ->latest()
            ->get();

        $activities = $gpoas->flatMap(fn ($gpoa) => $gpoa->activities->map(function ($activity) use ($gpoa) {
            $request = $activity->activityRequests->first();
            $activity->monitor_status = match ($request?->status) {
                null => 'not yet requested',
                'pending' => 'pending',
                'closed', 'report_submitted' => 'done',
                default => 'approved',
            };
            $activity->monitor_gpoa = $gpoa;
            return $activity;
        }));

        return view('users.activity-monitor', compact('activities'));
    }

    public function statuses()
    {
        $requests = ActivityRequest::where('user_id', auth()->id())
            ->with(['report', 'monitoringResult'])
            ->latest()
            ->get();

        foreach ($requests as $request) {
            $request->refreshLifecycleStatus();
        }

        return response()->json([
            'requests' => $requests->map(fn ($request) => [
                'id' => $request->id,
                'status' => $request->status,
                'is_urgent' => (bool) $request->is_urgent,
                'report_status' => $request->report?->status,
                'report_feedback' => $request->report?->feedback,
                'monitoring_compliance_status' => $request->monitoringResult?->compliance_status,
                'can_upload_reservation_slip' => auth()->user()->can('uploadReservationSlip', $request),
                'reservation_slip_url' => $request->reservation_slip ? asset('storage/' . $request->reservation_slip) : null,
            ])->values(),
        ]);
    }

    public function uploadReservationSlip(Request $request, ActivityRequest $activityRequest)
    {
        $this->authorize('uploadReservationSlip', $activityRequest);

        $validated = $request->validate([
            'reservation_slip' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $path = $validated['reservation_slip']->store('uploads/reservation-slips', 'public');

        if ($activityRequest->reservation_slip) {
            Storage::disk('public')->delete($activityRequest->reservation_slip);
        }

        $activityRequest->update(['reservation_slip' => $path]);

        return back()->with('success', 'Venue reservation slip uploaded successfully.');
    }

    public function create(Request $request)
    {
        $availableGpoas = Gpoa::where('user_id', auth()->id())
            ->with('activities')
            ->whereIn('status', ['approved', 'stored'])
            ->whereNotIn('id', WorkflowSubmission::where('document_type', OrganizationWorkflow::DOC_GPOA)
                ->where('is_current', true)
                ->whereHas('workflow', function ($q) {
                    $q->where(function ($workflowQuery) {
                        $workflowQuery->where('is_completed', true)
                            ->orWhere('current_stage', OrganizationWorkflow::STAGE_COMPLETED);
                    });
                })
                ->select('gpoa_id'))
            ->orderBy('school_year', 'desc')
            ->orderByRaw("CASE WHEN term = '1st Term' THEN 0 ELSE 1 END")
            ->get();

        if ($availableGpoas->isEmpty()) {
            $hasApprovedGpoas = Gpoa::where('user_id', auth()->id())
                ->whereIn('status', ['approved', 'stored'])
                ->exists();
            $hasCompletedGpoa = $hasApprovedGpoas && $this->gpoaWorkflowCompleted(
                Gpoa::where('user_id', auth()->id())
                    ->whereIn('status', ['approved', 'stored'])
                    ->value('id')
            );

            return redirect()->route('gpoa.index')
                ->with('error', $hasCompletedGpoa
                    ? 'This organization has completed its current GPOA cycle after the Summary Report was approved. Please submit a new GPOA before requesting new activities.'
                    : 'No approved GPOAs available for activity requests.');
        }

            if ($this->hasPendingActivityRequest()) {
                return redirect()->route('activity-requests.index')
                    ->with('error', "You can't request another activity right now. Please wait for the OSDW admin to review your request.");
            }

            if ($this->hasOutstandingActivityReport()) {
                return redirect()->route('activity-requests.index')
                    ->with('error', "You can't request another activity right now. Please submit the narrative report for your previous approved activity first.");
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

    public function resubmit(ActivityRequest $activityRequest, VenueAvailabilityService $venueAvailability)
    {
        $this->authorize('update', $activityRequest);

        if ($activityRequest->status !== ActivityRequest::STATUS_REJECTED) {
            return back()->with('error', 'Only rejected activity requests can be resubmitted.');
        }

        $venue = $activityRequest->venue_id
            ? $activityRequest->venueRecord
            : $venueAvailability->resolveVenue($activityRequest->venue);
        $availabilityData = array_merge($activityRequest->only([
            'date', 'end_date', 'start_time', 'end_time', 'venue',
        ]), ['venue_id' => $venue->id]);
        $conflict = $venueAvailability->conflictingRequest($availabilityData, $activityRequest->id);

        if ($conflict) {
            return back()->withErrors([
                'venue' => "Another activity is already scheduled at this venue and time ({$conflict->title}). Choose a different time or venue before resubmitting.",
            ]);
        }

        $activityRequest->update([
            'venue_id' => $venue->id,
            'status' => ActivityRequest::STATUS_PENDING,
            'reject_reason' => null,
        ]);

        return redirect()->route('activity-requests.index')
            ->with('success', 'Activity request resubmitted for review.');
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

        $minimumDate = $request->boolean('is_urgent')
            ? today()->toDateString()
            : today()->addDays(7)->toDateString();

        $validated = $request->validate([
            'gpoa_id' => [
                'required',
                Rule::exists('gpoas', 'id')->where(function ($q) {
                    $q->where('user_id', auth()->id())
                        ->whereIn('status', ['approved', 'stored']);
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
            'date' => 'required|date|after_or_equal:' . $minimumDate,
            'end_date' => 'nullable|date|after_or_equal:date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'venue' => 'required|string|max:255',
            'target_participants' => 'required|string|max:255',
            'person_in_charge' => 'required|string|max:255',
            'facilities_materials' => 'required|string|max:255',
            'estimated_budget' => 'required|numeric|min:0',
            'source_of_funds' => 'required|string|max:100',
            'communication_letter' => 'required|file|mimes:pdf|max:20480',
            'program_flows' => 'nullable|array',
            'program_flows.*.time' => 'required|string|max:50',
            'program_flows.*.flow' => 'required|string|max:255',
            'program_flows.*.person_in_charge' => 'required|string|max:255',
            'is_urgent' => 'sometimes|boolean',
            'urgent_reason' => [
                Rule::requiredIf(fn () => $request->boolean('is_urgent')),
                'nullable',
                'string',
                'max:2000',
            ],
        ], [
            'date.after_or_equal' => $request->boolean('is_urgent')
                ? 'Urgent activities cannot be scheduled in the past.'
                : 'Activity requests must be submitted at least 7 days before the activity date unless marked urgent.',
        ]);

        $gpoa = Gpoa::findOrFail($validated['gpoa_id']);

        $linkedActivity = GpoaActivity::where('id', $validated['gpoa_activity_id'])
            ->where('gpoa_id', $gpoa->id)
            ->first();

        if (!$linkedActivity) {
            return back()->withErrors(['gpoa_activity_id' => 'The selected planned GPOA activity could not be found.'])->withInput();
        }

        if ($message = GpoaMatchValidator::validate($linkedActivity, $validated)) {
            return back()->withErrors(['title' => $message])->withInput();
        }

        if ($this->gpoaWorkflowCompleted($gpoa->id)) {
            return back()->withErrors(['gpoa_id' => "This GPOA's cycle is already completed since its Summary Report was approved. Please submit a new GPOA before requesting new activities."])->withInput();
        }

            if ($this->hasPendingActivityRequest()) {
                return back()
                    ->withErrors(['gpoa_id' => "You can't request another activity right now. Please wait for the OSDW admin to review your request."])
                    ->withInput();
            }

            if ($this->hasOutstandingActivityReport()) {
                return back()
                    ->withErrors(['gpoa_id' => "You can't request another activity right now. Please submit the narrative report for your previous approved activity first."])
                    ->withInput();
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
            ->whereNotIn('status', ['rejected'])
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

        $commPath = $request->file('communication_letter')->store('uploads/comm', 'public');

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
            'communication_letter' => $commPath,
            'is_urgent' => $request->boolean('is_urgent'),
            'urgent_reason' => $validated['urgent_reason'] ?? null,
            'status' => ActivityRequest::STATUS_PENDING,
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

        return redirect()->route('activity-requests.index')
            ->with('success', 'Activity request submitted. Awaiting admin approval.');
    }

    private function gpoaWorkflowCompleted(int $gpoaId): bool
    {
        return WorkflowSubmission::where('document_type', OrganizationWorkflow::DOC_GPOA)
            ->where('gpoa_id', $gpoaId)
            ->where('is_current', true)
            ->whereHas('workflow', function ($q) {
                $q->where(function ($workflowQuery) {
                    $workflowQuery->where('is_completed', true)
                        ->orWhere('current_stage', OrganizationWorkflow::STAGE_COMPLETED);
                });
            })
            ->exists();
    }

    private function hasPendingActivityRequest(): bool
    {
        return ActivityRequest::where('user_id', auth()->id())
            ->where('status', ActivityRequest::STATUS_PENDING)
            ->exists();
    }

    private function hasOutstandingActivityReport(): bool
    {
        return ActivityRequest::where('user_id', auth()->id())
            ->whereIn('status', [
                ActivityRequest::STATUS_APPROVED,
                ActivityRequest::STATUS_IN_PROGRESS,
                ActivityRequest::STATUS_AWAITING_REPORT,
            ])
            ->whereDoesntHave('report')
            ->exists();
    }
}
