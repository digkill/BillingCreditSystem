import type {
  AdminDashboardResponse,
  ClientDashboardResponse,
} from '@/shared/api/types'
import { apiClient } from './http'

export async function fetchClientDashboard(customerId: string) {
  if (!customerId) {
    throw new Error('Идентификатор клиента не указан')
  }

  const { data } = await apiClient.get<ClientDashboardResponse>(`/client/${customerId}`)
  return data
}

export async function fetchAdminDashboard() {
  const { data } = await apiClient.get<AdminDashboardResponse>('/admin/dashboard')
  return data
}

