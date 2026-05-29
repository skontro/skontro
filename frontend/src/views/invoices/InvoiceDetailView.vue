<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useInvoiceStore } from '@/stores/invoices'
import { eurosToCents, centsToEuros } from '@/lib/money'
import type { InvoiceState } from '@/types/invoice'

const route = useRoute()
const store = useInvoiceStore()

const id = computed(() => route.params.id as string)
const inv = computed(() => store.current)
const actionError = ref('')

const showPayment = ref(false)
const showCancel = ref(false)
const paymentAmount = ref('')
const paymentMethod = ref('bank_transfer')
const paymentDate = ref(new Date().toISOString().slice(0, 10))
const cancelReason = ref('')

const PAYMENT_METHODS = [
  { value: 'cash', label: 'Cash' },
  { value: 'bank_transfer', label: 'Bank transfer' },
  { value: 'sepa_direct_debit', label: 'SEPA direct debit' },
  { value: 'credit_card', label: 'Credit card' },
  { value: 'other', label: 'Other' },
]

const canEdit = computed(() => inv.value?.state === 'draft')
const canIssue = computed(() => inv.value?.state === 'draft')
const canSend = computed(() => inv.value?.state === 'issued')
const canPay = computed(() =>
  (['issued', 'sent', 'partially_paid'] as InvoiceState[]).includes(
    inv.value?.state as InvoiceState
  )
)
const canCancel = computed(
  () => inv.value !== null && !(['paid', 'cancelled'] as InvoiceState[]).includes(inv.value.state)
)

onMounted(() => store.fetchOne(id.value))

async function run(action: () => Promise<unknown>): Promise<void> {
  actionError.value = ''
  try {
    await action()
    await store.fetchOne(id.value)
  } catch (e: unknown) {
    const response = (e as { response?: { status?: number; data?: { message?: string } } })
      ?.response
    actionError.value =
      response?.status === 409
        ? (response.data?.message ?? 'That action conflicts with the current state.')
        : 'Something went wrong.'
  }
}

function issue(): Promise<void> {
  return run(() => store.issue(id.value))
}

function send(): Promise<void> {
  return run(() => store.send(id.value))
}

async function submitPayment(): Promise<void> {
  await run(() =>
    store.recordPayment(id.value, {
      amount_cents: eurosToCents(paymentAmount.value),
      payment_date: paymentDate.value,
      method: paymentMethod.value,
    })
  )
  showPayment.value = false
  paymentAmount.value = ''
}

async function submitCancel(): Promise<void> {
  await run(() => store.cancel(id.value, cancelReason.value))
  showCancel.value = false
  cancelReason.value = ''
}
</script>

