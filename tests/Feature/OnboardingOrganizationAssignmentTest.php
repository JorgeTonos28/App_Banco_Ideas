<?php

namespace Tests\Feature;

use App\Models\Regional;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OnboardingOrganizationAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_invited_user_cannot_replace_the_organizational_unit_assigned_by_an_admin(): void
    {
        [$regional, $department] = $this->organization();
        $otherRegional = Regional::create([
            'code' => 'OTR',
            'name' => 'Regional ajena',
            'type' => 'regional',
            'is_active' => true,
        ]);
        $invitation = UserInvitation::create([
            'email' => 'nueva.persona@example.com',
            'name' => 'Nueva Persona',
            'role' => 'user',
            'regional_id' => $regional->id,
            'organizational_unit_id' => $department->id,
            'token' => Str::random(64),
            'expires_at' => now()->addDay(),
        ]);

        $this->get(route('onboarding.accept', $invitation->token))
            ->assertOk()
            ->assertSee($department->path_label)
            ->assertSee('Asignada por el administrador');

        $this->post(route('onboarding.activate', $invitation->token), [
            'name' => 'Nueva Persona',
            'password' => 'Segura1234',
            'password_confirmation' => 'Segura1234',
            'organizational_unit_id' => $otherRegional->id,
        ])->assertRedirect(route('my-ideas.index'));

        $user = User::where('email', $invitation->email)->firstOrFail();
        $this->assertSame($department->id, $user->organizational_unit_id);
        $this->assertSame($regional->id, $user->regional_id);
    }

    private function organization(): array
    {
        $regional = Regional::create([
            'code' => 'ONA',
            'name' => 'Oficina Nacional',
            'type' => 'regional',
            'is_active' => true,
        ]);
        $direction = Regional::create([
            'code' => 'DFIN',
            'name' => 'Dirección Financiera',
            'type' => 'direction',
            'parent_id' => $regional->id,
            'is_active' => true,
        ]);
        $department = Regional::create([
            'code' => 'CONT',
            'name' => 'Contabilidad',
            'type' => 'department',
            'parent_id' => $direction->id,
            'is_active' => true,
        ]);

        return [$regional, $department];
    }
}
