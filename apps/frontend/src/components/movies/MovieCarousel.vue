<script setup lang="ts">
import { ref } from 'vue'
import MovieCard from './MovieCard.vue'
import type { Genre, Movie } from '@/types'

defineProps<{
  title: string
  subtitle: string
  movies: Movie[]
  genres: Genre[]
  busyId: number | null
}>()

defineEmits<{ add: [movie: Movie] }>()

const track = ref<HTMLElement | null>(null)

function move(direction: -1 | 1) {
  const distance = Math.max(320, (track.value?.clientWidth ?? 0) * 0.82)
  track.value?.scrollBy({ left: distance * direction, behavior: 'smooth' })
}
</script>

<template>
  <section v-if="movies.length" class="carousel-section" :aria-label="title">
    <div class="carousel-heading">
      <div>
        <h2>{{ title }}</h2>
        <p>{{ subtitle }}</p>
      </div>
      <div class="carousel-controls" aria-label="Controles do carrossel">
        <button
          class="carousel-button"
          type="button"
          :aria-label="`Voltar em ${title}`"
          @click="move(-1)"
        >
          ‹
        </button>
        <button
          class="carousel-button"
          type="button"
          :aria-label="`Avançar em ${title}`"
          @click="move(1)"
        >
          ›
        </button>
      </div>
    </div>

    <div ref="track" class="carousel-track" tabindex="0">
      <MovieCard
        v-for="movie in movies"
        :key="movie.tmdb_id"
        :item="movie"
        :genres="genres"
        :busy="busyId === movie.tmdb_id"
        variant="carousel"
        @add="$emit('add', $event)"
      />
    </div>
  </section>
</template>
