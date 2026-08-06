<script setup lang="ts">
import type { Favorite, Genre, Movie } from '@/types'

const props = defineProps<{
  item: Movie | Favorite
  genres: Genre[]
  mode?: 'search' | 'favorite'
  busy?: boolean
}>()
defineEmits<{ add: [movie: Movie]; remove: [favorite: Favorite] }>()

function genreName(id: number): string {
  return props.genres.find((genre) => genre.id === id)?.name ?? `Gênero ${id}`
}

function year(date: string | null): string {
  return date?.slice(0, 4) ?? 'Data indisponível'
}
</script>

<template>
  <article class="movie-card">
    <div class="poster-wrap">
      <img
        v-if="item.poster_url"
        :src="item.poster_url"
        :alt="`Pôster de ${item.title}`"
        loading="lazy"
      />
      <div
        v-else
        class="poster-placeholder"
        role="img"
        :aria-label="`Sem pôster para ${item.title}`"
      >
        Sem imagem
      </div>
    </div>
    <div class="movie-card-body">
      <div>
        <p class="movie-year">{{ year(item.release_date) }}</p>
        <h2>{{ item.title }}</h2>
      </div>
      <p class="movie-overview">{{ item.overview || 'Descrição indisponível.' }}</p>
      <ul class="genre-list" aria-label="Gêneros">
        <li v-for="genreId in item.genre_ids" :key="genreId">{{ genreName(genreId) }}</li>
      </ul>
      <button
        v-if="mode === 'favorite'"
        class="button button-danger"
        type="button"
        :disabled="busy"
        @click="$emit('remove', item as Favorite)"
      >
        Remover
      </button>
      <button
        v-else
        class="button button-primary"
        type="button"
        :disabled="busy"
        @click="$emit('add', item as Movie)"
      >
        {{ busy ? 'Adicionando…' : 'Favoritar' }}
      </button>
    </div>
  </article>
</template>
