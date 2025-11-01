<?php

declare(strict_types=1);

namespace App\Customer\Domain\Event;

use App\Customer\Domain\Enum\CustomerStatus;
use App\Shared\Domain\Event\AbstractDomainEvent;
use Symfony\Component\Uid\Uuid;

final class CustomerStatusChanged extends AbstractDomainEvent
{
    public function __construct(
        private readonly Uuid $customerId,
        private readonly CustomerStatus $previousStatus,
        private readonly CustomerStatus $newStatus,
    ) {
        parent::__construct();
    }

    public static function eventName(): string
    {
        return 'customer.status_changed';
    }

    public function payload(): array
    {
        return [
            'customer_id' => $this->customerId->toRfc4122(),
            'previous_status' => $this->previousStatus->value,
            'new_status' => $this->newStatus->value,
        ];
    }
}
