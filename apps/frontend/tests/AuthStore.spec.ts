import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { authApi } from '@/api/auth'
import { useAuthStore } from '@/stores/auth'

vi.mock('@/api/auth', () => ({
  authApi: {
    user: vi.fn(),
    login: vi.fn(),
    register: vi.fn(),
    logout: vi.fn(),
  },
}))

describe('auth store', () => {
  beforeEach(() => setActivePinia(createPinia()))

  it('authenticates through the session API without browser storage', async () => {
    vi.mocked(authApi.login).mockResolvedValue()
    vi.mocked(authApi.user).mockResolvedValue({
      id: 1,
      name: 'Gabriel',
      email: 'gabriel@example.com',
      avatar_url: null,
      email_verified: true,
    })
    const store = useAuthStore()

    await store.login({ email: 'gabriel@example.com', password: 'secret', remember: false })

    expect(store.authenticated).toBe(true)
    expect(store.verified).toBe(true)
    expect(localStorage.length).toBe(0)
  })
})
