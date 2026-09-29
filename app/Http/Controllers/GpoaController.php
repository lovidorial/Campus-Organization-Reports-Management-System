<?php

namespace App\Http\Controllers;

use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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
            'document_path'       => 'nullable|file|mimes:pdf|max:20480',
            'planned_activities'  => 'required|array|min:1|max:' . config('gpoa.max_planned_activities'),
            'planned_activities.*.title' => 'nullable|required_with:planned_activities.*.sdgs|string|max:255',
            'planned_activities.*.date' => 'nullable|required_with:planned_activities.*.sdgs|date',
            'planned_activities.*.venue' => 'nullable|required_with:planned_activities.*.sdgs|string|max:255',
            'planned_activities.*.category' => 'nullable|required_with:planned_activities.*.sdgs|string|max:100',
            'planned_activities.*.sdgs' => 'nullable|array|min:1|max:8',
            'verify'              => 'required|accepted',
        ], [
            'planned_activities.max' => 'A GPOA may contain no more than ' . config('gpoa.max_planned_activities') . ' planned activities.',
        ]);

        $documentPath = $request->hasFile('document_path')
            ? $request->file('document_path')->store('uploads/gpoa', 'public')
            : null;

        $gpoa = Gpoa::create([
            'user_id'       => auth()->id(),
            'term'          => $validated['term'],
            'school_year'   => $validated['school_year'],
            'college'       => $validated['colleges'],
            'document_path' => $documentPath,
            'prepared_by'   => $validated['prepared_by'],
            'status'        => 'submitted',
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

        return view('gpoa.edit', compact('gpoa'));
    }

    public function update(Request $request, Gpoa $gpoa)
    {
        $this->authorize('update', $gpoa);

        $this->validatePlannedActivityEntries($request);

        $validated = $request->validate([
            'colleges' => 'required|string|max:100',
            'prepared_by' => 'required|string|max:255',
            'document_path' => 'nullable|file|mimes:pdf|max:20480',
            'planned_activities'  => 'required|array|min:1|max:' . config('gpoa.max_planned_activities'),
            'planned_activities.*.id' => 'nullable|integer',
            'planned_activities.*.title' => 'nullable|required_with:planned_activities.*.sdgs|string|max:255',
            'planned_activities.*.date' => 'nullable|required_with:planned_activities.*.sdgs|date',
            'planned_activities.*.venue' => 'nullable|required_with:planned_activities.*.sdgs|string|max:255',
            'planned_activities.*.category' => 'nullable|required_with:planned_activities.*.sdgs|string|max:100',
            'planned_activities.*.sdgs' => 'nullable|array|min:1|max:8',
            'verify' => 'required|accepted',
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

        return [
            'title' => trim((string) ($activityData['title'] ?? '')),
            'date' => isset($activityData['date']) && trim((string) $activityData['date']) !== '' ? $activityData['date'] : null,
            'venue' => trim((string) ($activityData['venue'] ?? '')),
            'category' => trim((string) ($activityData['category'] ?? '')),
            'sdgs' => $sdgs,
        ];
    }

    private function isBlankPlannedActivity(array $activityData): bool
    {
        return empty($activityData['title'])
            && empty($activityData['date'])
            && empty($activityData['venue'])
            && empty($activityData['category'])
            && empty($activityData['sdgs']);
    }

    private function validatePlannedActivityEntries(Request $request): void
    {
        $validator = Validator::make($request->all(), [
            'planned_activities' => 'required|array|min:1|max:' . config('gpoa.max_planned_activities'),
            'planned_activities.*.title' => 'nullable|required_with:planned_activities.*.sdgs|string|max:255',
            'planned_activities.*.date' => 'nullable|required_with:planned_activities.*.sdgs|date',
            'planned_activities.*.venue' => 'nullable|required_with:planned_activities.*.sdgs|string|max:255',
            'planned_activities.*.category' => 'nullable|required_with:planned_activities.*.sdgs|string|max:100',
            'planned_activities.*.sdgs' => 'nullable|array|min:1|max:8',
        ], [
            'planned_activities.max' => 'A GPOA may contain no more than ' . config('gpoa.max_planned_activities') . ' planned activities.',
        ]);

        $validator->after(function ($validator) use ($request) {
            foreach ((array) $request->input('planned_activities', []) as $index => $activity) {
                $hasAnyValue = collect(['title', 'date', 'venue', 'category'])
                    ->contains(fn ($field) => trim((string) ($activity[$field] ?? '')) !== '')
                    || !empty($activity['sdgs'] ?? []);

                if (!$hasAnyValue) {
                    continue;
                }

                $isComplete = collect(['title', 'date', 'venue', 'category'])
                    ->every(fn ($field) => trim((string) ($activity[$field] ?? '')) !== '')
                    && !empty($activity['sdgs'] ?? []);

                if (!$isComplete) {
                    $validator->errors()->add(
                        "planned_activities.{$index}",
                        'Activity ' . ($index + 1) . ' is incomplete - title, date, venue, category, and at least one SDG are all required.'
                    );
                }
            }
        });

        $validator->validate();
    }
}
