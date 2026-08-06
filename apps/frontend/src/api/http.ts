import axios, { AxiosError } from 'axios'
import type { ValidationErrors } from '@/types'

export const http = axios.create({
  baseURL: '/api/v1',
  headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
  withCredentials: true,
  withXSRFToken: true,
})

const rootHttp = axios.create({
  headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
  withCredentials: true,
  withXSRFToken: true,
})

export async function ensureCsrfCookie(): Promise<void> {
  await rootHttp.get('/sanctum/csrf-cookie')
}

export interface ApiErrorData {
  message?: string
  code?: string
  errors?: ValidationErrors
}

export function apiError(
  error: unknown,
  fallback = 'Não foi possível concluir a operação.',
): string {
  if (error instanceof AxiosError) {
    const data = error.response?.data as ApiErrorData | undefined
    if (data?.message) return data.message
  }

  if (error instanceof Error && error.message) return error.message
  return fallback
}

export function fieldErrors(error: unknown): ValidationErrors {
  if (error instanceof AxiosError) {
    return ((error.response?.data as ApiErrorData | undefined)?.errors ?? {}) as ValidationErrors
  }

  return {}
}
