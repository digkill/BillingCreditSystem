<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Customer\Domain\Customer;
use App\Loan\Domain\Enum\LoanApplicationStatus;
use App\Loan\Domain\LoanApplication;
use App\Payment\Domain\Enum\PaymentStatus;
use App\Payment\Domain\Payment;
use App\Shared\Domain\ValueObject\Money;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

final class AdminDashboardController extends AbstractController
{
    private const TEAM_MEMBERS = [
        ['id' => 'tm-1', 'name' => 'Алексей Петров', 'role' => 'Руководитель кредитного направления', 'sla' => '8 часов'],
        ['id' => 'tm-2', 'name' => 'Ксения Соколова', 'role' => 'Кредитный аналитик', 'sla' => '10 часов'],
        ['id' => 'tm-3', 'name' => 'Никита Поляков', 'role' => 'Риск-менеджер', 'sla' => '7 часов'],
    ];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/api/admin/dashboard', name: 'api_admin_dashboard', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        /** @var LoanApplication[] $loans */
        $loans = $this->entityManager->getRepository(LoanApplication::class)->findAll();
        /** @var Customer[] $customers */
        $customers = $this->entityManager->getRepository(Customer::class)->findAll();
        /** @var Payment[] $payments */
        $payments = $this->entityManager->getRepository(Payment::class)->findAll();

        $customersById = [];
        foreach ($customers as $customer) {
            $customersById[(string) $customer->id()] = $customer;
        }

        $totals = [
            'total' => \count($loans),
            'approved' => 0,
            'disbursed' => 0,
            'rejected' => 0,
            'pending' => 0,
            'statusCount' => [],
            'principalMinor' => 0,
            'activePrincipalMinor' => 0,
        ];

        $currency = null;
        $approvedByDate = [];
        $rejectedByDate = [];

        $managerAssignments = [];
        $teamStats = [];
        foreach (self::TEAM_MEMBERS as $member) {
            $teamStats[$member['id']] = [
                'info' => $member,
                'assigned' => [],
                'approvals' => 0,
            ];
        }

        $teamCount = \count(self::TEAM_MEMBERS);
        $teamIndex = 0;

        foreach ($loans as $loan) {
            $currency ??= $loan->principal()->currency();
            $status = $loan->status();
            $statusValue = $status->value;

            $totals['statusCount'][$statusValue] = ($totals['statusCount'][$statusValue] ?? 0) + 1;
            $totals['principalMinor'] += $loan->principal()->amount();

            if (LoanApplicationStatus::Approved === $status) {
                ++$totals['approved'];
            }

            if (\in_array($status, [LoanApplicationStatus::Active, LoanApplicationStatus::Closed], true)) {
                ++$totals['disbursed'];
                $totals['activePrincipalMinor'] += $loan->principal()->amount();
            }

            if (LoanApplicationStatus::Rejected === $status) {
                ++$totals['rejected'];
            }

            if (\in_array($status, [LoanApplicationStatus::Draft, LoanApplicationStatus::Submitted], true)) {
                ++$totals['pending'];
            }

            if (null !== $loan->approvedAt()) {
                $key = $loan->approvedAt()->format('Y-m-d');
                $approvedByDate[$key] = ($approvedByDate[$key] ?? 0) + 1;
            }

            if (null !== $loan->rejectedAt()) {
                $key = $loan->rejectedAt()->format('Y-m-d');
                $rejectedByDate[$key] = ($rejectedByDate[$key] ?? 0) + 1;
            }

            $manager = self::TEAM_MEMBERS[$teamIndex % $teamCount];
            $managerAssignments[(string) $loan->id()] = $manager;
            $teamStats[$manager['id']]['assigned'][] = $loan;
            if (\in_array($status, [LoanApplicationStatus::Approved, LoanApplicationStatus::Active, LoanApplicationStatus::Closed], true)) {
                $teamStats[$manager['id']]['approvals']++;
            }
            ++$teamIndex;
        }

        $paymentsTotal = \count($payments);
        $overduePayments = 0;
        $loansWithOverdue = [];
        foreach ($payments as $payment) {
            if (PaymentStatus::Overdue === $payment->status()) {
                ++$overduePayments;
                $loansWithOverdue[(string) $payment->loanId()] = true;
            }
        }

        $approvalRate = 0;
        if ($totals['total'] > 0) {
            $approvalRate = (int) round((($totals['approved'] + $totals['disbursed']) / $totals['total']) * 100);
        }

        $overdueShare = $paymentsTotal > 0 ? round(($overduePayments / $paymentsTotal) * 100, 1) : 0.0;
        $nplShare = $totals['total'] > 0 ? round((\count($loansWithOverdue) / $totals['total']) * 100, 1) : 0.0;

        $averageTicketMinor = $totals['total'] > 0 ? (int) round($totals['principalMinor'] / $totals['total']) : 0;

        $pipelineStatuses = [
            LoanApplicationStatus::Draft->value,
            LoanApplicationStatus::Submitted->value,
            LoanApplicationStatus::Approved->value,
            LoanApplicationStatus::Active->value,
            LoanApplicationStatus::Closed->value,
            LoanApplicationStatus::Rejected->value,
        ];

