import { useMemo } from 'react'
import { useQuery } from '@tanstack/react-query'
import {
  AlertTriangle,
  ArrowUpRight,
  BarChart3,
  Briefcase,
  Clock,
  FileSpreadsheet,
  Loader2,
  Shield,
  Users,
} from 'lucide-react'

import { fetchAdminDashboard } from '@/shared/api/dashboard'
import type { AdminLoanDto, MoneyDto } from '@/shared/api/types'
import { Button } from '@/shared/components/ui/button'
import { Badge } from '@/shared/components/ui/badge'
const moneyFormatter = (money?: MoneyDto | null, fallback = '—') => {
  if (!money) {
    return fallback
  }

  const formatter = new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: money.currency,
    maximumFractionDigits: 0,
  })

  return formatter.format(money.amount)
}

const adminStatusStyles: Record<string, { label: string; className: string }> = {
  draft: { label: 'Черновик', className: 'bg-slate-100 text-slate-700' },
  submitted: { label: 'На проверке', className: 'bg-blue-100 text-blue-800' },
  approved: { label: 'Одобрено', className: 'bg-emerald-100 text-emerald-800' },
  active: { label: 'Выдан', className: 'bg-emerald-200 text-emerald-900' },
  closed: { label: 'Закрыт', className: 'bg-slate-200 text-slate-700' },
  rejected: { label: 'Отказ', className: 'bg-red-100 text-red-800' },
}

const moneyHint = (money?: MoneyDto | null) => moneyFormatter(money)

