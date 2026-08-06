<script setup lang="ts">
defineProps<{ message: string; retryLabel?: string }>()
defineEmits<{ retry: [] }>()
</script>

<template>
  <div class="alert alert-error" role="alert">
    <p>{{ message }}</p>
    <button v-if="retryLabel" type="button" class="button button-ghost" @click="$emit('retry')">
      {{ retryLabel }}
    </button>
  </div>
</template>
