<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

async function logout() {
  await auth.logout()
  await router.push({ name: 'login' })
}
</script>

<template>
  <header class="app-header">
    <a href="#main-content" class="skip-link">Pular para o conteúdo</a>
    <RouterLink to="/movies" class="brand" aria-label="Blogflix, página inicial">
      <span aria-hidden="true">▶</span> Blogflix
    </RouterLink>
    <nav v-if="auth.authenticated" aria-label="Navegação principal">
      <RouterLink v-if="auth.verified" to="/movies">Filmes</RouterLink>
      <RouterLink v-if="auth.verified" to="/favorites">Favoritos</RouterLink>
      <button class="button button-ghost" type="button" @click="logout">Sair</button>
    </nav>
  </header>
</template>
