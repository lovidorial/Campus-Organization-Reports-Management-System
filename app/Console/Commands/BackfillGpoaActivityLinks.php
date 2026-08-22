<?php

namespace App\Console\Commands;

use App\Models\ActivityRequest;
use App\Services\GpoaActivityLinker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BackfillGpoaActivityLinks extends Command
{
    protected $signature = 'gpoa:backfill-activity-links';

    protected $description = 'Backfill GPOA activity links for approved activity requests';

    public function handle(GpoaActivityLinker $linker): int
    {
        $fixedCount = 0;
        $skippedCount = 0;
        $skipped = [];

        DB::transaction(function () use ($linker, &$fixedCount, &$skippedCount, &$skipped): void {
            ActivityRequest::whereNull('gpoa_activity_id')
                ->whereIn('status', [
                    ActivityRequest::STATUS_APPROVED,
                    ActivityRequest::STATUS_IN_PROGRESS,
                    ActivityRequest::STATUS_AWAITING_REPORT,
                    ActivityRequest::STATUS_REPORT_SUBMITTED,
                    ActivityRequest::STATUS_CLOSED,
                ])
                ->lockForUpdate()
                ->each(function (ActivityRequest $activity) use ($linker, &$fixedCount, &$skippedCount, &$skipped): void {
                    try {
                        $linker->link($activity);
                        $fixedCount++;
                    } catch (RuntimeException $exception) {
                        $skipped[] = [
                            'id' => $activity->id,
                            'title' => $activity->title,
                            'status' => $activity->status,
                        ];
                        $skippedCount++;

                        $this->warn("Skipped activity {$activity->id}: {$exception->getMessage()}");
                    }
                });
        });

        $this->info("Fixed {$fixedCount} activity request(s).");

        if ($skippedCount > 0) {
            $this->warn('These activities have no parent GPOA at all and must be reviewed manually - they cannot be auto-linked.');
            $this->warn('They were likely created before GPOA-gating existed in this system. Decide whether to:');
            $this->line('(a) manually assign them to an existing GPOA, or');
            $this->line('(b) leave them excluded from monitoring/summary reports since they predate the current workflow.');
            $this->table(['ID', 'Title', 'Status'], $skipped);
        }

        return self::SUCCESS;
    }
}
