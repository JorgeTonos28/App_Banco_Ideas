<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $notifications = $user->notifications;

        // Grouping
        $today = $notifications->filter(fn ($n) => $n->created_at->isToday());
        $thisWeek = $notifications->filter(fn ($n) => !$n->created_at->isToday() && $n->created_at->isCurrentWeek());
        $earlier = $notifications->filter(fn ($n) => !$n->created_at->isCurrentWeek());

        return view('notifications.index', compact('today', 'thisWeek', 'earlier', 'notifications'));
    }

    public function markAsRead(string $id): RedirectResponse|JsonResponse
    {
        $notification = auth()->user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Notificación marcada como leída.');
    }

    public function markAllAsRead(): RedirectResponse
    {
        auth()->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Todas las notificaciones han sido marcadas como leídas.');
    }
}
