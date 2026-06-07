<?php

use App\Enums\SupportTicketCategory;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    Storage::fake('local');
});

test('user can create ticket with valid attachment', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('support.tickets.store'), [
        'subject' => 'مشکل فنی',
        'category' => SupportTicketCategory::Technical->value,
        'message' => 'فایل پیوست را برای بررسی ارسال می‌کنم.',
        'attachment' => UploadedFile::fake()->image('screenshot.png'),
    ])->assertRedirect();

    $message = SupportTicketMessage::query()->first();
    $attachment = SupportTicketAttachment::query()->first();

    expect($message)->not->toBeNull()
        ->and($attachment)->not->toBeNull()
        ->and($attachment->support_ticket_message_id)->toBe($message->id)
        ->and($attachment->original_name)->toBe('screenshot.png');

    Storage::disk('local')->assertExists($attachment->path);
});

test('user can reply with valid attachment', function () {
    $user = User::factory()->create();
    $ticket = SupportTicket::factory()->forUser($user)->open()->create();

    $this->actingAs($user)->post(route('support.tickets.messages.store', $ticket), [
        'body' => 'فایل جدید را پیوست کردم.',
        'attachment' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
    ])->assertRedirect();

    $message = $ticket->messages()->latest('id')->first();
    $attachment = SupportTicketAttachment::query()->first();

    expect($message?->body)->toBe('فایل جدید را پیوست کردم.')
        ->and($attachment)->not->toBeNull()
        ->and($attachment->mime_type)->toBe('application/pdf');

    Storage::disk('local')->assertExists($attachment->path);
});

test('admin can reply with valid attachment', function () {
    $admin = User::factory()->admin()->create();
    $ticket = SupportTicket::factory()->open()->create();

    $this->actingAs($admin)->post(route('admin.support.messages.store', $ticket), [
        'body' => 'فایل راهنما را پیوست کردیم.',
        'attachment' => UploadedFile::fake()->image('guide.webp'),
    ])->assertRedirect();

    $attachment = SupportTicketAttachment::query()->first();

    expect($attachment)->not->toBeNull()
        ->and($attachment->original_name)->toBe('guide.webp');

    Storage::disk('local')->assertExists($attachment->path);
});

test('oversized attachment is rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->from(route('support.index'))->post(route('support.tickets.store'), [
        'subject' => 'فایل بزرگ',
        'category' => SupportTicketCategory::Other->value,
        'message' => 'این فایل بیش از حد مجاز است.',
        'attachment' => UploadedFile::fake()->create('large.zip', 5121, 'application/zip'),
    ])->assertRedirect(route('support.index'))
        ->assertSessionHasErrors(['attachment']);

    expect(SupportTicket::query()->count())->toBe(0)
        ->and(SupportTicketAttachment::query()->count())->toBe(0);
});

test('invalid attachment type is rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->from(route('support.index'))->post(route('support.tickets.store'), [
        'subject' => 'فایل نامعتبر',
        'category' => SupportTicketCategory::Other->value,
        'message' => 'این فایل مجاز نیست.',
        'attachment' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'),
    ])->assertRedirect(route('support.index'))
        ->assertSessionHasErrors(['attachment']);

    expect(SupportTicket::query()->count())->toBe(0);
});

test('ticket owner can download own attachment', function () {
    $user = User::factory()->create();
    $ticket = SupportTicket::factory()->forUser($user)->create();
    $message = SupportTicketMessage::factory()->forTicket($ticket)->fromUser($user)->create();
    $path = sprintf('support-attachments/%d/%d/test.png', $ticket->id, $message->id);
    Storage::disk('local')->put($path, 'fake-image-content');
    $attachment = SupportTicketAttachment::factory()->forMessage($message)->create([
        'path' => $path,
        'original_name' => 'my-file.png',
    ]);

    $this->actingAs($user)
        ->get(route('support.tickets.attachments.download', [$ticket, $attachment]))
        ->assertOk()
        ->assertDownload('my-file.png');
});

test('another user cannot download attachment', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $ticket = SupportTicket::factory()->forUser($owner)->create();
    $message = SupportTicketMessage::factory()->forTicket($ticket)->fromUser($owner)->create();
    $path = sprintf('support-attachments/%d/%d/test.png', $ticket->id, $message->id);
    Storage::disk('local')->put($path, 'fake-image-content');
    $attachment = SupportTicketAttachment::factory()->forMessage($message)->create([
        'path' => $path,
    ]);

    $this->actingAs($otherUser)
        ->get(route('support.tickets.attachments.download', [$ticket, $attachment]))
        ->assertForbidden();
});

test('admin can download any attachment', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();
    $ticket = SupportTicket::factory()->forUser($owner)->create();
    $message = SupportTicketMessage::factory()->forTicket($ticket)->fromUser($owner)->create();
    $path = sprintf('support-attachments/%d/%d/test.pdf', $ticket->id, $message->id);
    Storage::disk('local')->put($path, 'fake-pdf-content');
    $attachment = SupportTicketAttachment::factory()->forMessage($message)->create([
        'path' => $path,
        'original_name' => 'invoice.pdf',
        'mime_type' => 'application/pdf',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.support.attachments.download', [$ticket, $attachment]))
        ->assertOk()
        ->assertDownload('invoice.pdf');
});

test('attachment download does not expose public storage path', function () {
    $user = User::factory()->create();
    $ticket = SupportTicket::factory()->forUser($user)->create();
    $message = SupportTicketMessage::factory()->forTicket($ticket)->fromUser($user)->create();
    $path = sprintf('support-attachments/%d/%d/secret.png', $ticket->id, $message->id);
    Storage::disk('local')->put($path, 'secret-content');
    $attachment = SupportTicketAttachment::factory()->forMessage($message)->create([
        'path' => $path,
        'original_name' => 'secret.png',
    ]);

    $response = $this->actingAs($user)
        ->get(route('support.tickets.attachments.download', [$ticket, $attachment]));

    $response->assertOk();

    $content = $response->getContent() ?: '';
    expect($content)->not->toContain('/storage/')
        ->and($content)->not->toContain('support-attachments/')
        ->and($response->headers->get('Content-Disposition'))->toContain('secret.png');
});

test('attachment from another ticket returns not found', function () {
    $user = User::factory()->create();
    $ticket = SupportTicket::factory()->forUser($user)->create();
    $otherTicket = SupportTicket::factory()->forUser($user)->create();
    $message = SupportTicketMessage::factory()->forTicket($otherTicket)->fromUser($user)->create();
    $path = sprintf('support-attachments/%d/%d/other.png', $otherTicket->id, $message->id);
    Storage::disk('local')->put($path, 'content');
    $attachment = SupportTicketAttachment::factory()->forMessage($message)->create([
        'path' => $path,
    ]);

    $this->actingAs($user)
        ->get(route('support.tickets.attachments.download', [$ticket, $attachment]))
        ->assertNotFound();
});
