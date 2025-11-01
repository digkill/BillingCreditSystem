<?php

declare(strict_types=1);

namespace App\Customer\Domain\Enum;

enum CustomerStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Suspended = 'suspended';
    case Closed = 'closed';
}
