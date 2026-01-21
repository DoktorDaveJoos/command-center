<?php

use App\Enums\InboxItemSource;
use App\Models\InboxItem;
use App\Models\Workspace;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->workspace = Workspace::factory()->create([
        'inbound_email_token' => 'test123token',
    ]);
});

test('valid webhook creates inbox item', function () {
    $response = $this->postJson('/webhooks/resend/inbound', [
        'to' => 'inbox+test123token@inbox.example.com',
        'subject' => 'Test Email Subject',
        'text' => 'This is the email content.',
        'html' => '<p>This is the email content.</p>',
        'from' => 'sender@example.com',
    ]);

    $response->assertOk();
    $response->assertJson(['status' => 'ok']);

    $this->assertDatabaseHas('inbox_items', [
        'workspace_id' => $this->workspace->id,
        'source' => InboxItemSource::Email->value,
        'raw_subject' => 'Test Email Subject',
    ]);

    $item = InboxItem::first();
    expect($item->raw_content)->toBe('<p>This is the email content.</p>');
});

test('invalid workspace token returns 200 but no item created', function () {
    $response = $this->postJson('/webhooks/resend/inbound', [
        'to' => 'inbox+invalidtoken@inbox.example.com',
        'subject' => 'Test Email Subject',
        'text' => 'This is the email content.',
    ]);

    $response->assertOk();
    $response->assertJson(['status' => 'ok']);

    $this->assertDatabaseCount('inbox_items', 0);
});

test('missing to address returns 200 but no item created', function () {
    $response = $this->postJson('/webhooks/resend/inbound', [
        'subject' => 'Test Email Subject',
        'text' => 'This is the email content.',
    ]);

    $response->assertOk();
    $response->assertJson(['status' => 'ok']);

    $this->assertDatabaseCount('inbox_items', 0);
});

test('to address without token returns 200 but no item created', function () {
    $response = $this->postJson('/webhooks/resend/inbound', [
        'to' => 'regular@inbox.example.com',
        'subject' => 'Test Email Subject',
        'text' => 'This is the email content.',
    ]);

    $response->assertOk();
    $response->assertJson(['status' => 'ok']);

    $this->assertDatabaseCount('inbox_items', 0);
});

test('invalid signature returns 401 when secret is configured', function () {
    config(['services.resend.webhook_secret' => base64_encode('test-secret')]);

    $response = $this->postJson('/webhooks/resend/inbound', [
        'to' => 'inbox+test123token@inbox.example.com',
        'subject' => 'Test Email Subject',
        'text' => 'This is the email content.',
    ], [
        'svix-signature' => 'v1,invalid-signature',
        'svix-timestamp' => (string) time(),
        'svix-id' => 'msg_test123',
    ]);

    $response->assertStatus(401);
    $response->assertJson(['error' => 'Invalid signature']);
});

test('prefers html content over text', function () {
    $response = $this->postJson('/webhooks/resend/inbound', [
        'to' => 'inbox+test123token@inbox.example.com',
        'subject' => 'Test Email Subject',
        'text' => 'Plain text content',
        'html' => '<p>HTML content</p>',
    ]);

    $response->assertOk();

    $item = InboxItem::first();
    expect($item->raw_content)->toBe('<p>HTML content</p>');
});

test('falls back to text when no html', function () {
    $response = $this->postJson('/webhooks/resend/inbound', [
        'to' => 'inbox+test123token@inbox.example.com',
        'subject' => 'Test Email Subject',
        'text' => 'Plain text content',
    ]);

    $response->assertOk();

    $item = InboxItem::first();
    expect($item->raw_content)->toBe('Plain text content');
});

test('handles array of recipients', function () {
    $response = $this->postJson('/webhooks/resend/inbound', [
        'to' => ['inbox+test123token@inbox.example.com', 'other@example.com'],
        'subject' => 'Test Email Subject',
        'text' => 'This is the email content.',
    ]);

    $response->assertOk();

    $this->assertDatabaseHas('inbox_items', [
        'workspace_id' => $this->workspace->id,
        'raw_subject' => 'Test Email Subject',
    ]);
});

test('handles missing subject', function () {
    $response = $this->postJson('/webhooks/resend/inbound', [
        'to' => 'inbox+test123token@inbox.example.com',
        'text' => 'This is the email content.',
    ]);

    $response->assertOk();

    $item = InboxItem::first();
    expect($item->raw_subject)->toBeNull();
});