        $pipeline = array_map(
            fn (string $status) => [
                'status' => $status,
                'count' => $totals['statusCount'][$status] ?? 0,
            ],
            $pipelineStatuses
        );

        $loansPayload = [];
        foreach ($loans as $loan) {
            $loanId = (string) $loan->id();
            $customerId = (string) $loan->customerId();
            $customer = $customersById[$customerId] ?? null;
            $manager = $managerAssignments[$loanId] ?? null;

            $loansPayload[] = [
                'id' => $loanId,
                'customerId' => $customerId,
                'customerName' => $customer?->fullName(),
                'customerStatus' => $customer?->status()->value,
                'status' => $loan->status()->value,
                'principal' => $this->moneyToArray($loan->principal()),
                'interestRate' => $loan->terms()->interestRate(),
                'termMonths' => $loan->terms()->termMonths(),
                'createdAt' => $this->formatDate($loan->createdAt()),
                'submittedAt' => $this->formatDate($loan->submittedAt()),
                'approvedAt' => $this->formatDate($loan->approvedAt()),
                'activatedAt' => $this->formatDate($loan->activatedAt()),
                'updatedAt' => $this->formatDate($loan->updatedAt()),
                'riskBand' => $this->riskBandForStatus($loan->status()),
                'probability' => $this->probabilityForStatus($loan->status()),
                'manager' => $manager ? [
                    'id' => $manager['id'],
                    'name' => $manager['name'],
                    'role' => $manager['role'],
                ] : null,
            ];
        }

        $maxAssigned = 0;
        foreach ($teamStats as $data) {
            $maxAssigned = max($maxAssigned, \count($data['assigned']));
        }
        $maxAssigned = max($maxAssigned, 1);

        $teamPayload = [];
        foreach ($teamStats as $memberId => $data) {
            $assignedCount = \count($data['assigned']);
            $approvals = $data['approvals'];
            $workload = (int) round(($assignedCount / $maxAssigned) * 100);
            $approval = $assignedCount > 0 ? (int) round(($approvals / $assignedCount) * 100) : 0;

            $teamPayload[] = [
                'id' => $memberId,
                'name' => $data['info']['name'],
                'role' => $data['info']['role'],
                'workload' => $workload,
                'approvalRate' => $approval,
                'sla' => $data['info']['sla'],
            ];
        }

        $dailyMetrics = $this->dailyMetrics($approvedByDate, $rejectedByDate);

        $response = [
            'metrics' => [
                'totalApplications' => $totals['total'],
                'pendingApplications' => $totals['pending'],
                'approvedApplications' => $totals['approved'],
                'disbursedApplications' => $totals['disbursed'],
                'rejectedApplications' => $totals['rejected'],
                'approvalRate' => $approvalRate,
                'portfolioAmount' => $this->moneyFromMinor($totals['activePrincipalMinor'], $currency),
                'averageTicket' => $this->moneyFromMinor($averageTicketMinor, $currency),
                'overdueShare' => $overdueShare,
                'nplShare' => $nplShare,
            ],
            'pipeline' => $pipeline,
            'daily' => $dailyMetrics,
            'loans' => $loansPayload,
            'team' => $teamPayload,
        ];

        return $this->json($response);
    }

    /**
     * @param array<string,int> $approved
     * @param array<string,int> $rejected
     * @return array<int,array{date:string,approved:int,rejected:int}>
     */
    private function dailyMetrics(array $approved, array $rejected): array
    {
        $result = [];
        $today = new \DateTimeImmutable('today');

        for ($i = 6; $i >= 0; --$i) {
            $date = $today->modify(sprintf('-%d days', $i));
            $key = $date->format('Y-m-d');
            $result[] = [
                'date' => $date->format('d.m'),
                'approved' => $approved[$key] ?? 0,
                'rejected' => $rejected[$key] ?? 0,
            ];
        }

        return $result;
    }

    private function probabilityForStatus(LoanApplicationStatus $status): int
    {
        return match ($status) {
            LoanApplicationStatus::Draft => 25,
            LoanApplicationStatus::Submitted => 55,
            LoanApplicationStatus::Approved => 85,
            LoanApplicationStatus::Active => 95,
            LoanApplicationStatus::Closed => 100,
            LoanApplicationStatus::Rejected => 5,
        };
    }

    private function riskBandForStatus(LoanApplicationStatus $status): string
    {
        return match ($status) {
            LoanApplicationStatus::Rejected => 'Высокий',
            LoanApplicationStatus::Submitted,
            LoanApplicationStatus::Draft => 'Средний',
            LoanApplicationStatus::Approved,
            LoanApplicationStatus::Active,
            LoanApplicationStatus::Closed => 'Низкий',
        };
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

    private function formatAmount(int $minorUnits): float
    {
        return round($minorUnits / 100, 2);
    }

    private function formatDate(?\DateTimeImmutable $date): ?string
    {
        return $date?->format(\DateTimeInterface::ATOM);
    }
}
