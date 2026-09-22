<?php

namespace App\Http\Controllers;

use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\GpoaModificationRequest;
use App\Models\OrganizationWorkflow;
use App\Models\WorkflowEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GpoaModificationRequestController extends Controller
{
    public function store(Request $request, Gpoa $gpoa)
    {
        abort_unless($gpoa->user_id === auth()->id(), 403);
        abort_unless($gpoa->isApproved(), 422, 'Only an approved GPOA can be modified.');

        $validated = $request->validate([
            'type' => ['required', Rule::in(['add', 'remove', 'edit'])],
            'gpoa_activity_id' => [
                'nullable',
                Rule::exists('gpoa_activities', 'id')->where('gpoa_id', $gpoa->id),
            ],
            'payload' => 'nullable|array',
            'remarks' => 'required|string|max:2000',
        ]);

        if (in_array($validated['type'], ['remove', 'edit'], true) && empty($validated['gpoa_activity_id'])) {
            return back()->withErrors(['gpoa_activity_id' => 'Select the planned activity this request should change.'])->withInput();
        }

        if ($validated['type'] === 'add' && $gpoa->activities()->count() >= config('gpoa.max_planned_activities')) {
            return back()->withErrors(['type' => 'The approved GPOA already has the maximum of ' . config('gpoa.max_planned_activities') . ' planned activities.'])->withInput();
        }

        if ($validated['type'] === 'add' && collect(['title', 'date', 'venue'])->contains(fn ($field) => empty($validated['payload'][$field] ?? null))) {
            return back()->withErrors(['payload' => 'Adding a planned activity requires a title, date, and venue.'])->withInput();
        }

        GpoaModificationRequest::create([
            'gpoa_id' => $gpoa->id,
            'gpoa_activity_id' => $validated['gpoa_activity_id'] ?? null,
            'type' => $validated['type'],
            'payload' => $validated['payload'] ?? [],
            'remarks' => $validated['remarks'],
            'requested_by' => auth()->id(),
        ]);

        return back()->with('success', 'GPOA modification request submitted for admin review.');
    }

    public function approve(GpoaModificationRequest $modificationRequest)
    {
        abort_unless($modificationRequest->status === GpoaModificationRequest::STATUS_PENDING, 422, 'This modification request has already been reviewed.');

        DB::transaction(function () use ($modificationRequest) {
            $gpoa = $modificationRequest->gpoa()->lockForUpdate()->firstOrFail();
            $activity = $modificationRequest->gpoa_activity_id
                ? GpoaActivity::where('gpoa_id', $gpoa->id)->lockForUpdate()->findOrFail($modificationRequest->gpoa_activity_id)
                : null;

            $changedActivity = match ($modificationRequest->type) {
                GpoaModificationRequest::TYPE_ADD => $this->addActivity($gpoa, $modificationRequest->payload ?? []),
                GpoaModificationRequest::TYPE_EDIT => $this->editActivity($activity, $modificationRequest->payload ?? []),
                GpoaModificationRequest::TYPE_REMOVE => $this->removeActivity($activity),
            };

            $modificationRequest->update([
                'status' => GpoaModificationRequest::STATUS_APPROVED,
                'reviewed_by' => auth()->id(),
                'gpoa_activity_id' => $changedActivity?->id ?? $modificationRequest->gpoa_activity_id,
            ]);

            $workflow = OrganizationWorkflow::where('user_id', $gpoa->user_id)
                ->where('term', $gpoa->term)->where('school_year', $gpoa->school_year)->firstOrFail();

            WorkflowEvent::create([
                'organization_workflow_id' => $workflow->id,
                'user_id' => auth()->id(),
                'event_type' => 'gpoa_modification_approved',
                'description' => 'Approved GPOA activity modification request.',
                'metadata' => [
                    'modification_request_id' => $modificationRequest->id,
                    'gpoa_id' => $gpoa->id,
                    'gpoa_activity_id' => $changedActivity?->id ?? $modificationRequest->gpoa_activity_id,
                    'type' => $modificationRequest->type,
                ],
                'created_at' => now(),
            ]);
        });

        return back()->with('success', 'GPOA modification approved and applied.');
    }

    public function reject(Request $request, GpoaModificationRequest $modificationRequest)
    {
        $request->validate(['remarks' => 'nullable|string|max:2000']);
        $modificationRequest->update([
            'status' => GpoaModificationRequest::STATUS_REJECTED,
            'reviewed_by' => auth()->id(),
            'remarks' => $request->input('remarks', $modificationRequest->remarks),
        ]);

        return back()->with('success', 'GPOA modification request rejected.');
    }

    private function addActivity(Gpoa $gpoa, array $payload): GpoaActivity
    {
        abort_if($gpoa->activities()->count() >= config('gpoa.max_planned_activities'), 422, 'The GPOA activity cap has been reached.');
        return $gpoa->activities()->create($this->activityData($payload));
    }

    private function editActivity(?GpoaActivity $activity, array $payload): GpoaActivity
    {
        abort_unless($activity, 422, 'The planned activity no longer exists.');
        $activity->update($this->activityData(array_merge($activity->only([
            'title', 'date', 'venue', 'category', 'sdgs', 'objectives', 'expected_outcome',
            'target_participants', 'person_in_charge', 'facilities_materials',
            'estimated_budget', 'source_of_funds', 'plan_key_strategy', 'preceding_activity',
        ]), $payload)));
        return $activity;
    }

    private function removeActivity(?GpoaActivity $activity): ?GpoaActivity
    {
        abort_unless($activity, 422, 'The planned activity no longer exists.');
        $activity->delete();
        return null;
    }

    private function activityData(array $payload): array
    {
        return collect([
            'title', 'date', 'venue', 'category', 'sdgs', 'objectives', 'expected_outcome',
            'target_participants', 'person_in_charge', 'facilities_materials',
            'estimated_budget', 'source_of_funds', 'plan_key_strategy', 'preceding_activity',
        ])->mapWithKeys(fn ($field) => [$field => $payload[$field] ?? null])->all();
    }
}