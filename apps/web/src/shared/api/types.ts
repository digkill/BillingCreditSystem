export type MoneyDto = {
  amount: number
  currency: string
  minorUnits: number
}

export type PaymentDto = {
  id: string
  loanId: string
  dueDate: string
  status: string
  expectedAmount: MoneyDto
  paidAmount: MoneyDto
  createdAt: string
  updatedAt: string
}

export type LoanScheduleItemDto = {
  dueDate: string
  amount: MoneyDto | null
}

export type LoanDto = {
  id: string
  status: string
  principal: MoneyDto
  interestRate: number
  termMonths: number
  createdAt: string
  updatedAt: string
  submittedAt: string | null
  approvedAt: string | null
  activatedAt: string | null
  closedAt: string | null
  rejectedAt: string | null
  schedule: LoanScheduleItemDto[]
  outstanding: MoneyDto
  nextPayment: PaymentDto | null
  payments: PaymentDto[]
}

export type ApplicationSummaryDto = {
  id: string
  status: string
  principal: MoneyDto
  termMonths: number
  updatedAt: string | null
  submittedAt: string | null
  approvedAt: string | null
}

export type ClientDashboardResponse = {
  customer: {
    id: string
    fullName: string
    email: string
    status: string
    createdAt: string
    updatedAt: string
  }
  metrics: {
    activeLoans: number
    totalPrincipal: MoneyDto | null
    outstandingBalance: MoneyDto | null
    nextPayment: PaymentDto | null
  }
  loans: LoanDto[]
  applications: ApplicationSummaryDto[]
  payments: PaymentDto[]
  offers: LoanOfferDto[]
}

export type LoanOfferDto = {
  id: string
  name: string
  description: string
  rate: number
  maxAmount: number
  term: {
    from: number
    to: number
  }
  preApproved: boolean
  purposes: string[]
}

export type AdminLoanDto = {
  id: string
  customerId: string
  customerName: string | undefined
  customerStatus: string | undefined
  status: string
  principal: MoneyDto
  interestRate: number
  termMonths: number
  createdAt: string
  submittedAt: string | null
  approvedAt: string | null
  activatedAt: string | null
  updatedAt: string
  riskBand: string
  probability: number
  manager:
    | {
        id: string
        name: string
        role: string
      }
    | null
}

export type AdminDashboardResponse = {
  metrics: {
    totalApplications: number
    pendingApplications: number
    approvedApplications: number
    disbursedApplications: number
    rejectedApplications: number
    approvalRate: number
    portfolioAmount: MoneyDto | null
    averageTicket: MoneyDto | null
    overdueShare: number
    nplShare: number
  }
  pipeline: Array<{
    status: string
    count: number
  }>
  daily: Array<{
    date: string
    approved: number
    rejected: number
  }>
  loans: AdminLoanDto[]
  team: Array<{
    id: string
    name: string
    role: string
    workload: number
    approvalRate: number
    sla: string
  }>
}
