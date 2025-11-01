<?php

declare(strict_types=1);

namespace App\Loan\Application\Command;

final class ApproveLoanApplicationCommand
{
    public function __construct(public readonly string $loanId)
    {
    }
}

