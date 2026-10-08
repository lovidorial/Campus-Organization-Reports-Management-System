<?php

namespace App\Http\Controllers;

use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\ActivityRequest;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\GpoaImportParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class GpoaController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $gpoas = Gpoa::where('user_id', auth()->id())
            ->withCount('activities')
            ->latest()
            ->paginate(10);

        $monitoringCounts = [
            'Pending' => 0,
            'Ongoing' => 0,
            'Completed' => 0,
            'Archived' => 0,
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

        $submissionStatus = $user->gpoaSubmissionStatus();
        $duplicateCurrentGpoa = $user->term && $user->school_year
            ? $user->gpoas()->where('term', $user->term)->where('school_year', $user->school_year)->exists()
            : false;
        $canSubmitGpoa = $user->isAdmin() || ($submissionStatus['allowed'] && ! $duplicateCurrentGpoa);
        $submissionBlockMessage = $this->submissionBlockMessage($user, $submissionStatus);

        return view('gpoa.index', compact('gpoas', 'monitoringCounts', 'completionPercent', 'canSubmitGpoa', 'submissionBlockMessage'));
    }

    public function create()
    {
        $user = auth()->user();
        $blockMessage = $this->submissionBlockMessage($user);
        if ($blockMessage && ! $user->isAdmin()) {
            return redirect()->route('gpoa.index')->with('error', $blockMessage);
        }

        $detectedCollege = $this->detectCollegeFromOrganization($user);

        return view('gpoa.create', compact('detectedCollege'));
    }

    public function importPreview(Request $request, GpoaImportParser $parser)
    {
        $user = $request->user();
        $blockMessage = $this->submissionBlockMessage($user);
        if ($blockMessage && ! $user->isAdmin()) {
            return redirect()->route('gpoa.index')->with('error', $blockMessage);
        }

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
        $user = $request->user();
        if (! $user->isAdmin()) {
            $submissionStatus = $user->gpoaSubmissionStatus();
            if (! $submissionStatus['allowed']) {
                throw ValidationException::withMessages([
                    'gpoa' => $this->submissionBlockMessage($user, $submissionStatus),
                ]);
            }
        }

        foreach (['term', 'school_year'] as $lockedField) {
            if (filled($user->{$lockedField})) {
                $request->merge([$lockedField => $user->{$lockedField}]);
            }
        }

        $this->validatePlannedActivityEntries($request);

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

        if (Gpoa::where('user_id', $user->id)
            ->where('term', $validated['term'])
            ->where('school_year', $validated['school_year'])
            ->exists()) {
            throw ValidationException::withMessages([
                'term' => "A GPOA for {$validated['term']} / SY {$validated['school_year']} has already been submitted.",
            ]);
        }

        $documentPath = $request->file('document_path')->store('uploads/gpoa', 'private');

        try {
            $gpoa = DB::transaction(function () use ($user, $validated, $documentPath, $request): Gpoa {
                $gpoa = Gpoa::create([
                    'user_id'       => $user->id,
                    'term'          => $validated['term'],
                    'school_year'   => $validated['school_year'],
                    'college'       => $validated['colleges'],
                    'document_path' => $documentPath,
                    'prepared_by'   => $validated['prepared_by'],
                    'status'        => 'approved',
                    'approved_at'   => now(),
                ]);

                $this->syncPlannedActivities($gpoa, $request->input('planned_activities', []));

                return $gpoa;
            });
        } catch (Throwable) {
            Storage::disk('private')->delete($documentPath);

            throw ValidationException::withMessages([
                'term' => "A GPOA for {$validated['term']} / SY {$validated['school_year']} has already been submitted.",
            ]);
        }

        User::where('role', 'admin')->each(function (User $admin) use ($gpoa, $user) {
            UserNotification::create([
                'user_id' => $admin->id,
                'type' => 'gpoa_submitted',
                'title' => 'New GPOA Submission',
                'message' => ($user->org_name ?? $user->name) . " submitted a GPOA for {$gpoa->term} / SY {$gpoa->school_year}.",
            ]);
        });

        return redirect()->route('dashboard')
            ->with('success', 'GPOA submitted successfully.')
            ->with('clearGpoaDraftKey', 'gpoa-draft:'.$user->id.':'.$validated['school_year']);
    }

    public function edit(Gpoa $gpoa)
    {
        $this->authorize('update', $gpoa);

        $gpoa->load('activities');
        $plannedActivitySeed = $gpoa->activities->map(fn (GpoaActivity $activity) => [
            'id' => $activity->id,
            'title' => $activity->title,
            'time_frame' => $activity->time_frame,
            'date' => $activity->date_is_month_only ? $activity->date?->format('Y-m') : $activity->date?->toDateString(),
            'end_date' => $activity->end_date?->toDateString(),
            'start_time' => $activity->start_time,
            'end_time' => $activity->end_time,
            'venue' => $activity->venue,
            'category' => $activity->category,
            'sdgs' => $activity->sdgs ?? [],
            'objectives' => $activity->objectives,
            'expected_outcome' => $activity->expected_outcome,
            'plan_key_strategy' => $activity->plan_key_strategy,
            'target_participants' => $activity->target_participants,
            'person_in_charge' => $activity->person_in_charge,
            'facilities_materials' => $activity->facilities_materials,
            'estimated_budget' => $activity->estimated_budget,
            'source_of_funds' => $activity->source_of_funds,
        ])->values();

        return view('gpoa.edit', compact('gpoa', 'plannedActivitySeed'));
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
                Storage::disk('private')->delete($gpoa->document_path);
                Storage::disk('public')->delete($gpoa->document_path);
            }
            $gpoa->document_path = $request->file('document_path')->store('uploads/gpoa', 'private');
        }

        $gpoa->update([
            'college' => $validated['colleges'],
            'prepared_by' => $validated['prepared_by'],
            'document_path' => $gpoa->document_path,
        ]);

        $this->syncPlannedActivities($gpoa, $request->input('planned_activities', []));

        return redirect()->route('dashboard')
            ->with('success', 'GPOA updated successfully.')
            ->with('clearGpoaDraftKey', 'gpoa-draft:'.auth()->id().':gpoa:'.$gpoa->id.':'.$gpoa->school_year);
    }

    public function show(Gpoa $gpoa)
    {
        $this->authorize('view', $gpoa);

        $gpoa->load('activities.activityRequest.report');

        return view('gpoa.show', compact('gpoa'));
    }

    private function submissionBlockMessage(User $user, ?array $submissionStatus = null): ?string
    {
        $submissionStatus ??= $user->gpoaSubmissionStatus();
        if (! $submissionStatus['allowed']) {
            $blockingGpoa = $submissionStatus['blockingGpoa'];
            $termAndYear = $blockingGpoa->term . ' SY ' . $blockingGpoa->school_year;

            return "Finish all activities in {$termAndYear} first. {$submissionStatus['unfinishedCount']} remaining.";
        }

        if ($user->term && $user->school_year && $user->gpoas()
            ->where('term', $user->term)
            ->where('school_year', $user->school_year)
            ->exists()) {
            return "A GPOA for {$user->term} / SY {$user->school_year} has already been submitted.";
        }

        return null;
    }

    public function document(Gpoa $gpoa)
    {
        $this->authorize('view', $gpoa);
        abort_unless($gpoa->document_path, 404, 'GPOA document not found.');

        foreach (['private', 'public'] as $diskName) {
            $disk = Storage::disk($diskName);
            if ($disk->exists($gpoa->document_path)) {
                return $disk->response($gpoa->document_path, basename($gpoa->document_path), [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline',
                ]);
            }
        }

        abort(404, 'GPOA document not found.');
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

        $removedActivities = $gpoa->activities()
            ->whereNotIn('id', $retainedIds)
            ->get();

        foreach ($removedActivities as $activity) {
            $hasSubmittedRequest = $activity->activityRequest()->exists()
                || ActivityRequest::query()->where('gpoa_activity_id', $activity->id)->exists();

            if (! $hasSubmittedRequest) {
                $activity->delete();
            }
        }
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
            'start_time' => trim((string) ($activityData['start_time'] ?? '')) !== '' ? trim((string) $activityData['start_time']) : null,
            'end_time' => trim((string) ($activityData['end_time'] ?? '')) !== '' ? trim((string) $activityData['end_time']) : null,
            'date_is_month_only' => $timeFrame === 'month_only' || ! empty($activityData['date_is_month_only']),
            'venue' => trim((string) ($activityData['venue'] ?? '')) !== '' ? trim((string) $activityData['venue']) : null,
            'category' => trim((string) ($activityData['category'] ?? '')) !== ''
                ? trim((string) $activityData['category'])
                : null,
            'sdgs' => $sdgs,
            'objectives' => trim((string) ($activityData['objectives'] ?? '')),
            'expected_outcome' => trim((string) ($activityData['expected_outcome'] ?? '')),
            'plan_key_strategy' => trim((string) ($activityData['plan_key_strategy'] ?? '')),
            'target_participants' => trim((string) ($activityData['target_participants'] ?? '')),
            'person_in_charge' => trim((string) ($activityData['person_in_charge'] ?? '')),
            'facilities_materials' => trim((string) ($activityData['facilities_materials'] ?? '')),
            'estimated_budget' => trim((string) ($activityData['estimated_budget'] ?? '')) !== ''
                ? (float) $activityData['estimated_budget']
                : null,
            'source_of_funds' => trim((string) ($activityData['source_of_funds'] ?? '')) !== ''
                ? trim((string) $activityData['source_of_funds'])
                : null,
        ];
    }

    private function isBlankPlannedActivity(array $activityData): bool
    {
        foreach ([
            'title', 'time_frame', 'date', 'end_date', 'venue', 'category', 'objectives',
            'expected_outcome', 'plan_key_strategy', 'target_participants', 'person_in_charge',
            'facilities_materials', 'source_of_funds',
        ] as $field) {
            if (trim((string) ($activityData[$field] ?? '')) !== '') {
                return false;
            }
        }

        return empty($activityData['sdgs'])
            && (($activityData['estimated_budget'] ?? null) === null || $activityData['estimated_budget'] === '');
    }

    private function validatePlannedActivityEntries(Request $request): void
    {
        $validator = Validator::make($request->all(), [
            'planned_activities' => 'required|array|min:1|max:' . config('gpoa.max_planned_activities'),
            'planned_activities.*.category' => [
                'nullable',
                'string',
                'max:255',
                Rule::in(array_values(array_unique(array_merge(
                    array_keys((array) config('gpoa_activity_limits', [])),
                    ['Religious Activity', 'Socio-Cultural and Sports', 'Makakalikasan (Clean and Green)', 'Extension Services Conducted']
                )))),
            ],
            'planned_activities.*.source_of_funds' => 'nullable|string|max:255',
        ], [
            'planned_activities.max' => 'A GPOA may contain no more than ' . config('gpoa.max_planned_activities') . ' planned activities.',
        ]);

        $validator->after(function ($validator) use ($request) {
            foreach ((array) $request->input('planned_activities', []) as $index => $activity) {
                if (! is_array($activity)) {
                    continue;
                }

                $rawBudget = trim((string) ($activity['estimated_budget'] ?? ''));
                $activity = $this->normalizePlannedActivityData($activity);
                $title = trim((string) ($activity['title'] ?? ''));
                $timeFrame = trim((string) ($activity['time_frame'] ?? ''));
                $date = trim((string) ($activity['date'] ?? ''));
                $endDate = trim((string) ($activity['end_date'] ?? ''));
                $venue = trim((string) ($activity['venue'] ?? ''));
                if ($this->isBlankPlannedActivity($activity)) {
                    continue;
                }

                $activityNumber = $index + 1;
                if ($title === '') {
                    $validator->errors()->add("planned_activities.{$index}.title", "Activity {$activityNumber}: Title is required.");
                }

                if ($timeFrame === '') {
                    $validator->errors()->add("planned_activities.{$index}.time_frame", "Activity {$activityNumber}: Date or time frame is required.");
                } elseif (! in_array($timeFrame, ['exact_date', 'date_range', 'month_only'], true)) {
                    $validator->errors()->add("planned_activities.{$index}.time_frame", "Activity {$activityNumber}: Select a valid date or time frame.");
                }

                if ($timeFrame === 'exact_date' && $date === '') {
                    $validator->errors()->add("planned_activities.{$index}.date", "Activity {$activityNumber}: Date is required.");
                }

                if ($timeFrame === 'date_range') {
                    if ($date === '') {
                        $validator->errors()->add("planned_activities.{$index}.date", "Activity {$activityNumber}: Start date is required.");
                    }

                    if ($endDate === '') {
                        $validator->errors()->add("planned_activities.{$index}.end_date", "Activity " . ($index + 1) . ' end date is required for a date range.');
                    }

                    if ($date !== '' && $endDate !== '' && $endDate < $date) {
                        $validator->errors()->add("planned_activities.{$index}.end_date", 'Activity ' . ($index + 1) . ' end date cannot be before the start date.');
                    }
                }

                if ($timeFrame === 'month_only' && $date === '') {
                    $validator->errors()->add("planned_activities.{$index}.date", "Activity {$activityNumber}: Month is required.");
                }

                foreach ([
                    'venue' => 'Venue',
                    'objectives' => 'Objectives',
                    'expected_outcome' => 'Expected Outcome',
                    'plan_key_strategy' => 'Delivery Strategy',
                    'target_participants' => 'Target Participants',
                    'person_in_charge' => 'Person in Charge',
                ] as $field => $label) {
                    if (trim((string) ($activity[$field] ?? '')) === '') {
                        $validator->errors()->add("planned_activities.{$index}.{$field}", "Activity {$activityNumber}: {$label} is required.");
                    }
                }

                if (empty($activity['sdgs'] ?? [])) {
                    $validator->errors()->add("planned_activities.{$index}.sdgs", "Activity {$activityNumber}: Select at least one SDG.");
                }

                if ($rawBudget === '' || ! is_numeric($rawBudget)) {
                    $validator->errors()->add("planned_activities.{$index}.estimated_budget", "Activity {$activityNumber}: Estimated Budget is required and must be numeric.");
                }
            }
        });

        $validator->validate();
    }
}
