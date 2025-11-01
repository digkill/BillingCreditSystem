<?php

declare(strict_types=1);

namespace App\Loan\Application\Command;

use App\Loan\Domain\LoanApplication;
use App\Loan\Domain\Repository\LoanApplicationRepository;
use App\Loan\Domain\ValueObject\LoanTerms;
use App\Shared\Application\Bus\DomainEventBus;
use App\Shared\Domain\ValueObject\Money;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final class SubmitLoanApplicationHandler
{
    public function __construct(
        private readonly LoanApplicationRepository $loanApplications,
        private readonly EntityManagerInterface $entityManager,
        private readonly DomainEventBus $eventBus,
    ) {
    }

    public function __invoke(SubmitLoanApplicationCommand $command): Uuid
    {
        $loan = LoanApplication::draft(
            Uuid::fromString($command->customerId),
            Money::fromSubunits($command->principalAmount, $command->currency),
            LoanTerms::create($command->interestRate, $command->termMonths),
        );
        $loan->submit();

        $result = $this->entityManager->wrapInTransaction(function () use ($loan) {
            $this->loanApplications->save($loan);
            $this->entityManager->flush();

            return $loan->id();
        });

        $this->eventBus->publish(...$loan->pullDomainEvents());

        return $result;
    }
}

