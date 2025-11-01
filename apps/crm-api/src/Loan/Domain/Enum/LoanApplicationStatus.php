<?php

declare(strict_types=1);

namespace App\Loan\Domain\Enum;

enum LoanApplicationStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Active = 'active';
    case Closed = 'closed';
    case Rejected = 'rejected';
}

