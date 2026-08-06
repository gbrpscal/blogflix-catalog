import type { LocationQuery, LocationQueryRaw } from 'vue-router'
import type { MovieSort } from '@/types'

const movieSorts: MovieSort[] = ['releases', 'highlights', 'title_asc', 'title_desc']

export interface MovieCatalogRouteState {
  query: string
  genreId?: number
  sort: MovieSort
  page: number
}

function firstValue(value: LocationQuery[string] | undefined): string | undefined {
  if (Array.isArray(value)) return value[0] ?? undefined
  return value ?? undefined
}

function positiveInteger(value: LocationQuery[string] | undefined): number | undefined {
  const normalized = firstValue(value)
  if (!normalized || !/^\d+$/.test(normalized)) return undefined

  const parsed = Number(normalized)
  return Number.isSafeInteger(parsed) && parsed > 0 ? parsed : undefined
}

export function parseMovieCatalogQuery(query: LocationQuery): MovieCatalogRouteState {
  const requestedSort = firstValue(query.s) as MovieSort | undefined

  return {
    query: firstValue(query.q)?.trim() ?? '',
    genreId: positiveInteger(query.g),
    sort: requestedSort && movieSorts.includes(requestedSort) ? requestedSort : 'highlights',
    page: positiveInteger(query.p) ?? 1,
  }
}

export function buildMovieCatalogQuery(state: MovieCatalogRouteState): LocationQueryRaw {
  const query: LocationQueryRaw = {}
  const normalizedQuery = state.query.trim()

  if (normalizedQuery) query.q = normalizedQuery
  if (state.genreId) query.g = String(state.genreId)
  if (state.sort !== 'highlights') query.s = state.sort
  if (state.page > 1) query.p = String(state.page)

  return query
}
