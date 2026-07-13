<?php

namespace App\Http\Controllers;

use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\MonitoringResult;
use App\Services\GpoaMatchValidator;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminGpoaController extends Controller
{
    public function index(Request $request)
    {
        $query = Gpoa::with(['user', 'activities'])->withCount('activities');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('term', 'like', "%{$term}%")
                  ->orWhere('school_year', 'like', "%{$term}%")
                  ->orWhereHas('user', function ($u) use ($term) {
                      $u->where('org_name', 'like', "%{$term}%")
                        ->orWhere('name', 'like', "%{$term}%");
                  });
            });
        }

        $gpoas = $query->latest()->paginate(10)->withQueryString();

        $stats = [
            'total'    => Gpoa::count(),
            'pending'  => Gpoa::where('status', 'pending')->count(),
            'approved' => Gpoa::whereIn('status', ['approved', 'stored'])->count(),
            'rejected' => Gpoa::where('status', 'rejected')->count(),
        ];

        return view('admin.gpoa.index', compact('gpoas', 'stats'));
    }

    public function show(Gpoa $gpoa)
    {
        $gpoa->load(['user', 'activities', 'approver']);

        return view('admin.gpoa.show', compact('gpoa'));
    }

    public function approve(Gpoa $gpoa)
    {
        if ($gpoa->status !== 'pending') {
            return back()->with('error', 'Only pending GPOAs can be approved.');
        }

        $gpoa->update([
            'status'      => 'stored',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'stored_at'   => now(),
            'reject_reason' => null,
        ]);

        return back()->with('success', 'GPOA verified, approved, and stored successfully.');
    }

    public function reject(Request $request, Gpoa $gpoa)
    {
        $request->validate(['reject_reason' => 'nullable|string|max:500']);

        if ($gpoa->status !== 'pending') {
            return back()->with('error', 'Only pending GPOAs can be rejected.');
        }

        $gpoa->update([
            'status'        => 'rejected',
            'reject_reason' => $request->reject_reason,
        ]);

        return back()->with('success', 'GPOA rejected.');
    }
}
