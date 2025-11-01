<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Money
{
    #[ORM\Column(name: 'amount', type: 'bigint')]
    private int $amount;

    #[ORM\Column(name: 'currency', length: 3)]
    private string $currency;

    private function __construct(int $amount, string $currency)
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Money amount cannot be negative.');
        }

        if (3 !== strlen($currency)) {
            throw new \InvalidArgumentException('Money currency must be ISO 4217 code.');
        }

        $this->amount = $amount;
        $this->currency = strtoupper($currency);
    }

    public static function fromSubunits(int $amount, string $currency): self
    {
        return new self($amount, $currency);
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        if ($other->amount > $this->amount) {
            throw new \InvalidArgumentException('Resulting money cannot be negative.');
        }

        return new self($this->amount - $other->amount, $this->currency);
    }

    public function isZero(): bool
    {
        return 0 === $this->amount;
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Currencies must match.');
        }
    }
}
