<?php

namespace App\Console\Commands;

use App\Services\TaskReminderService;
use Illuminate\Console\Command;

class SendTaskReminders extends Command
{
    protected $signature = 'tasks:send-reminders';

    protected $description = 'Envía los recordatorios vencidos de tareas por correo o navegador.';

    public function handle(TaskReminderService $service): int
    {
        $result = $service->sendDue();
        $this->info("Recordatorios enviados: {$result['sent']}; fallidos: {$result['failed']}.");

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
