import { useMemo } from 'react'
import { Badge } from '@/shared/components/ui/badge'

const STATUS_COLORS: Record<string, string> = {
  submitted: 'bg-blue-100 text-blue-800',
  approved: 'bg-green-100 text-green-800',
  active: 'bg-emerald-100 text-emerald-800',
  overdue: 'bg-amber-100 text-amber-900',
}

const mockData = [
  {
    id: 'LN-2024-0001',
    customer: 'Alice Johnson',
    principal: '$15,000',
    status: 'submitted',
    repayment: '12 months',
  },
  {
    id: 'LN-2024-0002',
    customer: 'Ben Carter',
    principal: '$8,400',
    status: 'approved',
    repayment: '24 months',
  },
  {
    id: 'LN-2024-0003',
    customer: 'Veronica Miles',
    principal: '$42,000',
    status: 'active',
    repayment: '36 months',
  },
]

export function LoanApplicationsTable() {
  const rows = useMemo(() => mockData, [])

  return (
    <section className="rounded-xl border bg-card">
      <header className="flex items-center justify-between border-b px-6 py-4">
        <div>
          <h2 className="text-lg font-semibold">Recent Applications</h2>
          <p className="text-sm text-muted-foreground">
            Track approvals and pending disbursements.
          </p>
        </div>
      </header>
      <div className="overflow-x-auto">
        <table className="min-w-full divide-y divide-border text-sm">
          <thead className="bg-muted/50">
            <tr className="text-left text-xs uppercase tracking-wide text-muted-foreground">
              <th className="px-6 py-3">Loan ID</th>
              <th className="px-6 py-3">Customer</th>
              <th className="px-6 py-3">Principal</th>
              <th className="px-6 py-3">Term</th>
              <th className="px-6 py-3 text-right">Status</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-border bg-card">
            {rows.map((row) => (
              <tr key={row.id}>
                <td className="px-6 py-4 font-medium text-card-foreground">{row.id}</td>
                <td className="px-6 py-4 text-muted-foreground">{row.customer}</td>
                <td className="px-6 py-4 text-card-foreground">{row.principal}</td>
                <td className="px-6 py-4 text-muted-foreground">{row.repayment}</td>
                <td className="px-6 py-4 text-right">
                  <Badge variant="outline" className={STATUS_COLORS[row.status] ?? ''}>
                    {row.status}
                  </Badge>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </section>
  )
}
