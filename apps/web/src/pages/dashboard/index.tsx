import { useMemo } from 'react'
import { ArrowUpRight, Users, Wallet } from 'lucide-react'

import { Button } from '@/shared/components/ui/button'
import { LoanApplicationsTable } from '@/widgets/layout/loan-applications-table'

export function DashboardPage() {
  const summary = useMemo(
    () => [
      {
        title: 'Active Loans',
        value: '128',
        change: '+12.4% vs last month',
        icon: Wallet,
      },
      {
        title: 'Customers',
        value: '2,431',
        change: '+4.1% new approvals',
        icon: Users,
      },
      {
        title: 'Total Exposure',
        value: '$9.6M',
        change: '+2.8% growth',
        icon: ArrowUpRight,
      },
    ],
    []
  )

  return (
    <div className="space-y-6">
      <section className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 className="text-2xl font-semibold">Loan Portfolio Overview</h1>
          <p className="text-sm text-muted-foreground">
            Monitor applications, repayments, and credit risk signals in real time.
          </p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline">Export Report</Button>
          <Button>Disburse Batch</Button>
        </div>
      </section>

      <section className="grid gap-4 md:grid-cols-3">
        {summary.map((item) => (
          <article key={item.title} className="rounded-xl border bg-card p-4 shadow-sm">
            <div className="flex items-center justify-between text-sm text-muted-foreground">
              <span>{item.title}</span>
              <item.icon className="h-4 w-4" />
            </div>
            <div className="mt-3 text-2xl font-semibold text-card-foreground">{item.value}</div>
            <p className="mt-2 text-xs text-muted-foreground">{item.change}</p>
          </article>
        ))}
      </section>

      <LoanApplicationsTable />
    </div>
  )
}

