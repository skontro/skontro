<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useInvoiceStore } from '@/stores/invoices'
import type { InvoiceState } from '@/types/invoice'

const router = useRouter()
const store = useInvoiceStore()
const stateFilter = ref('')

const STATES: { value: InvoiceState; label: string }[] = [
  { value: 'draft', label: 'Draft' },
  { value: 'issued', label: 'Issued' },
  { value: 'sent', label: 'Sent' },
  { value: 'partially_paid', label: 'Partially paid' },
  { value: 'paid', label: 'Paid' },
  { value: 'cancelled', label: 'Cancelled' },
]

const BADGE: Record<InvoiceState, string> = {
  draft: 'bg-slate-100 text-slate-600',
  issued: 'bg-blue-100 text-blue-700',
  sent: 'bg-indigo-100 text-indigo-700',
  partially_paid: 'bg-amber-100 text-amber-700',
  paid: 'bg-green-100 text-green-700',
  cancelled: 'bg-red-100 text-red-700',
}

onMounted(() => store.fetchList())

watch(stateFilter, (value) => store.fetchList(value ? { state: value } : {}))

function open(id: string): void {
  router.push(`/invoices/${id}`)
}
</script>

<template>
  <div>
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-slate-800">
        Invoices
      </h1>
      <router-link
        to="/invoices/new"
        class="rounded bg-slate-800 px-4 py-2 text-sm font-medium text-white"
      >
        New invoice
      </router-link>
    </div>

    <select
      v-model="stateFilter"
      class="mt-4 rounded border border-slate-300 px-3 py-2 text-sm"
    >
      <option value="">
        All states
      </option>
      <option
        v-for="s in STATES"
        :key="s.value"
        :value="s.value"
      >
        {{ s.label }}
      </option>
    </select>

    <div class="mt-4 overflow-hidden rounded-lg border border-slate-200 bg-white">
      <table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-slate-600">
          <tr>
            <th class="px-4 py-3">
              Number
            </th>
            <th class="px-4 py-3">
              Customer
            </th>
            <th class="px-4 py-3">
              Date
            </th>
            <th class="px-4 py-3 text-right">
              Total
            </th>
            <th class="px-4 py-3">
              State
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="inv in store.items"
            :key="inv.id"
            class="cursor-pointer border-t border-slate-100 hover:bg-slate-50"
            @click="open(inv.id)"
          >
            <td class="px-4 py-3 font-mono text-xs text-slate-500">
              {{ inv.number ?? 'Draft' }}
            </td>
            <td class="px-4 py-3 text-slate-800">
              {{ inv.customer?.company_name ?? inv.customer?.contact_name ?? '—' }}
            </td>
            <td class="px-4 py-3 text-slate-600">
              {{ inv.invoice_date }}
            </td>
            <td class="px-4 py-3 text-right text-slate-800">
              {{ inv.total_formatted }}
            </td>
            <td class="px-4 py-3">
              <span
                class="rounded px-2 py-1 text-xs font-medium"
                :class="BADGE[inv.state]"
              >
                {{ inv.state }}
              </span>
            </td>
          </tr>
          <tr v-if="!store.loading && store.items.length === 0">
            <td
              colspan="5"
              class="px-4 py-8 text-center text-slate-400"
            >
              No invoices yet.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <p
      v-if="store.meta"
      class="mt-3 text-sm text-slate-500"
    >
      {{ store.meta.total }} invoice(s)
    </p>
  </div>
</template>
