export interface User {
  id: number
  name: string
  email: string
  avatar_url: string | null
  email_verified: boolean
}

export interface Meta {
  tmdb_enabled: boolean
  google_oauth_enabled: boolean
}

export interface Movie {
  tmdb_id: number
  title: string
  overview: string | null
  poster_path: string | null
  poster_url: string | null
  backdrop_url?: string | null
  release_date: string | null
  genre_ids: number[]
  vote_average?: number | null
  runtime?: number | null
}

export interface Favorite extends Movie {
  id: number
  created_at: string
}

export interface Genre {
  id: number
  name: string
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  total: number
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: PaginationMeta
  links?: Record<string, string | null>
}

export interface ValidationErrors {
  [field: string]: string[]
}
