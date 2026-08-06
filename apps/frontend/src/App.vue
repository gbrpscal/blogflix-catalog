<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterView } from 'vue-router'
import AppHeader from '@/components/layout/AppHeader.vue'
import { useAppStore } from '@/stores/app'

const app = useAppStore()
onMounted(() => app.loadMeta().catch(() => undefined))
</script>

<template>
  <div class="app-shell">
    <AppHeader />
    <main id="main-content" class="page-container">
      <RouterView />
    </main>
    <footer class="app-footer">
      <p>Este produto usa a API do TMDB, mas não é endossado ou certificado pelo TMDB.</p>
    </footer>
  </div>
</template>
