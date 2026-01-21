<?php

use App\Enums\InboxItemStatus;
use App\Models\InboxItem;
use App\Models\User;
use App\Models\Workspace;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->withOwner($this->user)->create();
});

test('can list inbox items for own workspace', function () {
    InboxItem::factory()->count(3)->create(['workspace_id' => $this->workspace->id]);
    InboxItem::factory()->count(2)->create(); // Different workspace

    $response = $this->actingAs($this->user)
        ->getJson('/api/inbox-items');

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(3);
});

test('can create manual inbox item', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/inbox-items', [
            'raw_subject' => 'Test Subject',
            'raw_content' => 'Test content for inbox item',
        ]);

    $response->assertSuccessful();
    expect($response->json('data.source'))->toBe('manual');
    expect($response->json('data.raw_subject'))->toBe('Test Subject');
    expect($response->json('data.status'))->toBe('new');

    $this->assertDatabaseHas('inbox_items', [
        'workspace_id' => $this->workspace->id,
        'raw_subject' => 'Test Subject',
    ]);
});

test('can view inbox item', function () {
    $item = InboxItem::factory()->create(['workspace_id' => $this->workspace->id]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/inbox-items/{$item->id}");

    $response->assertSuccessful();
    expect($response->json('data.id'))->toBe($item->id);
});

test('can update inbox item status', function () {
    $item = InboxItem::factory()->create(['workspace_id' => $this->workspace->id]);

    $response = $this->actingAs($this->user)
        ->putJson("/api/inbox-items/{$item->id}", [
            'status' => 'archived',
        ]);

    $response->assertSuccessful();
    expect($response->json('data.status'))->toBe('archived');

    $item->refresh();
    expect($item->status)->toBe(InboxItemStatus::Archived);
});

test('cannot delete inbox items', function () {
    $item = InboxItem::factory()->create(['workspace_id' => $this->workspace->id]);

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/inbox-items/{$item->id}");

    $response->assertStatus(405); // Method not allowed
});

test('cannot access other workspace items', function () {
    $otherWorkspace = Workspace::factory()->create();
    $item = InboxItem::factory()->create(['workspace_id' => $otherWorkspace->id]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/inbox-items/{$item->id}");

    $response->assertForbidden();
});

test('can filter inbox items by status', function () {
    InboxItem::factory()->count(2)->create([
        'workspace_id' => $this->workspace->id,
        'status' => InboxItemStatus::New,
    ]);
    InboxItem::factory()->count(1)->archived()->create([
        'workspace_id' => $this->workspace->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/inbox-items?status=new');

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(2);
});
