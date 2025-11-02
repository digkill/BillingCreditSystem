<?php

declare(strict_types=1);

namespace App\Loan\Domain;

use App\Loan\Domain\Enum\LoanApplicationStatus;
use App\Loan\Domain\Event\LoanApplicationApproved;
use App\Loan\Domain\Event\LoanApplicationSubmitted;
use App\Loan\Domain\Event\LoanActivated;
use App\Loan\Domain\ValueObject\LoanTerms;
use App\Loan\Domain\ValueObject\RepaymentSchedule;
use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\ValueObject\Money;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'loan_applications')]
class LoanApplication extends AggregateRoot
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'uuid')]
    private Uuid $customerId;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'principal_')]
    private Money $principal;

    #[ORM\Embedded(class: LoanTerms::class)]
    private LoanTerms $terms;

    #[ORM\Column(enumType: LoanApplicationStatus::class)]
    private LoanApplicationStatus $status;

    #[ORM\Embedded(class: RepaymentSchedule::class)]
    private RepaymentSchedule $schedule;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $submittedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $approvedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $activatedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $closedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $rejectedAt = null;

    private function __construct(
        Uuid $id,
        Uuid $customerId,
        Money $principal,
        LoanTerms $terms,
        LoanApplicationStatus $status,
        RepaymentSchedule $schedule,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ) {
        $this->id = $id;
        $this->customerId = $customerId;
        $this->principal = $principal;
        $this->terms = $terms;
        $this->status = $status;
        $this->schedule = $schedule;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function draft(Uuid $customerId, Money $principal, LoanTerms $terms): self
    {
        $now = new \DateTimeImmutable();
        $instance = new self(
            Uuid::v7(),
            $customerId,
            $principal,
            $terms,
            LoanApplicationStatus::Draft,
            RepaymentSchedule::empty(),
            $now,
            $now,
        );

        return $instance;
    }

    public function submit(): void
    {
        if (!\in_array($this->status, [LoanApplicationStatus::Draft, LoanApplicationStatus::Rejected], true)) {
            throw new \DomainException('Only draft or rejected applications can be submitted.');
        }

        $this->status = LoanApplicationStatus::Submitted;
        $this->submittedAt = new \DateTimeImmutable();
        $this->updatedAt = $this->submittedAt;
        $this->recordThat(new LoanApplicationSubmitted($this->id, $this->customerId, $this->principal->amount(), $this->principal->currency(), $this->terms->interestRate(), $this->terms->termMonths()));
    }

    public function approve(): void
    {
        if ($this->status !== LoanApplicationStatus::Submitted) {
            throw new \DomainException('Only submitted applications can be approved.');
        }

        $this->status = LoanApplicationStatus::Approved;
        $this->approvedAt = new \DateTimeImmutable();
        $this->updatedAt = $this->approvedAt;
        $this->recordThat(new LoanApplicationApproved($this->id));
    }

    public function activate(RepaymentSchedule $schedule): void
    {
        if ($this->status !== LoanApplicationStatus::Approved) {
            throw new \DomainException('Only approved applications can be activated.');
        }

        $this->status = LoanApplicationStatus::Active;
        $this->activatedAt = new \DateTimeImmutable();
        $this->updatedAt = $this->activatedAt;
        $this->schedule = $schedule;
        $this->recordThat(new LoanActivated($this->id));
    }

    public function close(): void
    {
        if (!\in_array($this->status, [LoanApplicationStatus::Active, LoanApplicationStatus::Rejected], true)) {
            throw new \DomainException('Only active or rejected applications can be closed.');
        }

        $this->status = LoanApplicationStatus::Closed;
        $this->closedAt = new \DateTimeImmutable();
        $this->updatedAt = $this->closedAt;
    }

    public function reject(string $reason): void
    {
        if ($this->status !== LoanApplicationStatus::Submitted) {
            throw new \DomainException('Only submitted applications can be rejected.');
        }

        $this->status = LoanApplicationStatus::Rejected;
        $this->rejectedAt = new \DateTimeImmutable();
        $this->updatedAt = $this->rejectedAt;
        // future: record rejection event with reason
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function customerId(): Uuid
    {
        return $this->customerId;
    }

    public function status(): LoanApplicationStatus
    {
        return $this->status;
    }

    public function principal(): Money
    {
        return $this->principal;
    }

    public function terms(): LoanTerms
    {
        return $this->terms;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function submittedAt(): ?\DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function approvedAt(): ?\DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function activatedAt(): ?\DateTimeImmutable
    {
        return $this->activatedAt;
    }

    public function closedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function rejectedAt(): ?\DateTimeImmutable
    {
        return $this->rejectedAt;
    }

    public function schedule(): RepaymentSchedule
    {
        return $this->schedule;
    }
}

