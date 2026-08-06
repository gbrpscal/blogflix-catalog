<script setup lang="ts">
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { authApi } from '@/api/auth'
import { apiError } from '@/api/http'
import ErrorMessage from '@/components/feedback/ErrorMessage.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const route = useRoute()
const busy = ref(false)
const sent = ref(false)
const error = ref('')
const verified = route.query.verified === '1' || auth.verified

async function resend() {
  busy.value = true
  error.value = ''
  try {
    await authApi.resendVerification()
    sent.value = true
  } catch (caught) {
    error.value = apiError(caught)
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <section class="auth-card center" aria-labelledby="verify-title">
    <div class="verification-icon" aria-hidden="true">✉</div>
    <h1 id="verify-title">{{ verified ? 'E-mail confirmado' : 'Confirme seu e-mail' }}</h1>
    <template v-if="verified">
      <p>Seu endereço foi confirmado. Seu catálogo já está disponível.</p>
      <RouterLink class="button button-primary" to="/movies">Explorar filmes</RouterLink>
    </template>
    <template v-else>
      <p>Enviamos uma mensagem para {{ auth.user?.email }}.</p>
      <div v-if="sent" class="alert alert-success" role="status">Novo e-mail enviado.</div>
      <ErrorMessage v-if="error" :message="error" />
      <button class="button button-primary" type="button" :disabled="busy" @click="resend">
        {{ busy ? 'Enviando…' : 'Reenviar confirmação' }}
      </button>
    </template>
  </section>
</template>
