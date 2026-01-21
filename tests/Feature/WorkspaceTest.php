<?php

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Auth;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('workspace is created on user registration', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertRedirect();

    $user = User::where('email', 'test@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->workspaces)->toHaveCount(1);
    expect($user->workspaces->first()->name)->toBe("Test User's Workspace");
});

test('user is attached as workspace owner', function () {
    $this->post('/register', [
        'name' => 'Owner User',
        'email' => 'owner@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $user = User::where('email', 'owner@example.com')->first();
    $workspace = $user->workspaces->first();

    expect($workspace->pivot->role)->toBe('owner');
    expect($workspace->owner()->id)->toBe($user->id);
});

test('inbound email token is generated and unique', function () {
    $this->post('/register', [
        'name' => 'User One',
        'email' => 'user1@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    Auth::logout();

    $this->post('/register', [
        'name' => 'User Two',
        'email' => 'user2@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $user1 = User::where('email', 'user1@example.com')->first();
    $user2 = User::where('email', 'user2@example.com')->first();

    expect($user1)->not->toBeNull();
    expect($user2)->not->toBeNull();

    $token1 = $user1->workspaces->first()->inbound_email_token;
    $token2 = $user2->workspaces->first()->inbound_email_token;

    expect($token1)->toHaveLength(32);
    expect($token2)->toHaveLength(32);
    expect($token1)->not->toBe($token2);
});

test('user can access their workspace via currentWorkspace', function () {
    $this->post('/register', [
        'name' => 'Access User',
        'email' => 'access@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $user = User::where('email', 'access@example.com')->first();

    expect($user->currentWorkspace())->not->toBeNull();
    expect($user->currentWorkspace()->name)->toBe("Access User's Workspace");
});

test('user can have multiple workspaces', function () {
    $user = User::factory()->create();

    $workspace1 = Workspace::factory()->withOwner($user)->create();
    $workspace2 = Workspace::factory()->create();
    $workspace2->users()->attach($user->id, ['role' => 'member']);

    $user->refresh();

    expect($user->workspaces)->toHaveCount(2);
    expect($user->ownedWorkspaces)->toHaveCount(1);
});
