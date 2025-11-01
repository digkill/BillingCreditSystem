<?php

declare(strict_types=1);

namespace App\Loan\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class RepaymentSchedule
{
    #[ORM\Column(name: 'schedule', type: 'json', nullable: true)]
    private ?array $installments;

    private function __construct(?array $installments)
    {
        $this->installments = $installments;
    }

    public static function empty(): self
    {
        return new self(null);
    }

    public static function fromArray(array $installments): self
    {
        return new self($installments);
    }

    public function installments(): ?array
    {
        return $this->installments;
    }

    public function isGenerated(): bool
    {
        return null !== $this->installments;
    }
}

