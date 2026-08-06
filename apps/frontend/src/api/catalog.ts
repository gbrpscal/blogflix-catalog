import { http } from './http'
import type {
  Favorite,
  Genre,
  Meta,
  Movie,
  MovieCollections,
  MovieSort,
  PaginatedResponse,
} from '@/types'

export const catalogApi = {
  async meta(): Promise<Meta> {
    const response = await http.get<{ data: Meta }>('/meta')
    return response.data.data
  },
  async movies(params: {
    query?: string
    page?: number
    genreId?: number
    sort?: MovieSort
  }): Promise<PaginatedResponse<Movie>> {
    const response = await http.get<PaginatedResponse<Movie>>('/movies', {
      params: {
        query: params.query || undefined,
        page: params.page ?? 1,
        genre_id: params.genreId,
        sort: params.sort ?? 'highlights',
      },
    })
    return response.data
  },
  async collections(): Promise<MovieCollections> {
    const response = await http.get<{ data: MovieCollections }>('/movies/collections')
    return response.data.data
  },
  async genres(): Promise<Genre[]> {
    const response = await http.get<{ data: Genre[] }>('/genres')
    return response.data.data
  },
  async favorites(page = 1, genreId?: number): Promise<PaginatedResponse<Favorite>> {
    const response = await http.get<PaginatedResponse<Favorite>>('/favorites', {
      params: { page, genre_id: genreId },
    })
    return response.data
  },
  async addFavorite(tmdbId: number): Promise<Favorite> {
    const response = await http.post<{ data: Favorite }>('/favorites', { tmdb_id: tmdbId })
    return response.data.data
  },
  async removeFavorite(id: number): Promise<void> {
    await http.delete(`/favorites/${id}`)
  },
}
