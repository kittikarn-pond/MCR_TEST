<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_page_shows_current_user_data(): void
    {
        $user = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

        $this->actingAs($user)->get('/profile/edit')
            ->assertOk()
            ->assertSee('Jane Doe')
            ->assertSee('jane@example.com');
    }

    public function test_user_can_update_own_name_and_email(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/profile', ['name' => 'Updated Name', 'email' => 'updated@example.com'])
            ->assertRedirect(route('profile.edit'));

        $this->assertSame('Updated Name', $user->fresh()->name);
        $this->assertSame('updated@example.com', $user->fresh()->email);
    }

    public function test_user_can_change_own_password(): void
    {
        $user = User::factory()->create(['password' => 'original123']);

        $this->actingAs($user)->put('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'brandnew123',
            'password_confirmation' => 'brandnew123',
        ])->assertRedirect(route('profile.edit'));

        $this->assertTrue(Hash::check('brandnew123', $user->fresh()->password));
    }

    public function test_blank_password_keeps_current_password(): void
    {
        $user = User::factory()->create(['password' => 'original123']);

        $this->actingAs($user)->put('/profile', ['name' => 'Same', 'email' => $user->email, 'password' => ''])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('original123', $user->fresh()->password));
    }

    public function test_cannot_take_another_users_email(): void
    {
        $other = User::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->put('/profile', ['name' => 'X', 'email' => $other->email])
            ->assertSessionHasErrors('email');
    }

    public function test_only_the_logged_in_user_is_modified(): void
    {
        $other = User::factory()->create(['name' => 'Untouched']);
        $user = User::factory()->create();

        $this->actingAs($user)->put('/profile', ['name' => 'Mine', 'email' => $user->email]);

        $this->assertSame('Untouched', $other->fresh()->name);
    }
}