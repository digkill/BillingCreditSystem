import { NavLink, Outlet } from 'react-router-dom'
import { Building2, CreditCard, ShieldCheck } from 'lucide-react'

import { Button } from '@/shared/components/ui/button'
import { cn } from '@/shared/lib/utils'

export function MainLayout() {
  const navItems = [
    {
      to: '/client',
      label: 'Личный кабинет',
      icon: CreditCard,
    },
    {
      to: '/admin',
      label: 'Админ-панель',
      icon: ShieldCheck,
    },
  ]

  return (
    <div className="min-h-screen bg-muted/30">
      <header className="border-b bg-background">
        <div className="container flex items-center justify-between py-4">
          <div className="flex items-center gap-2 text-lg font-semibold">
            <CreditCard className="h-5 w-5 text-primary" />
            Billing Credit System
          </div>
          <nav className="flex items-center gap-4 text-sm">
            {navItems.map((item) => (
              <NavLink
                key={item.to}
                to={item.to}
                className={({ isActive }) =>
                  cn(
                    'flex items-center gap-2 rounded-md px-3 py-2 transition-colors',
                    isActive ? 'bg-muted text-foreground' : 'text-muted-foreground hover:text-foreground'
                  )
                }
              >
                <item.icon className="h-4 w-4" />
                {item.label}
              </NavLink>
            ))}
          </nav>
          <Button variant="secondary" size="sm" className="gap-2">
            <Building2 className="h-4 w-4" />
            Новая заявка
          </Button>
        </div>
      </header>
      <main className="container py-8">
        <Outlet />
      </main>
    </div>
  )
}

