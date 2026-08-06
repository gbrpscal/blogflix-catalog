<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import FormField from '@/components/forms/FormField.vue'
import ErrorMessage from '@/components/feedback/ErrorMessage.vue'
import { apiError, fieldErrors } from '@/api/http'
import { useAuthStore } from '@/stores/auth'
import type { ValidationErrors } from '@/types'

const auth = useAuthStore()
const router = useRouter()
const form = reactive({ name: '', email: '', password: '', password_confirmation: '' })
const error = ref('')
const errors = ref<ValidationErrors>({})

async function submit() {
  error.value = ''
  errors.value = {}
  try {
    await auth.register(form)
    await router.push('/verify-email')
  } catch (caught) {
    error.value = apiError(caught)
    errors.value = fieldErrors(caught)
  }
}
</script>

<template>
  <section class="auth-card" aria-labelledby="register-title">
    <div class="section-heading">
      <p class="eyebrow">Nova conta</p>
      <h1 id="register-title">Crie seu catálogo</h1>
    </div>
    <ErrorMessage v-if="error" :message="error" />
    <form @submit.prevent="submit">
      <FormField
        id="name"
        v-model="form.name"
        label="Nome"
        autocomplete="name"
        required
        :error="errors.name?.[0]"
      />
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
        autocomplete="new-password"
        required
        :error="errors.password?.[0]"
      />
      <FormField
        id="password-confirmation"
        v-model="form.password_confirmation"
        label="Confirme a senha"
        type="password"
        autocomplete="new-password"
        required
      />
      <button class="button button-primary button-block" type="submit" :disabled="auth.loading">
        {{ auth.loading ? 'Criando…' : 'Criar conta' }}
      </button>
    </form>
    <p class="center">Já tem uma conta? <RouterLink to="/login">Entrar</RouterLink></p>
  </section>
</template>
