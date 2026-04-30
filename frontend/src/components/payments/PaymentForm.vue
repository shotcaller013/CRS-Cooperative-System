<template>
  <form @submit.prevent="emit('submit', form)">
    <!-- Schedule preview banner -->
    <div v-if="schedule" class="schedule-preview">
      <div class="sp-grid">
        <div class="sp-item">
          <div class="sp-label">Period</div>
          <div class="sp-val mono">{{ String(schedule.period_no).padStart(2,'0') }}</div>
        </div>
        <div class="sp-item">
          <div class="sp-label">Due date</div>
          <div class="sp-val">{{ formatDate(schedule.due_date) }}</div>
        </div>
        <div class="sp-item">
          <div class="sp-label">Amount due</div>
          <div class="sp-val mono">{{ formatCurrency(schedule.amount_due) }}</div>
        </div>
        <div class="sp-item">
          <div class="sp-label">Already paid</div>
          <div class="sp-val mono">{{ formatCurrency(schedule.paid_amount) }}</div>
        </div>
        <div class="sp-item">
          <div class="sp-label">Penalty</div>
          <div class="sp-val mono" style="color:var(--red)">{{ formatCurrency(schedule.penalty_amount || 0) }}</div>
        </div>
        <div class="sp-item sp-balance">
          <div class="sp-label">Balance due</div>
          <div class="sp-val mono">{{ formatCurrency(balanceDue) }}</div>
        </div>
      </div>
      <button type="button" class="btn btn-ghost btn-sm pay-full-btn" @click="payFull">
        ↳ Pay full balance
      </button>
    </div>

    <div class="form-2col">
      <div class="form-group">
        <label class="form-label">Amount paid (₱) *</label>
        <input v-model.number="form.amount_paid" class="form-input" :class="{ 'input-error': errors.amount_paid }"
          type="number" step="0.01" min="0.01" />
        <span v-if="errors.amount_paid" class="err-msg">{{ Array.isArray(errors.amount_paid) ? errors.amount_paid[0] : errors.amount_paid }}</span>
      </div>
      <div class="form-group">
        <label class="form-label">Payment type *</label>
        <select v-model="form.payment_type" class="form-select">
          <option value="full">Full payment</option>
          <option value="partial">Partial payment</option>
          <option value="advance">Advance payment</option>
          <option value="penalty">Penalty only</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">O.R. number</label>
        <input v-model="form.or_number" class="form-input" placeholder="Official receipt number" />
      </div>
      <div class="form-group">
        <label class="form-label">Payment date *</label>
        <input v-model="form.payment_date" class="form-input" type="date" :max="today" />
      </div>
      <div v-if="schedule?.penalty_amount > 0" class="form-group">
        <label class="form-label">Penalty paid (₱)</label>
        <input v-model.number="form.penalty_paid" class="form-input" type="number" step="0.01" min="0" />
      </div>
      <div class="form-group" style="grid-column:span 2">
        <label class="form-label">Notes</label>
        <textarea v-model="form.notes" class="form-textarea" rows="2"></textarea>
      </div>
    </div>

    <div class="form-footer">
      <button type="button" class="btn btn-secondary" @click="emit('cancel')">Cancel</button>
      <button type="submit" class="btn btn-primary" :disabled="saving">
        {{ saving ? 'Saving…' : 'Record payment' }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { reactive, computed } from 'vue'
import { useCurrency } from '@/composables/useCurrency'
import { useDate }     from '@/composables/useDate'

const props = defineProps({
  loanId:     { type: Number, required: true },
  scheduleId: { type: Number, required: true },
  schedule:   { type: Object, default: null },
  errors:     { type: Object, default: () => ({}) },
  saving:     { type: Boolean, default: false },
})
const emit = defineEmits(['submit', 'cancel'])

const { formatCurrency } = useCurrency()
const { formatDate }     = useDate()

const today = new Date().toISOString().slice(0, 10)

const form = reactive({
  loan_id:      props.loanId,
  schedule_id:  props.scheduleId,
  amount_paid:  null,
  payment_type: 'full',
  or_number:    '',
  payment_date: today,
  penalty_paid: 0,
  notes:        '',
})

const balanceDue = computed(() => {
  if (!props.schedule) return 0
  return Math.max(0,
    (parseFloat(props.schedule.amount_due)  - parseFloat(props.schedule.paid_amount)) +
     parseFloat(props.schedule.penalty_amount || 0)
  )
})

function payFull() {
  form.amount_paid  = +(parseFloat(props.schedule?.amount_due || 0) - parseFloat(props.schedule?.paid_amount || 0)).toFixed(2)
  form.penalty_paid = +(parseFloat(props.schedule?.penalty_amount || 0)).toFixed(2)
  form.payment_type = 'full'
}
</script>

<style scoped>
.schedule-preview {
  background: var(--blue-pale); border-radius: 8px;
  padding: 12px 16px; margin-bottom: 18px;
  border-left: 3px solid var(--blue);
}
.sp-grid   { display: grid; grid-template-columns: repeat(6, 1fr); gap: 8px; }
.sp-item   { display: flex; flex-direction: column; gap: 2px; }
.sp-balance .sp-val { color: var(--crs-red); font-weight: 700; }
.sp-label  { font-size: 10px; text-transform: uppercase; letter-spacing: 0.4px; color: var(--ink-muted); }
.sp-val    { font-size: 13px; font-weight: 500; }
.pay-full-btn { margin-top: 10px; color: var(--blue); font-size: 12px; }
.form-2col  { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 20px; }
.form-footer {
  display: flex; justify-content: flex-end; gap: 10px;
  padding-top: 18px; border-top: 1px solid var(--border); margin-top: 14px;
}
.input-error { border-color: var(--red) !important; }
.err-msg { font-size: 11px; color: var(--red); display: block; margin-top: 3px; }
</style>
