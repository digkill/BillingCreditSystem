<?php

declare(strict_types=1);

namespace App\Payment\Application\Command;

final class RegisterPaymentCommand
{
    public function __construct(
        public readonly string $loanId,
        public readonly string $dueDate,
        public readonly int $amount,
        public readonly string $currency,
    ) {
    }
}

