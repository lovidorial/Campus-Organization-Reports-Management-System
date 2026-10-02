<?php

namespace App\Http\Controllers;

use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\GpoaImportParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;

class GpoaController extends Controller
{
    public function index()
    {
        $gpoas = Gpoa::where('user_id', auth()->id())
            ->withCount('activities')
            ->latest()
            ->paginate(10);

        $monitoringCounts = [
            'Pending' => 0,
            'Ongoing' => 0,
            'Completed' => 0,
        ];

        $activities = GpoaActivity::query()
            ->whereHas('gpoa', fn ($query) => $query->where('user_id', auth()->id()))
            ->withMonitoringData()
            ->get();

        foreach ($activities as $activity) {
            $monitoringCounts[$activity->monitoringStatus()['status']]++;
        }

        $completionPercent = $activities->isEmpty()
            ? 0
            : (int) round(($monitoringCounts['Completed'] / $activities->count()) * 100);

        return view('gpoa.index', compact('gpoas', 'monitoringCounts', 'completionPercent'));
    }

    public function create()
    {
        $user = auth()->user();

        $detectedCollege = $this->detectCollegeFromOrganization($user);

        return view('gpoa.create', compact('detectedCollege'));
    }

    public function importPreview(Request $request, GpoaImportParser $parser)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:docx,xlsx|max:10240',
            'school_year' => 'required|string|max:20',
        ], [
            'file.required' => 'Choose a Word (.docx) or Excel (.xlsx) file to import.',
            'file.mimes' => 'Only Word (.docx) and Excel (.xlsx) files can be imported.',
            'file.max' => 'The import file must be 10 MB or smaller.',
            'school_year.required' => 'A school year is required to infer dates without a year.',
        ]);

        try {
            return response()->json($parser->parse($validated['file'], $validated['school_year']));
        } catch (Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage() ?: 'This file could not be read. Choose a valid DOCX or XLSX file.',
            ], 422);
        }
    }

    private function detectCollegeFromOrganization($user): ?string
    {
        if (!empty($user->college)) {
            return strtoupper($user->college);
        }

        if (!empty($user->organization?->college)) {
            return strtoupper($user->organization->college);
        }

        $orgName = strtoupper(trim($user->org_name ?? $user->organization?->name ?? ''));
        $orgName = preg_replace('/[^A-Z0-9\- ]+/', '', $orgName);

        $collegeMap = [
            // CICS
            'ITOUCH PUBLICATION' => 'CICS',
            'ITOUCH' => 'CICS',
            'CICS SC' => 'CICS',
            'CICS-SC' => 'CICS',
            'COLLEGE OF INFORMATION AND COMPUTING SCIENCES' => 'CICS',

            // CTE
            'THE MENTOR' => 'CTE',
            'THE ACADEMIA' => 'CTE',
            'CTE SC' => 'CTE',
            'CTE-SC' => 'CTE',
            'CCTE SC' => 'CTE',
            'CCTE-SC' => 'CTE',
            'PASSED' => 'CTE',
            'COLLEGE OF TEACHER EDUCATION' => 'CTE',

            // CFAS
            'THE WATERWORLD' => 'CFAS',
            'THE AQUARIUS' => 'CFAS',
            'CFAS SC' => 'CFAS',
            'CFAS-SC' => 'CFAS',
            'COLLEGE OF FISHERIES AND AQUATIC SCIENCES' => 'CFAS',

            // CHM
            'THE BANQUET' => 'CHM',
            'CHM SC' => 'CHM',
            'CHM-SC' => 'CHM',
            'SAB-CHM' => 'CHM',
            'SAB CHM' => 'CHM',
            'HMS' => 'CHM',
            'COLLEGE OF HOSPITALITY MANAGEMENT' => 'CHM',

            // CCJE
            'THE CALIBER' => 'CCJE',
            'CJS SC' => 'CCJE',
            'CJS-SC' => 'CCJE',
            'COLLEGE OF CRIMINAL JUSTICE EDUCATION' => 'CCJE',

            // CBEA
            'THE LEDGER' => 'CBEA',
            'COLLEGE OF BUSINESS, ENTREPRENEURSHIP AND ACCOUNTANCY' => 'CBEA',

            // CET
            'THE CONDUIT' => 'CET',
            'COLLEGE OF ENGINEERING AND TECHNOLOGY' => 'CET',

            // Agriculture
            'FAME' => 'AGRICULTURE',
            'FAS SCO' => 'AGRICULTURE',
            'FAS-SCO' => 'AGRICULTURE',
            'ASIDETS' => 'AGRICULTURE',
            'COLLEGE OF AGRICULTURE' => 'AGRICULTURE',
        ];

        foreach ($collegeMap as $needle => $college) {
            if (str_contains($orgName, $needle)) {
                return $college;
            }
        }

        return null;
    }

    public function store(Request $request)
    {
        $this->validatePlannedActivityEntries($request);

        $user = $request->user();
        $validated = $request->validate([
            'colleges'            => 'required|string|max:100',
            'term'                => 'required|string|max:50',
            'school_year'         => 'required|string|max:20',
            'prepared_by'         => 'required|string|max:255',
            'document_path'       => 'required|file|mimes:pdf|max:20480',
            'planned_activities'  => 'required|array|min:1|max:' . config('gpoa.max_planned_activities'),
            'approved_confirmation' => 'required|accepted',
            'verify'              => 'required|accepted',
        ], [
            'planned_activities.max' => 'A GPOA may contain no more than ' . config('gpoa.max_planned_activities') . ' planned activities.',
        ]);

        $documentPath = $request->file('document_path')->store('uploads/gpoa', 'public');

        $gpoa = Gpoa::create([
            'user_id'       => auth()->id(),
            'term'          => $validated['term'],
            'school_year'   => $validated['school_year'],
            'college'       => $validated['colleges'],
            'document_path' => $documentPath,
            'prepared_by'   => $validated['prepared_by'],
            'status'        => 'approved',
            'approved_at'   => now(),
        ]);

        $this->syncPlannedActivities($gpoa, $request->input('planned_activities', []));

        User::where('role', 'admin')->each(function (User $admin) use ($gpoa, $user) {
            UserNotification::create([
                'user_id' => $admin->id,
                'type' => 'gpoa_submitted',
                'title' => 'New GPOA Submission',
                'message' => ($user->org_name ?? $user->name) . " submitted a GPOA for {$gpoa->term} / SY {$gpoa->school_year}.",
            ]);
        });

        return redirect()->route('dashboard')
            ->with('success', 'GPOA submitted successfully.');
    }

    public function edit(Gpoa $gpoa)
    {
        $this->authorize('update', $gpoa);

        $gpoa->load('activities');

        return view('gpoa.edit', compact('gpoa', 'workflow'));
    }

    public function update(Request $request, Gpoa $gpoa)
    {
        $this->authorize('update', $gpoa);

        $this->validatePlannedActivityEntries($request);

        $validated = $request->validate([
            'colleges' => 'required|string|max:100',
            'prepared_by' => 'required|string|max:255',
            'document_path' => ($gpoa->document_path ? 'nullable' : 'required') . '|file|mimes:pdf|max:20480',
            'planned_activities'  => 'required|array|min:1|max:' . config('gpoa.max_planned_activities'),
        ], [
            'planned_activities.max' => 'A GPOA may contain no more than ' . config('gpoa.max_planned_activities') . ' planned activities.',
        ]);

        if ($request->hasFile('document_path')) {
            if ($gpoa->document_path) {
                Storage::disk('public')->delete($gpoa->document_path);
            }
            $gpoa->document_path = $request->file('document_path')->store('uploads/gpoa', 'public');
        }

        $gpoa->update([
            'college' => $validated['colleges'],
            'prepared_by' => $validated['prepared_by'],
            'document_path' => $gpoa->document_path,
        ]);

        $this->syncPlannedActivities($gpoa, $request->input('planned_activities', []));

        return redirect()->route('dashboard')
            ->with('success', 'GPOA updated successfully.');
    }

    public function show(Gpoa $gpoa)
    {
        $this->authorize('view', $gpoa);

        $gpoa->load('activities.activityRequest.report');

        return view('gpoa.show', compact('gpoa'));
    }

    private function syncPlannedActivities(Gpoa $gpoa, array $plannedActivities): void
    {
        $existingActivities = $gpoa->activities()->get()->keyBy('id');
        $retainedIds = [];

        foreach ($plannedActivities as $activityData) {
            $normalizedActivity = $this->normalizePlannedActivityData($activityData);

            if ($this->isBlankPlannedActivity($normalizedActivity)) {
                continue;
            }

            $existingActivity = isset($activityData['id'])
                ? $existingActivities->get((int) $activityData['id'])
                : null;

            if ($existingActivity) {
                $existingActivity->update($normalizedActivity);
                $retainedIds[] = $existingActivity->id;
                continue;
            }

            $retainedIds[] = $gpoa->activities()->create($normalizedActivity)->id;
        }

        $gpoa->activities()->whereNotIn('id', $retainedIds)->delete();
    }

    private function normalizePlannedActivityData(array $activityData): array
    {
        $sdgs = $activityData['sdgs'] ?? [];

        if (is_string($sdgs)) {
            $sdgs = array_values(array_filter(array_map('trim', explode(',', $sdgs)), fn ($value) => $value !== ''));
        }

        $sdgs = array_values(array_unique(array_map('intval', array_filter((array) $sdgs, 'is_numeric'))));

        $timeFrame = trim((string) ($activityData['time_frame'] ?? ''));
        $dateValue = trim((string) ($activityData['date'] ?? ''));
        $endDateValue = trim((string) ($activityData['end_date'] ?? ''));

        if ($timeFrame === 'month_only' && $dateValue !== '' && preg_match('/^\d{4}-\d{2}$/', $dateValue)) {
            $dateValue = date('Y-m-01', strtotime($dateValue));
        }

        if ($timeFrame !== 'date_range') {
            $endDateValue = '';
        }

        return [
            'title' => trim((string) ($activityData['title'] ?? '')),
            'time_frame' => $timeFrame,
            'date' => $dateValue !== '' ? $dateValue : null,
            'end_date' => $endDateValue !== '' ? $endDateValue : null,
            'start_time' => trim((string) ($activityData['start_time'] ?? '')) !== '' ? $activityData['start_time'] : null,
            'end_time' => trim((string) ($activityData['end_time'] ?? '')) !== '' ? $activityData['end_time'] : null,
            'date_is_month_only' => $timeFrame === 'month_only' || ! empty($activityData['date_is_month_only']),
            'venue' => trim((string) ($activityData['venue'] ?? '')) !== '' ? trim((string) $activityData['venue']) : null,
            'category' => trim((string) ($activityData['category'] ?? '')),
            'sdgs' => $sdgs,
        ];
    }

    private function isBlankPlannedActivity(array $activityData): bool
    {
        return empty($activityData['title'])
            && empty($activityData['time_frame'])
            && empty($activityData['date'])
            && empty($activityData['end_date'])
            && empty($activityData['venue'])
            && empty($activityData['category'])
            && empty($activityData['sdgs']);
    }

    private function validatePlannedActivityEntries(Request $request): void
    {
        $validator = Validator::make($request->all(), [
            'planned_activities' => 'required|array|min:1|max:' . config('gpoa.max_planned_activities'),
        ], [
            'planned_activities.max' => 'A GPOA may contain no more than ' . config('gpoa.max_planned_activities') . ' planned activities.',
        ]);

        $validator->after(function ($validator) use ($request) {
            foreach ((array) $request->input('planned_activities', []) as $index => $activity) {
                $title = trim((string) ($activity['title'] ?? ''));
                $timeFrame = trim((string) ($activity['time_frame'] ?? ''));
                $date = trim((string) ($activity['date'] ?? ''));
                $endDate = trim((string) ($activity['end_date'] ?? ''));
                $venue = trim((string) ($activity['venue'] ?? ''));
                $category = trim((string) ($activity['category'] ?? ''));
                $hasAnyValue = $title !== ''
                    || $timeFrame !== ''
                    || $date !== ''
                    || $endDate !== ''
                    || $venue !== ''
                    || $category !== ''
                    || ! empty($activity['sdgs'] ?? []);

                if (! $hasAnyValue) {
                    continue;
                }

                if ($title === '') {
                    $validator->errors()->add("planned_activities.{$index}.title", 'Activity ' . ($index + 1) . ' title is required.');
                }

                if ($timeFrame === '') {
                    $validator->errors()->add("planned_activities.{$index}.time_frame", 'Activity ' . ($index + 1) . ' time frame is required.');
                }

                if (! in_array($timeFrame, ['exact_date', 'date_range', 'month_only'], true)) {
                    continue;
                }

                if ($timeFrame === 'exact_date' && $date === '') {
                    $validator->errors()->add("planned_activities.{$index}.date", 'Activity ' . ($index + 1) . ' exact date is required.');
                }

                if ($timeFrame === 'date_range') {
                    if ($date === '') {
                        $validator->errors()->add("planned_activities.{$index}.date", 'Activity ' . ($index + 1) . ' start date is required for a date range.');
                    }

                    if ($endDate === '') {
                        $validator->errors()->add("planned_activities.{$index}.end_date", 'Activity ' . ($index + 1) . ' end date is required for a date range.');
                    }

                    if ($date !== '' && $endDate !== '' && $endDate < $date) {
                        $validator->errors()->add("planned_activities.{$index}.end_date", 'Activity ' . ($index + 1) . ' end date cannot be before the start date.');
                    }
                }

                if ($timeFrame === 'month_only' && $date === '') {
                    $validator->errors()->add("planned_activities.{$index}.date", 'Activity ' . ($index + 1) . ' month is required for a month-only activity.');
                }
            }
        });

        $validator->validate();
    }
}
