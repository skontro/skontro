<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'

const apiStatus = ref<string>('checking...')
const apiVersion = ref<string>('')

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000/api/v1'

onMounted(async () => {
  try {
    const response = await axios.get(`${apiBaseUrl}/health`)
    apiStatus.value = response.data.status
    apiVersion.value = response.data.version
  } catch {
    apiStatus.value = 'unreachable'
  }
})
</script>

<template>
  <main class="min-h-screen flex items-center justify-center bg-slate-50">
    <div class="text-center">
      <h1 class="text-4xl font-bold text-slate-800">
        Skontro
      </h1>
      <p class="mt-2 text-slate-600">
        Open-source German-compliant mini-ERP
      </p>
      <div class="mt-6 inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 shadow">
        <span class="text-sm text-slate-500">API status:</span>
        <span
          class="text-sm font-medium"
          :class="apiStatus === 'ok' ? 'text-emerald-600' : 'text-amber-600'"
        >{{ apiStatus }}</span>
        <span
          v-if="apiVersion"
          class="text-xs text-slate-400"
        >v{{ apiVersion }}</span>
      </div>
    </div>
  </main>
</template>
