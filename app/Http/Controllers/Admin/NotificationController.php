<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Notifikasi in-app (PRD F9.1–F9.2).
 */
class NotificationController extends Controller
{
    public function index(): Response
    {
        $notifications = request()->user()
            ->notifications()
            ->paginate(20);

        return Inertia::render('Admin/Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    public function markAllRead(): RedirectResponse
    {
        request()->user()->unreadNotifications->markAsRead();

        return back()->with('success', __('common.saved'));
    }

    public function markRead(DatabaseNotification $notification): RedirectResponse
    {
        if ($notification->notifiable_id !== request()->user()->id) {
            abort(403, __('common.forbidden'));
        }

        $notification->markAsRead();

        return back();
    }
}
