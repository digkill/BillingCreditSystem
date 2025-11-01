<?php

declare(strict_types=1);

namespace App\Customer\Infrastructure\Persistence\Doctrine;

use App\Customer\Domain\Customer;
use App\Customer\Domain\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Uid\Uuid;

final class DoctrineCustomerRepository implements CustomerRepository
{
    private ObjectRepository $repository;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(Customer::class);
    }

    public function save(Customer $customer): void
    {
        $this->entityManager->persist($customer);
    }

    public function findById(Uuid $id): ?Customer
    {
        return $this->repository->find($id);
    }

    public function findByEmail(string $email): ?Customer
    {
        return $this->repository->findOneBy(['email.value' => strtolower($email)]);
    }
}