export function AdminDashboardPage() {
  const { data, isLoading, isError, error } = useQuery({
    queryKey: ['admin-dashboard'],
    queryFn: fetchAdminDashboard,
  })

  const metricsCards = useMemo(() => {
    if (!data) {
      return []
    }

    return [
      {
        title: 'Заявки в работе',
        value: data.metrics.totalApplications,
        hint: `Ожидают решения: ${data.metrics.pendingApplications}`,
        icon: Users,
      },
      {
        title: 'Процент одобрений',
        value: `${data.metrics.approvalRate}%`,
        hint: `Отказов: ${data.metrics.rejectedApplications}`,
        icon: Shield,
      },
      {
        title: 'Портфель',
        value: moneyHint(data.metrics.portfolioAmount),
        hint: `Просрочка: ${data.metrics.overdueShare}%`,
        icon: Briefcase,
      },
      {
        title: 'Средний чек',
        value: moneyHint(data.metrics.averageTicket),
        hint: `NPL30+: ${data.metrics.nplShare}%`,
        icon: BarChart3,
      },
    ]
  }, [data])

  if (isLoading) {
    return (
      <div className="flex min-h-[320px] items-center justify-center text-muted-foreground">
        <Loader2 className="mr-2 h-5 w-5 animate-spin" />
        Загружаем метрики портфеля...
      </div>
    )
  }

  if (isError || !data) {
    return (
      <div className="flex min-h-[200px] items-center justify-center">
        <div className="flex items-center gap-3 rounded-lg border border-destructive/60 bg-destructive/10 px-4 py-3 text-sm text-destructive">
          <AlertTriangle className="h-5 w-5" />
          <span>Не удалось получить данные админ-панели. {error instanceof Error ? error.message : ''}</span>
        </div>
      </div>
    )
  }

  const totalApplications = Math.max(data.metrics.totalApplications, 1)
  const pipelineWithShare = data.pipeline.map((stage) => ({
    ...stage,
    label: adminStatusStyles[stage.status]?.label ?? stage.status,
    share: Math.round((stage.count / totalApplications) * 100),
  }))

  const dailyAvgApproved = Math.round(
    data.daily.reduce((acc, item) => acc + item.approved, 0) / Math.max(data.daily.length, 1)
  )

  return (
    <div className="space-y-8">
      <section className="flex flex-col gap-4 rounded-xl border bg-card p-6 shadow-sm md:flex-row md:items-center md:justify-between">
        <div>
          <p className="text-sm text-muted-foreground">Операционный центр</p>
          <h1 className="text-2xl font-semibold text-card-foreground">Администрирование портфеля</h1>
          <p className="mt-2 max-w-2xl text-sm text-muted-foreground">
            Отслеживайте воронку одобрений, загрузку аналитиков и ключевые показатели качества портфеля.
          </p>
        </div>
        <div className="flex flex-col gap-2 text-sm text-muted-foreground">
          <div className="flex items-center gap-2">
            <ArrowUpRight className="h-4 w-4 text-primary" />
            Одобрено / выдано:{' '}
            <span className="font-semibold text-card-foreground">
              {data.metrics.approvedApplications + data.metrics.disbursedApplications} заявок
            </span>
          </div>
          <div className="flex items-center gap-2">
            <Clock className="h-4 w-4 text-primary" />
            Просрочка:{' '}
            <span className="font-semibold text-card-foreground">{data.metrics.overdueShare}%</span>
          </div>
        </div>
      </section>

      <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        {metricsCards.map((card) => (
          <article key={card.title} className="rounded-xl border bg-card p-4">
            <div className="flex items-center justify-between text-sm text-muted-foreground">
              <span>{card.title}</span>
              <card.icon className="h-4 w-4 text-primary" />
            </div>
            <p className="mt-3 text-2xl font-semibold text-card-foreground">{card.value}</p>
            <p className="mt-1 text-xs text-muted-foreground">{card.hint}</p>
          </article>
        ))}
      </section>

      <section className="grid gap-6 xl:grid-cols-[1.2fr,1fr]">
        <article className="rounded-xl border bg-card p-6 shadow-sm">
          <header className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h2 className="text-lg font-semibold text-card-foreground">Воронка одобрений</h2>
              <p className="text-sm text-muted-foreground">
                Стадии прохождения заявок от первичной оценки до выдачи.
              </p>
            </div>
            <Button variant="outline" size="sm" className="gap-2">
              <FileSpreadsheet className="h-4 w-4" />
              Экспортировать
            </Button>
          </header>
          <div className="mt-6 space-y-4">
            {pipelineWithShare.map((stage) => {
              const style = adminStatusStyles[stage.status] ?? {
                label: stage.status,
                className: 'bg-muted text-muted-foreground',
              }
              return (
                <div key={stage.status} className="rounded-lg border bg-background p-4">
                  <div className="flex items-center justify-between text-sm">
                    <div className="flex items-center gap-2">
                      <Badge variant="outline" className={style.className}>
                        {style.label}
                      </Badge>
                      <span className="text-muted-foreground">
                        {stage.count} заявок · {stage.share}%
                      </span>
                    </div>
                    <span className="text-xs text-muted-foreground">
                      Конверсия: {Math.min(stage.share + 12, 100)}%
                    </span>
                  </div>
                  <div className="mt-3 h-2 w-full rounded-full bg-muted">
                    <div
                      className="h-2 rounded-full bg-primary transition-all"
                      style={{ width: `${stage.share}%` }}
                    />
                  </div>
                </div>
              )
            })}
          </div>
        </article>

        <article className="rounded-xl border bg-card p-6 shadow-sm">
          <header className="flex items-center justify-between">
            <div>
              <h2 className="text-lg font-semibold text-card-foreground">Динамика решений</h2>
              <p className="text-sm text-muted-foreground">
                Одобрения и отказы по дням недели (последние 7 дней).
              </p>
            </div>
            <Badge variant="outline" className="bg-primary/10 text-primary">
              Ср. {dailyAvgApproved} одобр./день
            </Badge>
          </header>
          <div className="mt-5 space-y-3">
            {data.daily.map((item) => {
              const total = item.approved + item.rejected
              const approvedShare = total > 0 ? Math.round((item.approved / total) * 100) : 0
              const rejectedShare = total > 0 ? 100 - approvedShare : 0

              return (
                <div key={item.date}>
                  <div className="flex items-center justify-between text-xs text-muted-foreground">
                    <span className="font-medium text-card-foreground">{item.date}</span>
                    <span>
                      {item.approved} / {item.rejected}
                    </span>
                  </div>
                  <div className="mt-2 flex h-2 w-full overflow-hidden rounded-full bg-muted">
                    <div className="h-2 bg-emerald-500" style={{ width: `${approvedShare}%` }} />
                    <div className="h-2 bg-red-400" style={{ width: `${rejectedShare}%` }} />
                  </div>
                </div>
              )
            })}
          </div>
        </article>
      </section>

      <section className="grid gap-6 lg:grid-cols-[1.3fr,1fr]">
        <article className="rounded-xl border bg-card p-6 shadow-sm">
          <header className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h2 className="text-lg font-semibold text-card-foreground">Заявки</h2>
              <p className="text-sm text-muted-foreground">
                Детализированный список заявок с этапом обработки и ответственными.
              </p>
            </div>
            <Button size="sm" className="gap-2">
              <ArrowUpRight className="h-4 w-4" />
              Новая заявка
            </Button>
          </header>
          <div className="mt-6 overflow-x-auto">
            <table className="min-w-full divide-y divide-border text-sm">
              <thead className="bg-muted/50 text-left text-xs uppercase tracking-wide text-muted-foreground">
                <tr>
                  <th className="px-4 py-3">ID</th>
                  <th className="px-4 py-3">Клиент</th>
                  <th className="px-4 py-3">Сумма</th>
                  <th className="px-4 py-3">Ставка</th>
                  <th className="px-4 py-3">Срок</th>
                  <th className="px-4 py-3">Менеджер</th>
                  <th className="px-4 py-3">Статус</th>
                  <th className="px-4 py-3 text-right">% выдачи</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border bg-card">
                {data.loans.map((loan) => (
                  <LoanRow key={loan.id} loan={loan} />
                ))}
              </tbody>
            </table>
          </div>
        </article>

        <article className="rounded-xl border bg-card p-6 shadow-sm">
          <header className="flex items-center justify-between">
            <div>
              <h2 className="text-lg font-semibold text-card-foreground">Команда</h2>
              <p className="text-sm text-muted-foreground">SLA и загрузка аналитиков и риск-менеджеров.</p>
            </div>
            <Badge variant="outline" className="bg-emerald-100 text-emerald-800">
              {data.team.length} специалиста
            </Badge>
          </header>
          <div className="mt-5 space-y-4 text-sm">
            {data.team.map((member) => (
              <div key={member.id} className="rounded-lg border bg-background p-4">
                <div className="flex flex-col gap-1">
                  <div className="flex items-center justify-between">
                    <h3 className="text-base font-semibold text-card-foreground">{member.name}</h3>
                    <span className="text-xs text-muted-foreground">{member.role}</span>
                  </div>
                  <div className="mt-2 grid grid-cols-2 gap-3 text-xs text-muted-foreground">
                    <div>
                      Загрузка: <span className="text-card-foreground">{member.workload}%</span>
                      <div className="mt-1 h-2 w-full rounded-full bg-muted">
                        <div
                          className="h-2 rounded-full bg-primary"
                          style={{ width: `${member.workload}%` }}
                        />
                      </div>
                    </div>
                    <div>
                      Одобрения: <span className="text-card-foreground">{member.approvalRate}%</span>
                      <div className="mt-1 h-2 w-full rounded-full bg-primary/20">
                        <div
                          className="h-2 rounded-full bg-emerald-500"
                          style={{ width: `${member.approvalRate}%` }}
                        />
                      </div>
                    </div>
                  </div>
                  <p className="mt-3 text-xs text-muted-foreground">
                    Среднее время решения: <span className="text-card-foreground">{member.sla}</span>
                  </p>
                </div>
              </div>
            ))}
          </div>
        </article>
      </section>
    </div>
  )
}

