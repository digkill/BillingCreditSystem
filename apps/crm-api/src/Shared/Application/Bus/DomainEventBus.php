<?php

declare(strict_types=1);

namespace App\Shared\Application\Bus;

use App\Shared\Domain\Event\DomainEvent;

interface DomainEventBus
{
    /**
     * @param DomainEvent[] $events
     */
    public function publish(DomainEvent ...$events): void;
}
