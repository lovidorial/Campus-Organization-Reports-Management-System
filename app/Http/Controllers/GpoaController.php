<?php

namespace App\Http\Controllers;

use App\Models\Gpoa;
use App\Models\OrganizationWorkflow;
use App\Services\OrganizationWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        $validated = $request->validate([
            'colleges'            => 'required|string|max:100',
            'term'                => 'required|string|max:50',
            'school_year'         => 'required|string|max:20',
            'prepared_by'         => 'required|string|max:255',
            'document_path'       => 'required|file|mimes:pdf|max:20480',
            'planned_activities'  => 'nullable|array',
            'planned_activities.*.title' => 'nullable|string|max:255',
            'planned_activities.*.date' => 'nullable|date',
            'planned_activities.*.venue' => 'nullable|string|max:255',
            'planned_activities.*.category' => 'nullable|string|max:100',
            'planned_activities.*.sdgs' => 'nullable',
            'planned_activities.*.objectives' => 'nullable|string',
            'planned_activities.*.expected_outcome' => 'nullable|string',
            'planned_activities.*.target_participants' => 'nullable|string|max:255',
            'planned_activities.*.person_in_charge' => 'nullable|string|max:255',
            'planned_activities.*.facilities_materials' => 'nullable|string',
            'planned_activities.*.estimated_budget' => 'nullable|numeric|min:0',
            'planned_activities.*.source_of_funds' => 'nullable|string|max:100',
            'planned_activities.*.plan_key_strategy' => 'nullable|string',
            'planned_activities.*.preceding_activity' => 'nullable|string|max:255',
            'verify'              => 'required|accepted',
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

        $validated = $request->validate([
            'colleges' => 'required|string|max:100',
            'prepared_by' => 'required|string|max:255',
            'document_path' => 'nullable|file|mimes:pdf|max:20480',
            'planned_activities'  => 'nullable|array',
            'planned_activities.*.title' => 'nullable|string|max:255',
            'planned_activities.*.date' => 'nullable|date',
            'planned_activities.*.venue' => 'nullable|string|max:255',
            'planned_activities.*.category' => 'nullable|string|max:100',
            'planned_activities.*.sdgs' => 'nullable',
            'planned_activities.*.objectives' => 'nullable|string',
            'planned_activities.*.expected_outcome' => 'nullable|string',
            'planned_activities.*.target_participants' => 'nullable|string|max:255',
            'planned_activities.*.person_in_charge' => 'nullable|string|max:255',
            'planned_activities.*.facilities_materials' => 'nullable|string',
            'planned_activities.*.estimated_budget' => 'nullable|numeric|min:0',
            'planned_activities.*.source_of_funds' => 'nullable|string|max:100',
            'planned_activities.*.plan_key_strategy' => 'nullable|string',
            'planned_activities.*.preceding_activity' => 'nullable|string|max:255',
            'verify' => 'required|accepted',
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

        if ($request->has('planned_activities')) {
            $this->syncPlannedActivities($gpoa, $request->input('planned_activities', []));
        }

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

        $estimatedBudget = $activityData['estimated_budget'] ?? null;
        $estimatedBudget = is_string($estimatedBudget) && trim($estimatedBudget) === '' ? null : $estimatedBudget;

        return [
            'title' => trim((string) ($activityData['title'] ?? '')),
            'date' => isset($activityData['date']) && trim((string) $activityData['date']) !== '' ? $activityData['date'] : null,
            'venue' => trim((string) ($activityData['venue'] ?? '')),
            'category' => trim((string) ($activityData['category'] ?? '')),
            'sdgs' => $sdgs,
            'objectives' => trim((string) ($activityData['objectives'] ?? '')),
            'expected_outcome' => trim((string) ($activityData['expected_outcome'] ?? '')),
            'target_participants' => trim((string) ($activityData['target_participants'] ?? '')),
            'person_in_charge' => trim((string) ($activityData['person_in_charge'] ?? '')),
            'facilities_materials' => trim((string) ($activityData['facilities_materials'] ?? '')),
            'estimated_budget' => $estimatedBudget !== null && is_numeric($estimatedBudget) ? (float) $estimatedBudget : null,
            'source_of_funds' => trim((string) ($activityData['source_of_funds'] ?? '')),
            'plan_key_strategy' => trim((string) ($activityData['plan_key_strategy'] ?? '')),
            'preceding_activity' => trim((string) ($activityData['preceding_activity'] ?? '')),
        ];
    }

    private function isBlankPlannedActivity(array $activityData): bool
    {
        foreach (['title', 'date', 'venue', 'category', 'objectives', 'expected_outcome', 'target_participants', 'person_in_charge', 'facilities_materials', 'estimated_budget', 'source_of_funds', 'plan_key_strategy', 'preceding_activity'] as $field) {
            if (!empty($activityData[$field])) {
                return false;
            }
        }

        return empty($activityData['sdgs']);
    }
}
