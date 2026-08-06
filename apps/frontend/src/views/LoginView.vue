<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import FormField from '@/components/forms/FormField.vue'
import ErrorMessage from '@/components/feedback/ErrorMessage.vue'
import { apiError, fieldErrors } from '@/api/http'
import { useAppStore } from '@/stores/app'
import { useAuthStore } from '@/stores/auth'
import type { ValidationErrors } from '@/types'

const auth = useAuthStore()
const app = useAppStore()
const route = useRoute()
const router = useRouter()
const form = reactive({ email: '', password: '', remember: false })
const error = ref(route.query.oauth_error ? 'Não foi possível entrar com o Google.' : '')
const errors = ref<ValidationErrors>({})

async function submit() {
  error.value = ''
  errors.value = {}
  try {
    await auth.login(form)
    const redirect =
      typeof route.query.redirect === 'string'
        ? route.query.redirect
        : auth.verified
          ? '/movies'
          : '/verify-email'
    await router.push(redirect)
  } catch (caught) {
    error.value = apiError(caught, 'E-mail ou senha inválidos.')
    errors.value = fieldErrors(caught)
  }
}
</script>

<template>
  <section class="auth-card" aria-labelledby="login-title">
    <div class="section-heading">
      <p class="eyebrow">Bem-vindo de volta</p>
      <h1 id="login-title">Entre na sua conta</h1>
    </div>
    <ErrorMessage v-if="error" :message="error" />
    <form @submit.prevent="submit">
      <FormField
        id="email"
        v-model="form.email"
        label="E-mail"
        type="email"
        autocomplete="email"
        required
        :error="errors.email?.[0]"
      />
      <FormField
        id="password"
        v-model="form.password"
        label="Senha"
        type="password"
        autocomplete="current-password"
        required
        :error="errors.password?.[0]"
      />
      <label class="checkbox">
        <input v-model="form.remember" type="checkbox" />
        <span>Manter conectado</span>
      </label>
      <button class="button button-primary button-block" type="submit" :disabled="auth.loading">
        {{ auth.loading ? 'Entrando…' : 'Entrar' }}
      </button>
    </form>
    <a
      v-if="app.meta.google_oauth_enabled"
      class="button button-secondary button-block"
      href="/api/v1/auth/google/redirect"
    >
      Entrar com Google
    </a>
    <p v-else class="muted center">Google OAuth ainda não configurado.</p>
    <div class="auth-links">
      <RouterLink to="/forgot-password">Esqueci minha senha</RouterLink>
      <RouterLink to="/register">Criar uma conta</RouterLink>
    </div>
  </section>
</template>
