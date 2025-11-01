import { createBrowserRouter } from 'react-router-dom'

import { DashboardPage } from '@/pages/dashboard'
import { MainLayout } from '@/widgets/layout/main-layout'

export const router = createBrowserRouter([
  {
    path: '/',
    element: <MainLayout />,
    children: [
      {
        index: true,
        element: <DashboardPage />,
      },
    ],
  },
])
