<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash, Notification, Password};
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LogoutAndAuthThemeTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email = 'auth-theme@test.local', array $attributes = []): User
    {
        return User::create(array_merge(['name' => 'Auth Theme User', 'email' => $email, 'email_verified_at' => now(), 'password' => Hash::make('Old!Password123'), 'role' => 'user', 'account_type' => 'photographer', 'status' => 'active', 'onboarding_completed_at' => now(), 'plan' => 'basic'], $attributes));
    }

    public function test_logout_is_native_post_destroys_authentication_and_redirects_home(): void
    {
        $user = $this->user();
        $this->actingAs($user)->post(route('logout'))->assertRedirect('/');
        $this->assertGuest();
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_every_logout_control_is_a_native_form_and_not_modal_or_ajax_navigation(): void
    {
        $vue = file_get_contents(resource_path('js/Layouts/AppShell.vue'));
        $this->assertStringContainsString('<form method="POST" action="/logout">', $vue);
        $this->assertStringNotContainsString("router.post('/logout')", $vue);
        foreach (['layouts/app.blade.php', 'admin/layout.blade.php', 'super-admin/layout.blade.php'] as $view) {
            $source = file_get_contents(resource_path('views/'.$view));
            $this->assertStringContainsString("route('logout')", $source);
            foreach (['data-modal', 'data-popup', 'data-remote', 'data-ajax'] as $attribute) $this->assertStringNotContainsString($attribute, $source);
        }
    }

    public function test_login_register_forgot_and_reset_reuse_the_common_auth_shell(): void
    {
        $this->get(route('login'))->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
        $this->get(route('register'))->assertInertia(fn (Assert $page) => $page->component('Auth/Register'));
        $this->get(route('password.request'))->assertInertia(fn (Assert $page) => $page->component('Auth/ForgotPassword'));
        $this->get(route('password.reset', ['token' => 'theme-token', 'email' => 'person@test.local']))->assertInertia(fn (Assert $page) => $page->component('Auth/ResetPassword')->where('token', 'theme-token'));
        foreach (['Login.vue', 'Register.vue', 'ForgotPassword.vue', 'ResetPassword.vue'] as $page) $this->assertStringContainsString('AuthShell', file_get_contents(resource_path('js/Pages/Auth/'.$page)));
        $this->assertStringContainsString("@extends('layouts.auth')", file_get_contents(resource_path('views/auth/otp.blade.php')));
    }

    public function test_forgot_password_is_non_enumerating_and_reset_token_updates_password(): void
    {
        Notification::fake();
        $user = $this->user('password-reset@test.local');
        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('success');
        Notification::assertSentTo($user, ResetPassword::class);
        $this->post(route('password.email'), ['email' => 'missing@test.local'])->assertSessionHas('success');
        $token = Password::createToken($user);
        $this->post(route('password.update'), ['token' => $token, 'email' => $user->email, 'password' => 'New!SecurePassword456', 'password_confirmation' => 'New!SecurePassword456'])->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('New!SecurePassword456', $user->fresh()->password));
    }

    public function test_authenticated_and_sensitive_auth_pages_are_not_browser_cached(): void
    {
        $user = $this->user('cache-auth@test.local');
        $this->actingAs($user)->get(route('dashboard'))->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private');
        auth()->logout();
        $this->withSession(['otp.channel' => 'email', 'otp.destination' => $user->email])->get(route('otp.show'))->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private');
    }
}
