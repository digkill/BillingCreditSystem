<?php

declare(strict_types=1);

namespace App\Customer\Application\Command;

use App\Customer\Domain\Customer;
use App\Customer\Domain\Enum\CustomerStatus;
use App\Customer\Domain\Repository\CustomerRepository;
use App\Customer\Domain\ValueObject\Email;
use App\Shared\Application\Bus\DomainEventBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final class RegisterCustomerHandler
{
    public function __construct(
        private readonly CustomerRepository $customers,
        private readonly EntityManagerInterface $entityManager,
        private readonly DomainEventBus $eventBus,
    ) {
    }

    public function __invoke(RegisterCustomerCommand $command): Uuid
    {
        $existing = $this->customers->findByEmail($command->email);
        if ($existing && $existing->status() !== CustomerStatus::Closed) {
            throw new \DomainException('Customer with this email already exists.');
        }

        $customer = Customer::register($command->fullName, Email::fromString($command->email));

        $result = $this->entityManager->wrapInTransaction(function () use ($customer) {
            $this->customers->save($customer);
            $this->entityManager->flush();

            return $customer->id();
        });

        $this->eventBus->publish(...$customer->pullDomainEvents());

        return $result;
    }
}

