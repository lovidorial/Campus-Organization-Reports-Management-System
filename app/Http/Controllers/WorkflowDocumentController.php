<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;

class WorkflowDocumentController extends Controller
{
    public function notifications()
    {
        $notifications = auth()->user()->notifications()->latest()->paginate(20);

        return view('users.notifications', compact('notifications'));
    }

    public function unreadNotificationCount()
    {
        return response()->json([
            'count' => auth()->user()->unreadNotificationsCount(),
        ]);
    }

    public function markNotificationRead(UserNotification $notification)
    {
        $this->authorize('update', $notification);

        $notification->markAsRead();

        return back();
    }

    public function markAllNotificationsRead()
    {
        auth()->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
