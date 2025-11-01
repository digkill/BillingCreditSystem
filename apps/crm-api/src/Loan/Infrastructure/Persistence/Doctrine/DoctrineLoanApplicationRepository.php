<?php

declare(strict_types=1);

namespace App\Loan\Infrastructure\Persistence\Doctrine;

use App\Loan\Domain\LoanApplication;
use App\Loan\Domain\Repository\LoanApplicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Uid\Uuid;

final class DoctrineLoanApplicationRepository implements LoanApplicationRepository
{
    private ObjectRepository $repository;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(LoanApplication::class);
    }

    public function save(LoanApplication $application): void
    {
        $this->entityManager->persist($application);
    }

    public function findById(Uuid $id): ?LoanApplication
    {
        return $this->repository->find($id);
    }
}

