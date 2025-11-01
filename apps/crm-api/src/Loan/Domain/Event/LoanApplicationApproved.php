<?php

declare(strict_types=1);

namespace App\Loan\Domain\Event;

use App\Shared\Domain\Event\AbstractDomainEvent;
use Symfony\Component\Uid\Uuid;

final class LoanApplicationApproved extends AbstractDomainEvent
{
    public function __construct(private readonly Uuid $loanId)
    {
        parent::__construct();
    }

    public static function eventName(): string
    {
        return 'loan_application.approved';
    }

    public function payload(): array
    {
        return [
            'loan_id' => $this->loanId->toRfc4122(),
        ];
    }
}

