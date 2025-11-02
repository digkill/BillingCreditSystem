<?php

declare(strict_types=1);

namespace App\Controller\Client;

use App\Customer\Domain\Customer;
use App\Loan\Domain\LoanApplication;
use App\Loan\Domain\Enum\LoanApplicationStatus;
use App\Payment\Domain\Enum\PaymentStatus;
use App\Payment\Domain\Payment;
use App\Shared\Domain\ValueObject\Money;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;

final class ClientDashboardController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/api/client/{customerId}', name: 'api_client_dashboard', methods: ['GET'])]
    public function __invoke(string $customerId): JsonResponse
    {
        $uuid = Uuid::fromString($customerId);

        /** @var Customer|null $customer */
        $customer = $this->entityManager->getRepository(Customer::class)->find($uuid);
        if (null === $customer) {
            throw $this->createNotFoundException('Customer not found.');
        }

        /** @var LoanApplication[] $loans */
        $loans = $this->entityManager
            ->getRepository(LoanApplication::class)
            ->findBy(['customerId' => $uuid], ['createdAt' => 'ASC']);

        $loanPayments = [];
        $loansPayload = [];
        $applicationsPayload = [];

        $activeLoansCount = 0;
        $totalPrincipal = 0;
        $totalOutstanding = 0;
        $currency = null;
        $nextPaymentCandidate = null;

        /** @var Payment[] $payments */
        $payments = $this->entityManager->getRepository(Payment::class)->findBy(
            ['loanId' => array_map(static fn (LoanApplication $loan) => $loan->id(), $loans)]
        );

        $paymentsByLoan = [];
        foreach ($payments as $payment) {
            $loanId = (string) $payment->loanId();
            $paymentsByLoan[$loanId][] = $payment;
        }

        foreach ($loans as $loan) {
            $loanId = (string) $loan->id();
            $currency ??= $loan->principal()->currency();

            $loanTotalExpected = 0;
            $loanTotalPaid = 0;

            $paymentsForLoan = $paymentsByLoan[$loanId] ?? [];
            usort($paymentsForLoan, static fn (Payment $a, Payment $b) => $a->dueDate() <=> $b->dueDate());

            $paymentsPayload = [];
            foreach ($paymentsForLoan as $payment) {
                $loanTotalExpected += $payment->expectedAmount()->amount();
                $loanTotalPaid += $payment->paidAmount()->amount();

                if (
                    PaymentStatus::Paid !== $payment->status()
                    && (null === $nextPaymentCandidate || $payment->dueDate() < $nextPaymentCandidate->dueDate())
                ) {
                    $nextPaymentCandidate = $payment;
                }

                $paymentsPayload[] = $this->paymentToArray($payment, $loanId);
            }

            $outstandingMinor = max($loan->principal()->amount(), $loanTotalExpected) - $loanTotalPaid;
            if ($outstandingMinor < 0) {
                $outstandingMinor = 0;
            }
            $outstandingMoney = Money::fromSubunits($outstandingMinor, $loan->principal()->currency());

            if (LoanApplicationStatus::Active === $loan->status()) {
                ++$activeLoansCount;
            }

            $totalPrincipal += $loan->principal()->amount();
            $totalOutstanding += $outstandingMoney->amount();

            $loanPayload = [
                'id' => $loanId,
                'status' => $loan->status()->value,
                'principal' => $this->moneyToArray($loan->principal()),
                'interestRate' => $loan->terms()->interestRate(),
                'termMonths' => $loan->terms()->termMonths(),
                'createdAt' => $this->formatDate($loan->createdAt()),
                'updatedAt' => $this->formatDate($loan->updatedAt()),
                'submittedAt' => $this->formatDate($loan->submittedAt()),
                'approvedAt' => $this->formatDate($loan->approvedAt()),
                'activatedAt' => $this->formatDate($loan->activatedAt()),
                'closedAt' => $this->formatDate($loan->closedAt()),
                'rejectedAt' => $this->formatDate($loan->rejectedAt()),
                'schedule' => $this->scheduleToArray($loan->schedule()->installments(), $loan->principal()->currency()),
                'outstanding' => $this->moneyToArray($outstandingMoney),
                'nextPayment' => $this->nextPaymentForLoan($paymentsForLoan, $loanId),
                'payments' => $paymentsPayload,
            ];

            $loansPayload[] = $loanPayload;
            $applicationsPayload[] = [
                'id' => $loanPayload['id'],
                'status' => $loanPayload['status'],
                'principal' => $loanPayload['principal'],
                'termMonths' => $loanPayload['termMonths'],
                'updatedAt' => $loanPayload['updatedAt'],
                'submittedAt' => $loanPayload['submittedAt'],
                'approvedAt' => $loanPayload['approvedAt'],
            ];

            $loanPayments[] = $paymentsPayload;
        }

        $loansFlatPayments = array_merge(...$loanPayments ?: [[]]);
        usort(
            $loansFlatPayments,
            static fn (array $left, array $right) => $left['dueDate'] <=> $right['dueDate']
        );

        $response = [
            'customer' => [
                'id' => (string) $customer->id(),
                'fullName' => $customer->fullName(),
                'email' => $customer->email()->value(),
                'status' => $customer->status()->value,
                'createdAt' => $this->formatDate($customer->createdAt()),
                'updatedAt' => $this->formatDate($customer->updatedAt()),
            ],
            'metrics' => [
                'activeLoans' => $activeLoansCount,
                'totalPrincipal' => $this->moneyFromMinor($totalPrincipal, $currency),
                'outstandingBalance' => $this->moneyFromMinor($totalOutstanding, $currency),
                'nextPayment' => null !== $nextPaymentCandidate
                    ? $this->paymentToArray($nextPaymentCandidate, (string) $nextPaymentCandidate->loanId())
                    : null,
            ],
            'loans' => $loansPayload,
            'applications' => $applicationsPayload,
            'payments' => $loansFlatPayments,
            'offers' => $this->offers(),
        ];

        return $this->json($response);
    }

    /**
     * @param array<int, array{due_date: string, amount: int}>|null $installments
     * @return array<int, array{dueDate: string, amount: array{amount: float, currency: string, minorUnits: int}}>
     */
    private function scheduleToArray(?array $installments, string $currency): array
    {
        if (null === $installments) {
            return [];
        }

        return array_map(
            fn (array $item) => [
                'dueDate' => $item['due_date'],
                'amount' => $this->moneyFromMinor((int) ($item['amount'] ?? 0), $currency),
            ],
            $installments
        );
    }

    private function nextPaymentForLoan(array $payments, string $loanId): ?array
    {
        $candidate = null;
        foreach ($payments as $payment) {
            if (PaymentStatus::Paid === $payment->status()) {
                continue;
            }

            if (null === $candidate || $payment->dueDate() < $candidate->dueDate()) {
                $candidate = $payment;
            }
        }

        return $candidate ? $this->paymentToArray($candidate, $loanId) : null;
    }

    private function paymentToArray(Payment $payment, string $loanId): array
    {
        return [
            'id' => (string) $payment->id(),
            'loanId' => $loanId,
            'dueDate' => $this->formatDate($payment->dueDate()),
            'status' => $payment->status()->value,
            'expectedAmount' => $this->moneyToArray($payment->expectedAmount()),
            'paidAmount' => $this->moneyToArray($payment->paidAmount()),
            'createdAt' => $this->formatDate($payment->createdAt()),
            'updatedAt' => $this->formatDate($payment->updatedAt()),
        ];
    }

    private function moneyToArray(Money $money): array
    {
        return [
            'amount' => $this->formatAmount($money->amount()),
            'currency' => $money->currency(),
            'minorUnits' => $money->amount(),
        ];
    }

    private function moneyFromMinor(?int $minorUnits, ?string $currency): ?array
    {
        if (null === $minorUnits || null === $currency) {
            return null;
        }

        return [
            'amount' => $this->formatAmount($minorUnits),
            'currency' => $currency,
            'minorUnits' => $minorUnits,
        ];
    }

    private function offers(): array
    {
        return [
            [
                'id' => 'offer-working-capital',
                'name' => 'Оборотный капитал',
                'description' => 'Быстрая поддержка оборотных средств с минимальным пакетом документов.',
                'rate' => 16.2,
                'maxAmount' => 800_000,
                'term' => ['from' => 6, 'to' => 24],
                'preApproved' => true,
                'purposes' => ['Пополнение оборотных средств', 'Покрытие кассовых разрывов'],
            ],
            [
                'id' => 'offer-equipment',
                'name' => 'Оборудование и техника',
                'description' => 'Финансирование закупки оборудования с отсрочкой первого платежа до 60 дней.',
                'rate' => 14.9,
                'maxAmount' => 1_200_000,
                'term' => ['from' => 12, 'to' => 36],
                'preApproved' => false,
                'purposes' => ['Техника', 'Оборудование', 'Модернизация производства'],
            ],
            [
                'id' => 'offer-overdraft',
                'name' => 'Кредитная линия',
                'description' => 'Гибкий лимит на счёте для покрытия временных разрывов в платежах.',
                'rate' => 18.4,
                'maxAmount' => 500_000,
                'term' => ['from' => 3, 'to' => 12],
                'preApproved' => true,
                'purposes' => ['Операционные расходы', 'Экстренные выплаты'],
            ],
        ];
    }

    private function formatAmount(int $minorUnits): float
    {
        return round($minorUnits / 100, 2);
    }

    private function formatDate(?\DateTimeImmutable $date): ?string
    {
        return $date?->format(\DateTimeInterface::ATOM);
    }
}
