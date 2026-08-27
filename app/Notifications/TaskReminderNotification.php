<?php

namespace App\Notifications;

use App\Models\TaskReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskReminderNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly TaskReminder $reminder) {}

    public function via(object $notifiable): array
    {
        return $this->reminder->channel === 'email'
            ? ['mail', 'database']
            : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $task = $this->reminder->task;

        return (new MailMessage)
            ->subject('Recordatorio: '.$task->title)
            ->greeting('Hola, '.$notifiable->name)
            ->line('Tienes una tarea pendiente en el Centro de Innovación:')
            ->line($task->title)
            ->when($task->due_at, fn (MailMessage $mail) => $mail->line('Vence '.$task->due_at->translatedFormat('d M Y, h:i A').'.'))
            ->action('Abrir tarea', route('tasks.show', $task))
            ->line('Puedes cambiar o completar la tarea desde el módulo de Tareas.');
    }

    public function toArray(object $notifiable): array
    {
        $task = $this->reminder->task;

        return [
            'kind' => 'task_reminder',
            'channel' => $this->reminder->channel,
            'task_reminder_id' => $this->reminder->id,
            'task_id' => $task->id,
            'title' => 'Recordatorio de tarea',
            'message' => $task->title,
            'url' => route('tasks.show', $task),
            'due_at' => $task->due_at?->toIso8601String(),
        ];
    }
}
