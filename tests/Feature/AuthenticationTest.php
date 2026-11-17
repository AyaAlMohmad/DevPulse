<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_authentication_pages_render_vue_components(): void
    {
        $this->get('/login')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->component('Auth/Login'));
        $this->get('/register')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->component('Auth/Register'));
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/monitors')->assertRedirect('/login');
    }

    public function test_user_can_register_with_a_hashed_password(): void
    {
        $this->post('/register', [
            'name' => 'Ada',
            'email' => 'ADA@example.com',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
        ])->assertRedirect('/dashboard');

        $user = User::where('email', 'ada@example.com')->sole();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('StrongPassword123!', $user->password));
    }

    public function test_invalid_or_duplicate_registration_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->post('/register', [
            'name' => '',
            'email' => $user->email,
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['name', 'email', 'password']);
        $this->assertGuest();
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create();
        $this->post('/login', ['email' => strtoupper($user->email), 'password' => 'password', 'remember' => true])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->get('/dashboard')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->where('auth.user.id', $user->id)
            ->missing('auth.user.password')
            ->missing('auth.user.remember_token'));
        $this->get('/login')->assertRedirect('/dashboard');
        $this->get('/register')->assertRedirect('/dashboard');
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_invalid_login_is_rejected_and_rate_limited(): void
    {
        $user = User::factory()->create();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        }
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_requires_valid_fields(): void
    {
        $this->post('/login', ['email' => 'invalid', 'remember' => 'invalid'])
            ->assertSessionHasErrors(['email', 'password', 'remember']);
        $this->assertGuest();
    }

    public function test_login_lockout_expires(): void
    {
        $user = User::factory()->create();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        }
        $this->travel(61)->seconds();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/register', [])->assertSessionHasErrors();
        }
        $this->post('/register', [])->assertStatus(429);
    }

    public function test_inertia_receives_validation_errors_after_a_failed_login(): void
    {
        $this->from('/login')->post('/login', [
            'email' => 'nobody@example.com',
            'password' => 'incorrect',
        ])->assertRedirect('/login');
        $this->get('/login')->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Auth/Login')
            ->has('errors.email')
            ->where('auth.user', null));
    }

    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
