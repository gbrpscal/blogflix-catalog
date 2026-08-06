<script setup lang="ts">
defineProps<{ label?: string }>()
</script>

<template>
  <div class="loading" role="status" aria-live="polite">
    <span class="spinner" aria-hidden="true"></span>
    <span>{{ label ?? 'Carregando…' }}</span>
  </div>
</template>
