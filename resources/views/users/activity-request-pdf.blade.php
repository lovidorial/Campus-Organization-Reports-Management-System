<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Concept Paper</title>
    <style>
        @page { size: legal portrait; margin: 1.62in 0.55in 0.78in 1.55in; }
        * { box-sizing: border-box; }
        body { color: #111; font-family: "DejaVu Sans", sans-serif; font-size: 8.5pt; line-height: 1.32; }
        table { border-collapse: collapse; width: 100%; }
        .letterhead { position: fixed; top: -1.42in; left: -1.02in; right: -0.48in; height: 1.27in; border-bottom: 1px solid #111; }
        .letterhead-table { height: 1.1in; table-layout: fixed; }
        .letterhead-logo { width: 0.86in; text-align: left; vertical-align: middle; }
        .letterhead-logo img { width: 0.76in; height: 0.76in; object-fit: contain; }
        .letterhead-copy { text-align: center; vertical-align: middle; font-family: "DejaVu Serif", serif; }
        .republic { font-size: 8pt; }
        .university { margin-top: 1px; font-size: 14pt; font-weight: bold; }
        .campus { margin-top: 1px; font-size: 9pt; font-weight: bold; }
        .address { margin-top: 1px; font-size: 8pt; font-style: italic; }
        .contact { margin-top: 3px; font-family: "DejaVu Sans", sans-serif; font-size: 5.6pt; }
        .organization-heading { position: fixed; top: -0.16in; left: -1.02in; right: -0.48in; text-align: center; font-family: "DejaVu Serif", serif; font-size: 8pt; font-weight: bold; }
        .sidebar { position: fixed; top: 0.04in; bottom: 0.06in; left: -1.31in; width: 1.12in; overflow: hidden; font-size: 5.2pt; line-height: 1.18; text-align: justify; }
        .sidebar-building { display: block; width: 100%; height: 0.72in; margin: 0 auto 5px; object-fit: contain; }
        .sidebar-title { margin: 5px 0 2px; font-size: 6pt; font-weight: bold; text-align: left; }
        .sidebar p { margin: 0 0 3px; }
        .sidebar ul { margin: 1px 0 3px; padding-left: 9px; }
        .sidebar li { margin-bottom: 2px; }
        .page-footer { position: fixed; bottom: -0.62in; left: 0; right: 0; height: 0.51in; border-top: 1px solid #111; padding-top: 3px; text-align: center; }
        .page-footer img { width: 100%; height: 0.43in; object-fit: fill; }
        .concept-title { margin: 0 0 9px; text-align: center; font-family: "DejaVu Serif", serif; font-size: 13pt; font-weight: bold; }
        .activity-table { table-layout: fixed; border: 1px solid #111; font-size: 8pt; }
        .activity-table th, .activity-table td { border: 1px solid #111; padding: 5px 6px; vertical-align: top; }
        .activity-table th { width: 28%; text-align: left; font-weight: bold; }
        .short-row { page-break-inside: avoid; }
        .long-row { page-break-inside: auto; }
        .objective-item { margin-bottom: 2px; }
        .objective-item:last-child { margin-bottom: 0; }
        .sdg-item { margin-bottom: 2px; font-weight: bold; }
        .sdg-item:last-child { margin-bottom: 0; }
        .flow-item { margin-bottom: 5px; }
        .flow-item:last-child { margin-bottom: 0; }
        .flow-time { font-style: italic; }
        .signatories { width: 100%; margin-top: 17px; table-layout: fixed; page-break-inside: avoid; }
        .signatories td { width: 50%; padding: 0 12px; vertical-align: top; }
        .signatories td:first-child { padding-left: 0; }
        .signatories td:last-child { padding-right: 0; }
        .signatory-heading { margin-bottom: 15px; font-weight: bold; }
        .signature-line { height: 25px; border-bottom: 1px solid #111; }
        .prepared-name { min-height: 13px; margin-top: 3px; font-weight: bold; text-transform: uppercase; }
        .prepared-details, .adviser-label { margin-top: 2px; font-size: 7pt; }
        .adviser-columns { display: table; width: 100%; table-layout: fixed; }
        .adviser { display: table-cell; width: 50%; padding-right: 8px; text-align: center; }
        .adviser:last-child { padding-right: 0; padding-left: 8px; }
    </style>
</head>
<body>
    @php
        $request = $activityRequest;
        $requester = $request->user;
        $college = trim((string) ($organizationCollege ?? ''));
        $collegeNames = [
            'CICS' => 'College of Information and Computing Sciences',
            'CTICS' => 'College of Information and Computing Sciences',
            'CTE' => 'College of Teacher Education',
            'CTED' => 'College of Teacher Education',
            'CCJE' => 'College of Criminal Justice Education',
            'CHM' => 'College of Hospitality Management',
            'CFAS' => 'College of Fisheries and Aquatic Sciences',
            'CBEA' => 'College of Business, Entrepreneurship and Accountancy',
            'CIT' => 'College of Industrial Technology',
            'CET' => 'College of Engineering and Technology',
            'AGRICULTURE' => 'College of Agriculture',
        ];
        $collegeLabel = $collegeNames[strtoupper($college)] ?? $college;
        $organizationLabel = trim((string) ($organizationName ?? ''));
        if ($college !== '' && $organizationLabel !== '') {
            foreach ([$college, $collegeLabel] as $collegePrefix) {
                if ($collegePrefix !== '' && str_starts_with(mb_strtolower($organizationLabel), mb_strtolower($collegePrefix))) {
                    $organizationLabel = trim(mb_substr($organizationLabel, mb_strlen($collegePrefix)), " \t\n\r\0\x0B-");
                    break;
                }
            }
        }
        if (strtoupper($organizationLabel) === 'SC') {
            $organizationLabel = 'Student Council';
        }
        $organizationHeading = trim(implode(' ', array_filter([$collegeLabel, $organizationLabel])));

        $dateParts = [];
        if ($request->date) {
            $dateParts[] = $request->date->format('F j, Y (l)');
        }
        if ($request->end_date && (! $request->date || $request->end_date->ne($request->date))) {
            $dateParts[] = $request->end_date->format('F j, Y (l)');
        }
        $formatTime = static function ($time): ?string {
            if (! filled($time)) {
                return null;
            }

            try {
                return \Illuminate\Support\Carbon::parse($time)->format('g:i A');
            } catch (\Throwable) {
                return (string) $time;
            }
        };
        $timeParts = array_values(array_filter([$formatTime($request->start_time), $formatTime($request->end_time)]));
        $dateTime = implode(' – ', $dateParts);
        if ($timeParts !== []) {
            $dateTime .= ($dateTime !== '' ? ', ' : '') . implode(' – ', $timeParts);
        }

        $objectives = preg_split('/\r\n|\r|\n/', trim((string) $request->objectives), -1, PREG_SPLIT_NO_EMPTY);
        $sdgOptions = config('sdg', []);
        $sdgs = collect($request->sdgs ?? [])->map(function ($number) use ($sdgOptions): ?array {
            $number = (int) $number;
            $sdg = $sdgOptions[$number] ?? null;

            return $sdg ? ['number' => $number, 'label' => $sdg['label']] : null;
        })->filter()->values();
        $programFlows = $request->programFlows->sortBy('sort_order')->values();
        $rows = [];
        $addRow = static function (string $label, $value, string $type = 'text') use (&$rows): void {
            if (filled($value)) {
                $rows[] = ['label' => $label, 'value' => $value, 'type' => $type];
            }
        };

        $addRow('Title', $request->title);
        $addRow('Date & Time', $dateTime);
        $addRow('Platform', 'Face to Face');
        $addRow('Venue', $request->venue);
        $addRow('Objectives', $objectives !== [] ? $objectives : null, 'objectives');
        $addRow('SDG Component', $sdgs->isNotEmpty() ? $sdgs : null, 'sdgs');
        $addRow('Brief Description', $request->description);
        $addRow('Program of activities', $programFlows->isNotEmpty() ? $programFlows : null, 'program');
        $addRow('Persons involved', $request->person_in_charge);

        $participants = collect([
            filled($request->target_participants) ? $request->target_participants : null,
            $request->participants_count !== null ? 'Expected: ' . $request->participants_count : null,
        ])->filter()->values();
        $addRow('Participants/recipients', $participants->isNotEmpty() ? $participants : null, 'participants');

        if ($request->estimated_budget !== null) {
            $amount = (float) $request->estimated_budget;
            $rows[] = [
                'label' => 'Budgetary requirement',
                'value' => number_format($amount, floor($amount) === $amount ? 0 : 2) . ' php',
                'source' => $request->source_of_funds,
                'type' => 'budget',
            ];
        }
        $requesterOrganization = $requester?->organization?->name ?? $requester?->org_name ?? $organizationName ?? '';
        $requesterPosition = trim(implode(', ', array_filter([$requesterOrganization, $requester?->position])));
    @endphp

    <header class="letterhead">
        <table class="letterhead-table">
            <tr>
                <td class="letterhead-logo">
                    @if(!empty($templateImages['csuLogo']))<img src="{{ $templateImages['csuLogo'] }}" alt="Cagayan State University logo">@endif
                </td>
                <td class="letterhead-copy">
                    <div class="republic">Republic of the Philippines</div>
                    <div class="university">CAGAYAN STATE UNIVERSITY</div>
                    <div class="campus">APARRI CAMPUS</div>
                    <div class="address">Maura, Aparri, Cagayan Valley, 3515</div>
                    <div class="contact">Website: www.aparri.csu.edu.ph | Email Address: csuaparri@csu.edu.ph | Phone No.: 09XX-XXX-XXXX</div>
                </td>
                <td class="letterhead-logo"></td>
            </tr>
        </table>
    </header>

    @if($organizationHeading !== '')<div class="organization-heading">{{ mb_strtoupper($organizationHeading) }}</div>@endif

    <aside class="sidebar">
        @if(!empty($templateImages['campusBuilding']))<img class="sidebar-building" src="{{ $templateImages['campusBuilding'] }}" alt="Aparri Campus">@endif
        <div class="sidebar-title">VISION</div>
        <p>CSU is a University with global stature in the arts, culture, agriculture and fisheries, the sciences as well as technological and professional fields.</p>
        <div class="sidebar-title">MISSION</div>
        <p>Cagayan State University shall produce globally competent graduates through excellent instruction, innovative and creative research, responsive public service and productive industry and community engagement.</p>
        <div class="sidebar-title">CORE VALUES</div>
        <p><strong>Competence</strong></p>
        <ul><li>Critical Thinker;</li><li>Creative Problem-Solver;</li><li>Competitive Performer: Nationally, Regionally and Globally.</li></ul>
        <p><strong>Social Responsibility</strong></p>
        <ul><li>Sensitive to Ethical Demands;</li><li>Steward of the Environment for Future Generations;</li><li>Social Justice and Economic Equity Advocate.</li></ul>
        <p><strong>Unifying Presence</strong></p>
        <ul><li>Uniting Theory and Practice;</li><li>Uniting Strata of Society;</li><li>Unifying the Nation, the ASEAN Region and the world;</li><li>Uniting the University and the community.</li></ul>
    </aside>

    <footer class="page-footer">
        @if(!empty($templateImages['footerLogos']))<img src="{{ $templateImages['footerLogos'] }}" alt="University partner and accreditation logos">@endif
    </footer>

    <main>
        <h1 class="concept-title">CONCEPT PAPER</h1>
        <table class="activity-table">
            <colgroup><col style="width: 28%"><col style="width: 72%"></colgroup>
            <tbody>
                @foreach($rows as $row)
                    <tr class="{{ in_array($row['type'], ['objectives', 'program'], true) ? 'long-row' : 'short-row' }}">
                        <th>{{ $row['label'] }}</th>
                        <td>
                            @if($row['type'] === 'objectives')
                                @foreach($row['value'] as $objective)<div class="objective-item">&#8226; {{ $objective }}</div>@endforeach
                            @elseif($row['type'] === 'sdgs')
                                @foreach($row['value'] as $sdg)<div class="sdg-item">SDG {{ $sdg['number'] }} &ndash; {{ $sdg['label'] }}</div>@endforeach
                            @elseif($row['type'] === 'program')
                                @foreach($row['value'] as $flow)
                                    <div class="flow-item">
                                        @if(filled($flow->flow))<strong>{{ $flow->flow }}</strong>@endif
                                        @php($flowDetails = collect([$flow->time, $flow->person_in_charge])->filter()->implode(' · '))
                                        @if(filled($flowDetails))<br><em class="flow-time">{{ $flowDetails }}</em>@endif
                                    </div>
                                @endforeach
                            @elseif($row['type'] === 'participants')
                                @foreach($row['value'] as $participant)<div>{{ $participant }}</div>@endforeach
                            @elseif($row['type'] === 'budget')
                                <div>{{ $row['value'] }}</div>
                                @if(filled($row['source']))<div>{{ $row['source'] }}</div>@endif
                            @else
                                {{ $row['value'] }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="signatories">
            <tr>
                <td>
                    <div class="signatory-heading">Prepared by:</div>
                    <div class="signature-line"></div>
                    <div class="prepared-name">{{ $requester?->name ? mb_strtoupper($requester->name) : '' }}</div>
                    @if($requesterPosition !== '')<div class="prepared-details">{{ $requesterPosition }}</div>@endif
                </td>
                <td>
                    <div class="signatory-heading">Noted:</div>
                    <div class="adviser-columns">
                        <div class="adviser"><div class="signature-line"></div><div class="adviser-label">Adviser</div></div>
                        <div class="adviser"><div class="signature-line"></div><div class="adviser-label">Co-Adviser</div></div>
                    </div>
                </td>
            </tr>
        </table>
    </main>
</body>
</html>