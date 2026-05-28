<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

async function submit(): Promise<void> {
  error.value = ''
  loading.value = true
  try {
    await auth.login({ email: email.value, password: password.value })
    router.push('/')
  } catch {
    error.value = 'These credentials do not match our records.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <main class="min-h-screen flex items-center justify-center bg-slate-50">
    <div class="w-full max-w-sm rounded-lg bg-white p-8 shadow">
      <h1 class="text-2xl font-bold text-slate-800">
        Sign in to Skontro
      </h1>
      <form
        class="mt-6 space-y-4"
        @submit.prevent="submit"
      >
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
        </div>
        <p
          v-if="error"
          class="text-sm text-red-600"
        >
          {{ error }}
        </p>
        <button
          type="submit"
          :disabled="loading"
          class="w-full rounded bg-slate-800 py-2 font-medium text-white disabled:opacity-50"
        >
          {{ loading ? 'Signing in…' : 'Sign in' }}
        </button>
      </form>
      <p class="mt-4 text-center text-sm text-slate-500">
        No account?
        <router-link
          to="/register"
          class="font-medium text-slate-800"
        >
          Create one
        </router-link>
      </p>
    </div>
  </main>
</template>
