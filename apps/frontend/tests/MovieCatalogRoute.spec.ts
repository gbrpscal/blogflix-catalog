import { describe, expect, it } from 'vitest'
import { buildMovieCatalogQuery, parseMovieCatalogQuery } from '@/utils/movieCatalogRoute'

describe('movie catalog route state', () => {
  it('keeps the default URL compact', () => {
    expect(
      buildMovieCatalogQuery({
        query: 'matrix',
        genreId: 878,
        sort: 'highlights',
        page: 1,
      }),
    ).toEqual({ q: 'matrix', g: '878' })
  })

  it('includes only non-default ordering and pagination values', () => {
    expect(
      buildMovieCatalogQuery({
        query: 'matrix',
        genreId: 878,
        sort: 'releases',
        page: 3,
      }),
    ).toEqual({ q: 'matrix', g: '878', s: 'releases', p: '3' })
  })

  it('restores valid filters and safely defaults invalid values', () => {
    expect(
      parseMovieCatalogQuery({
        q: ' matrix ',
        g: '878',
        s: 'title_desc',
        p: '3',
      }),
    ).toEqual({
      query: 'matrix',
      genreId: 878,
      sort: 'title_desc',
      page: 3,
    })

    expect(
      parseMovieCatalogQuery({
        g: '-1',
        s: 'unknown',
        p: 'zero',
      }),
    ).toEqual({
      query: '',
      genreId: undefined,
      sort: 'highlights',
      page: 1,
    })
  })
})
