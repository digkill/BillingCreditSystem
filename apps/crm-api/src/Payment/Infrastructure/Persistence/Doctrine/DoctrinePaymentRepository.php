<?php

declare(strict_types=1);

namespace App\Payment\Infrastructure\Persistence\Doctrine;

use App\Payment\Domain\Payment;
use App\Payment\Domain\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Uid\Uuid;

final class DoctrinePaymentRepository implements PaymentRepository
{
    private ObjectRepository $repository;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(Payment::class);
    }

    public function save(Payment $payment): void
    {
        $this->entityManager->persist($payment);
    }

    public function findById(Uuid $id): ?Payment
    {
        return $this->repository->find($id);
    }

    public function findByLoan(Uuid $loanId): array
    {
        return $this->repository->findBy(['loanId' => $loanId]);
    }
}

