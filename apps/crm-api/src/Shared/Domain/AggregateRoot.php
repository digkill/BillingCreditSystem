<?php

declare(strict_types=1);

namespace App\Shared\Domain;

use App\Shared\Domain\Event\RecordsEvents;

abstract class AggregateRoot
{
    use RecordsEvents;
}
