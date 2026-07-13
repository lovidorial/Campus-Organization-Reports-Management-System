<?php

namespace App\Services;

use App\Models\GpoaActivity;

class GpoaMatchValidator
{
    public static function validate(GpoaActivity $lineItem, array $data): ?string
    {
        if ($lineItem->title !== ($data['title'] ?? '')) {
            return 'Activity title must match the approved GPOA entry.';
        }

        $lineDate = $lineItem->date->toDateString();
        $requestDate = isset($data['date']) ? date('Y-m-d', strtotime($data['date'])) : '';

        if ($lineDate !== $requestDate) {
            return 'Activity date must match the approved GPOA entry.';
        }

        if ($lineItem->venue !== ($data['venue'] ?? '')) {
            return 'Activity venue must match the approved GPOA entry.';
        }

        return null;
    }
}
