<?php

declare(strict_types=1);

namespace App\Payment\Domain\Enum;

enum PaymentStatus: string
{
    case Scheduled = 'scheduled';
    case Due = 'due';
    case Paid = 'paid';
    case Overdue = 'overdue';
}

