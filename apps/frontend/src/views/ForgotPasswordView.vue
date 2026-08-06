<script setup lang="ts">
import { ref } from 'vue'
import { authApi } from '@/api/auth'
import { apiError, fieldErrors } from '@/api/http'
import FormField from '@/components/forms/FormField.vue'
import ErrorMessage from '@/components/feedback/ErrorMessage.vue'

const email = ref('')
const busy = ref(false)
const error = ref('')
const emailError = ref('')
const sent = ref(false)

async function submit() {
  busy.value = true
  error.value = ''
  emailError.value = ''
  try {
    await authApi.forgotPassword(email.value)
    sent.value = true
  } catch (caught) {
    error.value = apiError(caught)
    emailError.value = fieldErrors(caught).email?.[0] ?? ''
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <section class="auth-card" aria-labelledby="forgot-title">
    <div class="section-heading">
      <p class="eyebrow">Recuperação</p>
      <h1 id="forgot-title">Esqueceu sua senha?</h1>
    </div>
    <p>Informe seu e-mail. Se houver uma conta, enviaremos um link de redefinição.</p>
    <div v-if="sent" class="alert alert-success" role="status">
      Solicitação recebida. Confira sua caixa de entrada.
    </div>
    <ErrorMessage v-if="error" :message="error" />
    <form v-if="!sent" @submit.prevent="submit">
      <FormField
        id="email"
        v-model="email"
        label="E-mail"
        type="email"
        autocomplete="email"
        required
        :error="emailError"
      />
      <button class="button button-primary button-block" type="submit" :disabled="busy">
        {{ busy ? 'Enviando…' : 'Enviar link' }}
      </button>
    </form>
    <p class="center"><RouterLink to="/login">Voltar ao login</RouterLink></p>
  </section>
</template>
