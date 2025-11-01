<?php

declare(strict_types=1);

namespace App\Customer\Domain\Event;

use App\Shared\Domain\Event\AbstractDomainEvent;
use Symfony\Component\Uid\Uuid;

final class CustomerRegistered extends AbstractDomainEvent
{
    public function __construct(
        private readonly Uuid $customerId,
        private readonly string $fullName,
        private readonly string $email,
    ) {
        parent::__construct();
    }

    public static function eventName(): string
    {
        return 'customer.registered';
    }

    public function customerId(): Uuid
    {
        return $this->customerId;
    }

    public function payload(): array
    {
        return [
            'customer_id' => $this->customerId->toRfc4122(),
            'full_name' => $this->fullName,
            'email' => $this->email,
        ];
    }
}
