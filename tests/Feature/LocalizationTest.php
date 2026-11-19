<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_landing_page_is_localized_in_all_supported_languages(): void
    {
        foreach (['en' => 'Sign in', 'ar' => 'تسجيل الدخول', 'fr' => 'Se connecter'] as $locale => $label) {
            $this->withSession(['locale' => $locale])->get('/')
                ->assertOk()->assertInertia(fn ($page) => $page->component('Welcome')
                ->where('locale', $locale)->where('translations.Sign in', $label));
        }
    }

    public function test_language_switch_is_saved_in_the_session_and_returns_to_the_requested_page(): void
    {
        $this->post('/locale', ['locale' => 'ar', 'redirect' => 'login'])
            ->assertRedirect('/login')->assertSessionHas('locale', 'ar');
        $this->get('/login')->assertSee('lang="ar"', false)->assertSee('dir="rtl"', false);
        $this->post('/locale', ['locale' => 'fr', 'redirect' => 'home'])->assertRedirect('/');
        $this->get('/')->assertSee('lang="fr"', false)->assertSee('dir="ltr"', false);
    }

    public function test_unsupported_languages_and_external_redirects_are_rejected(): void
    {
        $this->postJson('/locale', ['locale' => '../../en', 'redirect' => 'home'])
            ->assertUnprocessable()->assertJsonValidationErrors('locale');
        $this->postJson('/locale', ['locale' => 'fr', 'redirect' => 'https://example.com'])
            ->assertUnprocessable()->assertJsonValidationErrors('redirect');
    }

    public function test_validation_and_authentication_errors_use_the_selected_language(): void
    {
        $this->withSession(['locale' => 'ar'])->postJson('/login', [])
            ->assertUnprocessable()->assertJsonPath('errors.email.0', 'حقل البريد الإلكتروني مطلوب.');
        $this->withSession(['locale' => 'fr'])->postJson('/login', ['email' => 'missing@example.com', 'password' => 'invalid'])
            ->assertUnprocessable()->assertJsonPath('errors.email.0', 'Ces identifiants ne correspondent pas à nos données.');
    }

    public function test_dashboard_keeps_api_status_values_and_localizes_flash_messages(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['locale' => 'fr'])->post('/monitors', [
            'name' => 'Health', 'url' => 'https://example.com/health', 'interval_seconds' => 60,
        ])->assertRedirect('/dashboard')->assertSessionHas('success', 'Sonde créée. Utilisez « Vérifier » pour lancer son premier contrôle.');
        $this->get('/dashboard')->assertInertia(fn ($page) => $page->component('Dashboard')
            ->where('locale', 'fr')->where('monitors.data.0.status', 'unknown'));
    }

    public function test_translation_catalogs_have_identical_keys_and_placeholders(): void
    {
        $english = json_decode(file_get_contents(lang_path('en.json')), true, flags: JSON_THROW_ON_ERROR);
        foreach (['ar', 'fr'] as $locale) {
            $catalog = json_decode(file_get_contents(lang_path($locale.'.json')), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame(array_keys($english), array_keys($catalog));
            foreach ($english as $key => $value) {
                $this->assertNotSame('', $catalog[$key]);
                preg_match_all('/:[a-z_]+/', $value, $expected);
                preg_match_all('/:[a-z_]+/', $catalog[$key], $actual);
                sort($expected[0]);
                sort($actual[0]);
                $this->assertSame($expected[0], $actual[0], $locale.': '.$key);
            }
        }
    }

    public function test_language_survives_login_and_logout_without_preserving_authentication(): void
    {
        $user = User::factory()->create(['password' => Hash::make('secure-password')]);
        $this->withSession(['locale' => 'ar'])->post('/login', ['email' => $user->email, 'password' => 'secure-password'])
            ->assertRedirect('/dashboard')->assertSessionHas('locale', 'ar');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/login')->assertSessionHas('locale', 'ar');
        $this->assertGuest();
        $this->get('/login')->assertInertia(fn ($page) => $page->where('locale', 'ar'));
    }

    public function test_invalid_or_missing_session_locales_do_not_leak_previous_request_language(): void
    {
        app()->setLocale('fr');
        $this->get('/')->assertInertia(fn ($page) => $page->where('locale', 'en'));
        $this->withSession(['locale' => '../../fr'])->get('/')
            ->assertInertia(fn ($page) => $page->where('locale', 'en'));
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
