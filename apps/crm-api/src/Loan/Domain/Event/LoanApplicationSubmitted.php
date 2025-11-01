<?php

declare(strict_types=1);

namespace App\Loan\Domain\Event;

use App\Shared\Domain\Event\AbstractDomainEvent;
use Symfony\Component\Uid\Uuid;

final class LoanApplicationSubmitted extends AbstractDomainEvent
{
    public function __construct(
        private readonly Uuid $loanId,
        private readonly Uuid $customerId,
        private readonly int $principalAmount,
        private readonly string $currency,
        private readonly float $interestRate,
        private readonly int $termMonths,
    ) {
        parent::__construct();
    }

    public static function eventName(): string
    {
        return 'loan_application.submitted';
    }

    public function payload(): array
    {
        return [
            'loan_id' => $this->loanId->toRfc4122(),
            'customer_id' => $this->customerId->toRfc4122(),
            'principal_amount' => $this->principalAmount,
            'currency' => $this->currency,
            'interest_rate' => $this->interestRate,
            'term_months' => $this->termMonths,
        ];
    }
}
