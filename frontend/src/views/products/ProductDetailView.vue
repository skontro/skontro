<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useProductStore } from '@/stores/products'

const route = useRoute()
const store = useProductStore()

onMounted(() => store.fetchOne(route.params.id as string))

async function toggleArchive(): Promise<void> {
  if (!store.current) return
  if (store.current.is_active) {
    await store.archive(store.current.id)
  } else {
    await store.unarchive(store.current.id)
  }
  await store.fetchOne(route.params.id as string)
}
</script>

<template>
  <div
    v-if="store.current"
    class="max-w-lg"
  >
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-slate-800">
        {{ store.current.name }}
      </h1>
      <span class="text-lg text-slate-800">{{ store.current.unit_price_formatted }}</span>
    </div>
    <dl class="mt-6 space-y-2 text-sm">
      <div class="flex gap-2">
        <dt class="w-32 text-slate-500">
          VAT
        </dt>
        <dd>{{ store.current.vat_rate }}%</dd>
      </div>
      <div class="flex gap-2">
        <dt class="w-32 text-slate-500">
          Unit
        </dt>
        <dd>{{ store.current.unit }} ({{ store.current.unit_code }})</dd>
      </div>
      <div class="flex gap-2">
        <dt class="w-32 text-slate-500">
          SKU
        </dt>
        <dd>{{ store.current.sku ?? '—' }}</dd>
      </div>
      <div class="flex gap-2">
        <dt class="w-32 text-slate-500">
          Status
        </dt>
        <dd>{{ store.current.is_active ? 'Active' : 'Archived' }}</dd>
      </div>
    </dl>
    <div class="mt-6 flex gap-3">
      <router-link
        :to="`/products/${store.current.id}/edit`"
        class="rounded bg-slate-800 px-4 py-2 text-sm font-medium text-white"
      >
        Edit
      </router-link>
      <button
        class="rounded border border-slate-300 px-4 py-2 text-sm text-slate-700"
        @click="toggleArchive"
      >
        {{ store.current.is_active ? 'Archive' : 'Unarchive' }}
      </button>
    </div>
  </div>
</template>
