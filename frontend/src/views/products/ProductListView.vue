<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useProductStore } from '@/stores/products'

const router = useRouter()
const store = useProductStore()
const search = ref('')
const includeArchived = ref(false)

let debounce: ReturnType<typeof setTimeout> | undefined

function load(): void {
  store.fetchList({ search: search.value, include_archived: includeArchived.value ? 1 : 0 })
}

onMounted(load)

watch(search, () => {
  clearTimeout(debounce)
  debounce = setTimeout(load, 300)
})

watch(includeArchived, load)

function open(id: string): void {
  router.push(`/products/${id}`)
}
</script>

<template>
  <div>
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-slate-800">
        Products &amp; Services
      </h1>
      <router-link
        to="/products/new"
        class="rounded bg-slate-800 px-4 py-2 text-sm font-medium text-white"
      >
        New item
      </router-link>
    </div>

    <div class="mt-4 flex items-center gap-4">
      <input
        v-model="search"
        type="search"
        placeholder="Search by name or SKU"
        class="w-full max-w-md rounded border border-slate-300 px-3 py-2"
      >
      <label class="flex items-center gap-2 text-sm text-slate-600">
        <input
          v-model="includeArchived"
          type="checkbox"
        >
        Show archived
      </label>
    </div>

    <div class="mt-4 overflow-hidden rounded-lg border border-slate-200 bg-white">
      <table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-slate-600">
          <tr>
            <th class="px-4 py-3">
              Name
            </th>
            <th class="px-4 py-3">
              Unit
            </th>
            <th class="px-4 py-3 text-right">
              Price
            </th>
            <th class="px-4 py-3 text-right">
              VAT
            </th>
            <th class="px-4 py-3">
              Status
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="p in store.items"
            :key="p.id"
            class="cursor-pointer border-t border-slate-100 hover:bg-slate-50"
            @click="open(p.id)"
          >
            <td class="px-4 py-3 text-slate-800">
              {{ p.name }}
            </td>
            <td class="px-4 py-3 text-slate-600">
              {{ p.unit }}
            </td>
            <td class="px-4 py-3 text-right text-slate-800">
              {{ p.unit_price_formatted }}
            </td>
            <td class="px-4 py-3 text-right text-slate-600">
              {{ p.vat_rate }}%
            </td>
            <td class="px-4 py-3">
              <span :class="p.is_active ? 'text-green-600' : 'text-slate-400'">
                {{ p.is_active ? 'Active' : 'Archived' }}
              </span>
            </td>
          </tr>
          <tr v-if="!store.loading && store.items.length === 0">
            <td
              colspan="5"
              class="px-4 py-8 text-center text-slate-400"
            >
              No products yet.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
