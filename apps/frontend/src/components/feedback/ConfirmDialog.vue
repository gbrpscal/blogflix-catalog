<script setup lang="ts">
import { nextTick, ref, watch } from 'vue'

const props = defineProps<{ open: boolean; title: string; message: string; busy?: boolean }>()
const emit = defineEmits<{ confirm: []; cancel: [] }>()
const cancelButton = ref<HTMLButtonElement | null>(null)

watch(
  () => props.open,
  async (open) => {
    if (open) {
      await nextTick()
      cancelButton.value?.focus()
    }
  },
)
</script>

<template>
  <div v-if="open" class="dialog-backdrop" @click.self="emit('cancel')">
    <section class="dialog" role="dialog" aria-modal="true" aria-labelledby="dialog-title">
      <h2 id="dialog-title">{{ title }}</h2>
      <p>{{ message }}</p>
      <div class="dialog-actions">
        <button
          ref="cancelButton"
          type="button"
          class="button button-ghost"
          :disabled="busy"
          @click="emit('cancel')"
        >
          Cancelar
        </button>
        <button
          type="button"
          class="button button-danger"
          :disabled="busy"
          @click="emit('confirm')"
        >
          {{ busy ? 'Removendo…' : 'Remover' }}
        </button>
      </div>
    </section>
  </div>
</template>
