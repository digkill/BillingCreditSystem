<?php

declare(strict_types=1);

namespace App\Loan\Domain\Repository;

use App\Loan\Domain\LoanApplication;
use Symfony\Component\Uid\Uuid;

interface LoanApplicationRepository
{
    public function save(LoanApplication $application): void;

    public function findById(Uuid $id): ?LoanApplication;
}

