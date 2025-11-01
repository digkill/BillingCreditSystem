<?php

declare(strict_types=1);

namespace App\Loan\Application\Command;

final class ActivateLoanCommand
{
    /**
     * @param array<int, array<string, scalar>> $schedule
     */
    public function __construct(
        public readonly string $loanId,
        public readonly array $schedule,
    ) {
    }
}

