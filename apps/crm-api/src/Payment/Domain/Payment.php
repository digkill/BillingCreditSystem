<?php

declare(strict_types=1);

namespace App\Payment\Domain;

use App\Payment\Domain\Enum\PaymentStatus;
use App\Payment\Domain\Event\PaymentRegistered;
use App\Payment\Domain\Event\PaymentStatusChanged;
use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\ValueObject\Money;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'payments')]
class Payment extends AggregateRoot
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'uuid')]
    private Uuid $loanId;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $dueDate;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'expected_')]
    private Money $expectedAmount;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'paid_')]
    private Money $paidAmount;

    #[ORM\Column(enumType: PaymentStatus::class)]
    private PaymentStatus $status;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    private function __construct(
        Uuid $id,
        Uuid $loanId,
        \DateTimeImmutable $dueDate,
        Money $expectedAmount,
        Money $paidAmount,
        PaymentStatus $status,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ) {
        $this->id = $id;
        $this->loanId = $loanId;
        $this->dueDate = $dueDate;
        $this->expectedAmount = $expectedAmount;
        $this->paidAmount = $paidAmount;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function schedule(Uuid $loanId, \DateTimeImmutable $dueDate, Money $expectedAmount): self
    {
        $now = new \DateTimeImmutable();
        $instance = new self(
            Uuid::v7(),
            $loanId,
            $dueDate,
            $expectedAmount,
            Money::fromSubunits(0, $expectedAmount->currency()),
            PaymentStatus::Scheduled,
            $now,
            $now,
        );

        $instance->recordThat(new PaymentRegistered($instance->id, $loanId, $dueDate, $expectedAmount->amount(), $expectedAmount->currency()));

        return $instance;
    }

    public function markDue(): void
    {
        $this->transitionTo(PaymentStatus::Due);
    }

    public function registerPayment(Money $amount): void
    {
        $this->assertSameCurrency($amount);
        $this->paidAmount = $this->paidAmount->add($amount);
        if ($this->paidAmount->amount() >= $this->expectedAmount->amount()) {
            $this->transitionTo(PaymentStatus::Paid);
        }
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function markOverdue(): void
    {
        $this->transitionTo(PaymentStatus::Overdue);
    }

    private function transitionTo(PaymentStatus $status): void
    {
        if ($this->status === $status) {
            return;
        }

        $previous = $this->status;
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
        $this->recordThat(new PaymentStatusChanged($this->id, $previous, $status));
    }

    private function assertSameCurrency(Money $amount): void
    {
        if ($amount->currency() !== $this->expectedAmount->currency()) {
            throw new \InvalidArgumentException('Currency mismatch when registering payment.');
        }
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function dueDate(): \DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function expectedAmount(): Money
    {
        return $this->expectedAmount;
    }

    public function paidAmount(): Money
    {
        return $this->paidAmount;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function loanId(): Uuid
    {
        return $this->loanId;
    }

    public function status(): PaymentStatus
    {
        return $this->status;
    }
}

