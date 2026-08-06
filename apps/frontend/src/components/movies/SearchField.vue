<script setup lang="ts">
import type { Genre } from '@/types'

const props = defineProps<{
  modelValue: string
  genreId?: number
  genres: Genre[]
  loading?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
  'update:genreId': [value: number | undefined]
  search: []
  reset: []
}>()

function changeGenre(event: Event) {
  const value = Number((event.target as HTMLSelectElement).value)
  emit('update:genreId', value || undefined)
}
</script>

<template>
  <form class="catalog-toolbar" role="search" @submit.prevent="$emit('search')">
    <div class="search-row">
      <label for="movie-search" class="sr-only">Buscar filme por nome</label>
      <span class="search-icon" aria-hidden="true">⌕</span>
      <input
        id="movie-search"
        :value="modelValue"
        type="search"
        minlength="2"
        maxlength="100"
        placeholder="Busque por um filme…"
        @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
      />
      <button class="button button-primary" type="submit" :disabled="loading">
        {{ loading ? 'Buscando…' : 'Buscar' }}
      </button>
    </div>

    <div class="filter-row" aria-label="Filtros da busca">
      <label class="filter-control">
        <span>Gênero</span>
        <select :value="genreId ?? ''" @change="changeGenre">
          <option value="">Todos os gêneros</option>
          <option v-for="genre in genres" :key="genre.id" :value="genre.id">
            {{ genre.name }}
          </option>
        </select>
      </label>

      <button
        v-if="props.modelValue || props.genreId"
        class="button button-ghost clear-filters"
        type="button"
        @click="$emit('reset')"
      >
        Limpar busca
      </button>
    </div>
  </form>
</template>
