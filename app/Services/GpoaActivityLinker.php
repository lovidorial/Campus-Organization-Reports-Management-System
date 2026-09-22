<?php

namespace App\Services;

use App\Models\ActivityRequest;
use App\Models\GpoaActivity;
use RuntimeException;

class GpoaActivityLinker
{
    public function link(ActivityRequest $activity): GpoaActivity
    {
        if (!$activity->gpoa_activity_id) {
            throw new RuntimeException('Activity requests must select an approved planned GPOA activity.');
        }

        $gpoaActivity = GpoaActivity::where('id', $activity->gpoa_activity_id)
            ->where('gpoa_id', $activity->gpoa?->id ?? $activity->gpoaActivity?->gpoa_id)
            ->first();

        if (!$gpoaActivity) {
            throw new RuntimeException('The selected planned GPOA activity no longer exists.');
        }

        $gpoaActivity->update(['activity_request_id' => $activity->id]);

        return $gpoaActivity;
    }
}
