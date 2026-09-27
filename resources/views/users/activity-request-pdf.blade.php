<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity Request {{ $activityRequest->id }}</title>
    <style>
        @page { margin: 34px 38px; }
        body { color: #1f2937; font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        .letterhead { border-bottom: 2px solid #b45309; margin-bottom: 16px; padding-bottom: 10px; width: 100%; }
        .letterhead td { vertical-align: middle; }
        .logo { height: 56px; max-width: 76px; }
        .org-name { color: #7c2d12; font-size: 15px; font-weight: bold; }
        .document-title { color: #334155; font-size: 11px; margin-top: 4px; }
        .generated { color: #64748b; font-size: 8px; text-align: right; }
        h2 { border-bottom: 1px solid #cbd5e1; color: #7c2d12; font-size: 10px; margin: 15px 0 7px; padding-bottom: 4px; text-transform: uppercase; }
        table { border-collapse: collapse; width: 100%; }
        .summary { margin-bottom: 10px; }
        .summary td { padding: 4px 6px; }
        .summary .label { background: #f1f5f9; color: #475569; font-size: 8px; font-weight: bold; width: 17%; }
        .summary .value { border-bottom: 1px solid #e2e8f0; width: 33%; }
        .fields td { border: 1px solid #dbe2ea; padding: 5px 6px; vertical-align: top; width: 50%; }
        .field-label { color: #64748b; display: block; font-size: 7px; font-weight: bold; margin-bottom: 3px; text-transform: uppercase; }
        .field-value { line-height: 1.4; overflow-wrap: anywhere; white-space: pre-wrap; }
        .flow th, .flow td { border: 1px solid #cbd5e1; padding: 5px 6px; text-align: left; }
        .flow th { background: #f1f5f9; color: #475569; font-size: 8px; }
        .flow td { font-size: 8px; }
        .empty { color: #64748b; font-style: italic; padding: 6px 0; }
        .footer { border-top: 1px solid #cbd5e1; color: #64748b; font-size: 7px; margin-top: 18px; padding-top: 6px; }
    </style>
</head>
<body>
    @php
        $request = $activityRequest;
        $statusLabel = str_replace('_', ' ', ucfirst($request->status));
        $timeRange = ($request->start_time ? substr((string) $request->start_time, 0, 5) : '—')
            . ' – '
            . ($request->end_time ? substr((string) $request->end_time, 0, 5) : '—');
        $dateRange = $request->date?->format('M d, Y') ?? '—';
        if ($request->end_date) {
            $dateRange .= ' – ' . $request->end_date->format('M d, Y');
        }
        $fields = [
            'Title' => $request->title,
            'Category' => $request->category,
            'Venue' => $request->venue,
            'Request / User / GPOA / Planned Activity / Venue IDs' => implode(' / ', [
                $request->id,
                $request->user_id,
                $request->gpoa_id ?? '—',
                $request->gpoa_activity_id ?? '—',
                $request->venue_id ?? '—',
            ]),
            'Date / End Date' => $dateRange,
            'Start / End Time' => $timeRange,
            'Activity Level' => $request->activity_level,
            'SDGs' => $request->sdgs ? implode(', ', $request->sdgs) : '—',
            'Description' => $request->description,
            'Objectives' => $request->objectives,
            'Expected Outcome' => $request->expected_outcome,
            'Plan / Key Strategy' => $request->plan_key_strategy,
            'Target Participants' => $request->target_participants,
            'Participants Count' => $request->participants_count,
            'Person in Charge' => $request->person_in_charge,
            'Facilities / Materials' => $request->facilities_materials,
            'Estimated Budget' => $request->estimated_budget !== null ? number_format((float) $request->estimated_budget, 2) : '—',
            'Source of Funds' => $request->source_of_funds,
            'Preceding Activity' => $request->preceding_activity,
            'Remarks' => $request->remarks,
            'Rejection Reason' => $request->reject_reason,
            'Urgent' => $request->is_urgent ? 'Yes' : 'No',
        ];

        if ($request->is_urgent && $request->urgent_reason) {
            $fields['Urgent Reason'] = $request->urgent_reason;
        }

        $fields['Communication Letter'] = $request->communication_letter ?? '—';
        $fields['Reservation Slip'] = $request->reservation_slip ?? '—';
        $fields['Created / Updated'] = ($request->created_at?->format('M d, Y H:i') ?? '—')
            . ' / '
            . ($request->updated_at?->format('M d, Y H:i') ?? '—');
    @endphp

    <table class="letterhead">
        <tr>
            @if($logoDataUri)
                <td style="width: 88px;"><img class="logo" src="{{ $logoDataUri }}" alt="Organization logo"></td>
            @endif
            <td>
                <div class="org-name">{{ $organizationName }}</div>
                <div class="document-title">Activity Request Documentation</div>
            </td>
            <td class="generated">Generated {{ now()->format('M d, Y') }}</td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td class="label">Request ID</td><td class="value">{{ $request->id }}</td>
            <td class="label">Status</td><td class="value">{{ $statusLabel }}</td>
        </tr>
        <tr>
            <td class="label">Organization</td><td class="value">{{ $organizationName }}</td>
            <td class="label">Urgency</td><td class="value">{{ $request->is_urgent ? 'Urgent' : 'Standard' }}</td>
        </tr>
    </table>

    <h2>Activity Details</h2>
    <table class="fields">
        @foreach(collect($fields)->chunk(2) as $fieldPair)
            <tr>
                @foreach($fieldPair as $label => $value)
                    <td>
                        <span class="field-label">{{ $label }}</span>
                        <span class="field-value">{{ filled($value) ? $value : '—' }}</span>
                    </td>
                @endforeach
                @if($fieldPair->count() === 1)
                    <td></td>
                @endif
            </tr>
        @endforeach
    </table>

    <h2>Program Flow</h2>
    @if($request->programFlows->isNotEmpty())
        <table class="flow">
            <thead>
                <tr><th style="width: 18%;">Time</th><th style="width: 52%;">Flow</th><th style="width: 30%;">Person in Charge</th></tr>
            </thead>
            <tbody>
                @foreach($request->programFlows as $flow)
                    <tr><td>{{ $flow->time }}</td><td>{{ $flow->flow }}</td><td>{{ $flow->person_in_charge }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="empty">No program flow items were provided.</p>
    @endif

    <div class="footer">Activity Request #{{ $request->id }} · {{ $organizationName }} · Printed documentation copy</div>
</body>
</html>