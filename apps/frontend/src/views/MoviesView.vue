<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { catalogApi } from '@/api/catalog'
import { apiError } from '@/api/http'
import ErrorMessage from '@/components/feedback/ErrorMessage.vue'
import LoadingIndicator from '@/components/feedback/LoadingIndicator.vue'
import AppPagination from '@/components/movies/AppPagination.vue'
import MovieCard from '@/components/movies/MovieCard.vue'
import SearchField from '@/components/movies/SearchField.vue'
import { useAppStore } from '@/stores/app'
import type { Genre, Movie, PaginationMeta } from '@/types'

const app = useAppStore()
const query = ref('')
const movies = ref<Movie[]>([])
const genres = ref<Genre[]>([])
const pagination = ref<PaginationMeta>({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(false)
const error = ref('')
const notice = ref('')
const busyId = ref<number | null>(null)
const searched = ref(false)

onMounted(async () => {
  await app.loadMeta().catch(() => undefined)
  if (!app.meta.tmdb_enabled) {
    error.value = 'A integração TMDB ainda não foi configurada no servidor.'
    return
  }
  genres.value = await catalogApi.genres().catch(() => [])
})

async function search(page = 1) {
  if (query.value.trim().length < 2) return
  loading.value = true
  error.value = ''
  notice.value = ''
  searched.value = true
  try {
    const response = await catalogApi.search(query.value.trim(), page)
    movies.value = response.data
    pagination.value = response.meta
  } catch (caught) {
    error.value = apiError(caught, 'Não foi possível buscar filmes.')
  } finally {
    loading.value = false
  }
}

async function add(movie: Movie) {
  busyId.value = movie.tmdb_id
  error.value = ''
  notice.value = ''
  try {
    await catalogApi.addFavorite(movie.tmdb_id)
    notice.value = `${movie.title} foi adicionado aos favoritos.`
  } catch (caught) {
    error.value = apiError(caught)
  } finally {
    busyId.value = null
  }
}
</script>

<template>
  <section aria-labelledby="movies-title">
    <div class="hero">
      <p class="eyebrow">Seu próximo filme</p>
      <h1 id="movies-title">Encontre histórias para guardar</h1>
      <p>Pesquise no catálogo do TMDB e monte sua própria seleção.</p>
      <SearchField v-model="query" :loading="loading" @search="search(1)" />
    </div>
    <div v-if="notice" class="alert alert-success" role="status">{{ notice }}</div>
    <ErrorMessage
      v-if="error"
      :message="error"
      :retry-label="searched ? 'Tentar novamente' : undefined"
      @retry="search(pagination.current_page)"
    />
    <LoadingIndicator v-if="loading" label="Buscando filmes…" />
    <div v-else-if="searched && movies.length === 0 && !error" class="empty-state">
      <h2>Nenhum filme encontrado</h2>
      <p>Tente outro título ou revise a escrita.</p>
    </div>
    <div v-else class="movie-grid" aria-live="polite">
      <MovieCard
        v-for="movie in movies"
        :key="movie.tmdb_id"
        :item="movie"
        :genres="genres"
        :busy="busyId === movie.tmdb_id"
        @add="add"
      />
    </div>
    <AppPagination
      :current="pagination.current_page"
      :last="pagination.last_page"
      @change="search"
    />
  </section>
</template>
