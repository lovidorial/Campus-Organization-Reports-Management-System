<?php

namespace App\Http\Controllers;

use App\Models\Gpoa;
use App\Models\GpoaActivity;
use Illuminate\Http\Request;

class GpoaController extends Controller
{
    public function index()
    {
        $gpoas = Gpoa::where('user_id', auth()->id())
            ->withCount('activities')
            ->latest()
            ->paginate(10);

        $user = auth()->user();
        $term = $user->term ?? '1st Term';
        $schoolYear = $user->school_year ?? (date('Y') . '-' . (date('Y') + 1));

        $hasApprovedGpoa = Gpoa::where('user_id', auth()->id())
            ->where('term', $term)
            ->where('school_year', $schoolYear)
            ->whereIn('status', ['approved', 'stored'])
            ->exists();

        return view('gpoa.index', compact('gpoas', 'hasApprovedGpoa', 'term', 'schoolYear'));
    }

    public function create()
    {
        $user = auth()->user();
        $term = $user->term ?? '1st Term';
        $schoolYear = $user->school_year ?? (date('Y') . '-' . (date('Y') + 1));

        $existingPending = Gpoa::where('user_id', auth()->id())
            ->where('term', $term)
            ->where('school_year', $schoolYear)
            ->where('status', 'pending')
            ->exists();

        if ($existingPending) {
            return redirect()->route('gpoa.index')
                ->with('error', 'You already have a pending GPOA for this term and school year.');
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

        $existing = Gpoa::where('user_id', auth()->id())
            ->where('term', $validated['term'])
            ->where('school_year', $validated['school_year'])
            ->whereIn('status', ['pending', 'approved', 'stored'])
            ->exists();

        if ($existing) {
            return back()->withErrors(['term' => 'A GPOA for this term and school year already exists or is pending review.'])->withInput();
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

        return redirect()->route('gpoa.index')->with('success', 'GPOA submitted successfully. Wait for admin approval before requesting activities.');
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
