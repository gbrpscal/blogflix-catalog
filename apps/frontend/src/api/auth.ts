import { ensureCsrfCookie, http } from './http'
import type { User } from '@/types'

export const authApi = {
  async user(): Promise<User> {
    const response = await http.get<{ data: User }>('/auth/user')
    return response.data.data
  },
  async login(payload: { email: string; password: string; remember: boolean }): Promise<void> {
    await ensureCsrfCookie()
    await http.post('/auth/login', payload)
  },
  async register(payload: {
    name: string
    email: string
    password: string
    password_confirmation: string
  }): Promise<void> {
    await ensureCsrfCookie()
    await http.post('/auth/register', payload)
  },
  async logout(): Promise<void> {
    await ensureCsrfCookie()
    await http.post('/auth/logout')
  },
  async forgotPassword(email: string): Promise<void> {
    await ensureCsrfCookie()
    await http.post('/auth/forgot-password', { email })
  },
  async resetPassword(payload: {
    email: string
    token: string
    password: string
    password_confirmation: string
  }): Promise<void> {
    await ensureCsrfCookie()
    await http.post('/auth/reset-password', payload)
  },
  async resendVerification(): Promise<void> {
    await ensureCsrfCookie()
    await http.post('/auth/email/verification-notification')
  },
}
