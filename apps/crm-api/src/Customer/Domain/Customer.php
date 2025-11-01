<?php

declare(strict_types=1);

namespace App\Customer\Domain;

use App\Customer\Domain\Enum\CustomerStatus;
use App\Customer\Domain\Event\CustomerRegistered;
use App\Customer\Domain\Event\CustomerStatusChanged;
use App\Customer\Domain\ValueObject\Email;
use App\Shared\Domain\AggregateRoot;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'customers')]
class Customer extends AggregateRoot
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(length: 120)]
    private string $fullName;

    #[ORM\Embedded(class: Email::class)]
    private Email $email;

    #[ORM\Column(enumType: CustomerStatus::class)]
    private CustomerStatus $status;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    private function __construct(
        Uuid $id,
        string $fullName,
        Email $email,
        CustomerStatus $status,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ) {
        $this->id = $id;
        $this->fullName = $fullName;
        $this->email = $email;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function register(string $fullName, Email $email): self
    {
        $now = new \DateTimeImmutable();
        $instance = new self(
            Uuid::v7(),
            $fullName,
            $email,
            CustomerStatus::Draft,
            $now,
            $now,
        );

        $instance->recordThat(new CustomerRegistered($instance->id, $instance->fullName, $instance->email->value()));

        return $instance;
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function fullName(): string
    {
        return $this->fullName;
    }

    public function status(): CustomerStatus
    {
        return $this->status;
    }

    public function activate(): void
    {
        $this->transitionTo(CustomerStatus::Active);
    }

    public function suspend(): void
    {
        $this->transitionTo(CustomerStatus::Suspended);
    }

    public function close(): void
    {
        $this->transitionTo(CustomerStatus::Closed);
    }

    private function transitionTo(CustomerStatus $status): void
    {
        if ($this->status === $status) {
            return;
        }

        $previous = $this->status;
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordThat(new CustomerStatusChanged($this->id, $previous, $status));
    }
}
