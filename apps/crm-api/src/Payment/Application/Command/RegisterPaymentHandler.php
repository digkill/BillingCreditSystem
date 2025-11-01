<?php

declare(strict_types=1);

namespace App\Payment\Application\Command;

use App\Payment\Domain\Payment;
use App\Payment\Domain\Repository\PaymentRepository;
use App\Shared\Application\Bus\DomainEventBus;
use App\Shared\Domain\ValueObject\Money;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final class RegisterPaymentHandler
{
    public function __construct(
        private readonly PaymentRepository $payments,
        private readonly EntityManagerInterface $entityManager,
        private readonly DomainEventBus $eventBus,
    ) {
    }

    public function __invoke(RegisterPaymentCommand $command): Uuid
    {
        $payment = Payment::schedule(
            Uuid::fromString($command->loanId),
            new \DateTimeImmutable($command->dueDate),
            Money::fromSubunits($command->amount, $command->currency),
        );

        $result = $this->entityManager->wrapInTransaction(function () use ($payment) {
            $this->payments->save($payment);
            $this->entityManager->flush();

            return $payment->id();
        });

        $this->eventBus->publish(...$payment->pullDomainEvents());

        return $result;
    }
}

