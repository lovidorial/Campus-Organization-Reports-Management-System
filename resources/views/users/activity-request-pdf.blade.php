<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Concept Paper</title>
    <style>
        @page { size: legal portrait; margin: 2.16in 0.55in 0.78in 1.55in; }
        * { box-sizing: border-box; }
        body { color: #111; font-family: "DejaVu Sans", sans-serif; font-size: 8.5pt; line-height: 1.32; }
        table { border-collapse: collapse; width: 100%; }
        .letterhead { position: fixed; top: -1.96in; left: -1.02in; right: -0.48in; height: 1.81in; border-bottom: 1px solid #111; }
        .letterhead-table { height: 1.39in; table-layout: fixed; }
        .letterhead-logo { width: 0.86in; text-align: left; vertical-align: middle; }
        .letterhead-logo img { width: 0.76in; height: 0.76in; object-fit: contain; }
        .letterhead-copy { text-align: center; vertical-align: middle; font-family: "DejaVu Serif", serif; }
        .republic { font-size: 8pt; }
        .university { margin-top: 1px; white-space: nowrap; font-size: 14pt; font-weight: bold; }
        .campus { margin-top: 1px; font-size: 9pt; font-weight: normal; }
        .address { margin-top: 1px; font-size: 8pt; font-style: italic; }
        .contact { margin-top: 3px; white-space: nowrap; font-family: "DejaVu Sans", sans-serif; font-size: 4.2pt; font-style: italic; }
        .contact a { color: #1456a0; text-decoration: underline; }
        .organization-heading { margin: 1px 0 0; padding: 0 8px; text-align: center; font-family: "DejaVu Serif", serif; font-size: 8.5pt; font-weight: bold; }
        .sidebar { position: fixed; top: 0.04in; bottom: 0.06in; left: -1.31in; width: 1.12in; overflow: hidden; font-size: 5.2pt; line-height: 1.18; text-align: justify; }
        .sidebar-building { display: block; width: 100%; height: 0.72in; margin: 0 auto 5px; object-fit: contain; }
        .sidebar-title { margin: 5px 0 2px; font-size: 6pt; font-weight: bold; text-align: left; }
        .sidebar p { margin: 0 0 3px; }
        .sidebar ul { margin: 1px 0 3px; padding-left: 9px; }
        .sidebar li { margin-bottom: 2px; }
        .page-footer { position: fixed; bottom: -0.62in; left: 0; right: 0; height: 0.51in; border-top: 1px solid #111; padding-top: 3px; text-align: center; }
        .page-footer img { width: 100%; height: 0.43in; object-fit: fill; }
        .concept-title { margin: 0 0 12px; text-align: center; font-family: "DejaVu Serif", serif; font-size: 13pt; font-weight: bold; }
        .activity-table { table-layout: fixed; border: 1px solid #111; font-size: 8pt; }
        .activity-table th, .activity-table td { border: 1px solid #111; padding: 5px 6px; vertical-align: top; }
        .activity-table th { width: 28%; text-align: left; font-weight: bold; }
        .activity-table td { text-align: justify; }
        .activity-table .emphasized-row td { font-weight: bold; text-align: left; }
        .short-row { page-break-inside: avoid; }
        .long-row { page-break-inside: auto; }
        .objectives-list { margin: 0; padding-left: 18px; text-align: justify; }
        .objectives-list li { padding-left: 2px; margin-bottom: 2px; }
        .objectives-list li:last-child { margin-bottom: 0; }
        .sdg-item { margin-bottom: 6px; text-align: justify; }
        .sdg-item:last-child { margin-bottom: 0; }
        .sdg-description { margin: 2px 0 0; font-weight: normal; text-align: justify; }
        .flow-list { text-align: center; }
        .flow-item { margin-bottom: 9px; page-break-inside: avoid; }
        .flow-item:last-child { margin-bottom: 0; }
        .flow-person { font-style: italic; }
        .flow-time { font-style: normal; }
        .justified-value { text-align: justify; }
        .budget-amount { font-weight: bold; }
        .signatories { width: 100%; margin-top: 17px; table-layout: fixed; page-break-inside: avoid; }
        .signatories td { width: 50%; padding: 0 12px; vertical-align: top; }
        .signatories td:first-child { padding-left: 0; }
        .signatories td:last-child { padding-right: 0; }
        .signatory-heading { margin-bottom: 15px; font-weight: bold; }
        .signature-line { height: 25px; border-bottom: 1px solid #111; }
        .prepared-name { min-height: 13px; margin-top: 3px; font-weight: bold; text-transform: uppercase; }
        .prepared-details, .adviser-label { margin-top: 2px; font-size: 7pt; }
        .adviser-columns { display: table; width: 100%; table-layout: fixed; }
        .adviser { display: table-cell; width: 50%; padding-right: 8px; text-align: left; }
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
        $organizationLabel = trim((string) ($organizationName ?? ''));

        $collegeCodes = array_keys($collegeNames);
        usort($collegeCodes, static fn ($left, $right) => mb_strlen($right) <=> mb_strlen($left));
        $organizationCode = null;
        foreach ($collegeCodes as $code) {
            if (preg_match('/^' . preg_quote($code, '/') . '(?:[\s-]+|$)/i', $organizationLabel, $matches)) {
                $organizationCode = strtoupper($code);
                $organizationLabel = trim(mb_substr($organizationLabel, mb_strlen($matches[0])), " \t\n\r\0\x0B-,:;");
                break;
            }
        }

        $collegeCode = strtoupper($college);
        if (! isset($collegeNames[$collegeCode]) && $organizationCode) {
            $collegeCode = $organizationCode;
        }
        $collegeLabel = $collegeNames[$collegeCode] ?? $college;

        if ($collegeLabel !== '' && str_starts_with(mb_strtolower($organizationLabel), mb_strtolower($collegeLabel))) {
            $organizationLabel = trim(mb_substr($organizationLabel, mb_strlen($collegeLabel)), " \t\n\r\0\x0B-,:;");
        }
        if (preg_match('/^(?:SC|STUDENT\s+COUNCIL)$/i', $organizationLabel)) {
            $organizationLabel = 'Student Council';
        } elseif (preg_match('/(?:^|[\s-])SC$/i', $organizationLabel)) {
            $organizationLabel = preg_replace('/(?:^|[\s-])SC$/i', ' Student Council', $organizationLabel);
            $organizationLabel = trim($organizationLabel);
        }
        $organizationHeading = trim(implode(' ', array_filter([$collegeLabel, $organizationLabel])));
        $shortOrganizationCode = $organizationCode ?: (isset($collegeNames[$collegeCode]) ? $collegeCode : null);
        $shortOrganizationLabel = $shortOrganizationCode
            ? $shortOrganizationCode . '-SC'
            : trim((string) ($organizationName ?? $requester?->org_name ?? ''));

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
        $sdgs = collect($request->sdgs ?? [])->map(function ($entry) use ($sdgOptions): ?array {
            $number = (int) data_get($entry, 'number', $entry);
            $sdg = $sdgOptions[$number] ?? null;

            return $sdg ? [
                'number' => $number,
                'label' => $sdg['label'],
                'description' => data_get($entry, 'description'),
            ] : null;
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
                'source' => $request->source_of_funds ?? '—',
                'type' => 'budget',
            ];
        }
        $requesterOrganization = $requester?->organization?->name ?? $requester?->org_name ?? $organizationName ?? '';
        $requesterPosition = trim(implode(', ', array_filter([$requesterOrganization, $requester?->position])));
    @endphp

    <header class="letterhead">
        <table class="letterhead-table">
            <colgroup><col style="width: 0.86in"><col><col style="width: 0.86in"></colgroup>
            <tr>
                <td class="letterhead-logo">
                    @if(!empty($templateImages['csuLogo']))<img src="{{ $templateImages['csuLogo'] }}" alt="Cagayan State University logo">@endif
                </td>
                <td class="letterhead-copy">
                    <div class="republic"><strong>R</strong>epublic <strong>o</strong>f <strong>t</strong>he <strong>P</strong>hilippines</div>
                    <div class="university">CAGAYAN STATE UNIVERSITY</div>
                    <div class="campus">APARRI CAMPUS</div>
                    <div class="address">Maura, Aparri, Cagayan Valley, 3515</div>
                    <div class="contact">Website: <a href="https://l.facebook.com/l.php?u=http%3A%2F%2Fwww.aparri.csu.edu.ph%2F%3Ffbclid%3DIwZXh0bgNhZW0CMTAAcGRvZgVicmlkETFweVF0bzNITFNLM25VSU5Ec3J0YwZhcHBfaWQQMjIyMDM5MTc4ODIwMDg5MgABHs2mhxUIL3LYVl3LmRto5Op7mGPLrFwg4IXFxoCqAv1amCVi4g7eCLlOPH-G_aem_wMKk0O5oVJ38QArVJBWo8g&amp;h=AUAOms_97cYwAzrNTOPsE0PeWorNgRWMR_88wb6pgcDZZuARL4UykIAWwC9v0w3VIvlmxITYxD7o8k6hyeNPxNdOVIQQ-KK-kD99PIvs1kMYJK7UxvmAAEzDEcAzitzc-8Az8g">www.aparri.csu.edu.ph</a> | Email Address: <a href="mailto:csuaparri@csu.edu.ph">csuaparri@csu.edu.ph</a> | Phone No.: 09XX-XXX-XXXX</div>
                </td>
                <td class="letterhead-logo"></td>
            </tr>
        </table>
        @if($organizationHeading !== '')<div class="organization-heading">{{ mb_strtoupper($organizationHeading) }}</div>@endif
    </header>

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
                    <tr class="{{ in_array($row['type'], ['objectives', 'sdgs', 'program'], true) ? 'long-row' : 'short-row' }}{{ in_array($row['label'], ['Title', 'Date & Time', 'Platform', 'Venue'], true) ? ' emphasized-row' : '' }}">
                        <th>{{ $row['label'] }}</th>
                        <td>
                            @if($row['type'] === 'objectives')
                                <ul class="objectives-list">
                                    @foreach($row['value'] as $objective)<li>{{ $objective }}</li>@endforeach
                                </ul>
                            @elseif($row['type'] === 'sdgs')
                                @foreach($row['value'] as $sdg)
                                    <div class="sdg-item">
                                        <strong>SDG {{ $sdg['number'] }} &ndash; {{ $sdg['label'] }}</strong>
                                        @if(filled($sdg['description']))<p class="sdg-description">{{ $sdg['description'] }}</p>@endif
                                    </div>
                                @endforeach
                            @elseif($row['type'] === 'program')
                                <div class="flow-list">
                                    @foreach($row['value'] as $flow)
                                        <div class="flow-item">
                                            @if(filled($flow->flow))<strong>{{ $flow->flow }}</strong>@endif
                                            @if(filled($flow->person_in_charge))<br><em class="flow-person">{{ $flow->person_in_charge }}</em>@endif
                                            @if(filled($flow->time))<br><span class="flow-time">{{ $formatTime($flow->time) }}</span>@endif
                                        </div>
                                    @endforeach
                                </div>
                            @elseif($row['type'] === 'participants')
                                @foreach($row['value'] as $participant)<div class="justified-value">{{ $participant }}</div>@endforeach
                            @elseif($row['type'] === 'budget')
                                <div class="budget-amount">{{ $row['value'] }}</div>
                                @if(filled($row['source']))<div>{{ $row['source'] }}</div>@endif
                            @else
                                <div class="justified-value">{{ $row['value'] }}</div>
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
                        <div class="adviser"><div class="signature-line"></div><div class="adviser-label">Adviser, {{ $shortOrganizationLabel }}</div></div>
                        <div class="adviser"><div class="signature-line"></div><div class="adviser-label">Co-Adviser, {{ $shortOrganizationLabel }}</div></div>
                    </div>
                </td>
            </tr>
        </table>
    </main>
</body>
</html>