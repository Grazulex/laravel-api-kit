<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

describe('Forgot Password', function (): void {
    it('sends reset link successfully', function (): void {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Password reset link sent to your email',
            ]);
    });

    it('fails with non-existent email', function (): void {
        $response = $this->postJson('/api/v1/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(422);
    });

    it('respects rate limiting', function (): void {
        $user = User::factory()->create();

        // Make 7 requests (limit is 6 per minute)
        for ($i = 0; $i < 7; $i++) {
            $response = $this->postJson('/api/v1/forgot-password', [
                'email' => $user->email,
            ]);
        }

        // Last request should be rate limited
        $response->assertStatus(429);
    });
});

describe('Reset Password', function (): void {
    it('resets password successfully with valid token', function (): void {
        $user = User::factory()->create();

        $token = Password::createToken($user);

        $response = $this->postJson('/api/v1/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Password reset successfully',
            ]);

        // Verify password was changed
        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));

        // Verify all tokens were deleted
        $this->assertDatabaseCount('personal_access_tokens', 0);
    });

    it('fails with invalid token', function (): void {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/reset-password', [
            'email' => $user->email,
            'token' => 'invalid-token',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid or expired reset token',
            ]);
    });

    it('fails with mismatched passwords', function (): void {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $response = $this->postJson('/api/v1/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword',
        ]);

        $response->assertStatus(422);
    });

    it('fails with non-existent email', function (): void {
        $response = $this->postJson('/api/v1/reset-password', [
            'email' => 'nonexistent@example.com',
            'token' => 'some-token',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'User not found',
            ]);
    });
});
