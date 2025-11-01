<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use Symfony\Component\Uid\Uuid;

/**
 * Immutable domain event contract.
 */
interface DomainEvent
{
    public function eventId(): Uuid;

    public function occurredAt(): \DateTimeImmutable;

    public static function eventName(): string;

    public function payload(): array;
}
