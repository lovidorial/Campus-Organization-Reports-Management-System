<?php

namespace App\Http\Controllers;

use App\Models\WorkflowSubmission;

class WorkflowSubmissionHistoryController extends Controller
{
    public function index()
    {
        $submissions = WorkflowSubmission::query()
            ->with(['workflow', 'reviewer'])
            ->whereHas('workflow', fn ($query) => $query->where('user_id', auth()->id()))
            ->orderByRaw('COALESCE(submitted_at, created_at) DESC')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('workflow.submission-history', compact('submissions'));
    }
}