<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use Symfony\Component\Uid\Uuid;

abstract class AbstractDomainEvent implements DomainEvent
{
    private readonly Uuid $eventId;
    private readonly \DateTimeImmutable $occurredAt;

    public function __construct(?Uuid $eventId = null, ?\DateTimeImmutable $occurredAt = null)
    {
        $this->eventId = $eventId ?? Uuid::v7();
        $this->occurredAt = $occurredAt ?? new \DateTimeImmutable();
    }

    public function eventId(): Uuid
    {
        return $this->eventId;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    abstract public static function eventName(): string;
}
