import { Outlet } from 'react-router-dom'
import { Building2, CreditCard, Users } from 'lucide-react'

import { Button } from '@/shared/components/ui/button'

export function MainLayout() {
  return (
    <div className="min-h-screen bg-muted/30">
      <header className="border-b bg-background">
        <div className="container flex items-center justify-between py-4">
          <div className="flex items-center gap-2 text-lg font-semibold">
            <CreditCard className="h-5 w-5 text-primary" />
            Billing Credit System
          </div>
          <nav className="flex items-center gap-4 text-sm text-muted-foreground">
            <a href="#loans" className="flex items-center gap-2 hover:text-foreground">
              <Building2 className="h-4 w-4" /> Loans
            </a>
            <a href="#customers" className="flex items-center gap-2 hover:text-foreground">
              <Users className="h-4 w-4" /> Customers
            </a>
          </nav>
          <Button variant="secondary" size="sm">
            New Loan Application
          </Button>
        </div>
      </header>
      <main className="container py-8">
        <Outlet />
      </main>
    </div>
  )
}

