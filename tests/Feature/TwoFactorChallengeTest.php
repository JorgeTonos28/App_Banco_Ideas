<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFactorChallengeTest extends TestCase
{
    use RefreshDatabase;

    public function test_challenge_form_disables_the_inactive_code_field(): void
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
            'two_factor_type' => 'totp',
            'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
            'two_factor_recovery_codes' => ['RECOVERY123'],
        ]);

        $response = $this->withSession([
            '2fa:user:id' => $user->id,
        ])->get(route('2fa.challenge'));

        $response->assertOk();
        $response->assertSee(':disabled="useRecovery"', false);
        $response->assertSee(':disabled="!useRecovery"', false);
    }

    public function test_a_recovery_code_completes_the_pending_login(): void
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
            'two_factor_type' => 'totp',
            'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
            'two_factor_recovery_codes' => ['RECOVERY123'],
        ]);

        $response = $this->withSession([
            '2fa:user:id' => $user->id,
            '2fa:remember' => false,
        ])->post(route('2fa.verify'), [
            'code' => 'RECOVERY123',
        ]);

        $response->assertRedirect(route('my-ideas.index'));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
            'two_factor_recovery_codes' => json_encode(['RECOVERY123']),
        ]);
    }
}
