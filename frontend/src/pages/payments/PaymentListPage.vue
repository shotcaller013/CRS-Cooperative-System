<template>
  <div class="pay-wrap">
    <header class="topbar">
      <span class="topbar-page">Payments</span>
      <span class="topbar-sep">/</span>
      <span class="topbar-sub">Collection · Receipt recording · Loan balance tracking</span>
      <div class="topbar-right">
        <button class="btn btn-primary btn-sm" @click="openNewPayment">
          + Record payment
        </button>
      </div>
    </header>

    <!-- Filters -->
    <div class="pay-toolbar">
      <div class="search-box">
        <svg class="ico" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input v-model="filters.loan_id" placeholder="Filter by loan ID…" type="number" />
      </div>
      <div class="filter-group">
        <label class="form-label" style="white-space:nowrap">Date from</label>
        <input v-model="filters.date_from" class="form-input filter-date" type="date" />
      </div>
      <div class="filter-group">
        <label class="form-label" style="white-space:nowrap">Date to</label>
        <input v-model="filters.date_to" class="form-input filter-date" type="date" />
      </div>
      <button class="btn btn-secondary btn-sm" @click="fetchData">Apply</button>
      <button class="btn btn-ghost btn-sm" @click="clearFilters">Clear</button>
    </div>

    <!-- Table -->
    <div class="pay-content">
      <div class="card">
        <div v-if="paymentStore.loading" class="loading-state">
          <div class="spinner"></div>
        </div>
        <table v-else class="data-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>O.R. #</th>
              <th>Member</th>
              <th>Loan ID</th>
              <th>Type</th>
              <th class="text-right">Amount paid</th>
              <th class="text-right">Penalty paid</th>
              <th class="text-right">Balance after</th>
              <th>Received by</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!paymentStore.list.length">
              <td colspan="9" class="empty-row">No payments recorded yet.</td>
            </tr>
            <tr v-for="p in paymentStore.list" :key="p.id" class="pay-row">
              <td class="mono" style="font-size:12px">{{ formatDate(p.payment_date) }}</td>
              <td class="mono">{{ p.or_number || '—' }}</td>
              <td>{{ p.member_name || '—' }}</td>
              <td class="mono">{{ p.loan_id }}</td>
              <td><span :class="['badge', typeBadge(p.payment_type)]">{{ p.payment_type }}</span></td>
              <td class="text-right mono">{{ formatCurrency(p.amount_paid) }}</td>
              <td class="text-right mono" :style="p.penalty_paid > 0 ? 'color:var(--red)' : ''">
                {{ p.penalty_paid > 0 ? formatCurrency(p.penalty_paid) : '—' }}
              </td>
              <td class="text-right mono">{{ formatCurrency(p.balance_after) }}</td>
              <td style="font-size:12px;color:var(--ink-muted)">{{ p.received_by || '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Record Payment Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal-box">
        <div class="modal-header">
          <div class="modal-title">Record payment</div>
          <button class="btn btn-ghost btn-sm" @click="showModal = false">✕</button>
        </div>
        <div class="modal-body">
          <!-- Quick loan selector -->
          <div v-if="!selectedLoanId" class="loan-picker">
            <label class="form-label">Loan ID *</label>
            <div style="display:flex;gap:8px">
              <input v-model.number="loanIdInput" class="form-input" type="number" placeholder="Enter loan ID" />
              <button class="btn btn-primary btn-sm" @click="selectLoan" :disabled="!loanIdInput">Load</button>
            </div>
            <span class="field-hint">Enter the loan ID to look up amortization schedules</span>
          </div>

          <div v-else>
            <!-- Schedule picker -->
            <div class="form-group" style="margin-bottom:16px">
              <div class="loan-banner">
                <span class="mono">Loan #{{ selectedLoanId }}</span>
                <button class="btn btn-ghost btn-sm" @click="selectedLoanId = null; selectedScheduleId = null; selectedSchedule = null">Change</button>
              </div>
              <label class="form-label" style="margin-top:12px">Select amortization period *</label>
              <select v-model="selectedScheduleId" class="form-select" @change="onScheduleChange">
                <option value="" disabled>Choose a period…</option>
                <option v-for="s in schedules" :key="s.id" :value="s.id"
                  :disabled="s.status === 'PAID'">
                  Period {{ s.period_no }} · {{ formatDate(s.due_date) }} · {{ formatCurrency(s.amount_due) }} · {{ s.status }}
                </option>
              </select>
            </div>

            <PaymentForm
              v-if="selectedScheduleId"
              :loanId="selectedLoanId"
              :scheduleId="selectedScheduleId"
              :schedule="selectedSchedule"
              :errors="formErrors"
              :saving="paymentStore.saving"
              @submit="recordPayment"
              @cancel="showModal = false"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import PaymentForm          from '@/components/payments/PaymentForm.vue'
import { usePaymentStore }  from '@/stores/payment.store'
import { useCurrency }      from '@/composables/useCurrency'
import { useDate }          from '@/composables/useDate'
import { useToast }         from '@/composables/useToast'
import { request }          from '@/composables/useApi'

const paymentStore       = usePaymentStore()
const { formatCurrency } = useCurrency()
const { formatDate }     = useDate()
const { success, error } = useToast()

const filters = reactive({ loan_id: '', date_from: '', date_to: '' })
const showModal         = ref(false)
const loanIdInput       = ref(null)
const selectedLoanId    = ref(null)
const selectedScheduleId = ref('')
const selectedSchedule  = ref(null)
const schedules         = ref([])
const formErrors        = ref({})

const typeBadge = (t) => ({ full: 'badge-approved', partial: 'badge-pending', advance: 'badge-active', penalty: 'badge-rejected' }[t] || 'badge-draft')

async function fetchData() {
  const params = {}
  if (filters.loan_id)   params.loan_id   = filters.loan_id
  if (filters.date_from) params.date_from = filters.date_from
  if (filters.date_to)   params.date_to   = filters.date_to
  await paymentStore.fetchList(params)
}

function clearFilters() {
  filters.loan_id = ''; filters.date_from = ''; filters.date_to = ''
  fetchData()
}

function openNewPayment() {
  loanIdInput.value = null
  selectedLoanId.value = null
  selectedScheduleId.value = ''
  selectedSchedule.value = null
  schedules.value = []
  formErrors.value = {}
  showModal.value = true
}

async function selectLoan() {
  if (!loanIdInput.value) return
  try {
    const data = await request(`/loans/${loanIdInput.value}/amortization`)
    schedules.value = Array.isArray(data) ? data : (data.data ?? [])
    selectedLoanId.value = loanIdInput.value
  } catch {
    error('Loan not found or no schedule available.')
  }
}

function onScheduleChange() {
  selectedSchedule.value = schedules.value.find(s => s.id === selectedScheduleId.value) || null
}

async function recordPayment(form) {
  formErrors.value = {}
  try {
    await paymentStore.record(form)
    success('Payment recorded!')
    showModal.value = false
    fetchData()
  } catch (e) {
    if (e?.response?.status === 422) formErrors.value = e.response.data.errors || {}
    else error(e?.response?.data?.message || 'Failed to record payment.')
  }
}

onMounted(fetchData)
</script>

<style scoped>
.pay-wrap { display: flex; flex-direction: column; height: 100%; overflow: hidden; }
.topbar {
  display: flex; align-items: center; gap: 8px;
  padding: 14px 24px; border-bottom: 1px solid var(--border);
  background: white; flex-shrink: 0;
}
.topbar-page { font-weight: 600; font-size: 14px; }
.topbar-sep  { color: var(--ink-muted); }
.topbar-sub  { font-size: 12px; color: var(--ink-muted); flex: 1; }
.topbar-right { margin-left: auto; }

.pay-toolbar {
  display: flex; gap: 10px; align-items: flex-end; padding: 14px 24px;
  background: white; border-bottom: 1px solid var(--border); flex-shrink: 0;
}
.search-box {
  display: flex; align-items: center; gap: 6px;
  background: var(--surface-2); border: 1.5px solid var(--border);
  border-radius: 7px; padding: 6px 12px; flex: 0 0 200px;
}
.search-box input { border: none; background: transparent; outline: none; font-size: 13px; width: 100%; }
.ico { width: 15px; height: 15px; stroke: var(--ink-muted); fill: none; stroke-width: 2; stroke-linecap: round; }
.filter-group { display: flex; align-items: center; gap: 6px; }
.filter-date { width: 140px; }

.pay-content { flex: 1; overflow-y: auto; padding: 20px 24px; }
.loading-state { display: flex; justify-content: center; padding: 50px; }
.spinner { width: 28px; height: 28px; border: 3px solid var(--border); border-top-color: var(--crs-red); border-radius: 50%; animation: spin 0.7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.empty-row { text-align: center; padding: 40px; color: var(--ink-muted); font-size: 13px; }
.text-right { text-align: right; }
.pay-row:hover td { background: var(--surface); }

/* Modal */
.modal-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,0.45);
  display: flex; align-items: center; justify-content: center; z-index: 200;
}
.modal-box {
  background: white; border-radius: 12px; width: 680px; max-width: 96vw;
  max-height: 90vh; display: flex; flex-direction: column;
  box-shadow: 0 20px 60px rgba(0,0,0,0.25);
}
.modal-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 18px 22px; border-bottom: 1px solid var(--border);
}
.modal-title { font-size: 16px; font-weight: 600; }
.modal-body  { padding: 22px; overflow-y: auto; }

.loan-picker { display: flex; flex-direction: column; gap: 8px; }
.loan-banner {
  display: flex; align-items: center; justify-content: space-between;
  padding: 8px 14px; background: var(--blue-pale); border-radius: 7px;
  font-size: 13px;
}
.field-hint { font-size: 11px; color: var(--ink-muted); display: block; margin-top: 3px; }
</style>
