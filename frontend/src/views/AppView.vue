<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

async function handleLogout(): Promise<void> {
  await auth.logout()
  router.push('/login')
}
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <header class="flex items-center justify-between border-b border-slate-200 bg-white px-6 py-4">
      <div>
        <span class="text-lg font-bold text-slate-800">Skontro</span>
        <span
          v-if="auth.tenant"
          class="ml-3 text-sm text-slate-500"
        >{{ auth.tenant.name }}</span>
      </div>
      <div class="flex items-center gap-4">
        <span
          v-if="auth.user"
          class="text-sm text-slate-600"
        >{{ auth.user.name }}</span>
        <button
          class="text-sm font-medium text-slate-800"
          @click="handleLogout"
        >
          Sign out
        </button>
      </div>
    </header>
    <main class="p-6">
      <p class="text-slate-600">
        Signed in. Dashboard and features land in upcoming milestones.
      </p>
    </main>
  </div>
</template>
