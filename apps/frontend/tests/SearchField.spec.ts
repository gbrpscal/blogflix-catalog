import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import SearchField from '@/components/movies/SearchField.vue'

describe('SearchField', () => {
  it('edits the genre without applying the search immediately', async () => {
    const wrapper = mount(SearchField, {
      props: {
        modelValue: '',
        genres: [
          { id: 18, name: 'Drama' },
          { id: 35, name: 'Comédia' },
        ],
      },
    })

    await wrapper.get('select').setValue('18')
    expect(wrapper.emitted('update:genreId')?.[0]).toEqual([18])
    expect(wrapper.emitted('search')).toBeUndefined()
  })

  it('submits an empty query so the initial catalog can be browsed', async () => {
    const wrapper = mount(SearchField, {
      props: { modelValue: '', genres: [] },
    })

    await wrapper.get('form').trigger('submit')
    expect(wrapper.emitted('search')).toHaveLength(1)
  })
})
