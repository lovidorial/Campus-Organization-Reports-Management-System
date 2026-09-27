<?php

namespace App\Http\Controllers;

use App\Models\ActivityRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $activities = ActivityRequest::query()
            ->where('status', ActivityRequest::STATUS_APPROVED)
            ->where(function ($query) {
                $query->whereDate('date', '>=', today()->toDateString())
                    ->orWhere(function ($query) {
                        $query->whereDate('date', '<', today()->toDateString())
                            ->whereDate('end_date', '>=', today()->toDateString());
                    });
            })
            ->orderBy('date')
            ->orderBy('start_time')
            ->get([
                'id',
                'title',
                'category',
                'date',
                'end_date',
                'start_time',
                'end_time',
                'venue',
            ]);

        return view('public.schedule', compact('activities'));
    }
}