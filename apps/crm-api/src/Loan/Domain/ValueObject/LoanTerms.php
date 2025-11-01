<?php

declare(strict_types=1);

namespace App\Loan\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class LoanTerms
{
    #[ORM\Column(name: 'interest_rate', type: 'float')]
    private float $interestRate;

    #[ORM\Column(name: 'term_months', type: 'integer')]
    private int $termMonths;

    private function __construct(float $interestRate, int $termMonths)
    {
        if ($interestRate < 0) {
            throw new \InvalidArgumentException('Interest rate must be non-negative.');
        }

        if ($termMonths <= 0) {
            throw new \InvalidArgumentException('Term must be greater than zero.');
        }

        $this->interestRate = $interestRate;
        $this->termMonths = $termMonths;
    }

    public static function create(float $interestRate, int $termMonths): self
    {
        return new self($interestRate, $termMonths);
    }

    public function interestRate(): float
    {
        return $this->interestRate;
    }

    public function termMonths(): int
    {
        return $this->termMonths;
    }
}

