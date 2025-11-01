<?php

declare(strict_types=1);

namespace App\Customer\Domain\Repository;

use App\Customer\Domain\Customer;
use Symfony\Component\Uid\Uuid;

interface CustomerRepository
{
    public function save(Customer $customer): void;

    public function findById(Uuid $id): ?Customer;

    public function findByEmail(string $email): ?Customer;
}
