<?php

declare(strict_types=1);

namespace App\Customer\Application\Command;

final class RegisterCustomerCommand
{
    public function __construct(
        public readonly string $fullName,
        public readonly string $email,
    ) {
    }
}

