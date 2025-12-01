<?php

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->authService = new AuthService();
});

test('can register a new user', function () {
    $userData = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
    ];

    $user = $this->authService->register($userData);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('John Doe')
        ->and($user->email)->toBe('john@example.com')
        ->and(Hash::check('password123', $user->password))->toBeTrue();
});

test('can login with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'john@example.com',
        'password' => Hash::make('password123'),
    ]);

    $loggedInUser = $this->authService->login('john@example.com', 'password123');

    expect($loggedInUser)->toBeInstanceOf(User::class)
        ->and($loggedInUser->id)->toBe($user->id);
});

test('returns null with invalid credentials', function () {
    User::factory()->create([
        'email' => 'john@example.com',
        'password' => Hash::make('password123'),
    ]);

    $result = $this->authService->login('john@example.com', 'wrongpassword');

    expect($result)->toBeNull();
});

test('returns null with non-existent email', function () {
    $result = $this->authService->login('nonexistent@example.com', 'password123');

    expect($result)->toBeNull();
});

test('can create token for user', function () {
    $user = User::factory()->create();

    $token = $this->authService->createToken($user);

    expect($token)->toBeString()
        ->and($user->tokens()->count())->toBe(1);
});

test('can logout user by deleting tokens', function () {
    $user = User::factory()->create();
    $user->createToken('test-token');

    expect($user->tokens()->count())->toBe(1);

    $this->authService->logout($user);

    expect($user->tokens()->count())->toBe(0);
});

