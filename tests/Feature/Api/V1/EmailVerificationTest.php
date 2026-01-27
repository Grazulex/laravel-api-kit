<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

describe('Email Verification', function (): void {
    it('verifies email successfully with valid link', function (): void {
        Event::fake();

        $user = User::factory()->create(['email_verified_at' => null]);
        $token = $user->createToken('test-token')->plainTextToken;

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson($verificationUrl);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Email verified successfully',
            ]);

        $this->assertNotNull($user->fresh()->email_verified_at);
        Event::assertDispatched(Verified::class);
    });

    it('returns success if email is already verified', function (): void {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $token = $user->createToken('test-token')->plainTextToken;

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson($verificationUrl);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Email already verified',
            ]);
    });

    it('fails verification without authentication', function (): void {
        $user = User::factory()->create(['email_verified_at' => null]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->postJson($verificationUrl);

        $response->assertStatus(401);
    });

    it('fails verification with invalid signature', function (): void {
        $user = User::factory()->create(['email_verified_at' => null]);
        $token = $user->createToken('test-token')->plainTextToken;

        // Invalid URL without signature
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson(sprintf('/api/v1/email/verify/%d/invalid-hash', $user->id));

        $response->assertStatus(403);
    });
});

describe('Resend Verification Email', function (): void {
    it('resends verification email successfully', function (): void {
        $user = User::factory()->create(['email_verified_at' => null]);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/email/resend', [
                'email' => $user->email,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Verification email sent successfully',
            ]);
    });

    it('fails to resend if email is already verified', function (): void {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/email/resend', [
                'email' => $user->email,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Email already verified',
            ]);
    });

    it('fails with invalid email', function (): void {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/email/resend', [
                'email' => 'nonexistent@example.com',
            ]);

        $response->assertStatus(422);
    });

    it('requires authentication', function (): void {
        $user = User::factory()->create(['email_verified_at' => null]);

        $response = $this->postJson('/api/v1/email/resend', [
            'email' => $user->email,
        ]);

        $response->assertStatus(401);
    });

    it('respects rate limiting', function (): void {
        $user = User::factory()->create(['email_verified_at' => null]);
        $token = $user->createToken('test-token')->plainTextToken;

        // Make 7 requests (limit is 6 per minute)
        for ($i = 0; $i < 7; $i++) {
            $response = $this->withHeader('Authorization', 'Bearer '.$token)
                ->postJson('/api/v1/email/resend', [
                    'email' => $user->email,
                ]);
        }

        // Last request should be rate limited
        $response->assertStatus(429);
    });
});
