<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useInvoiceStore } from '@/stores/invoices'
import { useCustomerStore } from '@/stores/customers'
import { useProductStore } from '@/stores/products'
import { eurosToCents, centsToEuros } from '@/lib/money'
import { previewTotals } from '@/lib/invoiceTotals'
import { UNITS, VAT_RATES } from '@/types/product'
import type { DraftLineInput, VatRate } from '@/types/invoice'
import type { ValidationErrors } from '@/types/auth'

const route = useRoute()
const router = useRouter()
const invoices = useInvoiceStore()
const customers = useCustomerStore()
const products = useProductStore()

const id = computed(() => (route.params.id === 'new' ? null : (route.params.id as string)))
const isEdit = computed(() => id.value !== null)

const customerId = ref('')
const invoiceDate = ref(new Date().toISOString().slice(0, 10))
const notesTop = ref('')
const lines = ref<DraftLineInput[]>([
  { description: '', quantity: '1', unit: 'Stück', unit_price_cents: 0, vat_rate: 19 },
])
const errors = ref<ValidationErrors>({})
const saving = ref(false)

// Each line keeps a EUR string the user edits; converted to cents on change.
const priceStrings = ref<string[]>([''])

onMounted(async () => {
  await customers.fetchList()
  await products.fetchList()
  if (id.value) {
    await invoices.fetchOne(id.value)
    const inv = invoices.current
    if (inv && inv.state === 'draft') {
      customerId.value = inv.customer?.id ?? ''
      invoiceDate.value = inv.invoice_date
      notesTop.value = inv.notes_top ?? ''
      lines.value = inv.lines.map((l) => ({
        description: l.description,
        quantity: l.quantity,
        unit: l.unit,
        unit_price_cents: l.unit_price_cents,
        vat_rate: l.vat_rate,
      }))
      priceStrings.value = inv.lines.map((l) =>
        centsToEuros(l.unit_price_cents).replace(/\s|€/g, '')
      )
    }
  }
})

const totals = computed(() => previewTotals(lines.value))

function addLine(): void {
  lines.value.push({
    description: '',
    quantity: '1',
    unit: 'Stück',
    unit_price_cents: 0,
    vat_rate: 19,
  })
  priceStrings.value.push('')
}

function removeLine(i: number): void {
  lines.value.splice(i, 1)
  priceStrings.value.splice(i, 1)
}

function onPriceInput(i: number): void {
  lines.value[i].unit_price_cents = eurosToCents(priceStrings.value[i])
}

function onProductPick(i: number, productId: string): void {
  const p = products.items.find((x) => x.id === productId)
  if (p) {
    lines.value[i].description = p.name
    lines.value[i].unit = p.unit
    lines.value[i].vat_rate = p.vat_rate as VatRate
    lines.value[i].unit_price_cents = p.unit_price_cents
    lines.value[i].product_id = p.id
    priceStrings.value[i] = centsToEuros(p.unit_price_cents).replace(/\s|€/g, '')
  }
}

