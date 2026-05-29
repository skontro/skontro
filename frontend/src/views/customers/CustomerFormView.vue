<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCustomerStore } from '@/stores/customers'
import type { CustomerFormData } from '@/types/customer'
import type { ValidationErrors } from '@/types/auth'

const route = useRoute()
const router = useRouter()
const store = useCustomerStore()

const id = computed(() => (route.params.id ? (route.params.id as string) : null))
const isEdit = computed(() => id.value !== null)

const form = ref<CustomerFormData>({
  type: 'company',
  company_name: '',
  contact_name: '',
  email: '',
  country_code: 'DE',
  vat_id: '',
})
const errors = ref<ValidationErrors>({})
const saving = ref(false)

onMounted(async () => {
  if (id.value) {
    await store.fetchOne(id.value)
    if (store.current) {
      form.value = {
        type: store.current.type,
        company_name: store.current.company_name ?? '',
        contact_name: store.current.contact_name,
        email: store.current.email ?? '',
        country_code: store.current.address.country_code ?? 'DE',
        vat_id: store.current.vat_id ?? '',
        payment_terms_days: store.current.payment_terms_days,
      }
    }
  }
})

function fieldError(field: string): string | undefined {
  return errors.value[field]?.[0]
}

async function submit(): Promise<void> {
  errors.value = {}
  saving.value = true
  try {
    if (isEdit.value && id.value) {
      await store.update(id.value, form.value)
    } else {
      await store.create(form.value)
    }
    router.push('/customers')
  } catch (e: unknown) {
    const response = (e as { response?: { status?: number; data?: { errors?: ValidationErrors } } })?.response
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
      {{ isEdit ? 'Edit customer' : 'New customer' }}
    </h1>

    <form
      class="mt-6 space-y-4"
      @submit.prevent="submit"
    >
      <div>
        <label class="block text-sm font-medium text-slate-700">Type</label>
        <select
          v-model="form.type"
          class="mt-1 w-full rounded border border-slate-300 px-3 py-2"
        >
          <option value="company">
            Company
          </option>
          <option value="individual">
            Individual
          </option>
        </select>
      </div>

      <div v-if="form.type === 'company'">
        <label class="block text-sm font-medium text-slate-700">Company name</label>
        <input
          v-model="form.company_name"
          class="mt-1 w-full rounded border border-slate-300 px-3 py-2"
        >
        <p
          v-if="fieldError('company_name')"
          class="mt-1 text-xs text-red-600"
        >
          {{ fieldError('company_name') }}
        </p>
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700">Contact name</label>
        <input
          v-model="form.contact_name"
          required
          class="mt-1 w-full rounded border border-slate-300 px-3 py-2"
        >
        <p
          v-if="fieldError('contact_name')"
          class="mt-1 text-xs text-red-600"
        >
          {{ fieldError('contact_name') }}
        </p>
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700">Email</label>
        <input
          v-model="form.email"
          type="email"
          class="mt-1 w-full rounded border border-slate-300 px-3 py-2"
        >
        <p
          v-if="fieldError('email')"
          class="mt-1 text-xs text-red-600"
        >
          {{ fieldError('email') }}
        </p>
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700">VAT ID</label>
        <input
          v-model="form.vat_id"
          class="mt-1 w-full rounded border border-slate-300 px-3 py-2"
        >
        <p
          v-if="fieldError('vat_id')"
          class="mt-1 text-xs text-red-600"
        >
          {{ fieldError('vat_id') }}
        </p>
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
          to="/customers"
          class="rounded border border-slate-300 px-4 py-2 text-slate-700"
        >
          Cancel
        </router-link>
      </div>
    </form>
  </div>
</template>
