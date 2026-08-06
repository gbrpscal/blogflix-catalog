<script setup lang="ts">
defineProps<{ modelValue: string; loading?: boolean }>()
defineEmits<{ 'update:modelValue': [value: string]; search: [] }>()
</script>

<template>
  <form class="search-form" role="search" @submit.prevent="$emit('search')">
    <label for="movie-search" class="sr-only">Buscar filme por nome</label>
    <input
      id="movie-search"
      :value="modelValue"
      type="search"
      minlength="2"
      maxlength="100"
      placeholder="Busque por um filme…"
      required
      @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    />
    <button class="button button-primary" type="submit" :disabled="loading">
      {{ loading ? 'Buscando…' : 'Buscar' }}
    </button>
  </form>
</template>
