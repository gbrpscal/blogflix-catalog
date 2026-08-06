<script setup lang="ts">
import type { Favorite, Genre, Movie } from '@/types'

const props = withDefaults(
  defineProps<{
    item: Movie | Favorite
    genres: Genre[]
    mode?: 'search' | 'favorite'
    variant?: 'grid' | 'carousel'
    busy?: boolean
  }>(),
  { mode: 'search', variant: 'grid' },
)

defineEmits<{ add: [movie: Movie]; remove: [favorite: Favorite] }>()

function genreName(id: number): string {
  return props.genres.find((genre) => genre.id === id)?.name ?? `Gênero ${id}`
}

function year(date: string | null): string {
  return date?.slice(0, 4) ?? 'Sem data'
}

function rating(value?: number | null): string {
  return typeof value === 'number' ? value.toFixed(1) : '—'
}
</script>

<template>
  <article :class="['movie-card', `movie-card-${variant}`]">
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
      <span class="rating-badge" :aria-label="`Avaliação ${rating(item.vote_average)} de 10`">
        <span aria-hidden="true">★</span> {{ rating(item.vote_average) }}
      </span>
    </div>

    <div class="movie-card-body">
      <div class="movie-card-heading">
        <p class="movie-year">{{ year(item.release_date) }}</p>
        <h2 :title="item.title">{{ item.title }}</h2>
      </div>

      <p class="movie-overview">{{ item.overview || 'Descrição indisponível.' }}</p>

      <ul v-if="item.genre_ids.length" class="genre-list" aria-label="Gêneros">
        <li
          v-for="genreId in item.genre_ids.slice(0, variant === 'carousel' ? 1 : 2)"
          :key="genreId"
        >
          {{ genreName(genreId) }}
        </li>
        <li
          v-if="item.genre_ids.length > (variant === 'carousel' ? 1 : 2)"
          aria-label="Mais gêneros"
        >
          +{{ item.genre_ids.length - (variant === 'carousel' ? 1 : 2) }}
        </li>
      </ul>

      <button
        v-if="mode === 'favorite'"
        class="button button-danger movie-action"
        type="button"
        :disabled="busy"
        @click="$emit('remove', item as Favorite)"
      >
        Remover
      </button>
      <button
        v-else
        class="button button-primary movie-action"
        type="button"
        :disabled="busy"
        @click="$emit('add', item as Movie)"
      >
        {{ busy ? 'Adicionando…' : 'Favoritar' }}
      </button>
    </div>
  </article>
</template>
