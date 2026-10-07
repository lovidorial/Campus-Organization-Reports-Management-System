<?php

namespace App\Http\Middleware;

use App\Models\BackupSetting;
use App\Services\BackupService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class RunDueScheduledTasks
{
    public function __construct(private BackupService $backupService) {}

    public function handle(Request $request, Closure $next): Response
    {
        try {
            if (! $this->shouldSkip($request) && Schema::hasTable('backup_settings')) {
                $settings = BackupSetting::query()->first();
                if ($settings && $settings->frequency !== 'manual' && Cache::add('scheduler:web-tick', now(), 60)) {
                    Cache::put('scheduler_last_run_at', now());

                    $settings->refresh();
                    if ($this->backupService->isBackupDue($settings)) {
                        $this->dispatchBackup();
                    }
                }
            }
        } catch (\Throwable $exception) {
            Log::warning('Web-triggered scheduler check failed; request will continue.', ['exception' => $exception]);
        }

        return $next($request);
    }

    protected function shouldSkip(Request $request): bool
    {
        if (! $request->isMethod('GET')) {
            return true;
        }
        if (! config('backup.web_scheduler', true)) {
            return true;
        }
        if (app()->environment('testing') && ! config('backup.web_scheduler_in_tests', false)) {
            return true;
        }
        if ($request->ajax() || $request->expectsJson()) {
            return true;
        }

        return preg_match('/\.(?:avif|css|gif|ico|jpe?g|js|map|png|svg|webp|woff2?|ttf)$/i', $request->path()) === 1;
    }

    protected function dispatchBackup(): void
    {
        try {
            if ($this->startDetachedProcess()) {
                return;
            }
        } catch (\Throwable $exception) {
            Log::warning('Unable to start detached web-triggered backup; it will run after the response.', ['exception' => $exception]);
        }

        Log::warning('Detached web-triggered backup process is unavailable; scheduling backup after the response.');
        app()->terminating(function (): void {
            try {
                $this->runAfterResponse();
            } catch (\Throwable $exception) {
                Log::error('Web-triggered backup failed after the response.', ['exception' => $exception]);
            }
        });
    }

    protected function startDetachedProcess(): bool
    {
        $phpBinary = $this->phpBinary();
        $artisan = base_path('artisan');

        if (PHP_OS_FAMILY === 'Windows') {
            if (! $this->functionEnabled('popen') || ! $this->functionEnabled('pclose')) {
                return false;
            }

            $command = 'start /B "" '.$this->quoteWindowsArgument($phpBinary).' '.$this->quoteWindowsArgument($artisan).' backup:run --source=web-trigger > NUL 2>&1';
            $process = @popen($command, 'r');
            if ($process === false) {
                return false;
            }

            return @pclose($process) === 0;
        }

        if (! $this->functionEnabled('exec')) {
            return false;
        }

        $command = escapeshellarg($phpBinary).' '.escapeshellarg($artisan).' backup:run --source=web-trigger > /dev/null 2>&1 &';
        exec($command, $output, $exitCode);

        return $exitCode === 0;
    }

    protected function runAfterResponse(): void
    {
        Artisan::call('backup:run', ['--source' => 'web-trigger']);
    }

    protected function phpBinary(): string
    {
        $configuredBinary = config('backup.php_binary');
        if (is_string($configuredBinary) && trim($configuredBinary) !== '') {
            return trim($configuredBinary);
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $binary = PHP_BINARY;
            if (strtolower(basename($binary)) === 'php.exe' && is_file($binary)) {
                return $binary;
            }

            $xamppRoot = dirname(dirname(dirname($binary)));
            $xamppPhp = $xamppRoot.DIRECTORY_SEPARATOR.'php'.DIRECTORY_SEPARATOR.'php.exe';
            if (is_file($xamppPhp)) {
                return $xamppPhp;
            }
        }

        return 'php';
    }

    protected function quoteWindowsArgument(string $argument): string
    {
        return '"'.str_replace('"', '\\"', $argument).'"';
    }

    protected function functionEnabled(string $function): bool
    {
        if (! function_exists($function)) {
            return false;
        }

        $disabledFunctions = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        return ! in_array($function, $disabledFunctions, true);
    }
}