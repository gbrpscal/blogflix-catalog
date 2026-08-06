import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import AppPagination from '@/components/movies/AppPagination.vue'

describe('AppPagination', () => {
  it('emits the next page and disables invalid navigation', async () => {
    const wrapper = mount(AppPagination, { props: { current: 1, last: 3 } })
    const [previous, next] = wrapper.findAll('button')

    expect(previous).toBeDefined()
    expect(next).toBeDefined()
    if (!previous || !next) throw new Error('Pagination controls were not rendered.')

    expect(previous.attributes('disabled')).toBeDefined()
    await next.trigger('click')
    expect(wrapper.emitted('change')?.[0]).toEqual([2])
  })
})
