<?php

namespace App\Http\Controllers;

use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Services\GpoaMatchValidator;
use Illuminate\Http\Request;

class ActivityRequestController extends Controller
{
    public function index()
    {
        $requests = ActivityRequest::where('user_id', auth()->id())
            ->with(['gpoaActivity.gpoa', 'report', 'monitoringResult'])
            ->latest()
            ->paginate(10);

        foreach ($requests as $req) {
            $req->refreshLifecycleStatus();
        }

        return view('users.activity-requests', compact('requests'));
    }

    public function create()
    {
        $user = auth()->user();
        $term = $user->term ?? '1st Term';
        $schoolYear = $user->school_year ?? (date('Y') . '-' . (date('Y') + 1));

        $gpoa = Gpoa::where('user_id', auth()->id())
            ->where('term', $term)
            ->where('school_year', $schoolYear)
            ->whereIn('status', ['approved', 'stored'])
            ->with(['activities' => function ($q) {
                $q->whereDoesntHave('activityRequests', function ($r) {
                    $r->whereNotIn('status', ['rejected']);
                });
            }])
            ->first();

        if (!$gpoa) {
            return redirect()->route('gpoa.index')
                ->with('error', 'No approved GPOA found for the current term and school year.');
        }

        $lineItems = $gpoa->activities;

        return view('users.create-request', compact('lineItems', 'gpoa'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'gpoa_activity_id'    => 'required|exists:gpoa_activities,id',
            'title'               => 'required|string|max:255',
            'date'                => 'required|date',
            'venue'               => 'required|string|max:255',
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

        $matchError = GpoaMatchValidator::validate($lineItem, $validated);
        if ($matchError) {
            return back()->withErrors(['match' => $matchError])->withInput();
        }

        $existing = ActivityRequest::where('gpoa_activity_id', $lineItem->id)
            ->whereNotIn('status', ['rejected'])
            ->exists();

        if ($existing) {
            return back()->withErrors(['gpoa_activity_id' => 'An activity request already exists for this GPOA entry.'])->withInput();
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
            'user_id'              => auth()->id(),
            'gpoa_activity_id'     => $lineItem->id,
            'title'                => $validated['title'],
            'date'                 => $validated['date'],
            'venue'                => $validated['venue'],
            'category'             => $lineItem->category,
            'description'          => $validated['description'] ?? $lineItem->description,
            'participants_count'   => $validated['participants_count'] ?? $lineItem->participants_count,
            'communication_letter' => $commPath,
            'status'               => ActivityRequest::STATUS_PENDING,
        ]);

        return redirect()->route('activity-requests.index')
            ->with('success', 'Activity request submitted. Awaiting admin approval.');
    }
}
