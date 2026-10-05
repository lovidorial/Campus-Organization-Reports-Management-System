<?php

use App\Http\Middleware\EnsureTermsAccepted;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->appendToGroup('web', SecurityHeaders::class);

        // REGISTER YOUR MIDDLEWARE ALIAS HERE
        $middleware->alias([
            'terms.accepted' => EnsureTermsAccepted::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('backup:run')->daily();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            if ($request->isMethod('POST') && $request->is('admin/backups/restore')) {
                return redirect()->back()->withErrors([
                    'backup_file' => 'The restore ZIP exceeds PHP post_max_size ('.ini_get('post_max_size').'). Increase post_max_size and upload_max_filesize or choose a smaller ZIP.',
                ]);
            }
        });
    })->create();
