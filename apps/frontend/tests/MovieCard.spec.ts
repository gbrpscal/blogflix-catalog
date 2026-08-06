import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import MovieCard from '@/components/movies/MovieCard.vue'
import type { Movie } from '@/types'

const movie: Movie = {
  tmdb_id: 550,
  title: 'Clube da Luta',
  overview: 'Um clássico moderno.',
  poster_path: '/poster.jpg',
  poster_url: 'https://image.tmdb.org/t/p/w500/poster.jpg',
  release_date: '1999-10-15',
  genre_ids: [18],
}

describe('MovieCard', () => {
  it('renders normalized movie data and emits add', async () => {
    const wrapper = mount(MovieCard, {
      props: { item: movie, genres: [{ id: 18, name: 'Drama' }] },
    })

    expect(wrapper.text()).toContain('Clube da Luta')
    expect(wrapper.text()).toContain('Drama')
    expect(wrapper.get('img').attributes('alt')).toBe('Pôster de Clube da Luta')

    await wrapper.get('button').trigger('click')
    expect(wrapper.emitted('add')?.[0]).toEqual([movie])
  })
})
