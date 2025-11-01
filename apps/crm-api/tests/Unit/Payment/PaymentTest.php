<?php

declare(strict_types=1);

use App\Payment\Domain\Enum\PaymentStatus;
use App\Payment\Domain\Payment;
use App\Shared\Domain\ValueObject\Money;
use Symfony\Component\Uid\Uuid;

test('payment transitions to paid after covering expected amount', function (): void {
    $payment = Payment::schedule(
        Uuid::v7(),
        new \DateTimeImmutable('2024-11-01'),
        Money::fromSubunits(10_000, 'USD'),
    );

    $payment->registerPayment(Money::fromSubunits(10_000, 'USD'));

    expect($payment->status())->toBe(PaymentStatus::Paid);
});

