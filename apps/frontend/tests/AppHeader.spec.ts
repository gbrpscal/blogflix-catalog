import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'
import { describe, expect, it } from 'vitest'
import AppHeader from '@/components/layout/AppHeader.vue'
import { useAuthStore } from '@/stores/auth'
import { useAppStore } from '@/stores/app'

const Page = { template: '<div />' }

describe('AppHeader', () => {
  it('links the logo to the movies home', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    const auth = useAuthStore()
    auth.user = {
      id: 1,
      name: 'Gabriel',
      email: 'gabriel@example.com',
      avatar_url: null,
      email_verified: true,
    }

    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/login', name: 'login', component: Page },
        { path: '/movies', name: 'movies', component: Page },
        { path: '/favorites', name: 'favorites', component: Page },
      ],
    })
    await router.push('/favorites')
    await router.isReady()

    const wrapper = mount(AppHeader, {
      global: { plugins: [pinia, router] },
    })

    expect(wrapper.get('.brand').attributes('href')).toBe('/movies')
    const app = useAppStore()
    const resetToken = app.catalogResetToken

    await wrapper.get('.brand').trigger('click')

    expect(app.catalogResetToken).toBe(resetToken + 1)
  })
})
