<?php

namespace Tests\Feature;

use App\Models\Idea;
use App\Models\Task;
use App\Models\TaskReminder;
use App\Models\TaskVolunteer;
use App\Models\User;
use App\Notifications\TaskReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InnovationTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_standalone_task_with_private_attachment_and_reminders(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('tasks.store'), [
            'title' => 'Preparar prototipo navegable',
            'description' => 'Construir el flujo principal y validar que todas las pantallas estén conectadas.',
            'priority' => 'alta',
            'participation_mode' => 'private',
            'due_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'remind_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'reminder_channels' => ['email', 'browser'],
            'attachments' => [UploadedFile::fake()->create('requisitos.pdf', 250, 'application/pdf')],
        ]);

        $task = Task::firstOrFail();
        $response->assertRedirect(route('tasks.show', $task));
        $this->assertSame($user->id, $task->created_by_user_id);
        $this->assertSame($user->id, $task->assigned_to_user_id);
        $this->assertDatabaseCount('task_reminders', 2);
        $this->assertDatabaseCount('task_attachments', 1);
        $attachment = $task->attachments()->firstOrFail();
        $this->assertStringNotContainsString('requisitos', $attachment->file_path);
        Storage::disk('local')->assertExists($attachment->file_path);
    }

    public function test_private_task_is_not_visible_to_an_unrelated_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $task = Task::create([
            'created_by_user_id' => $owner->id,
            'assigned_to_user_id' => $owner->id,
            'title' => 'Tarea privada del propietario',
            'participation_mode' => 'private',
        ]);

        $this->actingAs($other)->get(route('tasks.show', $task))->assertForbidden();
    }

    public function test_idea_owner_can_approve_a_community_volunteer_as_assignee(): void
    {
        $owner = User::factory()->create();
        $volunteer = User::factory()->create();
        $idea = Idea::factory()->for($owner)->create([
            'visibility' => 'public',
            'publication_status' => 'published',
            'access_scope' => 'profile',
            'allow_task_collaboration' => true,
        ]);
        $task = Task::create([
            'created_by_user_id' => $owner->id,
            'assigned_to_user_id' => $owner->id,
            'idea_id' => $idea->id,
            'title' => 'Validar el prototipo con cinco usuarios',
            'participation_mode' => 'open',
        ]);

        $this->actingAs($volunteer)
            ->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSee('Quiero colaborar');
        $this->actingAs($volunteer)
            ->post(route('tasks.volunteers.store', $task), ['message' => 'Puedo apoyar las entrevistas.'])
            ->assertRedirect();

        $request = TaskVolunteer::firstOrFail();
        $this->actingAs($owner)
            ->patch(route('tasks.volunteers.update', [$task, $request]), ['status' => 'approved'])
            ->assertRedirect();

        $this->assertDatabaseHas('task_volunteers', ['id' => $request->id, 'status' => 'approved']);
        $this->assertSame($volunteer->id, $task->fresh()->assigned_to_user_id);

        $this->actingAs($volunteer)
            ->patch(route('tasks.status.update', $task), ['status' => 'completada'])
            ->assertRedirect();
        $this->assertSame('completada', $task->fresh()->status);
    }

    public function test_due_reminders_are_sent_through_the_configured_channel(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $task = Task::create([
            'created_by_user_id' => $user->id,
            'assigned_to_user_id' => $user->id,
            'title' => 'Entregar informe de validación',
            'participation_mode' => 'private',
        ]);
        $reminder = TaskReminder::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'remind_at' => now()->subMinute(),
            'channel' => 'browser',
        ]);

        $this->artisan('tasks:send-reminders')->assertSuccessful();

        Notification::assertSentTo($user, TaskReminderNotification::class);
        $this->assertNotNull($reminder->fresh()->sent_at);
    }
}
