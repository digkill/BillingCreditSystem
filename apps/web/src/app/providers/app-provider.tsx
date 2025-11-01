import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import React from 'react'

import { ThemeProvider } from './theme-provider'

const queryClient = new QueryClient()

type Props = {
  children: React.ReactNode
}

export function AppProvider({ children }: Props) {
  return (
    <QueryClientProvider client={queryClient}>
      <ThemeProvider>{children}</ThemeProvider>
    </QueryClientProvider>
  )
}
