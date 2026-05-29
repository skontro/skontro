<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useCustomerStore } from '@/stores/customers'

const router = useRouter()
const store = useCustomerStore()
const search = ref('')

let debounce: ReturnType<typeof setTimeout> | undefined

onMounted(() => store.fetchList())

watch(search, (value) => {
  clearTimeout(debounce)
  debounce = setTimeout(() => store.fetchList({ search: value }), 300)
})

function open(id: string): void {
  router.push(`/customers/${id}`)
}
</script>

<template>
  <div>
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-slate-800">
        Customers
      </h1>
      <router-link
        to="/customers/new"
        class="rounded bg-slate-800 px-4 py-2 text-sm font-medium text-white"
      >
        New customer
      </router-link>
    </div>

    <input
      v-model="search"
      type="search"
      placeholder="Search by name, number, email, or VAT ID"
      class="mt-4 w-full max-w-md rounded border border-slate-300 px-3 py-2"
    >

    <div class="mt-4 overflow-hidden rounded-lg border border-slate-200 bg-white">
      <table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-slate-600">
          <tr>
            <th class="px-4 py-3">
              Number
            </th>
            <th class="px-4 py-3">
              Name
            </th>
            <th class="px-4 py-3">
              Email
            </th>
            <th class="px-4 py-3">
              Type
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="c in store.items"
            :key="c.id"
            class="cursor-pointer border-t border-slate-100 hover:bg-slate-50"
            @click="open(c.id)"
          >
            <td class="px-4 py-3 font-mono text-xs text-slate-500">
              {{ c.number }}
            </td>
            <td class="px-4 py-3 text-slate-800">
              {{ c.company_name ?? c.contact_name }}
            </td>
            <td class="px-4 py-3 text-slate-600">
              {{ c.email }}
            </td>
            <td class="px-4 py-3 text-slate-600">
              {{ c.type }}
            </td>
          </tr>
          <tr v-if="!store.loading && store.items.length === 0">
            <td
              colspan="4"
              class="px-4 py-8 text-center text-slate-400"
            >
              No customers yet.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <p
      v-if="store.meta"
      class="mt-3 text-sm text-slate-500"
    >
      {{ store.meta.total }} customer(s)
    </p>
  </div>
</template>
