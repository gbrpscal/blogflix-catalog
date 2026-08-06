<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { catalogApi } from '@/api/catalog'
import { apiError } from '@/api/http'
import ConfirmDialog from '@/components/feedback/ConfirmDialog.vue'
import ErrorMessage from '@/components/feedback/ErrorMessage.vue'
import LoadingIndicator from '@/components/feedback/LoadingIndicator.vue'
import AppPagination from '@/components/movies/AppPagination.vue'
import MovieCard from '@/components/movies/MovieCard.vue'
import type { Favorite, Genre, PaginationMeta } from '@/types'

const favorites = ref<Favorite[]>([])
const genres = ref<Genre[]>([])
const pagination = ref<PaginationMeta>({ current_page: 1, last_page: 1, total: 0 })
const selectedGenre = ref('')
const loading = ref(true)
const removing = ref(false)
const selected = ref<Favorite | null>(null)
const error = ref('')

onMounted(async () => {
  genres.value = await catalogApi.genres().catch(() => [])
  await load()
})

async function load(page = 1) {
  loading.value = true
  error.value = ''
  try {
    const genreId = selectedGenre.value ? Number(selectedGenre.value) : undefined
    const response = await catalogApi.favorites(page, genreId)
    favorites.value = response.data
    pagination.value = response.meta
  } catch (caught) {
    error.value = apiError(caught, 'Não foi possível carregar seus favoritos.')
  } finally {
    loading.value = false
  }
}

async function confirmRemove() {
  if (!selected.value) return
  removing.value = true
  try {
    await catalogApi.removeFavorite(selected.value.id)
    selected.value = null
    await load(pagination.value.current_page)
  } catch (caught) {
    error.value = apiError(caught)
  } finally {
    removing.value = false
  }
}
</script>

<template>
  <section aria-labelledby="favorites-title">
    <div class="page-heading">
      <div>
        <p class="eyebrow">Sua coleção</p>
        <h1 id="favorites-title">Filmes favoritos</h1>
      </div>
      <div class="filter-field">
        <label for="genre-filter">Filtrar por gênero</label>
        <select id="genre-filter" v-model="selectedGenre" @change="load(1)">
          <option value="">Todos os gêneros</option>
          <option v-for="genre in genres" :key="genre.id" :value="String(genre.id)">
            {{ genre.name }}
          </option>
        </select>
      </div>
    </div>
    <ErrorMessage
      v-if="error"
      :message="error"
      retry-label="Tentar novamente"
      @retry="load(pagination.current_page)"
    />
    <LoadingIndicator v-if="loading" label="Carregando favoritos…" />
    <div v-else-if="favorites.length === 0 && !error" class="empty-state">
      <h2>Nenhum favorito por aqui</h2>
      <p>
        {{
          selectedGenre
            ? 'Nenhum favorito corresponde a este gênero.'
            : 'Busque um filme e adicione-o à sua coleção.'
        }}
      </p>
      <RouterLink class="button button-primary" to="/movies">Buscar filmes</RouterLink>
    </div>
    <div v-else class="movie-grid">
      <MovieCard
        v-for="favorite in favorites"
        :key="favorite.id"
        :item="favorite"
        :genres="genres"
        mode="favorite"
        @remove="selected = $event"
      />
    </div>
    <AppPagination :current="pagination.current_page" :last="pagination.last_page" @change="load" />
    <ConfirmDialog
      :open="selected !== null"
      title="Remover favorito?"
      :message="selected ? `${selected.title} será removido da sua coleção.` : ''"
      :busy="removing"
      @cancel="selected = null"
      @confirm="confirmRemove"
    />
  </section>
</template>
