<?php

use App\Enums\SuggestionStatus;
use App\Models\Extraction;
use App\Models\InboxItem;
use App\Models\Suggestion;
use App\Models\User;
use App\Models\Workspace;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->withOwner($this->user)->create();
    $this->inboxItem = InboxItem::factory()->create(['workspace_id' => $this->workspace->id]);
    $this->extraction = Extraction::factory()->create(['inbox_item_id' => $this->inboxItem->id]);
});

test('can list suggestions for own workspace', function () {
    Suggestion::factory()->count(3)->create(['extraction_id' => $this->extraction->id]);

    // Create suggestions for different workspace
    $otherWorkspace = Workspace::factory()->create();
    $otherInboxItem = InboxItem::factory()->create(['workspace_id' => $otherWorkspace->id]);
    $otherExtraction = Extraction::factory()->create(['inbox_item_id' => $otherInboxItem->id]);
    Suggestion::factory()->count(2)->create(['extraction_id' => $otherExtraction->id]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/suggestions');

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(3);
});

test('can view a suggestion', function () {
    $suggestion = Suggestion::factory()->create(['extraction_id' => $this->extraction->id]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/suggestions/{$suggestion->id}");

    $response->assertSuccessful();
    expect($response->json('data.id'))->toBe($suggestion->id);
    expect($response->json('data.type'))->toBe($suggestion->type->value);
    expect($response->json('data.status'))->toBe(SuggestionStatus::Proposed->value);
});

test('can accept a suggestion', function () {
    $suggestion = Suggestion::factory()->create(['extraction_id' => $this->extraction->id]);

    $response = $this->actingAs($this->user)
        ->postJson("/api/suggestions/{$suggestion->id}/accept");

    $response->assertSuccessful();
    expect($response->json('data.status'))->toBe(SuggestionStatus::Accepted->value);

    $suggestion->refresh();
    expect($suggestion->status)->toBe(SuggestionStatus::Accepted);
});

test('can reject a suggestion', function () {
    $suggestion = Suggestion::factory()->create(['extraction_id' => $this->extraction->id]);

    $response = $this->actingAs($this->user)
        ->postJson("/api/suggestions/{$suggestion->id}/reject");

    $response->assertSuccessful();
    expect($response->json('data.status'))->toBe(SuggestionStatus::Rejected->value);

    $suggestion->refresh();
    expect($suggestion->status)->toBe(SuggestionStatus::Rejected);
});

test('cannot access suggestions from other workspace', function () {
    $otherWorkspace = Workspace::factory()->create();
    $otherInboxItem = InboxItem::factory()->create(['workspace_id' => $otherWorkspace->id]);
    $otherExtraction = Extraction::factory()->create(['inbox_item_id' => $otherInboxItem->id]);
    $suggestion = Suggestion::factory()->create(['extraction_id' => $otherExtraction->id]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/suggestions/{$suggestion->id}");

    $response->assertForbidden();
});

test('can filter suggestions by status', function () {
    Suggestion::factory()->count(2)->create([
        'extraction_id' => $this->extraction->id,
        'status' => SuggestionStatus::Proposed,
    ]);
    Suggestion::factory()->accepted()->create([
        'extraction_id' => $this->extraction->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/suggestions?status=proposed');

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(2);
});

test('can filter suggestions by type', function () {
    Suggestion::factory()->event()->count(2)->create([
        'extraction_id' => $this->extraction->id,
    ]);
    Suggestion::factory()->task()->create([
        'extraction_id' => $this->extraction->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/suggestions?type=event');

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(2);
});

test('suggestion model can access inbox item through extraction', function () {
    $suggestion = Suggestion::factory()->create(['extraction_id' => $this->extraction->id]);

    expect($suggestion->inboxItem())->not->toBeNull();
    expect($suggestion->inboxItem()->id)->toBe($this->inboxItem->id);
});

test('suggestion scopes work correctly', function () {
    Suggestion::factory()->count(2)->create([
        'extraction_id' => $this->extraction->id,
        'status' => SuggestionStatus::Proposed,
    ]);
    Suggestion::factory()->accepted()->create([
        'extraction_id' => $this->extraction->id,
    ]);

    expect(Suggestion::proposed()->count())->toBe(2);
    expect(Suggestion::forWorkspace($this->workspace->id)->count())->toBe(3);
});
