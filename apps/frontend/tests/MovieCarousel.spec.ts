import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import MovieCarousel from '@/components/movies/MovieCarousel.vue'
import type { Movie } from '@/types'

const movie: Movie = {
  tmdb_id: 550,
  title: 'Clube da Luta',
  overview: 'Um clássico moderno.',
  poster_path: '/poster.jpg',
  poster_url: 'https://image.tmdb.org/t/p/w500/poster.jpg',
  release_date: '1999-10-15',
  genre_ids: [18],
  vote_average: 8.4,
}

describe('MovieCarousel', () => {
  const scrollBy = vi.fn()

  beforeEach(() => {
    scrollBy.mockClear()
    Object.defineProperty(HTMLElement.prototype, 'scrollBy', {
      configurable: true,
      value: scrollBy,
    })
  })

  it('renders compact movies and supports next navigation', async () => {
    const wrapper = mount(MovieCarousel, {
      props: {
        title: 'Em alta',
        subtitle: 'Tendências da semana.',
        movies: [movie],
        genres: [{ id: 18, name: 'Drama' }],
        busyId: null,
      },
    })

    expect(wrapper.text()).toContain('Em alta')
    expect(wrapper.text()).toContain('Clube da Luta')
    expect(wrapper.findAll('.carousel-button')).toHaveLength(2)
    expect(wrapper.get('.carousel-track').attributes('tabindex')).toBeUndefined()

    await wrapper.findAll('.carousel-button')[1]!.trigger('click')
    expect(scrollBy).toHaveBeenCalledWith(expect.objectContaining({ behavior: 'smooth' }))
  })
})
