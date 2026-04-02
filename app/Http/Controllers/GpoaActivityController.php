<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\Request;

class GpoaActivityController extends Controller
{
    public function create()
    {
        return view('gpoa.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'               => 'required|string|max:255',
            'colleges'            => 'required|string|max:100',
            'date'                => 'required|date',
            'venue'               => 'required|string|max:255',
            'description'         => 'required|string',
            'participants_count'  => 'required|integer|min:1',
            'category'            => 'required|string|max:100',
            'basis_grading'       => 'nullable|string|max:50',
            'term'                => 'required|string|max:50',
            'school_year'         => 'required|string|max:20',
            'communication_letter'=> 'required|file|mimes:pdf|max:20480',
            'narrative_report'    => 'required|file|mimes:pdf|max:20480',
            'verify'              => 'required|accepted',
        ]);

        // Venue/date conflict check
        $conflict = Activity::where('date', $request->date)
            ->where('venue', $request->venue)
            ->where('status', 'approved')
            ->exists();

        if ($conflict) {
            return back()->withErrors(['venue' => 'An approved activity already exists at this venue on this date.'])->withInput();
        }

        $commPath      = $request->file('communication_letter')->store('uploads/comm', 'public');
        $narrativePath = $request->file('narrative_report')->store('uploads/narratives', 'public');

        Activity::create([
            'user_id'              => auth()->id(),
            'title'                => $validated['title'],
            'organization'         => auth()->user()->org_name,
            'date'                 => $validated['date'],
            'venue'                => $validated['venue'],
            'description'          => $validated['description'],
            'participants_count'   => $validated['participants_count'],
            'category'             => $validated['category'],
            'basis_grading'        => $request->basis_grading,
            'term'                 => $validated['term'],
            'school_year'          => $validated['school_year'],
            'communication_letter' => $commPath,
            'narrative_report'     => $narrativePath,
        ]);

        return redirect()->route('user.activities')->with('success', 'Activity submitted successfully!');
    }
}
