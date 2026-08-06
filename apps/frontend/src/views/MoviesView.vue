<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { catalogApi } from '@/api/catalog'
import { apiError } from '@/api/http'
import ErrorMessage from '@/components/feedback/ErrorMessage.vue'
import LoadingIndicator from '@/components/feedback/LoadingIndicator.vue'
import AppPagination from '@/components/movies/AppPagination.vue'
import MovieCard from '@/components/movies/MovieCard.vue'
import MovieCarousel from '@/components/movies/MovieCarousel.vue'
import SearchField from '@/components/movies/SearchField.vue'
import { useAppStore } from '@/stores/app'
import type { Genre, Movie, MovieCollections, MovieSort, PaginationMeta } from '@/types'
import {
  buildMovieCatalogQuery,
  parseMovieCatalogQuery,
  type MovieCatalogRouteState,
} from '@/utils/movieCatalogRoute'

const emptyCollections = (): MovieCollections => ({
  popular: [],
  top_rated: [],
  releases: [],
  trending: [],
})

const app = useAppStore()
const route = useRoute()
const router = useRouter()
const draftQuery = ref('')
const draftGenreId = ref<number | undefined>()
const query = ref('')
const genreId = ref<number | undefined>()
const sort = ref<MovieSort>('highlights')
const movies = ref<Movie[]>([])
const collections = ref<MovieCollections>(emptyCollections())
const genres = ref<Genre[]>([])
const pagination = ref<PaginationMeta>({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(false)
const collectionsLoading = ref(false)
const error = ref('')
const notice = ref('')
const busyId = ref<number | null>(null)
const loaded = ref(false)
let requestSequence = 0

const routeReady = ref(false)
let scrollAfterLoad = false

const showCollections = computed(() => query.value.trim() === '' && !genreId.value)
const resultHeading = computed(() => {
  if (query.value.trim()) return 'Resultados para “' + query.value.trim() + '”'
  if (genreId.value) {
    const genre = genres.value.find((item) => item.id === genreId.value)
    return genre ? 'Filmes de ' + genre.name : 'Filmes filtrados'
  }
  return 'Catálogo em destaque'
})

onMounted(async () => {
  await app.loadMeta().catch(() => undefined)
  if (!app.meta.tmdb_enabled) {
    error.value = 'A integração TMDB ainda não foi configurada no servidor.'
    return
  }

  void loadCollections()
  const [genreResult] = await Promise.allSettled([catalogApi.genres()])
  if (genreResult.status === 'fulfilled') genres.value = genreResult.value
  routeReady.value = true
  await loadFromRoute(true)
})

watch(
  () => route.fullPath,
  () => {
    if (routeReady.value && route.name === 'movies') void loadFromRoute()
  },
)

watch(
  () => app.catalogResetToken,
  () => {
    if (route.name !== 'movies') return
    draftQuery.value = ''
    draftGenreId.value = undefined
  },
)

async function loadFromRoute(forceDraft = false) {
  const state = parseMovieCatalogQuery(route.query)
  const searchChanged = state.query !== query.value || state.genreId !== genreId.value

  query.value = state.query
  genreId.value = state.genreId
  sort.value = state.sort
  if (forceDraft || searchChanged) {
    draftQuery.value = state.query
    draftGenreId.value = state.genreId
  }

  await loadMovies(state.page)
  if (scrollAfterLoad) {
    scrollAfterLoad = false
    document
      .querySelector('#catalog-results')
      ?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}
async function loadCollections() {
  collectionsLoading.value = true
  try {
    collections.value = await catalogApi.collections()
  } catch {
    collections.value = emptyCollections()
  } finally {
    collectionsLoading.value = false
  }
}

async function loadMovies(page = 1) {
  const normalizedQuery = query.value.trim()
  if (normalizedQuery.length === 1) {
    error.value = 'Digite pelo menos dois caracteres para buscar um filme.'
    return
  }

  const currentRequest = ++requestSequence
  loading.value = true
  error.value = ''
  notice.value = ''

  try {
    const response = await catalogApi.movies({
      query: normalizedQuery || undefined,
      page,
      genreId: genreId.value,
      sort: sort.value,
    })

    if (currentRequest !== requestSequence) return
    movies.value = response.data
    pagination.value = response.meta
    loaded.value = true
  } catch (caught) {
    if (currentRequest === requestSequence) {
      error.value = apiError(caught, 'Não foi possível carregar o catálogo.')
    }
  } finally {
    if (currentRequest === requestSequence) loading.value = false
  }
}

async function navigateCatalog(
  state: MovieCatalogRouteState,
  shouldScrollToResults = true,
): Promise<void> {
  const target = router.resolve({
    name: 'movies',
    query: buildMovieCatalogQuery(state),
  })

  if (target.fullPath === route.fullPath) return

  scrollAfterLoad = shouldScrollToResults
  await router.push(target)
}

async function submitSearch() {
  const normalizedQuery = draftQuery.value.trim()
  if (normalizedQuery.length === 1) {
    error.value = 'Digite pelo menos dois caracteres para buscar um filme.'
    return
  }

  await navigateCatalog({
    query: normalizedQuery,
    genreId: draftGenreId.value,
    sort: sort.value,
    page: 1,
  })
}

async function changeSort(event: Event) {
  const selectedSort = (event.target as HTMLSelectElement).value as MovieSort

  await navigateCatalog({
    query: query.value,
    genreId: genreId.value,
    sort: selectedSort,
    page: 1,
  })
}

async function changePage(page: number) {
  await navigateCatalog({
    query: query.value,
    genreId: genreId.value,
    sort: sort.value,
    page,
  })
}

async function clearSearch() {
  draftQuery.value = ''
  draftGenreId.value = undefined

  await navigateCatalog({
    query: '',
    genreId: undefined,
    sort: sort.value,
    page: 1,
  })
}

async function add(movie: Movie) {
  busyId.value = movie.tmdb_id
  error.value = ''
  notice.value = ''
  try {
    await catalogApi.addFavorite(movie.tmdb_id)
    notice.value = movie.title + ' foi adicionado aos favoritos.'
  } catch (caught) {
    error.value = apiError(caught)
  } finally {
    busyId.value = null
  }
}
</script>

<template>
  <section aria-labelledby="movies-title">
    <div class="hero catalog-hero">
      <p class="eyebrow">Seu próximo filme</p>
      <h1 id="movies-title">Descubra algo memorável</h1>
      <p>Explore seleções do momento ou encontre um título específico no catálogo.</p>
      <SearchField
        v-model="draftQuery"
        v-model:genre-id="draftGenreId"
        :genres="genres"
        :loading="loading"
        @search="submitSearch"
        @reset="clearSearch"
      />
    </div>

    <div v-if="notice" class="alert alert-success floating-notice" role="status">{{ notice }}</div>

    <div v-if="showCollections" class="discovery-sections" aria-label="Seleções de filmes">
      <div v-if="collectionsLoading" class="carousel-loading" aria-label="Carregando seleções">
        <span v-for="item in 6" :key="item" class="carousel-skeleton"></span>
      </div>
      <template v-else>
        <MovieCarousel
          title="Mais assistidos"
          subtitle="Os filmes com maior popularidade no TMDB agora."
          :movies="collections.popular"
          :genres="genres"
          :busy-id="busyId"
          @add="add"
        />
        <MovieCarousel
          title="Melhores avaliações"
          subtitle="Títulos que conquistaram as maiores notas da comunidade."
          :movies="collections.top_rated"
          :genres="genres"
          :busy-id="busyId"
          @add="add"
        />
        <MovieCarousel
          title="Lançamentos"
          subtitle="Filmes em exibição e novidades recentes."
          :movies="collections.releases"
          :genres="genres"
          :busy-id="busyId"
          @add="add"
        />
        <MovieCarousel
          title="Em alta"
          subtitle="O que ganhou atenção nesta semana."
          :movies="collections.trending"
          :genres="genres"
          :busy-id="busyId"
          @add="add"
        />
      </template>
    </div>

    <section id="catalog-results" class="catalog-results" aria-labelledby="results-heading">
      <div class="results-heading">
        <div>
          <p class="eyebrow">Navegue pela coleção</p>
          <h2 id="results-heading">{{ resultHeading }}</h2>
        </div>
        <div class="results-tools">
          <p v-if="loaded && !loading" class="result-count">
            {{ pagination.total.toLocaleString('pt-BR') }} filmes encontrados
          </p>
          <label class="catalog-sort">
            <span>Ordenar por</span>
            <select :value="sort" :disabled="loading" @change="changeSort">
              <option value="highlights">Destaques</option>
              <option value="releases">Lançamentos</option>
              <option value="title_asc">A–Z</option>
              <option value="title_desc">Z–A</option>
            </select>
          </label>
        </div>
      </div>

      <ErrorMessage
        v-if="error"
        :message="error"
        retry-label="Tentar novamente"
        @retry="loadMovies(pagination.current_page)"
      />
      <LoadingIndicator v-if="loading" label="Carregando filmes…" />
      <div v-else-if="loaded && movies.length === 0 && !error" class="empty-state">
        <h2>Nenhum filme encontrado</h2>
        <p>Tente outro título ou gênero.</p>
        <button class="button button-secondary" type="button" @click="clearSearch">
          Limpar busca
        </button>
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
        v-if="movies.length > 0"
        :current="pagination.current_page"
        :last="pagination.last_page"
        @change="changePage"
      />
    </section>
  </section>
</template>
