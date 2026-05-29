<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCustomerStore } from '@/stores/customers'

const route = useRoute()
const router = useRouter()
const store = useCustomerStore()

onMounted(() => store.fetchOne(route.params.id as string))

async function remove(): Promise<void> {
  if (store.current) {
    await store.remove(store.current.id)
    router.push('/customers')
  }
}
</script>

<template>
  <div
    v-if="store.current"
    class="max-w-lg"
  >
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-slate-800">
        {{ store.current.company_name ?? store.current.contact_name }}
      </h1>
      <span class="font-mono text-xs text-slate-500">{{ store.current.number }}</span>
    </div>
    <dl class="mt-6 space-y-2 text-sm">
      <div class="flex gap-2">
        <dt class="w-32 text-slate-500">
          Contact
        </dt><dd>{{ store.current.contact_name }}</dd>
      </div>
      <div class="flex gap-2">
        <dt class="w-32 text-slate-500">
          Email
        </dt><dd>{{ store.current.email }}</dd>
      </div>
      <div class="flex gap-2">
        <dt class="w-32 text-slate-500">
          VAT ID
        </dt><dd>{{ store.current.vat_id }}</dd>
      </div>
    </dl>
    <div class="mt-6 flex gap-3">
      <router-link
        :to="`/customers/${store.current.id}/edit`"
        class="rounded bg-slate-800 px-4 py-2 text-sm font-medium text-white"
      >
        Edit
      </router-link>
      <button
        class="rounded border border-red-300 px-4 py-2 text-sm text-red-600"
        @click="remove"
      >
        Delete
      </button>
    </div>
  </div>
</template>
