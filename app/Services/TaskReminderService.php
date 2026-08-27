<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskReminder;
use App\Models\User;
use App\Notifications\TaskReminderNotification;
use Illuminate\Support\Carbon;

class TaskReminderService
{
    public function sync(Task $task, User $user, ?string $remindAt, array $channels): void
    {
        $task->reminders()
            ->where('user_id', $user->id)
            ->whereNull('sent_at')
            ->delete();

        if (! $remindAt || $channels === []) {
            return;
        }

        foreach (array_unique($channels) as $channel) {
            $task->reminders()->create([
                'user_id' => $user->id,
                'remind_at' => Carbon::parse($remindAt),
                'channel' => $channel,
            ]);
        }
    }

    public function sendDue(): array
    {
        $sent = 0;
        $failed = 0;

        TaskReminder::query()
            ->whereNull('sent_at')
            ->where('remind_at', '<=', now())
            ->whereHas('task', fn ($query) => $query->whereNotIn('status', ['completada', 'cancelada']))
            ->with(['task.idea', 'user'])
            ->orderBy('id')
            ->chunkById(100, function ($reminders) use (&$sent, &$failed): void {
                foreach ($reminders as $reminder) {
                    try {
                        $reminder->user->notify(new TaskReminderNotification($reminder));
                        $reminder->update(['sent_at' => now(), 'failure_reason' => null]);
                        $sent++;
                    } catch (\Throwable $exception) {
                        $reminder->update([
                            'failure_reason' => mb_substr($exception->getMessage(), 0, 2000),
                        ]);
                        report($exception);
                        $failed++;
                    }
                }
            });

        return compact('sent', 'failed');
    }
}
