<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_shown_to_guests(): void
    {
        $this->get('/login')->assertOk()->assertSee('อีเมล')->assertSee('รหัสผ่าน');
    }

    public function test_valid_credentials_redirect_to_dashboard(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);

        $this->post('/login', ['email' => $user->email, 'password' => 'secret123'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_show_an_error_and_stay_guest(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);

        $this->from('/login')
            ->post('/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    #[DataProvider('protectedUrls')]
    public function test_guests_cannot_reach_protected_pages(string $method, string $url): void
    {
        $this->call($method, $url)->assertRedirect('/login');
    }

    public static function protectedUrls(): array
    {
        return [
            'dashboard' => ['GET', '/dashboard'],
            'users index' => ['GET', '/users'],
            'users create' => ['GET', '/users/create'],
            'users store' => ['POST', '/users'],
            'users edit' => ['GET', '/users/1/edit'],
            'users destroy' => ['DELETE', '/users/1'],
            'profile edit' => ['GET', '/profile/edit'],
            'profile update' => ['PUT', '/profile'],
        ];
    }

    public function test_dashboard_has_users_link_and_logout_button(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee(route('users.index'), false)
            ->assertSee('ออกจากระบบ');
    }

    public function test_logout_ends_the_session(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_logged_in_users_are_redirected_away_from_login(): void
    {
        $this->actingAs(User::factory()->create())->get('/login')->assertRedirect();
    }
}
