<?php

namespace App\Console\Commands;

use App\Models\ActivityReport;
use App\Models\ActivityReportPhoto;
use App\Models\ActivityRequest;
use App\Models\Gpoa;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MoveSensitiveDocumentsToPrivate extends Command
{
    protected $signature = 'documents:move-sensitive-to-private {--move : Actually move public files to private storage}';

    protected $description = 'Dry-run or move model-referenced sensitive documents from public to private storage';

    private array $counts = [
        'referenced' => 0,
        'public_files' => 0,
        'would_move' => 0,
        'moved' => 0,
        'already_private' => 0,
        'conflicts' => 0,
        'failures' => 0,
    ];

    public function handle(): int
    {
        $move = (bool) $this->option('move');
        $documents = $this->referencedDocuments();
        $this->counts['referenced'] = count($documents);

        foreach ($documents as $path => $types) {
            $this->processPath($path, $types, $move);
        }

        $this->newLine();
        $this->table(['Measure', 'Count'], collect($this->counts)
            ->map(fn (int $count, string $label) => ['Measure' => str_replace('_', ' ', ucfirst($label)), 'Count' => $count])
            ->values()
            ->all());

        if (! $move) {
            $this->info('Dry run only. No files were changed. Re-run with --move to move eligible files.');
        } elseif ($this->counts['failures'] > 0 || $this->counts['conflicts'] > 0) {
            $this->warn('Move completed with conflicts or failures. Review the output and rerun after resolving them.');
        } else {
            $this->info('All eligible sensitive files were moved. Database paths were not changed.');
        }

        return $this->counts['failures'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function referencedDocuments(): array
    {
        $documents = [];
        $add = static function (?string $path, string $type) use (&$documents): void {
            if (! filled($path)) {
                return;
            }

            $documents[$path] ??= [];
            $documents[$path][] = $type;
        };

        ActivityRequest::query()->select(['id', 'communication_letter'])->orderBy('id')->chunkById(500, function ($requests) use ($add): void {
            foreach ($requests as $request) {
                $add($request->communication_letter, 'communication letters');
            }
        });

        ActivityReport::query()->select(['id', 'narrative_report', 'attendance_sheet_path'])->orderBy('id')->chunkById(500, function ($reports) use ($add): void {
            foreach ($reports as $report) {
                $add($report->narrative_report, 'narrative reports');
                $add($report->attendance_sheet_path, 'attendance sheets');
            }
        });

        Gpoa::query()->select(['id', 'document_path'])->orderBy('id')->chunkById(500, function ($gpoas) use ($add): void {
            foreach ($gpoas as $gpoa) {
                $add($gpoa->document_path, 'GPOA PDFs');
            }
        });

        ActivityReportPhoto::query()->select(['id', 'path'])->orderBy('id')->chunkById(500, function ($photos) use ($add): void {
            foreach ($photos as $photo) {
                $add($photo->path, 'report photos');
            }
        });

        ksort($documents);

        return $documents;
    }

    private function processPath(string $path, array $types, bool $move): void
    {
        if (! $this->isSafeRelativePath($path)) {
            $this->counts['failures']++;
            $this->error('Skipped unsafe document path: '.$path);

            return;
        }

        $public = Storage::disk('public');
        $private = Storage::disk('private');
        $foundOnPublic = $public->exists($path);
        $foundOnPrivate = $private->exists($path);

        if (! $foundOnPublic) {
            if ($foundOnPrivate) {
                $this->counts['already_private']++;
            }

            return;
        }

        $this->counts['public_files']++;
        $this->line(implode(', ', array_unique($types)).': '.$path);

        if ($foundOnPrivate && ! $this->filesMatch($public->path($path), $private->path($path))) {
            $this->counts['conflicts']++;
            $this->warn('Private file already exists with different content; skipped: '.$path);

            return;
        }

        if (! $move) {
            $this->counts['would_move']++;

            return;
        }

        try {
            if (! $foundOnPrivate && ! $this->copyAndVerify($path)) {
                $this->counts['failures']++;
                $this->error('Copy verification failed; public source retained: '.$path);

                return;
            }

            if (! $public->delete($path)) {
                $this->counts['failures']++;
                $this->error('Could not delete the public source after copying: '.$path);

                return;
            }

            $this->counts['moved']++;
        } catch (Throwable) {
            $this->counts['failures']++;
            $this->error('Move failed; inspect storage permissions and file state: '.$path);
        }
    }

    private function copyAndVerify(string $path): bool
    {
        $public = Storage::disk('public');
        $private = Storage::disk('private');
        $read = $public->readStream($path);
        if (! is_resource($read)) {
            return false;
        }

        try {
            $written = $private->writeStream($path, $read);
        } finally {
            fclose($read);
        }

        return $written && $private->exists($path)
            && $this->filesMatch($public->path($path), $private->path($path));
    }

    private function filesMatch(string $source, string $destination): bool
    {
        if (! is_file($source) || ! is_file($destination)) {
            return false;
        }

        $sourceHash = hash_file('sha256', $source);
        $destinationHash = hash_file('sha256', $destination);

        return is_string($sourceHash)
            && is_string($destinationHash)
            && hash_equals($sourceHash, $destinationHash);
    }

    private function isSafeRelativePath(string $path): bool
    {
        return $path !== ''
            && ! str_starts_with($path, '/')
            && ! str_starts_with($path, '\\')
            && ! preg_match('/^[A-Za-z]:/', $path)
            && ! in_array('..', preg_split('~[\\\\/]~', $path) ?: [], true);
    }
}
