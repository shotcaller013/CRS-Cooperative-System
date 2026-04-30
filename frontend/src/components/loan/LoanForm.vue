<template>
  <form @submit.prevent="emit('submit', form)" class="crs-form">
    <!-- Member -->
    <div class="form-section-title">Member & Loan Type</div>
    <div class="form-grid">
      <div class="field">
        <label class="field-label">Member <span class="required">*</span></label>
        <Dropdown v-model="form.member_id"
          :options="members" optionLabel="full_name_no" optionValue="id"
          filter placeholder="Search member…" class="w-full"
          :class="{ 'p-invalid': errors.member_id }"
          :disabled="isEdit" />
        <small class="p-error">{{ errors.member_id }}</small>
      </div>

      <div class="field">
        <label class="field-label">Loan Type <span class="required">*</span></label>
        <Dropdown v-model="form.loan_type_id"
          :options="loanTypes" optionLabel="label" optionValue="id"
          placeholder="Select loan type" class="w-full"
          :class="{ 'p-invalid': errors.loan_type_id }"
          @change="onLoanTypeChange" />
        <small class="p-error">{{ errors.loan_type_id }}</small>
      </div>
    </div>

    <!-- Amount & Terms -->
    <div class="form-section-title">Loan Terms</div>
    <div class="form-grid">
      <div class="field">
        <label class="field-label">
          Loan Amount (₱) <span class="required">*</span>
          <span v-if="selectedLoanType" class="field-hint">
            {{ formatCurrency(selectedLoanType.min_amount) }} –
            {{ formatCurrency(selectedLoanType.max_amount) }}
          </span>
        </label>
        <InputNumber v-model="form.amount" mode="currency" currency="PHP"
          locale="en-PH" class="w-full" :minFractionDigits="2"
          :min="selectedLoanType?.min_amount || 0"
          :max="selectedLoanType?.max_amount || 9999999"
          :class="{ 'p-invalid': errors.amount }"
          @input="debouncedCalc" />
        <small class="p-error">{{ errors.amount }}</small>
      </div>

      <div class="field">
        <label class="field-label">
          Term (Months) <span class="required">*</span>
          <span v-if="selectedLoanType" class="field-hint">
            {{ selectedLoanType.min_term }}–{{ selectedLoanType.max_term }} mo
          </span>
        </label>
        <InputNumber v-model="form.term_months" class="w-full" suffix=" months"
          :min="selectedLoanType?.min_term || 1"
          :max="selectedLoanType?.max_term || 120"
          :class="{ 'p-invalid': errors.term_months }"
          @input="debouncedCalc" />
        <small class="p-error">{{ errors.term_months }}</small>
      </div>

      <div class="field">
        <label class="field-label">Payment Frequency <span class="required">*</span></label>
        <Dropdown v-model="form.frequency" :options="frequencies"
          optionLabel="label" optionValue="value"
          class="w-full" @change="debouncedCalc" />
      </div>

      <div class="field">
        <label class="field-label">First Due Date</label>
        <Calendar v-model="form.first_due_date" dateFormat="yy-mm-dd"
          class="w-full" :showIcon="true" />
      </div>

      <div class="field">
        <label class="field-label">Application Date</label>
        <Calendar v-model="form.application_date" dateFormat="yy-mm-dd"
          class="w-full" :showIcon="true" />
      </div>

      <div class="field">
        <label class="field-label">Status</label>
        <Dropdown v-model="form.status" :options="loanStatuses"
          class="w-full" />
      </div>
    </div>

    <!-- Live Amortization Summary -->
    <div v-if="calc" class="amort-summary">
      <div class="amort-title">Live Payment Summary</div>
      <div class="amort-grid">
        <div class="amort-item">
          <div class="amort-label">Periods</div>
          <div class="amort-val">{{ calc.n_periods }}</div>
        </div>
        <div class="amort-item">
          <div class="amort-label">First Payment</div>
          <div class="amort-val">{{ formatCurrency(calc.first_payment) }}</div>
        </div>
        <div class="amort-item">
          <div class="amort-label">Last Payment</div>
          <div class="amort-val">{{ formatCurrency(calc.last_payment) }}</div>
        </div>
        <div class="amort-item amort-total">
          <div class="amort-label">Total Payable</div>
          <div class="amort-val">{{ formatCurrency(calc.total_payment) }}</div>
        </div>
        <div class="amort-item">
          <div class="amort-label">Total Interest</div>
          <div class="amort-val">{{ formatCurrency(calc.total_interest) }}</div>
        </div>
      </div>
    </div>

    <!-- Purpose & Co-makers -->
    <div class="form-section-title">Additional Details</div>
    <div class="form-grid">
      <div class="field col-span-2">
        <label class="field-label">Purpose of Loan</label>
        <Textarea v-model="form.purpose" rows="2" class="w-full"
          placeholder="e.g. Home appliance purchase…" autoResize />
      </div>

      <div class="field">
        <label class="field-label">Co-Maker 1</label>
        <Dropdown v-model="form.co_maker_1_id"
          :options="coMakers" optionLabel="full_name_no" optionValue="id"
          filter placeholder="Select co-maker…" class="w-full" showClear />
      </div>

      <div class="field">
        <label class="field-label">Co-Maker 2</label>
        <Dropdown v-model="form.co_maker_2_id"
          :options="coMakers" optionLabel="full_name_no" optionValue="id"
          filter placeholder="Select co-maker…" class="w-full" showClear />
      </div>

      <div class="field col-span-2">
        <label class="field-label">Notes</label>
        <Textarea v-model="form.notes" rows="2" class="w-full" autoResize />
      </div>
    </div>

    <div class="form-actions">
      <Button type="button" label="Cancel" severity="secondary"
        outlined @click="emit('cancel')" />
      <Button type="button" label="Save as Draft" severity="secondary"
        @click="emit('submit', { ...form, status: 'DRAFT' })" :loading="loading" />
      <Button type="submit" label="Submit for Review"
        :loading="loading" icon="pi pi-send" />
    </div>
  </form>
