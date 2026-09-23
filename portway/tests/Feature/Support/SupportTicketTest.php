<?php

use App\Models\SupportTicket;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    seedRolesAndPermissions();
});

it('lets a user open a support ticket with an initial message', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Support\TicketsIndex::class)
        ->call('openCreate')
        ->set('subject', 'My site will not load')
        ->set('category', 'technical')
        ->set('priority', 'high')
        ->set('body', 'I get a 500 error whenever I visit my website.')
        ->call('create');

    $ticket = SupportTicket::where('user_id', $user->id)->first();

    expect($ticket)->not->toBeNull();
    expect($ticket->subject)->toBe('My site will not load');
    expect($ticket->messages)->toHaveCount(1);
    expect($ticket->messages->first()->is_staff_reply)->toBeFalse();
});

it('does not let one user read another user\'s support ticket', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $ticket = SupportTicket::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($stranger);

    Livewire::test(\App\Livewire\Support\TicketDetail::class, ['ticket' => $ticket])
        ->assertStatus(403);
});

it('marks a ticket as answered when staff reply, and pending when the user replies again', function () {
    $user = User::factory()->create();
    $staff = User::factory()->create();
    $staff->assignRole('Support');

    $ticket = SupportTicket::factory()->create(['user_id' => $user->id, 'status' => 'open']);

    $this->actingAs($staff);
    Livewire::test(\App\Livewire\Support\TicketDetail::class, ['ticket' => $ticket])
        ->set('reply', 'Thanks for reaching out — looking into it now.')
        ->call('sendReply');

    expect($ticket->refresh()->status)->toBe('answered');

    $this->actingAs($user);
    Livewire::test(\App\Livewire\Support\TicketDetail::class, ['ticket' => $ticket])
        ->set('reply', 'Still happening, any update?')
        ->call('sendReply');

    expect($ticket->refresh()->status)->toBe('pending');
});

it('lets the ticket owner close and reopen their own ticket', function () {
    $user = User::factory()->create();
    $ticket = SupportTicket::factory()->create(['user_id' => $user->id, 'status' => 'open']);

    $this->actingAs($user);

    Livewire::test(\App\Livewire\Support\TicketDetail::class, ['ticket' => $ticket])
        ->call('close');

    expect($ticket->refresh()->status)->toBe('closed');
});
