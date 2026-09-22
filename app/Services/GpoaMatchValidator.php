<?php

namespace App\Services;

use App\Models\GpoaActivity;

class GpoaMatchValidator
{
    public static function validate(GpoaActivity $lineItem, array $data): ?string
    {
        if (strcasecmp(trim((string) $lineItem->title), trim((string) ($data['title'] ?? ''))) !== 0) {
            return 'Activity title must match the approved GPOA entry.';
        }

        $lineDate = $lineItem->date->toDateString();
        $requestDate = isset($data['date']) ? date('Y-m-d', strtotime($data['date'])) : '';

        if ($lineDate !== $requestDate) {
            return 'Activity date must match the approved GPOA entry.';
        }

        if (strcasecmp(trim((string) $lineItem->venue), trim((string) ($data['venue'] ?? ''))) !== 0) {
            return 'Activity venue must match the approved GPOA entry.';
        }

        if (strcasecmp(trim((string) $lineItem->category), trim((string) ($data['category'] ?? ''))) !== 0) {
            return 'Activity category must match the approved GPOA entry.';
        }

        return null;
    }
}
