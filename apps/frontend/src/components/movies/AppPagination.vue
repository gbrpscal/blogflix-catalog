<script setup lang="ts">
defineProps<{ current: number; last: number }>()
defineEmits<{ change: [page: number] }>()
</script>

<template>
  <nav v-if="last > 1" class="pagination" aria-label="Paginação">
    <button
      class="button button-ghost"
      type="button"
      :disabled="current <= 1"
      @click="$emit('change', current - 1)"
    >
      Anterior
    </button>
    <span aria-live="polite">Página {{ current }} de {{ last }}</span>
    <button
      class="button button-ghost"
      type="button"
      :disabled="current >= last"
      @click="$emit('change', current + 1)"
    >
      Próxima
    </button>
  </nav>
</template>
