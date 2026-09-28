<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Support\Facades\DB;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class StorageUsageService
{
    public function overview(): array
    {
        $organizations = Organization::query()
            ->with('members:id,organization_id,profile_photo_path')
            ->orderBy('name')
            ->get();
        $pathsByUser = [];

        foreach (DB::table('users')->whereNotNull('profile_photo_path')->get(['id', 'profile_photo_path']) as $row) {
            $this->addPath($pathsByUser, (int) $row->id, $row->profile_photo_path);
        }
        foreach (DB::table('gpoas')->whereNotNull('document_path')->get(['user_id', 'document_path']) as $row) {
            $this->addPath($pathsByUser, (int) $row->user_id, $row->document_path);
        }
        foreach (DB::table('activity_requests')->where(function ($query) {
            $query->whereNotNull('communication_letter');
        })->get(['user_id', 'communication_letter']) as $row) {
            $this->addPath($pathsByUser, (int) $row->user_id, $row->communication_letter);
        }
        foreach (DB::table('activities')->where(function ($query) {
            $query->whereNotNull('communication_letter')->orWhereNotNull('narrative_report');
        })->get(['user_id', 'communication_letter', 'narrative_report']) as $row) {
            $this->addPath($pathsByUser, (int) $row->user_id, $row->communication_letter);
            $this->addPath($pathsByUser, (int) $row->user_id, $row->narrative_report);
        }
        foreach (DB::table('activity_reports')->join('activity_requests', 'activity_requests.id', '=', 'activity_reports.activity_request_id')
            ->whereNotNull('activity_reports.narrative_report')
            ->get(['activity_requests.user_id', 'activity_reports.narrative_report']) as $row) {
            $this->addPath($pathsByUser, (int) $row->user_id, $row->narrative_report);
        }
        foreach (DB::table('activity_report_photos')->join('activity_reports', 'activity_reports.id', '=', 'activity_report_photos.activity_report_id')
            ->join('activity_requests', 'activity_requests.id', '=', 'activity_reports.activity_request_id')
            ->get(['activity_requests.user_id', 'activity_report_photos.path']) as $row) {
            $this->addPath($pathsByUser, (int) $row->user_id, $row->path);
        }
        foreach (DB::table('workflow_submissions')->join('organization_workflows', 'organization_workflows.id', '=', 'workflow_submissions.organization_workflow_id')
            ->whereNotNull('workflow_submissions.file_path')
            ->get(['organization_workflows.user_id', 'workflow_submissions.file_path']) as $row) {
            $this->addPath($pathsByUser, (int) $row->user_id, $row->file_path);
        }

        $organizationUsage = $organizations->map(function (Organization $organization) use ($pathsByUser): array {
            $paths = [$organization->logo_path];
            foreach ($organization->members as $member) {
                array_push($paths, ...($pathsByUser[$member->id] ?? []));
            }
            foreach (DB::table('organization_members')->where('organization_id', $organization->id)->whereNotNull('photo_path')->pluck('photo_path') as $path) {
                $paths[] = $path;
            }

            $bytes = 0;
            foreach (array_unique(array_filter($paths)) as $path) {
                $bytes += $this->referencedFileSize((string) $path);
            }

            return [
                'id' => $organization->id,
                'name' => $organization->name,
                'members' => $organization->members->count(),
                'bytes' => $bytes,
                'limit_mb' => $organization->storage_limit_mb ?? 500,
            ];
        });

        return [
            'database_bytes' => $this->databaseSize(),
            'storage_bytes' => $this->directorySize(storage_path('app')),
            'organizations' => $organizationUsage,
        ];
    }

    public function organizationUsageBytes(int $organizationId): int
    {
        $organization = Organization::with('members:id,organization_id,profile_photo_path')->find($organizationId);
        if (! $organization) {
            return 0;
        }

        $userIds = $organization->members->pluck('id')->all();
        $paths = [$organization->logo_path];
        foreach ($userIds as $userId) {
            array_push($paths, ...DB::table('users')->where('id', $userId)->whereNotNull('profile_photo_path')->pluck('profile_photo_path')->all());
        }

        if ($userIds) {
            foreach ([
                ['gpoas', ['document_path']],
                ['activity_requests', ['communication_letter']],
                ['activities', ['communication_letter', 'narrative_report']],
            ] as [$table, $columns]) {
                $select = array_merge(['user_id'], $columns);
                foreach (DB::table($table)->whereIn('user_id', $userIds)->get($select) as $row) {
                    foreach ($columns as $column) {
                        $paths[] = $row->{$column};
                    }
                }
            }

            foreach (DB::table('activity_reports')->join('activity_requests', 'activity_requests.id', '=', 'activity_reports.activity_request_id')
                ->whereIn('activity_requests.user_id', $userIds)->get(['activity_reports.narrative_report']) as $row) {
                $paths[] = $row->narrative_report;
            }
            foreach (DB::table('activity_report_photos')->join('activity_reports', 'activity_reports.id', '=', 'activity_report_photos.activity_report_id')
                ->join('activity_requests', 'activity_requests.id', '=', 'activity_reports.activity_request_id')
                ->whereIn('activity_requests.user_id', $userIds)->pluck('activity_report_photos.path') as $path) {
                $paths[] = $path;
            }
            foreach (DB::table('workflow_submissions')->join('organization_workflows', 'organization_workflows.id', '=', 'workflow_submissions.organization_workflow_id')
                ->whereIn('organization_workflows.user_id', $userIds)->pluck('workflow_submissions.file_path') as $path) {
                $paths[] = $path;
            }
        }

        array_push($paths, ...DB::table('organization_members')->where('organization_id', $organizationId)->whereNotNull('photo_path')->pluck('photo_path')->all());

        $bytes = 0;
        foreach (array_unique(array_filter($paths)) as $path) {
            $bytes += $this->referencedFileSize((string) $path);
        }

        return $bytes;
    }

    public function storedFileSize(?string $path): int
    {
        return $path ? $this->referencedFileSize($path) : 0;
    }

    protected function databaseSize(): ?int
    {
        try {
            $connection = DB::connection();

            return match ($connection->getDriverName()) {
                'mysql', 'mariadb' => (int) (DB::selectOne(
                    'SELECT COALESCE(SUM(data_length + index_length), 0) AS size FROM information_schema.tables WHERE table_schema = DATABASE()'
                )->size ?? 0),
                'pgsql' => (int) (DB::selectOne('SELECT pg_database_size(current_database()) AS size')->size ?? 0),
                'sqlite' => $this->sqliteSize(),
                default => null,
            };
        } catch (\Throwable) {
            return null;
        }
    }

    protected function sqliteSize(): ?int
    {
        $databasePath = config('database.connections.sqlite.database');
        if (! $databasePath || $databasePath === ':memory:') {
            return null;
        }

        if (! str_starts_with($databasePath, DIRECTORY_SEPARATOR) && ! preg_match('/^[A-Za-z]:[\\\\\/]/', $databasePath)) {
            $databasePath = base_path($databasePath);
        }

        return is_file($databasePath) ? (int) filesize($databasePath) : null;
    }

    protected function directorySize(string $directory): int
    {
        if (! is_dir($directory)) {
            return 0;
        }

        $bytes = 0;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->isFile() && ! $file->isLink()) {
                $bytes += $file->getSize();
            }
        }

        return $bytes;
    }

    protected function addPath(array &$pathsByUser, int $userId, ?string $path): void
    {
        if ($path !== null && $path !== '') {
            $pathsByUser[$userId][] = $path;
        }
    }

    protected function referencedFileSize(string $path): int
    {
        if (preg_match('/^[a-z][a-z0-9+.-]*:\/\//i', $path)) {
            return 0;
        }

        $relativePath = ltrim(str_replace('\\', '/', $path), '/');
        if (in_array('..', explode('/', $relativePath), true)) {
            return 0;
        }

        foreach ([storage_path('app/public/' . $relativePath), storage_path('app/' . $relativePath)] as $candidate) {
            $resolved = realpath($candidate);
            if ($resolved !== false && is_file($resolved)) {
                return (int) filesize($resolved);
            }
        }

        return 0;
    }
}