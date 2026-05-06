<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_screen_can_be_rendered_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('user.password.edit'));

        $response->assertOk();
    }

    public function test_user_can_update_own_password(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('user.password.edit'))
            ->put(route('user.password.update'), [
                'current_password' => 'password',
                'password' => 'ClaveNueva123!',
                'password_confirmation' => 'ClaveNueva123!',
            ]);

        $response->assertRedirect(route('user.password.edit'));
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('status', 'password-updated');

        $this->assertTrue(Hash::check('ClaveNueva123!', $user->fresh()->password));
    }

    public function test_user_can_not_update_password_with_wrong_current_password(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('user.password.edit'))
            ->put(route('user.password.update'), [
                'current_password' => 'incorrecta',
                'password' => 'ClaveNueva123!',
                'password_confirmation' => 'ClaveNueva123!',
            ]);

        $response->assertRedirect(route('user.password.edit'));
        $response->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
