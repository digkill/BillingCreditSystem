import { RouterProvider } from 'react-router-dom'

import { AppProvider } from '@/app/providers/app-provider'
import { router } from '@/app/routes/router'

function App() {
  return (
    <AppProvider>
      <RouterProvider router={router} />
    </AppProvider>
  )
}

export default App

