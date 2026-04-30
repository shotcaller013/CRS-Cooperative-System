<template>
  <div class="filter-bar">

    <!-- Date mode tabs -->
    <div class="filter-group">
      <label class="filter-label">Period</label>
      <div class="date-mode-tabs">
        <button v-for="m in availableModes" :key="m.value"
          :class="['mode-btn', dateMode === m.value && 'mode-btn-active']"
          @click="dateMode = m.value">{{ m.label }}</button>
      </div>
    </div>

    <!-- Date inputs -->
    <template v-if="dateMode === 'range'">
      <div class="filter-group">
        <label class="filter-label">From</label>
        <input v-model="f.date_from" type="date" class="form-input filter-input" />
      </div>
      <div class="filter-group">
        <label class="filter-label">To</label>
        <input v-model="f.date_to" type="date" class="form-input filter-input" />
      </div>
    </template>
    <template v-if="dateMode === 'month'">
      <div class="filter-group">
        <label class="filter-label">Month</label>
        <input v-model="f.month" type="month" class="form-input filter-input" />
      </div>
    </template>
    <template v-if="dateMode === 'as_of'">
      <div class="filter-group">
        <label class="filter-label">As of date</label>
        <input v-model="f.as_of_date" type="date" class="form-input filter-input" />
      </div>
    </template>
    <template v-if="dateMode === 'fiscal'">
      <div class="filter-group">
        <label class="filter-label">Fiscal year</label>
        <select v-model.number="f.fiscal_year" class="form-select filter-input">
          <option v-for="y in fiscalYears" :key="y" :value="y">{{ y }}</option>
        </select>
      </div>
    </template>

    <!-- Company -->
    <div class="filter-group">
      <label class="filter-label">Company</label>
      <select v-model="f.company" class="form-select filter-input">
        <option value="">All companies</option>
        <option v-for="c in companies" :key="c" :value="c">{{ c }}</option>
      </select>
    </div>

    <!-- Department -->
    <div class="filter-group">
      <label class="filter-label">Department</label>
      <select v-model="f.department" class="form-select filter-input">
        <option value="">All departments</option>
        <option v-for="d in departments" :key="d" :value="d">{{ d }}</option>
      </select>
    </div>

    <!-- Loan type -->
    <div class="filter-group" v-if="showLoanType">
      <label class="filter-label">Loan type</label>
      <select v-model.number="f.loan_type_id" class="form-select filter-input">
        <option value="">All types</option>
        <option v-for="lt in loanTypes" :key="lt.id" :value="lt.id">{{ lt.label }}</option>
      </select>
    </div>

    <!-- Status (outstanding only) -->
    <div class="filter-group" v-if="showStatus">
      <label class="filter-label">Status</label>
      <select v-model="f.status" class="form-select filter-input">
        <option value="">All</option>
        <option value="ACTIVE">ACTIVE</option>
        <option value="APPROVED">APPROVED</option>
      </select>
    </div>

    <!-- Actions -->
    <div class="filter-actions">
      <button class="btn btn-primary btn-sm" :disabled="loading" @click="emit('run', buildParams())">
        {{ loading ? 'Loading…' : 'Run report' }}
      </button>
      <button class="btn btn-secondary btn-sm" :disabled="exporting" @click="emit('export', { params: buildParams(), format: 'excel' })"
        title="Export Excel">XLS</button>
      <button class="btn btn-secondary btn-sm" :disabled="exporting" @click="emit('export', { params: buildParams(), format: 'pdf' })"
        title="Export PDF">PDF</button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { request } from '@/composables/useApi'
import { settingService } from '@/services/setting.service'

const props = defineProps({
  initialDateMode: { type: String, default: 'month' },
  showLoanType:    { type: Boolean, default: true },
  showStatus:      { type: Boolean, default: false },
  loading:         { type: Boolean, default: false },
  exporting:       { type: Boolean, default: false },
})
const emit = defineEmits(['run', 'export'])

const dateMode    = ref(props.initialDateMode)
const companies   = ref([])
const departments = ref([])
const loanTypes   = ref([])
const fiscalYears = Array.from({ length: 10 }, (_, i) => new Date().getFullYear() - i)

const allModes = [
  { label: 'Month',       value: 'month'  },
  { label: 'Date range',  value: 'range'  },
  { label: 'As of',       value: 'as_of'  },
  { label: 'Fiscal year', value: 'fiscal' },
]
const availableModes = computed(() => allModes)

const today = new Date().toISOString().slice(0, 10)
const thisMonth = new Date().toISOString().slice(0, 7)

const f = reactive({
  date_from: '', date_to: '',
  month: thisMonth,
  as_of_date: today,
  fiscal_year: new Date().getFullYear(),
  company: '', department: '',
  loan_type_id: '', status: '',
})

function buildParams() {
  const p = {}
  if (dateMode.value === 'range') {
    if (f.date_from) p.date_from = f.date_from
    if (f.date_to)   p.date_to   = f.date_to
  } else if (dateMode.value === 'month') {
    if (f.month) p.month = f.month
  } else if (dateMode.value === 'as_of') {
    p.as_of_date = f.as_of_date || today
  } else if (dateMode.value === 'fiscal') {
    p.fiscal_year = f.fiscal_year
  }
  if (f.company)      p.company      = f.company
  if (f.department)   p.department   = f.department
  if (f.loan_type_id) p.loan_type_id = f.loan_type_id
  if (f.status)       p.status       = f.status
  return p
}

onMounted(async () => {
  try {
    const lt = await settingService.getLoanTypes()
    loanTypes.value = Array.isArray(lt) ? lt : (lt.data ?? [])
  } catch {}
  try {
    const depts = await request('/members/departments')
    departments.value = Array.isArray(depts) ? depts : (depts.data ?? [])
  } catch {}
})
</script>

<style scoped>
.filter-bar {
  display: flex; align-items: flex-end; gap: 10px; flex-wrap: wrap;
  padding: 12px 20px; border-bottom: 1px solid var(--border);
  background: white; flex-shrink: 0;
}
.filter-group { display: flex; flex-direction: column; gap: 4px; }
.filter-label {
  font-size: 10px; font-weight: 600; text-transform: uppercase;
  letter-spacing: .5px; color: var(--ink-muted);
}
.filter-input { width: 145px; }
.date-mode-tabs {
  display: flex; border: 1px solid var(--border);
  border-radius: 6px; overflow: hidden;
}
.mode-btn {
  padding: 5px 9px; font-size: 12px; border: none; background: transparent;
  cursor: pointer; color: var(--ink-muted); transition: all .15s;
}
.mode-btn-active { background: var(--crs-red); color: #fff; }
.filter-actions { display: flex; gap: 6px; align-items: center; margin-left: auto; }
</style>
