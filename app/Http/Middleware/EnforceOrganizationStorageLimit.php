<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Services\StorageUsageService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class EnforceOrganizationStorageLimit
{
    public function __construct(private readonly StorageUsageService $storageUsage)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $files = $this->uploadedFiles($request->allFiles());

        if (! $user || $user->isAdmin() || $files === []) {
            return $next($request);
        }

        $organization = $user->organization
            ?? Organization::query()->where('name', $user->org_name)->first();

        if (! $organization) {
            return $next($request);
        }

        $incomingBytes = array_sum(array_map(fn (UploadedFile $file): int => (int) $file->getSize(), $files));
        foreach ($this->replacementPaths($request) as $path) {
            $incomingBytes -= $this->storageUsage->storedFileSize($path);
        }

        $currentBytes = $this->storageUsage->organizationUsageBytes($organization->id);
        $limitBytes = (int) ($organization->storage_limit_mb ?? 500) * 1024 * 1024;

        if ($currentBytes + $incomingBytes > $limitBytes) {
            $remainingBytes = max(0, $limitBytes - $currentBytes);
            throw ValidationException::withMessages([
                'storage_limit' => sprintf(
                    'This upload exceeds your organization storage limit. %s MB is currently used; %s MB remains of the %s MB limit.',
                    number_format($currentBytes / 1048576, 2),
                    number_format($remainingBytes / 1048576, 2),
                    number_format($limitBytes / 1048576, 0)
                ),
            ]);
        }

        return $next($request);
    }

    private function uploadedFiles(array $files): array
    {
        $uploaded = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                if ($file->isValid()) {
                    $uploaded[] = $file;
                }
            } elseif (is_array($file)) {
                array_push($uploaded, ...$this->uploadedFiles($file));
            }
        }

        return $uploaded;
    }

    private function replacementPaths(Request $request): array
    {
        return match ($request->route()?->getName()) {
            'profile.update' => [$request->user()?->profile_photo_path],
            'activity-requests.reservation-slip' => [$request->route('activityRequest')?->reservation_slip],
            'gpoa.update' => [$request->route('gpoa')?->document_path],
            'organization.members.update' => [$request->route('member')?->photo_path],
            'activity-reports.store' => [$request->route('activityRequest')?->report?->narrative_report],
            default => [],
        };
    }
}