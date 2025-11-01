<?php

declare(strict_types=1);

namespace App\Payment\Domain\Repository;

use App\Payment\Domain\Payment;
use Symfony\Component\Uid\Uuid;

interface PaymentRepository
{
    public function save(Payment $payment): void;

    public function findById(Uuid $id): ?Payment;

    /**
     * @return Payment[]
     */
    public function findByLoan(Uuid $loanId): array;
}

