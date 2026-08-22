<?php

namespace App\Services;

use App\Models\ActivityRequest;
use App\Models\GpoaActivity;
use RuntimeException;

class GpoaActivityLinker
{
    public function link(ActivityRequest $activity): GpoaActivity
    {
        $gpoa = $activity->gpoa;

        if (!$gpoa) {
            throw new RuntimeException('Cannot link activity without a parent GPOA.');
        }

        $gpoaActivityData = [
            'gpoa_id' => $gpoa->id,
            'activity_request_id' => $activity->id,
            'title' => $activity->title,
            'date' => $activity->date,
            'venue' => $activity->venue,
            'category' => $activity->category,
            'objectives' => $activity->objectives,
            'expected_outcome' => $activity->expected_outcome,
            'target_participants' => $activity->target_participants,
            'sdgs' => $activity->sdgs,
            'plan_key_strategy' => $activity->plan_key_strategy,
            'person_in_charge' => $activity->person_in_charge,
            'facilities_materials' => $activity->facilities_materials,
            'estimated_budget' => $activity->estimated_budget,
            'source_of_funds' => $activity->source_of_funds,
            'preceding_activity' => $activity->preceding_activity,
            'remarks' => $activity->remarks,
        ];

        if ($activity->gpoa_activity_id) {
            return GpoaActivity::updateOrCreate(
                ['id' => $activity->gpoa_activity_id],
                $gpoaActivityData
            );
        }

        $gpoaActivity = GpoaActivity::updateOrCreate(
            ['activity_request_id' => $activity->id],
            $gpoaActivityData
        );
        $activity->update(['gpoa_activity_id' => $gpoaActivity->id]);

        return $gpoaActivity;
    }
}
