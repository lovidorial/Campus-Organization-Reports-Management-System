<?php

namespace App\Http\Controllers;

use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use Illuminate\Http\Request;

class ActivityRequestController extends Controller
{
    public function index()
    {
        $requests = ActivityRequest::where('user_id', auth()->id())
            ->with(['gpoaActivity.gpoa.activities', 'report', 'monitoringResult'])
            ->latest()
            ->get();

        foreach ($requests as $req) {
            $req->refreshLifecycleStatus();
        }

        $grouped = $requests->groupBy(fn ($request) => optional($request->gpoaActivity->gpoa)->id ?: 'ungrouped');

        return view('users.activity-requests', compact('grouped'));
    }

    public function create(Request $request)
    {
        $availableGpoas = Gpoa::where('user_id', auth()->id())
            ->whereIn('status', ['approved', 'stored'])
            ->with(['activities' => function ($q) {
                $q->whereDoesntHave('activityRequests', function ($r) {
                    $r->whereNotIn('status', ['rejected', 'report_submitted', 'closed']);
                });
            }])
            ->orderBy('school_year', 'desc')
            ->orderByRaw("FIELD(term, '1st Term', '2nd Term')")
            ->get();

        if ($availableGpoas->isEmpty()) {
            return redirect()->route('gpoa.index')
                ->with('error', 'No approved GPOAs available for activity requests.');
        }

        $selectedGpoaId = $request->query('gpoa') ?: $availableGpoas->first()->id;

        $gpoa = Gpoa::where('user_id', auth()->id())
            ->whereIn('status', ['approved', 'stored'])
            ->where('id', $selectedGpoaId)
            ->with(['activities' => function ($q) {
                $q->whereDoesntHave('activityRequests', function ($r) {
                    $r->whereNotIn('status', ['rejected', 'report_submitted', 'closed']);
                });
            }])
            ->first();

        if (!$gpoa) {
            $gpoa = $availableGpoas->first();
            $selectedGpoaId = $gpoa->id;
            $gpoa = Gpoa::where('id', $gpoa->id)
                ->with(['activities' => function ($q) {
                    $q->whereDoesntHave('activityRequests', function ($r) {
                        $r->whereNotIn('status', ['rejected', 'report_submitted', 'closed']);
                    });
                }])
                ->first();
        }

        $lineItems = $gpoa->activities;

        return view('users.create-request', compact('availableGpoas', 'gpoa', 'lineItems', 'selectedGpoaId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'gpoa_activity_id'    => 'required|exists:gpoa_activities,id',
            'description'         => 'nullable|string',
            'participants_count'  => 'nullable|integer|min:1',
            'communication_letter'=> 'required|file|mimes:pdf|max:20480',
        ]);

        $lineItem = GpoaActivity::where('id', $validated['gpoa_activity_id'])
            ->whereHas('gpoa', function ($q) {
                $q->where('user_id', auth()->id())
                  ->whereIn('status', ['approved', 'stored']);
            })
            ->firstOrFail();

        $existing = ActivityRequest::where('gpoa_activity_id', $lineItem->id)
            ->whereNotIn('status', ['rejected'])
            ->exists();

        if ($existing) {
            return back()->withErrors(['gpoa_activity_id' => 'An activity request already exists for this GPOA entry.'])->withInput();
        }

        $conflict = ActivityRequest::where('date', $lineItem->date)
            ->where('venue', $lineItem->venue)
            ->where('status', ActivityRequest::STATUS_APPROVED)
            ->exists();

        if ($conflict) {
            return back()->withErrors(['gpoa_activity_id' => 'An approved activity is already scheduled at this venue on this date.'])->withInput();
        }

        $commPath = $request->file('communication_letter')->store('uploads/comm', 'public');

        ActivityRequest::create([
            'user_id'              => auth()->id(),
            'gpoa_activity_id'     => $lineItem->id,
            'title'                => $lineItem->title,
            'date'                 => $lineItem->date,
            'venue'                => $lineItem->venue,
            'category'             => $lineItem->category,
            'activity_level'       => $lineItem->activity_level,
            'sdgs'                 => $lineItem->sdgs,
            'objectives'           => $lineItem->objectives,
            'expected_outcome'     => $lineItem->expected_outcome,
            'plan_key_strategy'    => $lineItem->plan_key_strategy,
            'target_participants'  => $lineItem->target_participants,
            'person_in_charge'     => $lineItem->person_in_charge,
            'facilities_materials' => $lineItem->facilities_materials,
            'estimated_budget'     => $lineItem->estimated_budget,
            'remarks'              => $lineItem->remarks,
            'source_of_funds'      => $lineItem->source_of_funds,
            'preceding_activity'   => $lineItem->preceding_activity,
            'description'          => $validated['description'] ?? null,
            'participants_count'   => $validated['participants_count'] ?? $lineItem->target_participants,
            'communication_letter' => $commPath,
            'status'               => ActivityRequest::STATUS_PENDING,
        ]);

        return redirect()->route('activity-requests.index')
            ->with('success', 'Activity request submitted. Awaiting admin approval.');
    }
}
