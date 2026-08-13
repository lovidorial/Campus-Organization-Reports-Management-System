<?php

namespace App\Http\Controllers;

use App\Models\ActivityRequest;
use App\Models\Gpoa;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActivityRequestController extends Controller
{
    public function index()
    {
        $requests = ActivityRequest::where('user_id', auth()->id())
            ->with(['gpoa', 'gpoaActivity.gpoa', 'report', 'monitoringResult'])
            ->latest()
            ->get();

        foreach ($requests as $req) {
            $req->refreshLifecycleStatus();
        }

        $grouped = $requests->groupBy(fn ($request) => optional($request->gpoa ?? $request->gpoaActivity?->gpoa)->id ?: 'ungrouped');

        return view('users.activity-requests', compact('grouped'));
    }

    public function create(Request $request)
    {
        $availableGpoas = Gpoa::where('user_id', auth()->id())
            ->whereIn('status', ['approved', 'stored'])
            ->orderBy('school_year', 'desc')
            ->orderByRaw("CASE WHEN term = '1st Term' THEN 0 ELSE 1 END")
            ->get();

        if ($availableGpoas->isEmpty()) {
            return redirect()->route('gpoa.index')
                ->with('error', 'No approved GPOAs available for activity requests.');
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

        return view('users.create-request', compact('availableGpoas', 'gpoa', 'selectedGpoaId', 'activityLimits', 'categoryCounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'gpoa_id' => [
                'required',
                Rule::exists('gpoas', 'id')->where(function ($q) {
                    $q->where('user_id', auth()->id())
                        ->whereIn('status', ['approved', 'stored']);
                }),
            ],
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'sdgs' => 'required|array|min:1|max:17',
            'sdgs.*' => 'integer|between:1,17',
            'objectives' => 'required|string',
            'expected_outcome' => 'required|string',
            'plan_key_strategy' => 'required|string',
            'date' => 'required|date',
            'venue' => 'required|string|max:255',
            'target_participants' => 'required|string|max:255',
            'person_in_charge' => 'required|string|max:255',
            'facilities_materials' => 'required|string|max:255',
            'estimated_budget' => 'required|numeric|min:0',
            'source_of_funds' => 'required|string|max:100',
            'preceding_activity' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'participants_count' => 'nullable|integer|min:1',
            'communication_letter' => 'required|file|mimes:pdf|max:20480',
        ]);

        $gpoa = Gpoa::findOrFail($validated['gpoa_id']);

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

        $conflict = ActivityRequest::where('date', $validated['date'])
            ->where('venue', $validated['venue'])
            ->where('status', ActivityRequest::STATUS_APPROVED)
            ->exists();

        if ($conflict) {
            return back()->withErrors(['venue' => 'An approved activity is already scheduled at this venue on this date.'])->withInput();
        }

        $commPath = $request->file('communication_letter')->store('uploads/comm', 'public');

        ActivityRequest::create([
            'user_id' => auth()->id(),
            'gpoa_id' => $gpoa->id,
            'gpoa_activity_id' => null,
            'title' => $validated['title'],
            'date' => $validated['date'],
            'venue' => $validated['venue'],
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
            'preceding_activity' => $validated['preceding_activity'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
            'description' => $validated['description'] ?? null,
            'participants_count' => $validated['participants_count'] ?? null,
            'communication_letter' => $commPath,
            'status' => ActivityRequest::STATUS_PENDING,
        ]);

        return redirect()->route('activity-requests.index')
            ->with('success', 'Activity request submitted. Awaiting admin approval.');
    }
}
