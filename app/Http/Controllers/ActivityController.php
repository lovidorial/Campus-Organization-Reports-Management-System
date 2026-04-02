<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Http\Request;

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
            'communication_letter' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'narrative_report' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
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