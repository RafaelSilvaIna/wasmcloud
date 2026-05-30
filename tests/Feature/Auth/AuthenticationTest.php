<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_unique_email_and_phone(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Rafael Silva',
            'email' => 'rafael@example.com',
            'phone' => '(11) 99999-0000',
            'password' => 'Senha123',
            'password_confirmation' => 'Senha123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'rafael@example.com',
            'phone' => '11999990000',
        ]);
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'rafael@example.com']);

        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Rafael Silva',
            'email' => 'rafael@example.com',
            'phone' => '(11) 99999-0001',
            'password' => 'Senha123',
            'password_confirmation' => 'Senha123',
        ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('email');
    }

    public function test_phone_must_be_unique(): void
    {
        User::factory()->create(['phone' => '11999990000']);

        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Rafael Silva',
            'email' => 'rafael@example.com',
            'phone' => '(11) 99999-0000',
            'password' => 'Senha123',
            'password_confirmation' => 'Senha123',
        ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('phone');
    }

    public function test_user_can_login_with_email_and_password(): void
    {
        User::factory()->create([
            'email' => 'rafael@example.com',
            'password' => 'Senha123',
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'rafael@example.com',
            'password' => 'Senha123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_home_ctas_change_when_user_is_authenticated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertSee('Dashboard');
        $response->assertSee('Acessar dashboard');
        $response->assertSee('data-authenticated="true"', false);
    }
}
