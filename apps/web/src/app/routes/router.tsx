import { Navigate, createBrowserRouter } from 'react-router-dom'

import { AdminDashboardPage } from '@/pages/admin'
import { ClientPortalPage } from '@/pages/client'
import { MainLayout } from '@/widgets/layout/main-layout'

export const router = createBrowserRouter([
  {
    path: '/',
    element: <MainLayout />,
    children: [
      {
        index: true,
        element: <Navigate to="/client" replace />,
      },
      {
        path: 'client',
        element: <ClientPortalPage />,
      },
      {
        path: 'admin',
        element: <AdminDashboardPage />,
      },
    ],
  },
])
