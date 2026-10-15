<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login()
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_login_screen_can_be_rendered()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('IoT Meter Monitoring');
        $response->assertSee('assets/1.jpg');
    }

    public function test_sidebar_renders_logo_for_authenticated_user()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('assets/1.jpg');
    }

    public function test_user_can_authenticate_using_username()
    {
        $user = User::factory()->create([
            'username' => 'operator1',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'login' => 'operator1',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard');
    }

    public function test_user_cannot_authenticate_with_invalid_password()
    {
        $user = User::factory()->create([
            'username' => 'operator1',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'login' => 'operator1',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('login');
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    public function test_user_can_update_profile_and_username()
    {
        $user = User::factory()->create([
            'name' => 'Operator Awal',
            'username' => 'operator_awal',
            'email' => 'awal@example.com',
        ]);

        $response = $this->actingAs($user)->put('/settings/profile', [
            'name' => 'Operator Baru',
            'username' => 'operator_baru',
            'email' => 'baru@example.com',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Operator Baru',
            'username' => 'operator_baru',
            'email' => 'baru@example.com',
        ]);
    }

    public function test_user_can_update_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword'),
        ]);

        $response = $this->actingAs($user)->put('/settings/password', [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHas('success');
        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }
}
