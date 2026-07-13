<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ActivityController extends Controller
{
    public function create()
    {
        return view('users.upload');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'venue' => 'required|string|max:255',
            'communication_letter' => 'nullable|file|mimes:pdf,doc,docx|max:20480',
            'narrative_report' => 'nullable|file|mimes:pdf,doc,docx|max:20480',
        ]);

        // Check for Date & Venue Conflict
        $conflict = Activity::where('date', $request->date)
            ->where('venue', $request->venue)
            ->where('status', 'approved')
            ->exists();

        if ($conflict) {
            return back()->withErrors(['venue' => 'An activity is already approved at this venue on this date.'])->withInput();
        }

        // Handle File Uploads
        $commPath = $request->hasFile('communication_letter') 
            ? $request->file('communication_letter')->store('uploads/comm', 'public') 
            : null;

        $narrativePath = $request->hasFile('narrative_report') 
            ? $request->file('narrative_report')->store('uploads/narratives', 'public') 
            : null;

        Activity::create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'date' => $validated['date'],
            'venue' => $validated['venue'],
            // 'organization' or category may be handled differently for GPOA

            'communication_letter' => $commPath,
            'narrative_report' => $narrativePath,
        ]);

        return redirect()->route('user.activities')->with('success', 'Activity submitted successfully!');
    }

    public function index()
    {
        $activities = Activity::where('user_id', auth()->id())->latest()->paginate(10);
        return view('users.activities', compact('activities'));
    }

    public function edit(Activity $activity)
    {
        if ($activity->user_id !== auth()->id()) {
            abort(403);
        }

        return view('users.edit-activity', compact('activity'));
    }

    public function update(Request $request, Activity $activity)
    {
        if ($activity->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'venue' => 'required|string|max:255',
            'communication_letter' => 'nullable|file|mimes:pdf,doc,docx|max:20480',
            'narrative_report' => 'nullable|file|mimes:pdf,doc,docx|max:20480',
        ]);

        if ($request->hasFile('communication_letter')) {
            if ($activity->communication_letter) {
                Storage::disk('public')->delete($activity->communication_letter);
            }
            $activity->communication_letter = $request->file('communication_letter')->store('uploads/comm', 'public');
        }

        if ($request->hasFile('narrative_report')) {
            if ($activity->narrative_report) {
                Storage::disk('public')->delete($activity->narrative_report);
            }
            $activity->narrative_report = $request->file('narrative_report')->store('uploads/narratives', 'public');
        }

        $updateData = [
            'title' => $validated['title'],
            'date' => $validated['date'],
            'venue' => $validated['venue'],
            'communication_letter' => $activity->communication_letter,
            'narrative_report' => $activity->narrative_report,
        ];

        // If activity was rejected, change status back to pending when resubmitting
        if ($activity->status === 'rejected') {
            $updateData['status'] = 'pending';
            $updateData['reject_reason'] = null;
        }

        $activity->update($updateData);

        $message = $activity->status === 'pending' && $activity->wasChanged('status') 
            ? 'Activity resubmitted for review successfully.' 
            : 'Activity updated successfully.';

        return redirect()->route('user.activities')->with('success', $message);
    }

    public function destroy(Activity $activity)
    {
        if ($activity->user_id !== auth()->id() || $activity->status === 'approved') {
            abort(403);
        }

        if ($activity->communication_letter) {
            Storage::disk('public')->delete($activity->communication_letter);
        }
        if ($activity->narrative_report) {
            Storage::disk('public')->delete($activity->narrative_report);
        }

        $activity->delete();

        return redirect()->route('user.activities')->with('success', 'Activity deleted successfully.');
    }

    public function deleteFile(Activity $activity, $type)
    {
        if ($activity->user_id !== auth()->id() || $activity->status === 'approved') {
            abort(403);
        }

        if (!in_array($type, ['communication_letter', 'narrative_report'])) {
            abort(404);
        }

        if ($activity->$type) {
            Storage::disk('public')->delete($activity->$type);
            $activity->update([$type => null]);
        }

        return redirect()->route('user.activities')->with('success', ucfirst(str_replace('_', ' ', $type)) . ' deleted successfully.');
    }

    public function publicActivities(Request $request)
    {
        $query = Activity::where('status', 'approved')->with('user', 'user.organization');

        // Search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%$search%")
                  ->orWhereHas('user', function($u) use ($search) {
                      $u->where('org_name', 'like', "%$search%")
                        ->orWhere('name', 'like', "%$search%");
                  })
                  ->orWhere('description', 'like', "%$search%");
            });
        }

        // Organization filter - filter by user_id (since each user is an organization)
        if ($request->has('organization') && $request->organization) {
            $query->where('user_id', $request->organization);
        }

        // School Year filter
        if ($request->has('school_year') && $request->school_year) {
            $query->where('school_year', $request->school_year);
        }

        $activities = $query->orderBy('date', 'desc')->paginate(12);
        
        // Get all organizations (users with activities) for filter dropdown
        $organizations = User::whereHas('activities', function($q) {
            $q->where('status', 'approved');
        })->with('organization')->get();
        
        // Get all school years for filter dropdown
        $schoolYears = Activity::where('status', 'approved')
            ->distinct()
            ->whereNotNull('school_year')
            ->orderBy('school_year', 'desc')
            ->pluck('school_year');
        
        return view('public.activities', compact('activities', 'organizations', 'schoolYears'));
    }
}