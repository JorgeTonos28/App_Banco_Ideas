<?php

namespace App\Http\Controllers;

use App\Notifications\TaskReminderNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskReminderController extends Controller
{
    public function browser(Request $request): JsonResponse
    {
        $notifications = $request->user()->unreadNotifications()
            ->where('type', TaskReminderNotification::class)
            ->latest()
            ->limit(30)
            ->get()
            ->filter(fn ($notification) => ($notification->data['channel'] ?? null) === 'browser')
            ->take(10)
            ->map(fn ($notification) => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? 'Recordatorio de tarea',
                'message' => $notification->data['message'] ?? '',
                'url' => $notification->data['url'] ?? route('tasks.index'),
            ])->values();

        return response()->json(['data' => $notifications]);
    }
}