<template>
  <div
    v-if="inv"
    class="max-w-2xl"
  >
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">
          {{ inv.number ?? 'Draft invoice' }}
        </h1>
        <p class="mt-1 text-sm text-slate-500">
          {{ inv.customer?.company_name ?? inv.customer?.contact_name ?? '—' }}
        </p>
      </div>
      <span class="rounded bg-slate-100 px-3 py-1 text-sm font-medium text-slate-700">
        {{ inv.state }}
      </span>
    </div>

    <table class="mt-6 w-full text-left text-sm">
      <thead class="text-slate-500">
        <tr>
          <th class="py-2">
            Description
          </th>
          <th class="py-2 text-right">
            Qty
          </th>
          <th class="py-2 text-right">
            Unit price
          </th>
          <th class="py-2 text-right">
            VAT
          </th>
          <th class="py-2 text-right">
            Net
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="(line, i) in inv.lines"
          :key="i"
          class="border-t border-slate-100"
        >
          <td class="py-2 text-slate-800">
            {{ line.description }}
          </td>
          <td class="py-2 text-right">
            {{ line.quantity }} {{ line.unit }}
          </td>
          <td class="py-2 text-right">
            {{ line.unit_price_formatted }}
          </td>
          <td class="py-2 text-right">
            {{ line.vat_rate }}%
          </td>
          <td class="py-2 text-right">
            {{ line.line_net_formatted }}
          </td>
        </tr>
      </tbody>
    </table>

    <div class="mt-4 border-t border-slate-200 pt-4 text-right text-sm">
      <div>
        Subtotal: <span class="font-medium">{{ centsToEuros(inv.subtotal_cents) }}</span>
      </div>
      <div>
        VAT: <span class="font-medium">{{ centsToEuros(inv.total_vat_cents) }}</span>
      </div>
      <div class="text-base">
        Total: <span class="font-bold">{{ inv.total_formatted }}</span>
      </div>
      <div
        v-if="inv.payments.length > 0"
        class="mt-1 text-slate-500"
      >
        Paid: {{ centsToEuros(inv.paid_cents ?? 0) }}
      </div>
    </div>

    <div
      v-if="inv.payments.length > 0"
      class="mt-4"
    >
      <h2 class="text-sm font-semibold text-slate-700">
        Payments
      </h2>
      <ul class="mt-1 text-sm text-slate-600">
        <li
          v-for="p in inv.payments"
          :key="p.id"
        >
          {{ p.payment_date }} — {{ p.amount_formatted }} ({{ p.method }})
        </li>
      </ul>
    </div>

    <p
      v-if="actionError"
      class="mt-4 text-sm text-red-600"
    >
      {{ actionError }}
    </p>

    <div class="mt-6 flex flex-wrap gap-3">
      <router-link
        v-if="canEdit"
        :to="`/invoices/${inv.id}/edit`"
        class="rounded border border-slate-300 px-4 py-2 text-sm text-slate-700"
      >
        Edit
      </router-link>
      <button
        v-if="canIssue"
        class="rounded bg-slate-800 px-4 py-2 text-sm font-medium text-white"
        @click="issue"
      >
        Issue
      </button>
      <button
        v-if="canSend"
        class="rounded bg-slate-800 px-4 py-2 text-sm font-medium text-white"
        @click="send"
      >
        Mark as sent
      </button>
      <button
        v-if="canPay"
        class="rounded border border-slate-300 px-4 py-2 text-sm text-slate-700"
        @click="showPayment = !showPayment"
      >
        Record payment
      </button>
      <button
        v-if="canCancel"
        class="rounded border border-red-300 px-4 py-2 text-sm text-red-600"
        @click="showCancel = !showCancel"
      >
        Cancel
      </button>
    </div>

    <div
      v-if="showPayment"
      class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4"
    >
      <h2 class="text-sm font-semibold text-slate-700">
        Record payment
      </h2>
      <div class="mt-2 flex flex-wrap items-end gap-3">
        <div>
          <label class="block text-xs text-slate-500">Amount €</label>
          <input
            v-model="paymentAmount"
            inputmode="decimal"
            class="mt-1 rounded border border-slate-300 px-2 py-1 text-sm"
          >
        </div>
        <div>
          <label class="block text-xs text-slate-500">Date</label>
          <input
            v-model="paymentDate"
            type="date"
            class="mt-1 rounded border border-slate-300 px-2 py-1 text-sm"
          >
        </div>
        <div>
          <label class="block text-xs text-slate-500">Method</label>
          <select
            v-model="paymentMethod"
            class="mt-1 rounded border border-slate-300 px-2 py-1 text-sm"
          >
            <option
              v-for="m in PAYMENT_METHODS"
              :key="m.value"
              :value="m.value"
            >
              {{ m.label }}
            </option>
          </select>
        </div>
        <button
          class="rounded bg-slate-800 px-4 py-2 text-sm font-medium text-white"
          @click="submitPayment"
        >
          Save payment
        </button>
      </div>
    </div>

    <div
      v-if="showCancel"
      class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4"
    >
      <h2 class="text-sm font-semibold text-red-700">
        Cancel invoice
      </h2>
      <div class="mt-2 flex items-end gap-3">
        <div class="flex-1">
          <label class="block text-xs text-slate-500">Reason</label>
          <input
            v-model="cancelReason"
            placeholder="Why is this invoice cancelled?"
            class="mt-1 w-full rounded border border-slate-300 px-2 py-1 text-sm"
          >
        </div>
        <button
          class="rounded bg-red-600 px-4 py-2 text-sm font-medium text-white"
          @click="submitCancel"
        >
          Confirm cancel
        </button>
      </div>
    </div>
  </div>
</template>