</template>

<script setup>
import { reactive, watch, computed, ref } from 'vue'
import InputText   from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Dropdown    from 'primevue/dropdown'
import Calendar    from 'primevue/calendar'
import Textarea    from 'primevue/textarea'
import Button      from 'primevue/button'
import { useCurrency } from '@/composables/useCurrency'
import { useLoanStore } from '@/stores/loan.store'

const props = defineProps({
  modelValue:  { type: Object,  default: () => ({}) },
  errors:      { type: Object,  default: () => ({}) },
  loading:     { type: Boolean, default: false },
  isEdit:      { type: Boolean, default: false },
  members:     { type: Array,   default: () => [] },
  loanTypes:   { type: Array,   default: () => [] },
})
const emit = defineEmits(['submit', 'cancel'])

const { formatCurrency } = useCurrency()
const loanStore = useLoanStore()
const calc = ref(null)

let calcTimer = null

const frequencies  = [
  { label: 'Monthly',              value: 'monthly' },
  { label: 'Bi-Monthly (15 & 30)', value: 'bimonthly' },
  { label: 'Weekly',               value: 'weekly' },
]
const loanStatuses = ['DRAFT', 'PENDING']

const form = reactive({
  member_id:        null,
  loan_type_id:     null,
  amount:           10000,
  term_months:      12,
  frequency:        'bimonthly',
  purpose:          '',
  co_maker_1_id:    null,
  co_maker_2_id:    null,
  status:           'DRAFT',
  application_date: new Date().toISOString().slice(0, 10),
  first_due_date:   null,
  notes:            '',
})

watch(() => props.modelValue, (val) => {
  if (val && Object.keys(val).length) Object.assign(form, val)
}, { immediate: true, deep: true })

const selectedLoanType = computed(() =>
  props.loanTypes.find(lt => lt.id === form.loan_type_id) || null
)

const coMakers = computed(() =>
  props.members
    .filter(m => m.id !== form.member_id)
    .map(m => ({ ...m, full_name_no: `${m.first_name} ${m.last_name} (${m.member_no})` }))
)

const membersFormatted = computed(() =>
  props.members.map(m => ({ ...m, full_name_no: `${m.first_name} ${m.last_name} (${m.member_no})` }))
)

function onLoanTypeChange() {
  if (!selectedLoanType.value) return
  const lt = selectedLoanType.value
  form.amount      = Math.min(lt.max_amount, Math.max(lt.min_amount, form.amount))
  form.term_months = Math.min(lt.max_term,   Math.max(lt.min_term,   form.term_months))
  debouncedCalc()
}

function debouncedCalc() {
  clearTimeout(calcTimer)
  calcTimer = setTimeout(runCalc, 400)
}

async function runCalc() {
  if (!form.loan_type_id || !form.amount || !form.term_months) return
  try {
    const lt = selectedLoanType.value
    calc.value = await loanStore.calculate({
      amount:      form.amount,
      term_months: form.term_months,
      frequency:   form.frequency,
      annual_rate: lt?.annual_rate || 0.12,
    })
  } catch {}
}
</script>

<style scoped>
.crs-form { display: flex; flex-direction: column; gap: 0; }
.form-section-title {
  font-size: 11px; font-weight: 700; text-transform: uppercase;
  letter-spacing: 0.6px; color: var(--text-color-secondary);
  padding: 16px 0 8px; border-bottom: 1px solid var(--surface-border); margin-bottom: 14px;
}
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 20px; margin-bottom: 4px; }
.col-span-2 { grid-column: span 2; }
.field { display: flex; flex-direction: column; gap: 5px; }
.field-label { font-size: 12px; font-weight: 600; color: var(--text-color-secondary); display: flex; justify-content: space-between; }
.field-hint { font-weight: 400; font-size: 11px; color: var(--primary-color); }
.required { color: var(--red-500); }

.amort-summary {
  background: var(--surface-50); border: 1px solid var(--surface-border);
  border-radius: 8px; padding: 14px 18px; margin: 14px 0;
  border-left: 3px solid var(--primary-color);
}
.amort-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-color-secondary); margin-bottom: 10px; }
.amort-grid  { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; }
.amort-item  { display: flex; flex-direction: column; gap: 2px; }
.amort-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.4px; color: var(--text-color-secondary); }
.amort-val   { font-size: 14px; font-weight: 600; font-family: monospace; }
.amort-total .amort-val { color: var(--primary-color); font-size: 16px; }

.form-actions {
  display: flex; justify-content: flex-end; gap: 10px;
  padding-top: 20px; border-top: 1px solid var(--surface-border); margin-top: 20px;
}
</style>
