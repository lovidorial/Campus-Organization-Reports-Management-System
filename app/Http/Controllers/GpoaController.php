<?php

namespace App\Http\Controllers;

use App\Models\Gpoa;
use App\Models\GpoaActivity;
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
            ->withCount('activities')
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

        return view('gpoa.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'colleges'            => 'required|string|max:100',
            'term'                => 'required|string|max:50',
            'school_year'         => 'required|string|max:20',
            'document_path'       => 'nullable|file|mimes:pdf|max:20480',
            'verify'              => 'required|accepted',
            'activities'          => 'required|array|min:1',
            'activities.*.title'  => 'required|string|max:255',
            'activities.*.date'   => 'required|date',
            'activities.*.venue'  => 'required|string|max:255',
            'activities.*.category'           => 'required|string|max:100',
            'activities.*.description'        => 'required|string',
            'activities.*.participants_count' => 'required|integer|min:1',
            'activities.*.basis_grading'      => 'nullable|string|max:50',
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
            'status'        => 'pending',
        ]);

        foreach ($validated['activities'] as $activity) {
            GpoaActivity::create([
                'gpoa_id'            => $gpoa->id,
                'title'              => $activity['title'],
                'date'               => $activity['date'],
                'venue'              => $activity['venue'],
                'category'           => $activity['category'],
                'description'        => $activity['description'],
                'participants_count' => $activity['participants_count'],
                'basis_grading'      => $activity['basis_grading'] ?? null,
            ]);
        }

        $this->workflowService->recordGpoaSubmission($workflow, $gpoa);

        return redirect()->route('dashboard')
            ->with('success', 'GPOA submitted successfully. Status: Under Review. Await OSDW approval.');
    }

    public function edit(Gpoa $gpoa)
    {
        if ($gpoa->user_id !== auth()->id()) {
            abort(403);
        }

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
        if ($gpoa->user_id !== auth()->id()) {
            abort(403);
        }

        $workflow = $this->workflowService->getOrCreateForUser(auth()->user(), $gpoa->term, $gpoa->school_year);
        $submission = $workflow->currentSubmission(OrganizationWorkflow::DOC_GPOA);

        if (!$submission || !in_array($submission->status, ['submitted', 'under_review'])) {
            return back()->with('error', 'GPOA can only be edited while pending OSDW review.');
        }

        $validated = $request->validate([
            'colleges'            => 'required|string|max:100',
            'document_path'       => 'nullable|file|mimes:pdf|max:20480',
            'verify'              => 'required|accepted',
            'activities'          => 'required|array|min:1',
            'activities.*.title'  => 'required|string|max:255',
            'activities.*.date'   => 'required|date',
            'activities.*.venue'  => 'required|string|max:255',
            'activities.*.category'           => 'required|string|max:100',
            'activities.*.description'        => 'required|string',
            'activities.*.participants_count' => 'required|integer|min:1',
            'activities.*.basis_grading'      => 'nullable|string|max:50',
        ]);

        if ($request->hasFile('document_path')) {
            if ($gpoa->document_path) {
                Storage::disk('public')->delete($gpoa->document_path);
            }
            $gpoa->document_path = $request->file('document_path')->store('uploads/gpoa', 'public');
        }

        $gpoa->update([
            'college' => $validated['colleges'],
            'document_path' => $gpoa->document_path,
            'status' => 'pending',
            'reject_reason' => null,
        ]);

        $gpoa->activities()->delete();

        foreach ($validated['activities'] as $activity) {
            GpoaActivity::create([
                'gpoa_id'            => $gpoa->id,
                'title'              => $activity['title'],
                'date'               => $activity['date'],
                'venue'              => $activity['venue'],
                'category'           => $activity['category'],
                'description'        => $activity['description'],
                'participants_count' => $activity['participants_count'],
                'basis_grading'      => $activity['basis_grading'] ?? null,
            ]);
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
        if ($gpoa->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $gpoa->load('activities');

        return view('gpoa.show', compact('gpoa'));
    }
}
