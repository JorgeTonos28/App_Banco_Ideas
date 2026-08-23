<?php

namespace Tests\Feature;

use App\Mail\UserInvitationMail;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class OnboardingInvitationMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creation_sends_an_onboarding_invitation(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'creation_type' => 'invitation',
            'name' => 'Nuevo Colaborador',
            'email' => 'colaborador@example.com',
            'role' => 'user',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $invitation = UserInvitation::where('email', 'colaborador@example.com')->firstOrFail();

        Mail::assertSent(UserInvitationMail::class, function (UserInvitationMail $mail) use ($invitation): bool {
            return $mail->hasTo('colaborador@example.com')
                && str_ends_with($mail->invitationUrl, "/onboarding/activar/{$invitation->token}");
        });
    }

    public function test_resending_an_invitation_sends_a_message_with_the_new_token(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $invitation = UserInvitation::create([
            'name' => 'Colaborador Pendiente',
            'email' => 'pendiente@example.com',
            'role' => 'user',
            'token' => Str::random(64),
            'expires_at' => now()->addHours(1),
        ]);

        $response = $this->actingAs($admin)->post(
            route('admin.users.invitations.resend', $invitation),
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $invitation->refresh();

        Mail::assertSent(UserInvitationMail::class, function (UserInvitationMail $mail) use ($invitation): bool {
            return $mail->hasTo('pendiente@example.com')
                && str_ends_with($mail->invitationUrl, "/onboarding/activar/{$invitation->token}");
        });
    }

    public function test_invitation_email_contains_the_activation_link_and_brand_colors(): void
    {
        $invitation = UserInvitation::create([
            'name' => 'Colaborador Invitado',
            'email' => 'invitado@example.com',
            'role' => 'user',
            'token' => Str::random(64),
            'expires_at' => now()->addHours(72),
        ]);

        $activationUrl = "https://apps.innovatep.com/banco/onboarding/activar/{$invitation->token}";
        $html = (new UserInvitationMail($invitation, $activationUrl))->render();

        $this->assertStringContainsString($activationUrl, $html);
        $this->assertStringContainsString('#003e6f', $html);
        $this->assertStringContainsString('#feb700', $html);
        $this->assertStringContainsString('INNOVATEP Ideas', $html);
    }
}
