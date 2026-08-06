<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { authApi } from '@/api/auth'
import { apiError, fieldErrors } from '@/api/http'
import FormField from '@/components/forms/FormField.vue'
import ErrorMessage from '@/components/feedback/ErrorMessage.vue'
import type { ValidationErrors } from '@/types'

const route = useRoute()
const router = useRouter()
const form = reactive({
  email: typeof route.query.email === 'string' ? route.query.email : '',
  token: typeof route.query.token === 'string' ? route.query.token : '',
  password: '',
  password_confirmation: '',
})
const busy = ref(false)
const error = ref('')
const errors = ref<ValidationErrors>({})

async function submit() {
  busy.value = true
  error.value = ''
  try {
    await authApi.resetPassword(form)
    await router.push({ name: 'login', query: { reset: 'success' } })
  } catch (caught) {
    error.value = apiError(caught)
    errors.value = fieldErrors(caught)
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <section class="auth-card" aria-labelledby="reset-title">
    <div class="section-heading">
      <p class="eyebrow">Nova senha</p>
      <h1 id="reset-title">Redefina sua senha</h1>
    </div>
    <ErrorMessage v-if="!form.token" message="O link de recuperação está incompleto." />
    <ErrorMessage v-if="error" :message="error" />
    <form v-if="form.token" @submit.prevent="submit">
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
        label="Nova senha"
        type="password"
        autocomplete="new-password"
        required
        :error="errors.password?.[0]"
      />
      <FormField
        id="password-confirmation"
        v-model="form.password_confirmation"
        label="Confirme a nova senha"
        type="password"
        autocomplete="new-password"
        required
      />
      <button class="button button-primary button-block" type="submit" :disabled="busy">
        {{ busy ? 'Salvando…' : 'Salvar nova senha' }}
      </button>
    </form>
  </section>
</template>
