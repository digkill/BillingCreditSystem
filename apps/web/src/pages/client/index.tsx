import { useEffect, useMemo, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import {
  ArrowRight,
  CalendarClock,
  CheckCircle2,
  FileText,
  HandCoins,
  Loader2,
  Sparkles,
} from 'lucide-react'

import { fetchClientDashboard } from '@/shared/api/dashboard'
import type { LoanDto, LoanOfferDto, MoneyDto } from '@/shared/api/types'
import { Button } from '@/shared/components/ui/button'
import { Badge } from '@/shared/components/ui/badge'
import { cn } from '@/shared/lib/utils'

const loanStatusMeta: Record<
  string,
  {
    label: string
    className: string
  }
> = {
  draft: { label: 'Черновик', className: 'bg-slate-200 text-slate-800' },
  submitted: { label: 'На проверке', className: 'bg-blue-100 text-blue-800' },
  approved: { label: 'Одобрено', className: 'bg-emerald-100 text-emerald-800' },
  active: { label: 'Активен', className: 'bg-emerald-200 text-emerald-900' },
  closed: { label: 'Закрыт', className: 'bg-slate-300 text-slate-700' },
  rejected: { label: 'Отказ', className: 'bg-rose-100 text-rose-900' },
}

const paymentStatusMeta: Record<string, { label: string; className: string }> = {
  scheduled: { label: 'Запланирован', className: 'text-slate-600' },
  due: { label: 'К оплате', className: 'text-blue-700' },
  paid: { label: 'Оплачен', className: 'text-emerald-700' },
  overdue: { label: 'Просрочен', className: 'text-red-600 font-semibold' },
}

const monthDayFormatter = new Intl.DateTimeFormat('ru-RU', {
  day: '2-digit',
  month: 'long',
})

const fullDateFormatter = new Intl.DateTimeFormat('ru-RU', {
  day: '2-digit',
  month: 'long',
  year: 'numeric',
})

function formatMoney(money?: MoneyDto | null) {
  if (!money) {
    return '—'
  }

  const formatter = new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: money.currency,
    maximumFractionDigits: 0,
  })

  return formatter.format(money.amount)
}

function formatDate(value?: string | null, formatter: Intl.DateTimeFormat = monthDayFormatter) {
  if (!value) {
    return '—'
  }

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return '—'
  }

  return formatter.format(date)
}

function loanProgress(loan: LoanDto) {
  if (loan.principal.minorUnits === 0) {
    return 0
  }

  const paidMinor = Math.max(
    0,
    loan.principal.minorUnits - loan.outstanding.minorUnits
  )
  const progress = Math.round((paidMinor / loan.principal.minorUnits) * 100)

  return Math.min(Math.max(progress, 0), 100)
}

const DEFAULT_CUSTOMER_ID = import.meta.env.VITE_DEFAULT_CUSTOMER_ID as string | undefined

