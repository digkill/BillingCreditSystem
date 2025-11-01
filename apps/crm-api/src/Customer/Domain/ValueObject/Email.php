<?php

declare(strict_types=1);

namespace App\Customer\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Email
{
    #[ORM\Column(name: 'email', length: 180, unique: true)]
    private string $value;

    private function __construct(string $value)
    {
        $filtered = filter_var($value, \FILTER_VALIDATE_EMAIL);
        if (false === $filtered) {
            throw new \InvalidArgumentException(sprintf('Invalid email "%s"', $value));
        }

        $this->value = strtolower($filtered);
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
