<?php

declare(strict_types=1);

use App\Enums\InvoiceState;
use App\Exceptions\InvalidTransitionException;
use App\Services\InvoiceStateMachine;

beforeEach(function () {
    $this->sm = new InvoiceStateMachine;
});

dataset('legal transitions', [
    [InvoiceState::Draft, InvoiceState::Issued],
    [InvoiceState::Draft, InvoiceState::Cancelled],
    [InvoiceState::Issued, InvoiceState::Sent],
    [InvoiceState::Issued, InvoiceState::PartiallyPaid],
    [InvoiceState::Issued, InvoiceState::Paid],
    [InvoiceState::Issued, InvoiceState::Cancelled],
    [InvoiceState::Sent, InvoiceState::PartiallyPaid],
    [InvoiceState::Sent, InvoiceState::Paid],
    [InvoiceState::Sent, InvoiceState::Cancelled],
    [InvoiceState::PartiallyPaid, InvoiceState::Paid],
    [InvoiceState::PartiallyPaid, InvoiceState::Cancelled],
    [InvoiceState::PartiallyPaid, InvoiceState::PartiallyPaid],
]);

dataset('illegal transitions', [
    [InvoiceState::Draft, InvoiceState::Sent],          // must issue first
    [InvoiceState::Draft, InvoiceState::Paid],          // must issue first
    [InvoiceState::Issued, InvoiceState::Draft],        // cannot un-issue
    [InvoiceState::Paid, InvoiceState::Cancelled],      // cannot cancel a paid invoice
    [InvoiceState::Paid, InvoiceState::Issued],         // paid is terminal
    [InvoiceState::Cancelled, InvoiceState::Issued],    // cancelled is terminal
    [InvoiceState::Cancelled, InvoiceState::Draft],     // cancelled is terminal
    [InvoiceState::Sent, InvoiceState::Issued],         // cannot go backwards
]);

test('legal transitions are permitted', function (InvoiceState $from, InvoiceState $to) {
    expect($this->sm->canTransition($from, $to))->toBeTrue();
    $this->sm->assertCanTransition($from, $to); // does not throw
})->with('legal transitions');

test('illegal transitions are rejected', function (InvoiceState $from, InvoiceState $to) {
    expect($this->sm->canTransition($from, $to))->toBeFalse();
    expect(fn () => $this->sm->assertCanTransition($from, $to))
        ->toThrow(InvalidTransitionException::class);
})->with('illegal transitions');

test('paid and cancelled are terminal', function () {
    expect($this->sm->allowedFrom(InvoiceState::Paid))->toBe([])
        ->and($this->sm->allowedFrom(InvoiceState::Cancelled))->toBe([]);
});

test('a paid invoice cannot be cancelled (FR-034)', function () {
    expect($this->sm->canTransition(InvoiceState::Paid, InvoiceState::Cancelled))->toBeFalse();
});

test('the exception carries both states for the 409 body', function () {
    try {
        $this->sm->assertCanTransition(InvoiceState::Paid, InvoiceState::Cancelled);
        $this->fail('expected InvalidTransitionException');
    } catch (InvalidTransitionException $e) {
        expect($e->from)->toBe(InvoiceState::Paid)
            ->and($e->to)->toBe(InvoiceState::Cancelled);
    }
});

test('only draft allows line editing', function () {
    expect(InvoiceState::Draft->allowsLineEditing())->toBeTrue()
        ->and(InvoiceState::Issued->allowsLineEditing())->toBeFalse()
        ->and(InvoiceState::Sent->allowsLineEditing())->toBeFalse()
        ->and(InvoiceState::Paid->allowsLineEditing())->toBeFalse();
});
