<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useProductStore } from '@/stores/products'
import { eurosToCents, centsToEuros } from '@/lib/money'
import { UNITS, VAT_RATES, type ProductFormData, type VatRate } from '@/types/product'
import type { ValidationErrors } from '@/types/auth'

const route = useRoute()
const router = useRouter()
const store = useProductStore()

const id = computed(() => (route.params.id ? (route.params.id as string) : null))
const isEdit = computed(() => id.value !== null)

const name = ref('')
const priceEur = ref('') // the user types euros; we convert to cents on submit
const vatRate = ref<VatRate>(19)
const unit = ref<string>('Stück')
const sku = ref('')
const description = ref('')
const errors = ref<ValidationErrors>({})
const saving = ref(false)

onMounted(async () => {
  if (id.value) {
    await store.fetchOne(id.value)
    const p = store.current
    if (p) {
      name.value = p.name
      priceEur.value = centsToEuros(p.unit_price_cents).replace(/\s|€/g, '')
      vatRate.value = p.vat_rate
      unit.value = p.unit
      sku.value = p.sku ?? ''
      description.value = p.description ?? ''
    }
  }
})

function fieldError(field: string): string | undefined {
  return errors.value[field]?.[0]
}

async function submit(): Promise<void> {
  errors.value = {}
  saving.value = true
  const payload: ProductFormData = {
    name: name.value,
    unit_price_cents: eurosToCents(priceEur.value), // euros -> cents at the boundary
    vat_rate: vatRate.value,
    unit: unit.value,
    sku: sku.value || null,
    description: description.value || null,
  }
  try {
    if (isEdit.value && id.value) {
      await store.update(id.value, payload)
    } else {
      await store.create(payload)
    }
    router.push('/products')
  } catch (e: unknown) {
    const response = (e as { response?: { status?: number; data?: { errors?: ValidationErrors } } })
      ?.response
    if (response?.status === 422) {
      errors.value = response.data?.errors ?? {}
    } else {
      errors.value = { general: ['Something went wrong. Please try again.'] }
    }
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="max-w-lg">
    <h1 class="text-2xl font-bold text-slate-800">
      {{ isEdit ? 'Edit item' : 'New item' }}
    </h1>
    <form
      class="mt-6 space-y-4"
      @submit.prevent="submit"
    >
      <div>
        <label class="block text-sm font-medium text-slate-700">Name</label>
        <input
          v-model="name"
          required
          class="mt-1 w-full rounded border border-slate-300 px-3 py-2"
        >
        <p
          v-if="fieldError('name')"
          class="mt-1 text-xs text-red-600"
        >
          {{ fieldError('name') }}
        </p>
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700">Unit price (EUR)</label>
        <input
          v-model="priceEur"
          inputmode="decimal"
          placeholder="0,00"
          class="mt-1 w-full rounded border border-slate-300 px-3 py-2"
        >
        <p
          v-if="fieldError('unit_price_cents')"
          class="mt-1 text-xs text-red-600"
        >
          {{ fieldError('unit_price_cents') }}
        </p>
      </div>

      <div class="flex gap-4">
        <div class="flex-1">
          <label class="block text-sm font-medium text-slate-700">VAT rate</label>
          <select
            v-model.number="vatRate"
            class="mt-1 w-full rounded border border-slate-300 px-3 py-2"
          >
            <option
              v-for="r in VAT_RATES"
              :key="r"
              :value="r"
            >
              {{ r }}%
            </option>
          </select>
        </div>
        <div class="flex-1">
          <label class="block text-sm font-medium text-slate-700">Unit</label>
          <select
            v-model="unit"
            class="mt-1 w-full rounded border border-slate-300 px-3 py-2"
          >
            <option
              v-for="u in UNITS"
              :key="u"
              :value="u"
            >
              {{ u }}
            </option>
          </select>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700">SKU (optional)</label>
        <input
          v-model="sku"
          class="mt-1 w-full rounded border border-slate-300 px-3 py-2"
        >
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700">Description (optional)</label>
        <textarea
          v-model="description"
          rows="3"
          class="mt-1 w-full rounded border border-slate-300 px-3 py-2"
        />
      </div>

      <p
        v-if="fieldError('general')"
        class="text-sm text-red-600"
      >
        {{ fieldError('general') }}
      </p>

      <div class="flex gap-3">
        <button
          type="submit"
          :disabled="saving"
          class="rounded bg-slate-800 px-4 py-2 font-medium text-white disabled:opacity-50"
        >
          {{ saving ? 'Saving…' : 'Save' }}
        </button>
        <router-link
          to="/products"
          class="rounded border border-slate-300 px-4 py-2 text-slate-700"
        >
          Cancel
        </router-link>
      </div>
    </form>
  </div>
</template>
