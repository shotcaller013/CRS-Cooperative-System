<template>
  <form @submit.prevent="handleSubmit" style="display:flex;flex-direction:column;gap:0">

    <div class="form-2col">
      <div class="form-group">
        <label class="form-label">Type code *</label>
        <input v-model="form.code" class="form-input" :class="{ 'input-error': errors.code }"
          placeholder="e.g. commodity" :disabled="isEdit" />
        <span v-if="errors.code" class="err-msg">{{ errors.code }}</span>
      </div>
      <div class="form-group">
        <label class="form-label">Label *</label>
        <input v-model="form.label" class="form-input" :class="{ 'input-error': errors.label }"
          placeholder="e.g. Commodity Loan" />
      </div>

      <div class="form-group" style="grid-column:span 2">
        <label class="form-label">Amount cap method *</label>
        <div class="radio-row">
          <label v-for="m in capMethods" :key="m.value" class="radio-opt">
            <input type="radio" v-model="form.amount_cap_method" :value="m.value" />
            {{ m.label }}
          </label>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Min amount (₱)</label>
        <input v-model.number="form.min_amount" class="form-input" type="number" min="0" />
      </div>
      <div class="form-group">
        <label class="form-label">Max amount (₱) <span class="hint-inline">(fixed ceiling)</span></label>
        <input v-model.number="form.max_amount" class="form-input" type="number" min="0" />
      </div>
      <div v-if="form.amount_cap_method !== 'fixed'" class="form-group">
        <label class="form-label">Salary multiplier</label>
        <input v-model.number="form.salary_multiplier" class="form-input" type="number" step="0.1" min="0" placeholder="e.g. 3.0" />
        <span class="field-hint">Max loanable = monthly salary × this value</span>
      </div>

      <div class="form-group">
        <label class="form-label">Min term (months)</label>
        <input v-model.number="form.min_term" class="form-input" type="number" min="1" />
      </div>
      <div class="form-group">
        <label class="form-label">Max term (months)</label>
        <input v-model.number="form.max_term" class="form-input" type="number" min="1" />
      </div>
    </div>

    <div class="sub-section">Interest rate band</div>
    <div class="form-2col">
      <div class="form-group">
        <label class="form-label">Min rate (%)</label>
        <input v-model.number="form.annual_rate_min_pct" class="form-input" type="number" step="0.1" min="0" max="100" />
      </div>
      <div class="form-group">
        <label class="form-label">Default rate (%) *</label>
        <input v-model.number="form.annual_rate_default_pct" class="form-input" type="number" step="0.1" min="0" max="100" />
        <span class="field-hint">Officer can override between min and max</span>
      </div>
      <div class="form-group">
        <label class="form-label">Max rate (%)</label>
        <input v-model.number="form.annual_rate_max_pct" class="form-input" type="number" step="0.1" min="0" max="100" />
      </div>
      <div class="form-group">
        <label class="form-label">Penalty rate (% per month)</label>
        <input v-model.number="form.penalty_rate_pct" class="form-input" type="number" step="0.1" min="0" max="100" />
        <span class="field-hint">Applied to overdue unpaid balance</span>
      </div>
    </div>

    <div class="sub-section">Member qualifications</div>
    <div class="form-2col">
      <div class="form-group" style="grid-column:span 2">
        <label class="form-label">Eligible employment statuses</label>
        <div class="check-row">
          <label v-for="s in empStatuses" :key="s" class="check-opt">
            <input type="checkbox" :value="s" v-model="form.allowed_emp_statuses" />
            {{ s }}
          </label>
        </div>
        <span class="field-hint">Leave all unchecked to allow any status</span>
      </div>
      <div class="form-group">
        <label class="form-label">Min share capital (₱)</label>
        <input v-model.number="form.min_share_capital" class="form-input" type="number" min="0" />
      </div>
      <div class="form-group">
        <label class="form-label">Min tenure (months)</label>
        <input v-model.number="form.min_tenure_months" class="form-input" type="number" min="0" />
      </div>
      <div class="form-group">
        <label class="form-label">Allow concurrent active loans</label>
        <select v-model="form.allow_concurrent" class="form-select">
          <option :value="false">Not allowed</option>
          <option :value="true">Allowed</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Status</label>
        <select v-model="form.is_active" class="form-select">
          <option :value="true">Active</option>
          <option :value="false">Inactive</option>
        </select>
      </div>
    </div>

    <div class="sub-section">
      Approval levels
      <button type="button" class="btn btn-ghost btn-sm" @click="addThreshold" style="margin-left:10px">+ Add level</button>
    </div>
    <p class="field-hint" style="margin-bottom:10px">Define who must approve based on loan amount. Leave empty for single manager approval.</p>
    <div v-for="(t, i) in form.thresholds" :key="i" class="threshold-row">
      <div class="form-group" style="flex:1">
        <label class="form-label">Level</label>
        <select v-model="t.level" class="form-select">
          <option value="manager">Manager</option>
          <option value="board">Board</option>
        </select>
      </div>
      <div class="form-group" style="flex:1">
        <label class="form-label">Approver role</label>
        <select v-model="t.approver_role" class="form-select">
          <option value="loan-officer">Loan Officer</option>
          <option value="manager">Manager</option>
          <option value="board">Board</option>
        </select>
      </div>
      <div class="form-group" style="width:120px">
        <label class="form-label">From (₱)</label>
        <input v-model.number="t.amount_from" class="form-input" type="number" min="0" />
      </div>
      <div class="form-group" style="width:120px">
        <label class="form-label">To (₱)</label>
        <input v-model.number="t.amount_to" class="form-input" type="number" min="0" placeholder="No limit" />
      </div>
      <div class="form-group" style="width:80px">
        <label class="form-label">Seq.</label>
        <input v-model.number="t.sequence" class="form-input" type="number" min="1" />
      </div>
      <button type="button" class="btn btn-ghost btn-sm" style="align-self:flex-end;margin-bottom:2px;color:var(--red)"
        @click="removeThreshold(i)">✕</button>
    </div>

    <div class="form-footer">
      <button type="button" class="btn btn-secondary" @click="emit('cancel')">Cancel</button>
      <button type="submit" class="btn btn-primary" :disabled="saving">
        {{ saving ? 'Saving…' : (isEdit ? 'Update loan type' : 'Save loan type') }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { reactive, watch } from 'vue'

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
  errors:     { type: Object, default: () => ({}) },
  saving:     { type: Boolean, default: false },
  isEdit:     { type: Boolean, default: false },
})
const emit = defineEmits(['submit', 'cancel'])