async function save(): Promise<void> {
  errors.value = {}
  saving.value = true
  const payload = {
    customer_id: customerId.value,
    invoice_date: invoiceDate.value,
    notes_top: notesTop.value || null,
    lines: lines.value,
  }
  try {
    const inv =
      isEdit.value && id.value
        ? await invoices.updateDraft(id.value, payload)
        : await invoices.createDraft(payload)
    router.push(`/invoices/${inv.id}`)
  } catch (e: unknown) {
    const response = (e as { response?: { status?: number; data?: { errors?: ValidationErrors } } })
      ?.response
    if (response?.status === 422) {
      errors.value = response.data?.errors ?? {}
    } else if (response?.status === 404) {
      errors.value = { customer_id: ['Select a valid customer.'] }
    } else {
      errors.value = { general: ['Something went wrong.'] }
    }
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="max-w-3xl">
    <h1 class="text-2xl font-bold text-slate-800">
      {{ isEdit ? 'Edit draft invoice' : 'New invoice' }}
    </h1>

    <div class="mt-6 grid grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-slate-700">Customer</label>
        <select
          v-model="customerId"
          class="mt-1 w-full rounded border border-slate-300 px-3 py-2"
        >
          <option
            value=""
            disabled
          >
            Select a customer
          </option>
          <option
            v-for="c in customers.items"
            :key="c.id"
            :value="c.id"
          >
            {{ c.company_name ?? c.contact_name }}
          </option>
        </select>
        <p
          v-if="errors.customer_id"
          class="mt-1 text-xs text-red-600"
        >
          {{ errors.customer_id[0] }}
        </p>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700">Invoice date</label>
        <input
          v-model="invoiceDate"
          type="date"
          class="mt-1 w-full rounded border border-slate-300 px-3 py-2"
        >
      </div>
    </div>

    <h2 class="mt-6 text-lg font-semibold text-slate-800">
      Line items
    </h2>
    <div class="mt-2 space-y-3">
      <div
        v-for="(line, i) in lines"
        :key="i"
        class="rounded border border-slate-200 p-3"
      >
        <div class="grid grid-cols-12 gap-2">
          <select
            class="col-span-3 rounded border border-slate-300 px-2 py-1 text-sm"
            @change="onProductPick(i, ($event.target as HTMLSelectElement).value)"
          >
            <option value="">
              — catalog —
            </option>
            <option
              v-for="p in products.items"
              :key="p.id"
              :value="p.id"
            >
              {{ p.name }}
            </option>
          </select>
          <input
            v-model="line.description"
            placeholder="Description"
            class="col-span-9 rounded border border-slate-300 px-2 py-1 text-sm"
          >
          <input
            v-model="line.quantity"
            inputmode="decimal"
            placeholder="Qty"
            class="col-span-2 rounded border border-slate-300 px-2 py-1 text-sm"
          >
          <select
            v-model="line.unit"
            class="col-span-3 rounded border border-slate-300 px-2 py-1 text-sm"
          >
            <option
              v-for="u in UNITS"
              :key="u"
              :value="u"
            >
              {{ u }}
            </option>
          </select>
          <input
            v-model="priceStrings[i]"
            inputmode="decimal"
            placeholder="Unit price €"
            class="col-span-3 rounded border border-slate-300 px-2 py-1 text-sm"
            @input="onPriceInput(i)"
          >
          <select
            v-model.number="line.vat_rate"
            class="col-span-2 rounded border border-slate-300 px-2 py-1 text-sm"
          >
            <option
              v-for="r in VAT_RATES"
              :key="r"
              :value="r"
            >
              {{ r }}%
            </option>
          </select>
          <button
            type="button"
            class="col-span-2 text-sm text-red-600"
            @click="removeLine(i)"
          >
            Remove
          </button>
        </div>
      </div>
    </div>
    <button
      type="button"
      class="mt-2 text-sm font-medium text-slate-800"
      @click="addLine"
    >
      + Add line
    </button>

    <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-4 text-right text-sm">
      <div>
        Subtotal: <span class="font-medium">{{ centsToEuros(totals.subtotalCents) }}</span>
      </div>
      <div>
        VAT: <span class="font-medium">{{ centsToEuros(totals.totalVatCents) }}</span>
      </div>
      <div class="text-base">
        Total: <span class="font-bold">{{ centsToEuros(totals.totalCents) }}</span>
      </div>
    </div>

    <p
      v-if="errors.general"
      class="mt-3 text-sm text-red-600"
    >
      {{ errors.general[0] }}
    </p>

    <div class="mt-6 flex gap-3">
      <button
        :disabled="saving"
        class="rounded bg-slate-800 px-4 py-2 font-medium text-white disabled:opacity-50"
        @click="save"
      >
        {{ saving ? 'Saving…' : 'Save draft' }}
      </button>
      <router-link
        to="/invoices"
        class="rounded border border-slate-300 px-4 py-2 text-slate-700"
      >
        Cancel
      </router-link>
    </div>
  </div>
</template>
