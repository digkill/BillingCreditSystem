<?php

declare(strict_types=1);

namespace App\Payment\Domain\Event;

use App\Payment\Domain\Enum\PaymentStatus;
use App\Shared\Domain\Event\AbstractDomainEvent;
use Symfony\Component\Uid\Uuid;

final class PaymentStatusChanged extends AbstractDomainEvent
{
    public function __construct(
        private readonly Uuid $paymentId,
        private readonly PaymentStatus $previous,
        private readonly PaymentStatus $current,
    ) {
        parent::__construct();
    }

    public static function eventName(): string
    {
        return 'payment.status_changed';
    }

    public function payload(): array
    {
        return [
            'payment_id' => $this->paymentId->toRfc4122(),
            'previous_status' => $this->previous->value,
            'current_status' => $this->current->value,
        ];
    }
}

