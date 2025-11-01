<?php

declare(strict_types=1);

namespace App\Loan\Application\Command;

final class SubmitLoanApplicationCommand
{
    public function __construct(
        public readonly string $customerId,
        public readonly int $principalAmount,
        public readonly string $currency,
        public readonly float $interestRate,
        public readonly int $termMonths,
    ) {
    }
}

