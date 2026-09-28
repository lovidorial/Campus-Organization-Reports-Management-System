<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Concept Paper - Activity Request {{ $activityRequest->id }}</title>
    <style>
        @page { margin: 25px 30px; }
        body { color: #26344a; font-family: "DejaVu Serif", serif; font-size: 8px; line-height: 1.3; }
        table { border-collapse: collapse; width: 100%; }
        .title-table { margin-bottom: 9px; }
        .title-table td { vertical-align: middle; }
        .logo-cell { width: 48px; }
        .logo { max-height: 38px; max-width: 42px; }
        .title-cell { text-align: center; }
        .document-title { color: #1e3a8a; font-size: 20px; font-weight: bold; }
        .title-rule { border-top: 2px solid #1e3a8a; margin-top: 5px; }
        .summary { margin-bottom: 8px; table-layout: fixed; }
        .summary td { border: 1px solid #b8cce3; padding: 5px 7px; }
        .label { background: #dbe7f5; color: #1e3a8a; font-weight: bold; }
        .section-title { background: #dbe7f5; border: 1px solid #b8cce3; color: #1e3a8a; font-size: 9px; font-weight: bold; padding: 5px 7px; }
        .section { margin-top: 8px; }
        .details { table-layout: fixed; }
        .details td { border: 1px solid #b8cce3; padding: 5px 7px; vertical-align: top; width: 50%; }
        .field { margin-bottom: 5px; }
        .field:last-child { margin-bottom: 0; }
        .field-label { color: #1e3a8a; font-size: 7px; font-weight: bold; text-transform: uppercase; }
        .field-value { margin-top: 2px; overflow-wrap: break-word; white-space: pre-wrap; }
        .objectives { margin: 2px 0 0; padding-left: 16px; }
        .objectives li { margin-bottom: 2px; }
        .flow th, .flow td { border: 1px solid #b8cce3; padding: 4px 6px; text-align: left; vertical-align: top; }
        .flow th { background: #dbe7f5; color: #1e3a8a; font-weight: bold; }
        .bottom-row { margin-top: 8px; table-layout: fixed; }
        .bottom-row > tbody > tr > td { vertical-align: top; width: 50%; }
        .bottom-row > tbody > tr > td:first-child { padding-right: 4px; }
        .bottom-row > tbody > tr > td:last-child { padding-left: 4px; }
        .budget th, .budget td { border: 1px solid #b8cce3; padding: 4px 5px; text-align: left; vertical-align: top; }
        .budget th { background: #dbe7f5; color: #1e3a8a; font-size: 7px; }
        .budget .amount { text-align: right; white-space: nowrap; }
        .budget-total td { background: #eef4fb; font-weight: bold; }
        .additional td { border: 1px solid #b8cce3; padding: 4px 6px; vertical-align: top; }
        .additional .label { width: 38%; }
        .signatures { margin-top: 13px; table-layout: fixed; }
        .signatures td { border-right: 1px solid #b8cce3; padding: 0 10px; text-align: center; vertical-align: top; width: 33.33%; }
        .signatures td:first-child { padding-left: 0; }
        .signatures td:last-child { border-right: 0; padding-right: 0; }
        .signature-space { height: 25px; }
        .signature-line { border-top: 1px solid #53647a; margin-bottom: 3px; }
        .signature-name { color: #26344a; font-size: 8px; font-weight: bold; }
        .signature-role { color: #52647c; font-size: 7px; }
        .prepared-label { color: #1e3a8a; font-size: 7px; font-weight: bold; margin-bottom: 2px; text-transform: uppercase; }
        .empty { color: #52647c; font-style: italic; }
    </style>
</head>
<body>
    @php
        $request = $activityRequest;
        $requester = $request->user;
        $statusLabel = str_replace('_', ' ', ucfirst((string) $request->status));
        $requestDate = $request->date?->format('M. d, Y') ?? '—';
        if ($request->end_date && $request->end_date->ne($request->date)) {
            $requestDate .= ' – ' . $request->end_date->format('M. d, Y');
        }
        $startTime = $request->start_time ? substr((string) $request->start_time, 0, 5) : null;
        $endTime = $request->end_time ? substr((string) $request->end_time, 0, 5) : null;
        $timeRange = $startTime || $endTime
            ? ($startTime ?? '—') . ' – ' . ($endTime ?? '—')
            : 'Time not set';
        $budget = $request->estimated_budget !== null
            ? '₱' . number_format((float) $request->estimated_budget, 2)
            : '—';
        $objectives = preg_split('/\r\n|\r|\n/', trim((string) $request->objectives), -1, PREG_SPLIT_NO_EMPTY);
        $createdBy = $requester?->name ?: '—';
        if (filled($requester?->position)) {
            $createdBy .= ' (' . $requester->position . ')';
        }
        $createdDate = $request->created_at?->format('M. d, Y') ?? '—';
        $updatedDate = $request->updated_at?->format('M. d, Y') ?? '—';
        $programFlows = $request->programFlows->sortBy('sort_order');
    @endphp

    <table class="title-table">
        <tr>
            <td class="logo-cell">
                @if($logoDataUri)
                    <img class="logo" src="{{ $logoDataUri }}" alt="Organization logo">
                @endif
            </td>
            <td class="title-cell"><div class="document-title">CONCEPT PAPER</div></td>
            <td class="logo-cell"></td>
        </tr>
        <tr><td colspan="3"><div class="title-rule"></div></td></tr>
    </table>

    <table class="summary">
        <tr>
            <td class="label" style="width: 18%;">Request ID</td>
            <td style="width: 32%;">{{ $request->id }}</td>
            <td class="label" style="width: 18%;">Status</td>
            <td style="width: 32%;">{{ filled($statusLabel) ? $statusLabel : '—' }}</td>
        </tr>
        <tr>
            <td class="label">Organization</td>
            <td>{{ filled($organizationName) ? $organizationName : '—' }}</td>
            <td class="label">Category</td>
            <td>{{ filled($request->category) ? $request->category : '—' }}</td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">PROJECT INFORMATION</div>
        <table class="details">
            <tr>
                <td>
                    <div class="field"><div class="field-label">Project Title</div><div class="field-value">{{ filled($request->title) ? $request->title : '—' }}</div></div>
                    <div class="field"><div class="field-label">Date &amp; Time</div><div class="field-value">{{ $requestDate }}<br>{{ $timeRange }}</div></div>
                    <div class="field"><div class="field-label">Venue</div><div class="field-value">{{ filled($request->venue) ? $request->venue : '—' }}</div></div>
                </td>
                <td>
                    <div class="field"><div class="field-label">Activity Level</div><div class="field-value">{{ filled($request->activity_level) ? $request->activity_level : '—' }}</div></div>
                    <div class="field"><div class="field-label">Target Participants</div><div class="field-value">{{ filled($request->target_participants) ? $request->target_participants : '—' }}</div></div>
                    <div class="field"><div class="field-label">Expected Participants</div><div class="field-value">{{ $request->participants_count !== null ? $request->participants_count : '—' }}</div></div>
                    <div class="field"><div class="field-label">Estimated Budget</div><div class="field-value">{{ $budget }}</div></div>
                    <div class="field"><div class="field-label">Funding Source</div><div class="field-value">{{ filled($request->source_of_funds) ? $request->source_of_funds : '—' }}</div></div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">ACTIVITY DETAILS</div>
        <table class="details">
            <tr>
                <td>
                    <div class="field">
                        <div class="field-label">Objectives</div>
                        @if(count($objectives))
                            <ol class="objectives">
                                @foreach($objectives as $objective)
                                    <li>{{ $objective }}</li>
                                @endforeach
                            </ol>
                        @else
                            <div class="field-value">—</div>
                        @endif
                    </div>
                    <div class="field"><div class="field-label">Target Participants / Beneficiaries</div><div class="field-value">{{ filled($request->target_participants) ? $request->target_participants : '—' }}</div></div>
                </td>
                <td>
                    <div class="field"><div class="field-label">Expected Outcome</div><div class="field-value">{{ filled($request->expected_outcome) ? $request->expected_outcome : '—' }}</div></div>
                    <div class="field"><div class="field-label">Description</div><div class="field-value">{{ filled($request->description) ? $request->description : '—' }}</div></div>
                </td>
            </tr>
        </table>
    </div>

    @if($programFlows->isNotEmpty())
        <div class="section">
            <div class="section-title">PROGRAM FLOW</div>
            <table class="flow">
                <thead>
                    <tr><th style="width: 20%;">Time</th><th style="width: 48%;">Activity</th><th style="width: 32%;">Person Responsible</th></tr>
                </thead>
                <tbody>
                    @foreach($programFlows as $flow)
                        <tr>
                            <td>{{ filled($flow->time) ? $flow->time : '—' }}</td>
                            <td>{{ filled($flow->flow) ? $flow->flow : '—' }}</td>
                            <td>{{ filled($flow->person_in_charge) ? $flow->person_in_charge : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <table class="bottom-row">
        <tr>
            <td>
                <div class="section-title">BUDGETARY REQUIREMENTS</div>
                <table class="budget">
                    <thead>
                        <tr><th style="width: 42%;">Particulars</th><th style="width: 17%;">Quantity</th><th style="width: 20%;">Unit Cost</th><th style="width: 21%;">Total Cost</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Estimated budget (single total)</td>
                            <td>—</td>
                            <td class="amount">—</td>
                            <td class="amount">{{ $budget }}</td>
                        </tr>
                        <tr class="budget-total"><td colspan="3">TOTAL</td><td class="amount">{{ $budget }}</td></tr>
                    </tbody>
                </table>
            </td>
            <td>
                <div class="section-title">ADDITIONAL INFORMATION</div>
                <table class="additional">
                    <tr><td class="label">Communication Letter</td><td>{{ filled($request->communication_letter) ? 'Yes' : 'No' }}</td></tr>
                    <tr><td class="label">Created</td><td>{{ $createdDate }}</td></tr>
                    <tr><td class="label">Updated</td><td>{{ $updatedDate }}</td></tr>
                    <tr><td class="label">Created by</td><td>{{ $createdBy }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="signatures">
        <tr>
            <td>
                <div class="prepared-label">Prepared by</div>
                <div class="signature-space"></div>
                <div class="signature-line"></div>
                <div class="signature-name">{{ filled($requester?->name) ? $requester->name : '—' }}</div>
                <div class="signature-role">{{ filled($requester?->position) ? $requester->position : 'Requester' }}</div>
            </td>
            <td>
                <div class="prepared-label">Noted by</div>
                <div class="signature-space"></div>
                <div class="signature-line"></div>
                <div class="signature-name">&nbsp;</div>
                <div class="signature-role">Adviser</div>
            </td>
            <td>
                <div class="prepared-label">Approved by</div>
                <div class="signature-space"></div>
                <div class="signature-line"></div>
                <div class="signature-name">&nbsp;</div>
                <div class="signature-role">Campus Dean</div>
            </td>
        </tr>
    </table>
</body>
</html>