<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->actingAs($this->admin);
    }

    public function test_index_lists_users_in_a_table(): void
    {
        $other = User::factory()->create(['name' => 'สมชาย ใจดี', 'email' => 'somchai@example.com']);

        $this->get('/users')
            ->assertOk()
            ->assertSee('สมชาย ใจดี')
            ->assertSee('somchai@example.com')
            ->assertSee($other->created_at->format('d/m/Y H:i'));
    }

    public function test_user_name_cannot_break_out_of_the_delete_confirm_handler(): void
    {
        // Browsers HTML-decode attribute values before running them as JavaScript,
        // so a name containing a quote must not be able to terminate the JS string.
        User::factory()->create(['name' => "x');alert(document.cookie);//"]);

        $html = $this->get('/users')->assertOk()->getContent();

        preg_match_all('/onsubmit="([^"]*)"/', $html, $matches);
        $this->assertNotEmpty($matches[1]);

        foreach ($matches[1] as $handler) {
            $this->assertStringNotContainsString("');alert(", html_entity_decode($handler, ENT_QUOTES));
        }
    }

    public function test_create_stores_user_with_hashed_password(): void
    {
        $this->post('/users', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('users.index'));

        $user = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertNotSame('password123', $user->password);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertNotNull($user->created_at);
    }

    public function test_create_validates_input(): void
    {
        $this->post('/users', [])->assertSessionHasErrors(['name', 'email', 'password']);

        $this->post('/users', [
            'name' => 'Dup',
            'email' => $this->admin->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->post('/users', [
            'name' => 'Mismatch',
            'email' => 'mismatch@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors('password');
    }

    public function test_update_changes_name_and_email(): void
    {
        $user = User::factory()->create();

        $this->put("/users/{$user->id}", ['name' => 'Renamed', 'email' => 'renamed@example.com'])
            ->assertRedirect(route('users.index'));

        $this->assertSame('Renamed', $user->fresh()->name);
        $this->assertSame('renamed@example.com', $user->fresh()->email);
    }

    public function test_update_with_blank_password_keeps_current_password(): void
    {
        $user = User::factory()->create(['password' => 'original123']);

        $this->put("/users/{$user->id}", [
            'name' => $user->name,
            'email' => $user->email,
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('users.index'));

        $this->assertTrue(Hash::check('original123', $user->fresh()->password));
    }

    public function test_update_can_change_password(): void
    {
        $user = User::factory()->create(['password' => 'original123']);

        $this->put("/users/{$user->id}", [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'brandnew123',
            'password_confirmation' => 'brandnew123',
        ])->assertRedirect(route('users.index'));

        $this->assertTrue(Hash::check('brandnew123', $user->fresh()->password));
    }

    public function test_update_rejects_email_taken_by_someone_else_but_allows_own(): void
    {
        $user = User::factory()->create();

        $this->put("/users/{$user->id}", ['name' => 'X', 'email' => $this->admin->email])
            ->assertSessionHasErrors('email');

        $this->put("/users/{$user->id}", ['name' => 'X', 'email' => $user->email])
            ->assertSessionHasNoErrors();
    }

    public function test_destroy_soft_deletes_user(): void
    {
        $user = User::factory()->create();

        $this->delete("/users/{$user->id}")->assertRedirect(route('users.index'));

        $this->assertSoftDeleted($user);
        $this->assertNotNull(User::withTrashed()->find($user->id)->deleted_at);
    }

    public function test_soft_deleted_user_is_hidden_from_the_list(): void
    {
        $user = User::factory()->create(['name' => 'Gone Person']);
        $this->get('/users')->assertSee('Gone Person');

        $this->delete("/users/{$user->id}");

        $this->get('/users')->assertDontSee('Gone Person');
    }

    public function test_soft_deleted_user_cannot_log_in(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);
        $this->delete("/users/{$user->id}");
        auth()->logout();

        $this->post('/login', ['email' => $user->email, 'password' => 'secret123'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_soft_deleted_users_email_cannot_be_reused(): void
    {
        $user = User::factory()->create();
        $this->delete("/users/{$user->id}");

        $this->post('/users', [
            'name' => 'Newcomer',
            'email' => $user->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('email');
    }

    public function test_editing_a_soft_deleted_user_returns_404(): void
    {
        $user = User::factory()->create();
        $this->delete("/users/{$user->id}");

        $this->get("/users/{$user->id}/edit")->assertNotFound();
    }

    public function test_cannot_delete_yourself(): void
    {
        $this->delete("/users/{$this->admin->id}")
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('error');

        $this->assertModelExists($this->admin);
    }
}