const capMethods  = [
  { label: 'Fixed peso limits', value: 'fixed' },
  { label: 'Salary multiplier', value: 'salary_multiplier' },
  { label: 'Lower of both',     value: 'both' },
]
const empStatuses = ['REGULAR', 'PROBI', 'SUSPENDED', 'INACTIVE']

const form = reactive({
  code: '', label: '',
  min_amount: 5000, max_amount: 50000,
  amount_cap_method: 'fixed', salary_multiplier: null,
  min_term: 3, max_term: 24,
  annual_rate_default_pct: 12, annual_rate_min_pct: 10, annual_rate_max_pct: 14,
  allowed_emp_statuses: ['REGULAR'],
  min_share_capital: 0, min_tenure_months: 0,
  allow_concurrent: false, penalty_rate_pct: 2,
  is_active: true, thresholds: [],
})

watch(() => props.modelValue, (val) => {
  if (!val || !Object.keys(val).length) return
  Object.assign(form, {
    ...val,
    annual_rate_default_pct: val.annual_rate_default != null ? +(val.annual_rate_default * 100).toFixed(2) : 12,
    annual_rate_min_pct:     val.annual_rate_min    != null ? +(val.annual_rate_min    * 100).toFixed(2) : 10,
    annual_rate_max_pct:     val.annual_rate_max    != null ? +(val.annual_rate_max    * 100).toFixed(2) : 14,
    penalty_rate_pct:        val.penalty_rate       != null ? +(val.penalty_rate       * 100).toFixed(2) : 2,
    thresholds: val.thresholds ? JSON.parse(JSON.stringify(val.thresholds)) : [],
  })
}, { immediate: true, deep: true })

function addThreshold() {
  form.thresholds.push({ level: 'manager', approver_role: 'manager', amount_from: 0, amount_to: null, sequence: form.thresholds.length + 1 })
}
function removeThreshold(i) { form.thresholds.splice(i, 1) }

function handleSubmit() {
  emit('submit', {
    ...form,
    annual_rate_default: form.annual_rate_default_pct / 100,
    annual_rate_min:     form.annual_rate_min_pct / 100,
    annual_rate_max:     form.annual_rate_max_pct / 100,
    penalty_rate:        form.penalty_rate_pct / 100,
  })
}
</script>

<style scoped>
.form-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 20px; margin-bottom: 4px; }
.sub-section {
  font-size: 10.5px; font-weight: 700; text-transform: uppercase;
  letter-spacing: 0.5px; color: var(--ink-muted);
  padding: 16px 0 8px; border-bottom: 1px solid var(--border);
  margin: 4px 0 12px; display: flex; align-items: center;
}
.field-hint { font-size: 11px; color: var(--ink-muted); display: block; margin-top: 3px; }
.hint-inline { font-size: 10px; font-weight: 400; color: var(--ink-muted); }
.radio-row, .check-row { display: flex; gap: 20px; flex-wrap: wrap; padding: 6px 0; }
.radio-opt, .check-opt {
  display: flex; align-items: center; gap: 6px;
  cursor: pointer; font-size: 13.5px; color: var(--ink);
}
.threshold-row {
  display: flex; gap: 10px; align-items: flex-start;
  background: var(--surface); border-radius: 8px;
  padding: 12px 14px; margin-bottom: 8px; border: 1px solid var(--border);
}
.form-footer {
  display: flex; justify-content: flex-end; gap: 10px;
  padding-top: 18px; border-top: 1px solid var(--border); margin-top: 14px;
}
.input-error { border-color: var(--red) !important; }
.err-msg { font-size: 11px; color: var(--red); margin-top: 3px; display: block; }
</style>
