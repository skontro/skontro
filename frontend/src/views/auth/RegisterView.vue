<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { isAxiosError } from 'axios'
import { useAuthStore } from '@/stores/auth'
import type { ValidationErrors } from '@/types/auth'

const router = useRouter()
const auth = useAuthStore()

const companyName = ref('')
const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const errors = ref<ValidationErrors>({})
const loading = ref(false)

function fieldError(field: string): string | undefined {
  return errors.value[field]?.[0]
}

async function submit(): Promise<void> {
  errors.value = {}
  loading.value = true
  try {
    await auth.register({
      company_name: companyName.value,
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
    router.push('/')
  } catch (e: unknown) {
    if (isAxiosError(e) && e.response?.status === 422) {
      errors.value = e.response.data.errors ?? {}
    } else {
      errors.value = { general: ['Something went wrong. Please try again.'] }
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <main class="min-h-screen flex items-center justify-center bg-slate-50">
    <div class="w-full max-w-sm rounded-lg bg-white p-8 shadow">
      <h1 class="text-2xl font-bold text-slate-800">
        Create your Skontro account
      </h1>
      <form
        class="mt-6 space-y-4"
        @submit.prevent="submit"
      >
        <div>
          <label
            class="block text-sm font-medium text-slate-700"
            for="company"
          >Company name</label>
          <input
            id="company"
            v-model="companyName"
            required
            class="mt-1 w-full rounded border border-slate-300 px-3 py-2"
          >
          <p
            v-if="fieldError('company_name')"
            class="mt-1 text-xs text-red-600"
          >
            {{ fieldError('company_name') }}
          </p>
        </div>
        <div>
          <label
            class="block text-sm font-medium text-slate-700"
            for="name"
          >Your name</label>
          <input
            id="name"
            v-model="name"
            required
            class="mt-1 w-full rounded border border-slate-300 px-3 py-2"
          >
          <p
            v-if="fieldError('name')"
            class="mt-1 text-xs text-red-600"
          >
            {{ fieldError('name') }}
          </p>
        </div>
        <div>
          <label
            class="block text-sm font-medium text-slate-700"
            for="email"
          >Email</label>
          <input
            id="email"
            v-model="email"
            type="email"
            required
            class="mt-1 w-full rounded border border-slate-300 px-3 py-2"
          >
          <p
            v-if="fieldError('email')"
            class="mt-1 text-xs text-red-600"
          >
            {{ fieldError('email') }}
          </p>
        </div>
        <div>
          <label
            class="block text-sm font-medium text-slate-700"
            for="password"
          >Password</label>
          <input
            id="password"
            v-model="password"
            type="password"
            required
            class="mt-1 w-full rounded border border-slate-300 px-3 py-2"
          >
          <p
            v-if="fieldError('password')"
            class="mt-1 text-xs text-red-600"
          >
            {{ fieldError('password') }}
          </p>
        </div>
        <div>
          <label
            class="block text-sm font-medium text-slate-700"
            for="password_confirmation"
          >Confirm password</label>
          <input
            id="password_confirmation"
            v-model="passwordConfirmation"
            type="password"
            required
            class="mt-1 w-full rounded border border-slate-300 px-3 py-2"
          >
        </div>
        <p
          v-if="fieldError('general')"
          class="text-sm text-red-600"
        >
          {{ fieldError('general') }}
        </p>
        <button
          type="submit"
          :disabled="loading"
          class="w-full rounded bg-slate-800 py-2 font-medium text-white disabled:opacity-50"
        >
          {{ loading ? 'Creating…' : 'Create account' }}
        </button>
      </form>
      <p class="mt-4 text-center text-sm text-slate-500">
        Already have an account?
        <router-link
          to="/login"
          class="font-medium text-slate-800"
        >
          Sign in
        </router-link>
      </p>
    </div>
  </main>
</template>
