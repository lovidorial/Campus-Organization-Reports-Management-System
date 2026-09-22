<?php

namespace App\Http\Controllers;

use App\Models\Gpoa;
use App\Models\OrganizationWorkflow;
use App\Services\OrganizationWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class GpoaController extends Controller
{
    public function __construct(
        private OrganizationWorkflowService $workflowService
    ) {}

    public function index()
    {
        $gpoas = Gpoa::where('user_id', auth()->id())
            ->withCount(['activities', 'activityRequests'])
            ->latest()
            ->paginate(10);

        $user = auth()->user();
        $term = $user->term ?? '1st Term';
        $schoolYear = $user->school_year ?? (date('Y') . '-' . (date('Y') + 1));

        $workflow = $this->workflowService->getOrCreateForUser($user, $term, $schoolYear);
        $hasApprovedGpoa = $workflow->isGpoaApproved();

        return view('gpoa.index', compact('gpoas', 'hasApprovedGpoa', 'term', 'schoolYear', 'workflow'));
    }

    public function create()
    {
        $user = auth()->user();
        $term = $user->term ?? '1st Term';
        $schoolYear = $user->school_year ?? (date('Y') . '-' . (date('Y') + 1));

        $workflow = $this->workflowService->getOrCreateForUser($user, $term, $schoolYear);

        if ($workflow->is_locked) {
            return redirect()->route('dashboard')
                ->with('error', 'Your workflow is completed and locked. Contact OSDW to reopen.');
        }

        $current = $workflow->currentSubmission(OrganizationWorkflow::DOC_GPOA);
        if ($current && in_array($current->status, ['submitted', 'under_review', 'approved'])) {
            return redirect()->route('gpoa.index')
                ->with('error', 'You already have a GPOA submitted or approved for this term and school year.');
        }

        $detectedCollege = $this->detectCollegeFromOrganization($user);

        return view('gpoa.create', compact('detectedCollege'));
    }

    private function detectCollegeFromOrganization($user): ?string
    {
        if (!empty($user->college)) {
            return strtoupper($user->college);
        }

        if (!empty($user->organization?->college)) {
            return strtoupper($user->organization->college);
        }

        $orgName = strtoupper(trim($user->org_name ?? $user->organization?->name ?? ''));
        $orgName = preg_replace('/[^A-Z0-9\- ]+/', '', $orgName);

        $collegeMap = [
            // CICS
            'ITOUCH PUBLICATION' => 'CICS',
            'ITOUCH' => 'CICS',
            'CICS SC' => 'CICS',
            'CICS-SC' => 'CICS',
            'COLLEGE OF INFORMATION AND COMPUTING SCIENCES' => 'CICS',

            // CTE
            'THE MENTOR' => 'CTE',
            'THE ACADEMIA' => 'CTE',
            'CTE SC' => 'CTE',
            'CTE-SC' => 'CTE',
            'CCTE SC' => 'CTE',
            'CCTE-SC' => 'CTE',
            'PASSED' => 'CTE',
            'COLLEGE OF TEACHER EDUCATION' => 'CTE',

            // CFAS
            'THE WATERWORLD' => 'CFAS',
            'THE AQUARIUS' => 'CFAS',
            'CFAS SC' => 'CFAS',
            'CFAS-SC' => 'CFAS',
            'COLLEGE OF FISHERIES AND AQUATIC SCIENCES' => 'CFAS',

            // CHM
            'THE BANQUET' => 'CHM',
            'CHM SC' => 'CHM',
            'CHM-SC' => 'CHM',
            'SAB-CHM' => 'CHM',
            'SAB CHM' => 'CHM',
            'HMS' => 'CHM',
            'COLLEGE OF HOSPITALITY MANAGEMENT' => 'CHM',

            // CCJE
            'THE CALIBER' => 'CCJE',
            'CJS SC' => 'CCJE',
            'CJS-SC' => 'CCJE',
            'COLLEGE OF CRIMINAL JUSTICE EDUCATION' => 'CCJE',

            // CBEA
            'THE LEDGER' => 'CBEA',
            'COLLEGE OF BUSINESS, ENTREPRENEURSHIP AND ACCOUNTANCY' => 'CBEA',

            // CET
            'THE CONDUIT' => 'CET',
            'COLLEGE OF ENGINEERING AND TECHNOLOGY' => 'CET',

            // Agriculture
            'FAME' => 'AGRICULTURE',
            'FAS SCO' => 'AGRICULTURE',
            'FAS-SCO' => 'AGRICULTURE',
            'ASIDETS' => 'AGRICULTURE',
            'COLLEGE OF AGRICULTURE' => 'AGRICULTURE',
        ];

        foreach ($collegeMap as $needle => $college) {
            if (str_contains($orgName, $needle)) {
                return $college;
            }
        }

        return null;
    }

    public function store(Request $request)
    {
        $this->validatePlannedActivityEntries($request);

        $validated = $request->validate([
            'colleges'            => 'required|string|max:100',
            'term'                => 'required|string|max:50',
            'school_year'         => 'required|string|max:20',
            'prepared_by'         => 'required|string|max:255',
            'document_path'       => 'nullable|file|mimes:pdf|max:20480',
            'planned_activities'  => 'required|array|min:1|max:' . config('gpoa.max_planned_activities'),
            'planned_activities.*.title' => 'nullable|required_with:planned_activities.*.sdgs|string|max:255',
            'planned_activities.*.date' => 'nullable|required_with:planned_activities.*.sdgs|date',
            'planned_activities.*.venue' => 'nullable|required_with:planned_activities.*.sdgs|string|max:255',
            'planned_activities.*.category' => 'nullable|required_with:planned_activities.*.sdgs|string|max:100',
            'planned_activities.*.sdgs' => 'nullable|array|min:1|max:8',
            'verify'              => 'required|accepted',
        ], [
            'planned_activities.max' => 'A GPOA may contain no more than ' . config('gpoa.max_planned_activities') . ' planned activities.',
        ]);

        $workflow = $this->workflowService->getOrCreateForUser(
            auth()->user(),
            $validated['term'],
            $validated['school_year']
        );

        if (!$workflow->canSubmitGpoa()) {
            return back()->withErrors(['term' => 'GPOA submission is not available at this stage.'])->withInput();
        }

        $documentPath = $request->hasFile('document_path')
            ? $request->file('document_path')->store('uploads/gpoa', 'public')
            : null;

        $gpoa = Gpoa::create([
            'user_id'       => auth()->id(),
            'term'          => $validated['term'],
            'school_year'   => $validated['school_year'],
            'college'       => $validated['colleges'],
            'document_path' => $documentPath,
            'prepared_by'   => $validated['prepared_by'],
            'status'        => 'pending',
        ]);

        $this->syncPlannedActivities($gpoa, $request->input('planned_activities', []));

        $this->workflowService->recordGpoaSubmission($workflow, $gpoa);

        return redirect()->route('dashboard')
            ->with('success', 'GPOA submitted successfully. Status: Under Review. Await OSDW approval.');
    }

    public function edit(Gpoa $gpoa)
    {
        $this->authorize('update', $gpoa);

        $workflow = $this->workflowService->getOrCreateForUser(auth()->user(), $gpoa->term, $gpoa->school_year);
        $submission = $workflow->currentSubmission(OrganizationWorkflow::DOC_GPOA);

        if (!$submission || !in_array($submission->status, ['submitted', 'under_review'])) {
            return redirect()->route('gpoa.show', $gpoa)
                ->with('error', 'GPOA can only be edited while pending OSDW review.');
        }

        $gpoa->load('activities');

        return view('gpoa.edit', compact('gpoa', 'workflow'));
    }

    public function update(Request $request, Gpoa $gpoa)
    {
        $this->authorize('update', $gpoa);

        $workflow = $this->workflowService->getOrCreateForUser(auth()->user(), $gpoa->term, $gpoa->school_year);
        $submission = $workflow->currentSubmission(OrganizationWorkflow::DOC_GPOA);

        if (!$submission || !in_array($submission->status, ['submitted', 'under_review'])) {
            return back()->with('error', 'GPOA can only be edited while pending OSDW review.');
        }

        $this->validatePlannedActivityEntries($request);

        $validated = $request->validate([
            'colleges' => 'required|string|max:100',
            'prepared_by' => 'required|string|max:255',
            'document_path' => 'nullable|file|mimes:pdf|max:20480',
            'planned_activities'  => 'required|array|min:1|max:' . config('gpoa.max_planned_activities'),
            'planned_activities.*.title' => 'nullable|required_with:planned_activities.*.sdgs|string|max:255',
            'planned_activities.*.date' => 'nullable|required_with:planned_activities.*.sdgs|date',
            'planned_activities.*.venue' => 'nullable|required_with:planned_activities.*.sdgs|string|max:255',
            'planned_activities.*.category' => 'nullable|required_with:planned_activities.*.sdgs|string|max:100',
            'planned_activities.*.sdgs' => 'nullable|array|min:1|max:8',
            'verify' => 'required|accepted',
        ], [
            'planned_activities.max' => 'A GPOA may contain no more than ' . config('gpoa.max_planned_activities') . ' planned activities.',
        ]);

        if ($request->hasFile('document_path')) {
            if ($gpoa->document_path) {
                Storage::disk('public')->delete($gpoa->document_path);
            }
            $gpoa->document_path = $request->file('document_path')->store('uploads/gpoa', 'public');
        }

        $gpoa->update([
            'college' => $validated['colleges'],
            'prepared_by' => $validated['prepared_by'],
            'document_path' => $gpoa->document_path,
            'status' => 'pending',
            'reject_reason' => null,
        ]);

        $this->syncPlannedActivities($gpoa, $request->input('planned_activities', []));

        $submission->update([
            'file_path' => $gpoa->document_path,
            'submitted_at' => now(),
            'status' => 'under_review',
            'reject_reason' => null,
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'GPOA updated successfully. Status: Under Review.');
    }

    public function show(Gpoa $gpoa)
    {
        $this->authorize('view', $gpoa);

        $gpoa->load('activities');
        $gpoa->loadCount('activityRequests');

        return view('gpoa.show', compact('gpoa'));
    }

    private function syncPlannedActivities(Gpoa $gpoa, array $plannedActivities): void
    {
        $gpoa->activities()->delete();

        foreach ($plannedActivities as $activityData) {
            $normalizedActivity = $this->normalizePlannedActivityData($activityData);

            if ($this->isBlankPlannedActivity($normalizedActivity)) {
                continue;
            }

            $gpoa->activities()->create($normalizedActivity);
        }
    }

    private function normalizePlannedActivityData(array $activityData): array
    {
        $sdgs = $activityData['sdgs'] ?? [];

        if (is_string($sdgs)) {
            $sdgs = array_values(array_filter(array_map('trim', explode(',', $sdgs)), fn ($value) => $value !== ''));
        }

        $sdgs = array_values(array_unique(array_map('intval', array_filter((array) $sdgs, 'is_numeric'))));

        return [
            'title' => trim((string) ($activityData['title'] ?? '')),
            'date' => isset($activityData['date']) && trim((string) $activityData['date']) !== '' ? $activityData['date'] : null,
            'venue' => trim((string) ($activityData['venue'] ?? '')),
            'category' => trim((string) ($activityData['category'] ?? '')),
            'sdgs' => $sdgs,
        ];
    }

    private function isBlankPlannedActivity(array $activityData): bool
    {
        return empty($activityData['title'])
            && empty($activityData['date'])
            && empty($activityData['venue'])
            && empty($activityData['category'])
            && empty($activityData['sdgs']);
    }

    private function validatePlannedActivityEntries(Request $request): void
    {
        $validator = Validator::make($request->all(), [
            'planned_activities' => 'required|array|min:1|max:' . config('gpoa.max_planned_activities'),
            'planned_activities.*.title' => 'nullable|required_with:planned_activities.*.sdgs|string|max:255',
            'planned_activities.*.date' => 'nullable|required_with:planned_activities.*.sdgs|date',
            'planned_activities.*.venue' => 'nullable|required_with:planned_activities.*.sdgs|string|max:255',
            'planned_activities.*.category' => 'nullable|required_with:planned_activities.*.sdgs|string|max:100',
            'planned_activities.*.sdgs' => 'nullable|array|min:1|max:8',
        ], [
            'planned_activities.max' => 'A GPOA may contain no more than ' . config('gpoa.max_planned_activities') . ' planned activities.',
        ]);

        $validator->after(function ($validator) use ($request) {
            foreach ((array) $request->input('planned_activities', []) as $index => $activity) {
                $hasAnyValue = collect(['title', 'date', 'venue', 'category'])
                    ->contains(fn ($field) => trim((string) ($activity[$field] ?? '')) !== '')
                    || !empty($activity['sdgs'] ?? []);

                if (!$hasAnyValue) {
                    continue;
                }

                $isComplete = collect(['title', 'date', 'venue', 'category'])
                    ->every(fn ($field) => trim((string) ($activity[$field] ?? '')) !== '')
                    && !empty($activity['sdgs'] ?? []);

                if (!$isComplete) {
                    $validator->errors()->add(
                        "planned_activities.{$index}",
                        'Activity ' . ($index + 1) . ' is incomplete - title, date, venue, category, and at least one SDG are all required.'
                    );
                }
            }
        });

        $validator->validate();
    }
}
