<?php

declare(strict_types=1);

namespace App\Loan\Application\Command;

use App\Loan\Domain\Enum\LoanApplicationStatus;
use App\Loan\Domain\Repository\LoanApplicationRepository;
use App\Shared\Application\Bus\DomainEventBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final class ApproveLoanApplicationHandler
{
    public function __construct(
        private readonly LoanApplicationRepository $loanApplications,
        private readonly EntityManagerInterface $entityManager,
        private readonly DomainEventBus $eventBus,
    ) {
    }

    public function __invoke(ApproveLoanApplicationCommand $command): void
    {
        $loan = $this->loanApplications->findById(Uuid::fromString($command->loanId));
        if (null === $loan) {
            throw new \DomainException('Loan application not found.');
        }

        if ($loan->status() !== LoanApplicationStatus::Submitted) {
            throw new \DomainException('Only submitted applications can be approved.');
        }

        $loan->approve();

        $this->entityManager->wrapInTransaction(function () use ($loan) {
            $this->entityManager->flush();
        });

        $this->eventBus->publish(...$loan->pullDomainEvents());
    }
}

