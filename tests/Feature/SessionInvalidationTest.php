<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Changing a user's password must log that user out of every other session,
 * without logging out the session that made the change.
 */
class SessionInvalidationTest extends TestCase
{
    use RefreshDatabase;

    /** Log in as $user with a real session and return that session's data (a "device"). */
    private function loginOnNewDevice(User $user, string $password): array
    {
        $this->flushSession();
        app('auth')->forgetGuards();

        $this->post('/login', ['email' => $user->email, 'password' => $password])
            ->assertRedirect(route('dashboard'));
        $this->get('/dashboard')->assertOk();

        return session()->all();
    }

    private function requestFromDevice(array $device): \Illuminate\Testing\TestResponse
    {
        $this->flushSession();
        app('auth')->forgetGuards();

        return $this->withSession($device)->get('/dashboard');
    }

    public function test_changing_own_password_logs_out_other_devices_but_not_this_one(): void
    {
        $user = User::factory()->create(['password' => 'original123']);

        $otherDevice = $this->loginOnNewDevice($user, 'original123');
        $thisDevice = $this->loginOnNewDevice($user, 'original123');

        // This device changes the password through /profile.
        $this->flushSession();
        app('auth')->forgetGuards();
        $this->withSession($thisDevice)->put('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'brandnew123',
            'password_confirmation' => 'brandnew123',
        ])->assertRedirect(route('profile.edit'));
        $thisDeviceAfter = session()->all();

        $this->requestFromDevice($thisDeviceAfter)->assertOk();
        $this->requestFromDevice($otherDevice)->assertRedirect('/login');
    }

    public function test_changing_own_password_via_users_page_keeps_this_session(): void
    {
        $user = User::factory()->create(['password' => 'original123']);

        $otherDevice = $this->loginOnNewDevice($user, 'original123');
        $thisDevice = $this->loginOnNewDevice($user, 'original123');

        $this->flushSession();
        app('auth')->forgetGuards();
        $this->withSession($thisDevice)->put("/users/{$user->id}", [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'brandnew123',
            'password_confirmation' => 'brandnew123',
        ])->assertRedirect(route('users.index'));
        $thisDeviceAfter = session()->all();

        $this->requestFromDevice($thisDeviceAfter)->assertOk();
        $this->requestFromDevice($otherDevice)->assertRedirect('/login');
    }

    public function test_admin_changing_another_users_password_logs_that_user_out(): void
    {
        $admin = User::factory()->create(['password' => 'adminpass123']);
        $victim = User::factory()->create(['password' => 'original123']);

        $victimDevice = $this->loginOnNewDevice($victim, 'original123');
        $adminDevice = $this->loginOnNewDevice($admin, 'adminpass123');

        $this->flushSession();
        app('auth')->forgetGuards();
        $this->withSession($adminDevice)->put("/users/{$victim->id}", [
            'name' => $victim->name,
            'email' => $victim->email,
            'password' => 'resetbyadmin123',
            'password_confirmation' => 'resetbyadmin123',
        ])->assertRedirect(route('users.index'));
        $adminDeviceAfter = session()->all();

        $this->requestFromDevice($victimDevice)->assertRedirect('/login');
        $this->requestFromDevice($adminDeviceAfter)->assertOk();
    }

    public function test_sessions_stay_valid_when_the_password_is_not_changed(): void
    {
        $user = User::factory()->create(['password' => 'original123']);

        $otherDevice = $this->loginOnNewDevice($user, 'original123');
        $thisDevice = $this->loginOnNewDevice($user, 'original123');

        $this->flushSession();
        app('auth')->forgetGuards();
        $this->withSession($thisDevice)->put('/profile', [
            'name' => 'Only the name changed',
            'email' => $user->email,
            'password' => '',
        ])->assertRedirect(route('profile.edit'));

        $this->requestFromDevice($otherDevice)->assertOk();
    }
}