type LoanRowProps = {
  loan: AdminLoanDto
}

function LoanRow({ loan }: LoanRowProps) {
  const statusStyle = adminStatusStyles[loan.status] ?? {
    label: loan.status,
    className: 'bg-muted text-muted-foreground',
  }

  return (
    <tr>
      <td className="px-4 py-3 font-medium text-card-foreground">{loan.id}</td>
      <td className="px-4 py-3 text-muted-foreground">
        <div className="flex flex-col">
          <span>{loan.customerName ?? '—'}</span>
          {loan.customerStatus ? (
            <span className="text-xs text-muted-foreground/70">Статус: {loan.customerStatus}</span>
          ) : null}
        </div>
      </td>
      <td className="px-4 py-3 font-medium text-card-foreground">{moneyHint(loan.principal)}</td>
      <td className="px-4 py-3 text-muted-foreground">{loan.interestRate.toFixed(1)}%</td>
      <td className="px-4 py-3 text-muted-foreground">{loan.termMonths} мес.</td>
      <td className="px-4 py-3 text-muted-foreground">{loan.manager?.name ?? '—'}</td>
      <td className="px-4 py-3">
        <Badge variant='outline' className={statusStyle.className}>
          {statusStyle.label}
        </Badge>
      </td>
      <td className="px-4 py-3">
        <div className="flex items-center justify-end gap-2">
          <span className="text-sm text-muted-foreground">{loan.probability}%</span>
          <div className="h-2 w-16 rounded-full bg-muted">
            <div className="h-2 rounded-full bg-primary" style={{ width: `${loan.probability}%` }} />
          </div>
        </div>
      </td>
    </tr>
  )
}
