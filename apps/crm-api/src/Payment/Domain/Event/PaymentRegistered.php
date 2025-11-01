<?php

declare(strict_types=1);

namespace App\Payment\Domain\Event;

use App\Shared\Domain\Event\AbstractDomainEvent;
use Symfony\Component\Uid\Uuid;

final class PaymentRegistered extends AbstractDomainEvent
{
    public function __construct(
        private readonly Uuid $paymentId,
        private readonly Uuid $loanId,
        private readonly \DateTimeImmutable $dueDate,
        private readonly int $amount,
        private readonly string $currency,
    ) {
        parent::__construct();
    }

    public static function eventName(): string
    {
        return 'payment.registered';
    }

    public function payload(): array
    {
        return [
            'payment_id' => $this->paymentId->toRfc4122(),
            'loan_id' => $this->loanId->toRfc4122(),
            'due_date' => $this->dueDate->format(DATE_ATOM),
            'amount' => $this->amount,
            'currency' => $this->currency,
        ];
    }
}