export function ClientPortalPage() {
  const {
    data,
    isLoading,
    isError,
    error,
  } = useQuery({
    queryKey: ['client-dashboard', DEFAULT_CUSTOMER_ID],
    queryFn: () => fetchClientDashboard(DEFAULT_CUSTOMER_ID ?? ''),
    enabled: Boolean(DEFAULT_CUSTOMER_ID),
  })

  const offers = data?.offers ?? []
  const [selectedOfferId, setSelectedOfferId] = useState<string | null>(null)
  const [simulation, setSimulation] = useState<{ amount: number; term: number }>({
    amount: 300_000,
    term: 12,
  })

  useEffect(() => {
    if (!offers.length) {
      return
    }

    const fallback = offers[0]
    const current = offers.find((offer) => offer.id === selectedOfferId) ?? fallback

    if (!selectedOfferId) {
      setSelectedOfferId(current.id)
    }

    setSimulation((prev) => {
      const clampedAmount =
        prev.amount > 0 ? Math.min(prev.amount, current.maxAmount) : Math.min(300_000, current.maxAmount)
      const clampedTerm = Math.min(Math.max(prev.term || current.term.from, current.term.from), current.term.to)

      if (prev.amount === clampedAmount && prev.term === clampedTerm) {
        return prev
      }

      return { amount: clampedAmount, term: clampedTerm }
    })
  }, [offers, selectedOfferId])

  const selectedOffer = useMemo<LoanOfferDto | undefined>(() => {
    if (!offers.length) {
      return undefined
    }
    const offerId = selectedOfferId ?? offers[0].id
    return offers.find((offer) => offer.id === offerId) ?? offers[0]
  }, [offers, selectedOfferId])

  const monthlyPayment = useMemo(() => {
    if (!selectedOffer) {
      return 0
    }

    const amount = Math.max(0, simulation.amount)
    const term = Math.max(1, simulation.term)
    const monthlyRate = selectedOffer.rate / 100 / 12

    if (monthlyRate === 0) {
      return amount / term
    }

    const factor = Math.pow(1 + monthlyRate, term)
    return (amount * monthlyRate * factor) / (factor - 1)
  }, [selectedOffer, simulation.amount, simulation.term])

  const totalPayment = useMemo(() => monthlyPayment * Math.max(1, simulation.term), [monthlyPayment, simulation.term])

  const activeLoans = useMemo(() => (data?.loans ?? []).filter((loan) => loan.status === 'active'), [data])
  if (!DEFAULT_CUSTOMER_ID) {
    return (
      <div className="rounded-xl border border-dashed border-amber-400 bg-amber-50 p-6 text-sm text-amber-900">
        Не указан идентификатор клиента. Добавьте переменную окружения
        <code className="mx-1 rounded bg-amber-100 px-1 py-0.5 text-xs">VITE_DEFAULT_CUSTOMER_ID</code>
        с UUID клиента, созданного в CRM.
      </div>
    )
  }

  if (isLoading) {
    return (
      <div className="flex min-h-[320px] items-center justify-center text-muted-foreground">
        <Loader2 className="mr-2 h-5 w-5 animate-spin" />
        Загружаем данные личного кабинета...
      </div>
    )
  }

  if (isError || !data) {
    return (
      <div className="rounded-xl border border-destructive/50 bg-destructive/10 p-6 text-sm text-destructive">
        Не удалось загрузить данные личного кабинета.
        {error instanceof Error ? ` ${error.message}` : null}
      </div>
    )
  }

  const { customer, metrics, applications, payments } = data
  const customerIdShort = customer.id.slice(0, 8).toUpperCase()

  return (
    <div className="space-y-10">
      <section className="flex flex-col gap-4 rounded-xl border bg-card p-6 shadow-sm md:flex-row md:items-center md:justify-between">
        <div>
          <p className="text-sm text-muted-foreground">Клиент #{customerIdShort}</p>
          <h1 className="text-2xl font-semibold text-card-foreground">{customer.fullName}</h1>
          <p className="mt-2 max-w-xl text-sm text-muted-foreground">
            Управляйте активными займами, отслеживайте платежи и отправляйте новые заявки онлайн.
          </p>
        </div>
        <div className="flex flex-col gap-2 text-sm text-muted-foreground">
          <div className="flex items-center gap-2">
            <CheckCircle2 className="h-4 w-4 text-emerald-500" />
            Статус клиента:{' '}
            <span className="font-semibold text-card-foreground">
              {loanStatusMeta[customer.status]?.label ?? customer.status}
            </span>
          </div>
          <div className="flex items-center gap-2">
            <HandCoins className="h-4 w-4 text-primary" />
            Email:{' '}
            <span className="font-semibold text-card-foreground">{customer.email}</span>
          </div>
        </div>
      </section>

      <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article className="rounded-xl border bg-card p-4">
          <p className="text-sm text-muted-foreground">Активные займы</p>
          <h2 className="mt-3 text-2xl font-semibold text-card-foreground">{metrics.activeLoans}</h2>
          <p className="mt-1 text-xs text-muted-foreground">Количество действующих договоров</p>
        </article>
        <article className="rounded-xl border bg-card p-4">
          <p className="text-sm text-muted-foreground">Выдано займов</p>
          <h2 className="mt-3 text-2xl font-semibold text-card-foreground">
            {formatMoney(metrics.totalPrincipal)}
          </h2>
          <p className="mt-1 text-xs text-muted-foreground">Сумма по всем заявкам клиента</p>
        </article>
        <article className="rounded-xl border bg-card p-4">
          <p className="text-sm text-muted-foreground">Остаток задолженности</p>
          <h2 className="mt-3 text-2xl font-semibold text-card-foreground">
            {formatMoney(metrics.outstandingBalance)}
          </h2>
          <p className="mt-1 text-xs text-muted-foreground">С учётом всех активных договоров</p>
        </article>
        <article className="rounded-xl border bg-card p-4">
          <p className="text-sm text-muted-foreground">Ближайший платёж</p>
          <h2 className="mt-3 text-2xl font-semibold text-card-foreground">
            {formatMoney(metrics.nextPayment?.expectedAmount)}
          </h2>
          <p className="mt-1 text-xs text-muted-foreground">
            {metrics.nextPayment ? formatDate(metrics.nextPayment.dueDate) : 'Дата не назначена'}
          </p>
        </article>
      </section>

      <section className="grid gap-6 lg:grid-cols-3">
        <article className="rounded-xl border bg-card p-6 shadow-sm lg:col-span-2">
          <header className="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
              <h2 className="text-lg font-semibold text-card-foreground">Активные займы</h2>
              <p className="text-sm text-muted-foreground">
                Детальная информация по текущим договорам и статусу выплат.
              </p>
            </div>
            <Button variant="outline" size="sm" className="gap-2">
              <FileText className="h-4 w-4" />
              График платежей
            </Button>
          </header>
          <div className="mt-6 space-y-5">
            {activeLoans.length === 0 ? (
              <div className="rounded-lg border border-dashed border-muted-foreground/40 bg-muted/40 p-6 text-sm text-muted-foreground">
                У клиента нет активных займов. Отправьте заявку, чтобы получить финансирование.
              </div>
            ) : (
              activeLoans.map((loan) => {
                const status = loanStatusMeta[loan.status] ?? {
                  label: loan.status,
                  className: 'bg-muted text-muted-foreground',
                }
                const progress = loanProgress(loan)
                return (
                  <div key={loan.id} className="rounded-lg border bg-background p-4">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                      <div>
                        <p className="text-sm text-muted-foreground">{loan.id}</p>
                        <h3 className="text-base font-semibold text-card-foreground">
                          Основной долг: {formatMoney(loan.principal)}
                        </h3>
                        <p className="mt-1 text-xs text-muted-foreground">
                          Выдан {formatDate(loan.activatedAt, fullDateFormatter)}, ставка{' '}
                          {loan.interestRate.toFixed(1)}% · срок {loan.termMonths} мес.
                        </p>
                      </div>
                      <Badge variant="outline" className={status.className}>
                        {status.label}
                      </Badge>
                    </div>
                    <div className="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                      <div className="flex flex-col gap-1 text-sm text-muted-foreground">
                        <span>
                          Остаток задолженности:{' '}
                          <strong className="text-card-foreground">{formatMoney(loan.outstanding)}</strong>
                        </span>
                        <span>
                          Следующий платёж:{' '}
                          <strong className="text-card-foreground">
                            {loan.nextPayment ? formatMoney(loan.nextPayment.expectedAmount) : '—'}
                          </strong>{' '}
                          {loan.nextPayment ? `до ${formatDate(loan.nextPayment.dueDate)}` : ''}
                        </span>
                      </div>
                    </div>
                    <div className="mt-4">
                      <div className="flex justify-between text-xs text-muted-foreground">
                        <span>Погашено {progress}%</span>
                        <span>Сумма {formatMoney(loan.principal)}</span>
                      </div>
                      <div className="mt-2 h-2 w-full rounded-full bg-muted">
                        <div
                          className="h-2 rounded-full bg-primary transition-all"
                          style={{ width: `${progress}%` }}
                        />
                      </div>
                    </div>
                  </div>
                )
              })
            )}
          </div>
        </article>
        <aside className="flex flex-col gap-4 rounded-xl border bg-card p-6 shadow-sm">
          <h2 className="text-lg font-semibold text-card-foreground">Документы и отчёты</h2>
          <div className="space-y-3 text-sm text-muted-foreground">
            <button className="flex w-full items-center justify-between rounded-lg border border-dashed p-3 text-left transition hover:border-primary hover:text-primary">
              <span className="flex items-center gap-2">
                <FileText className="h-4 w-4" />
                Выписка по счёту за квартал
              </span>
              <ArrowRight className="h-4 w-4" />
            </button>
            <button className="flex w-full items-center justify-between rounded-lg border border-dashed p-3 text-left transition hover:border-primary hover:text-primary">
              <span className="flex items-center gap-2">
                <CalendarClock className="h-4 w-4" />
                Запрос на отсрочку платежа
              </span>
              <ArrowRight className="h-4 w-4" />
            </button>
            <button className="flex w-full items-center justify-between rounded-lg border border-dashed p-3 text-left transition hover:border-primary hover:text-primary">
              <span className="flex items-center gap-2">
                <Sparkles className="h-4 w-4" />
                Финансирование по ускоренной процедуре
              </span>
              <ArrowRight className="h-4 w-4" />
            </button>
          </div>
          <div className="rounded-lg bg-muted/60 p-4 text-sm text-muted-foreground">
            <p className="font-semibold text-card-foreground">Поддержка</p>
            <p className="mt-1">Ваш персональный менеджер: Мария Зорина</p>
            <p className="mt-1">Тел: +7 (999) 555-43-21, support@credit.local</p>
          </div>
        </aside>
      </section>

      <section className="grid gap-6 xl:grid-cols-2">
        <article className="rounded-xl border bg-card p-6 shadow-sm">
          <header className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h2 className="text-lg font-semibold text-card-foreground">График платежей</h2>
              <p className="text-sm text-muted-foreground">
                Предстоящие и завершённые платежи по активным займам.
              </p>
            </div>
            <Button variant="outline" size="sm" className="gap-2">
              <FileText className="h-4 w-4" />
              Скачать PDF
            </Button>
          </header>
          <div className="mt-6 overflow-hidden rounded-lg border">
            <table className="min-w-full divide-y divide-border text-sm">
              <thead className="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground">
                <tr className="text-left">
                  <th className="px-4 py-3">Дата</th>
                  <th className="px-4 py-3">Сумма</th>
                  <th className="px-4 py-3">Статус</th>
                  <th className="px-4 py-3">Займ</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border bg-card">
                {payments.length === 0 ? (
                  <tr>
                    <td className="px-4 py-4 text-center text-sm text-muted-foreground" colSpan={4}>
                      Платежи не найдены.
                    </td>
                  </tr>
                ) : (
                  payments.map((payment) => {
                    const meta = paymentStatusMeta[payment.status] ?? {
                      label: payment.status,
                      className: 'text-muted-foreground',
                    }
                    return (
                      <tr key={payment.id}>
                        <td className="px-4 py-3 text-card-foreground">{formatDate(payment.dueDate, fullDateFormatter)}</td>
                        <td className="px-4 py-3 font-medium text-card-foreground">
                          {formatMoney(payment.expectedAmount)}
                        </td>
                        <td className={cn('px-4 py-3 text-sm', meta.className)}>{meta.label}</td>
                        <td className="px-4 py-3 text-xs text-muted-foreground">{payment.loanId}</td>
                      </tr>
                    )
                  })
                )}
              </tbody>
            </table>
          </div>
        </article>

        <article className="rounded-xl border bg-card p-6 shadow-sm">
          <header className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h2 className="text-lg font-semibold text-card-foreground">Мои заявки</h2>
              <p className="text-sm text-muted-foreground">
                История рассмотрения и текущий статус заявок на финансирование.
              </p>
            </div>
            <Button variant="outline" size="sm" className="gap-2">
              <Sparkles className="h-4 w-4" />
              Новая заявка
            </Button>
          </header>
          <ol className="mt-6 space-y-4">
            {applications.length === 0 ? (
              <div className="rounded-lg border border-dashed border-muted-foreground/50 bg-muted/30 p-5 text-sm text-muted-foreground">
                Пока нет заявок. Нажмите «Новая заявка», чтобы отправить запрос на финансирование.
              </div>
            ) : (
              applications.map((application) => {
                const status = loanStatusMeta[application.status] ?? {
                  label: application.status,
                  className: 'bg-muted text-muted-foreground',
                }
                return (
                  <li key={application.id} className="rounded-lg border bg-background p-4">
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                      <div>
                        <p className="text-sm text-muted-foreground">{application.id}</p>
                        <h3 className="text-base font-semibold text-card-foreground">
                          Сумма {formatMoney(application.principal)}, срок {application.termMonths} мес.
                        </h3>
                      </div>
                      <Badge variant="outline" className={status.className}>
                        {status.label}
                      </Badge>
                    </div>
                    <div className="mt-3 flex flex-col gap-1 text-xs text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                      <span>Создана {formatDate(application.submittedAt, fullDateFormatter)}</span>
                      <span>Обновлена {formatDate(application.updatedAt, fullDateFormatter)}</span>
                    </div>
                  </li>
                )
              })
            )}
          </ol>
        </article>
      </section>

      <section className="grid gap-6 lg:grid-cols-[2fr,3fr]">
        <article className="rounded-xl border bg-card p-6 shadow-sm">
          <h2 className="text-lg font-semibold text-card-foreground">Персональные предложения</h2>
          <p className="mt-1 text-sm text-muted-foreground">
            Персональные условия сформированы на основе вашей кредитной истории.
          </p>
          <div className="mt-5 space-y-4">
            {offers.map((offer) => (
              <button
                key={offer.id}
                type="button"
                onClick={() => setSelectedOfferId(offer.id)}
                className={cn(
                  'w-full rounded-lg border bg-background p-4 text-left transition',
                  offer.id === selectedOffer?.id ? 'border-primary shadow-sm' : 'border-muted hover:border-primary/60'
                )}
              >
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                  <div>
                    <h3 className="text-base font-semibold text-card-foreground">{offer.name}</h3>
                    <p className="mt-1 text-sm text-muted-foreground">{offer.description}</p>
                    <div className="mt-3 flex flex-wrap gap-2 text-xs text-muted-foreground">
                      {offer.purposes.map((purpose) => (
                        <span key={purpose} className="rounded-full bg-muted px-2 py-1">
                          {purpose}
                        </span>
                      ))}
                    </div>
                  </div>
                  <div className="text-sm text-muted-foreground">
                    <p>
                      Ставка от{' '}
                      <span className="font-semibold text-card-foreground">{offer.rate}%</span>
                    </p>
                    <p className="mt-1">
                      До{' '}
                      <span className="font-semibold text-card-foreground">
                        {new Intl.NumberFormat('ru-RU').format(offer.maxAmount)} ₽
                      </span>
                    </p>
                    <p className="mt-1">
                      Срок: {offer.term.from}–{offer.term.to} мес.
                    </p>
                  </div>
                </div>
                {offer.preApproved ? (
                  <Badge variant="outline" className="mt-3 bg-emerald-100 text-emerald-800">
                    Предварительно одобрено
                  </Badge>
                ) : null}
              </button>
            ))}
          </div>
        </article>

        <article className="rounded-xl border bg-card p-6 shadow-sm">
          <header>
            <h2 className="text-lg font-semibold text-card-foreground">Быстрая заявка</h2>
            <p className="mt-1 text-sm text-muted-foreground">
              Рассчитайте ориентировочный платёж и отправьте запрос на предварительное одобрение.
            </p>
          </header>
          <form className="mt-6 space-y-4">
            <label className="flex flex-col gap-1 text-sm">
              <span className="text-muted-foreground">Продукт</span>
              <select
                className="rounded-md border bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                value={selectedOffer?.id ?? ''}
                onChange={(event) => setSelectedOfferId(event.target.value)}
              >
                {offers.map((offer) => (
                  <option key={offer.id} value={offer.id}>
                    {offer.name}
                  </option>
                ))}
              </select>
            </label>
            <label className="flex flex-col gap-1 text-sm">
              <span className="text-muted-foreground">Сумма, ₽</span>
              <input
                type="number"
                min={10_000}
                step={10_000}
                className="rounded-md border bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                value={simulation.amount}
                onChange={(event) =>
                  setSimulation((prev) => ({
                    ...prev,
                    amount: Math.max(10_000, Math.min(Number.parseInt(event.target.value, 10) || 0, selectedOffer?.maxAmount ?? prev.amount)),
                  }))
                }
              />
            </label>
            <label className="flex flex-col gap-1 text-sm">
              <span className="text-muted-foreground">Срок, месяцев</span>
              <input
                type="number"
                min={selectedOffer?.term.from ?? 1}
                max={selectedOffer?.term.to ?? 60}
                className="rounded-md border bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                value={simulation.term}
                onChange={(event) =>
                  setSimulation((prev) => ({
                    ...prev,
                    term: Math.max(
                      selectedOffer?.term.from ?? 1,
                      Math.min(Number.parseInt(event.target.value, 10) || 1, selectedOffer?.term.to ?? 60)
                    ),
                  }))
                }
              />
            </label>
            <div className="rounded-lg bg-muted/60 p-4 text-sm text-muted-foreground">
              <p className="flex items-center gap-2 text-card-foreground">
                <Sparkles className="h-4 w-4 text-primary" />
                Предварительный расчёт
              </p>
              <p className="mt-2">
                Ежемесячный платёж:{' '}
                <span className="font-semibold text-card-foreground">
                  {new Intl.NumberFormat('ru-RU').format(Math.round(monthlyPayment))} ₽
                </span>
              </p>
              <p className="mt-1">
                Переплата за срок:{' '}
                <span className="font-semibold text-card-foreground">
                  {new Intl.NumberFormat('ru-RU').format(Math.max(Math.round(totalPayment - simulation.amount), 0))} ₽
                </span>
              </p>
              {selectedOffer ? (
                <p className="mt-1">Ставка по продукту: {selectedOffer.rate}% годовых</p>
              ) : null}
            </div>
            <div className="flex items-center justify-between">
              <div className="text-xs text-muted-foreground">
                Отправляя запрос, вы соглашаетесь с обработкой данных и получаете ответ за 15 минут.
              </div>
              <Button type="button" className="gap-2">
                Отправить заявку
                <ArrowRight className="h-4 w-4" />
              </Button>
            </div>
          </form>
        </article>
      </section>
    </div>
  )
}
