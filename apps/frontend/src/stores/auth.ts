import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { AxiosError } from 'axios'
import { authApi } from '@/api/auth'
import type { User } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const initialized = ref(false)
  const loading = ref(false)

  const authenticated = computed(() => user.value !== null)
  const verified = computed(() => user.value?.email_verified === true)

  async function restore(): Promise<void> {
    if (initialized.value) return
    try {
      user.value = await authApi.user()
    } catch (error) {
      if (!(error instanceof AxiosError) || error.response?.status !== 401) throw error
      user.value = null
    } finally {
      initialized.value = true
    }
  }

  async function login(payload: {
    email: string
    password: string
    remember: boolean
  }): Promise<void> {
    loading.value = true
    try {
      await authApi.login(payload)
      user.value = await authApi.user()
      initialized.value = true
    } finally {
      loading.value = false
    }
  }

  async function register(payload: {
    name: string
    email: string
    password: string
    password_confirmation: string
  }): Promise<void> {
    loading.value = true
    try {
      await authApi.register(payload)
      user.value = await authApi.user()
      initialized.value = true
    } finally {
      loading.value = false
    }
  }

  async function logout(): Promise<void> {
    await authApi.logout()
    user.value = null
    initialized.value = true
  }

  return { user, initialized, loading, authenticated, verified, restore, login, register, logout }
})
