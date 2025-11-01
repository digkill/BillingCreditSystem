<?php

declare(strict_types=1);

use App\Loan\Domain\Enum\LoanApplicationStatus;
use App\Loan\Domain\LoanApplication;
use App\Loan\Domain\ValueObject\LoanTerms;
use App\Loan\Domain\ValueObject\RepaymentSchedule;
use App\Shared\Domain\ValueObject\Money;
use Symfony\Component\Uid\Uuid;

test('loan application submits and approves', function (): void {
    $loan = LoanApplication::draft(
        Uuid::v7(),
        Money::fromSubunits(100_000, 'USD'),
        LoanTerms::create(0.12, 12),
    );

    $loan->submit();
    expect($loan->status())->toBe(LoanApplicationStatus::Submitted);

    $loan->approve();
    expect($loan->status())->toBe(LoanApplicationStatus::Approved);

    $loan->activate(RepaymentSchedule::fromArray([
        ['due_date' => '2024-11-01', 'amount' => 8_500],
    ]));
    expect($loan->status())->toBe(LoanApplicationStatus::Active);
});

