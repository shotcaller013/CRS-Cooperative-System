<template>
  <div class="page-wrap">
    <!-- Header -->
    <div class="page-header">
      <Button icon="pi pi-arrow-left" text @click="$router.push('/loans')" />
      <div class="header-main" v-if="loan">
        <div>
          <h1 class="page-title font-mono">{{ loan.loan_no }}</h1>
          <p class="page-sub">
            {{ loan.member?.first_name }} {{ loan.member?.last_name }} ·
            {{ loan.loan_type?.label }}
          </p>
        </div>
        <StatusBadge :status="loan.status" class="status-large" />
      </div>
      <div class="header-actions" v-if="loan">
        <Button v-if="loan.status === 'PENDING'"
          label="Approve Loan" icon="pi pi-check-circle"
          severity="success" @click="confirmApprove" />
        <Button v-if="['DRAFT','PENDING'].includes(loan.status)"
          label="Edit" icon="pi pi-pencil" outlined
          @click="$router.push(`/loans/${loan.id}/edit`)" />
        <Button v-if="loan.status !== 'CLOSED'"
          label="Delete" icon="pi pi-trash"
          severity="danger" outlined @click="confirmDelete" />
      </div>
    </div>

    <div v-if="loanStore.loading" class="loading-state">
      <ProgressSpinner />
    </div>

    <template v-else-if="loan">
      <TabView class="detail-tabs">

        <!-- ── Loan Details ── -->
        <TabPanel header="Loan Details">
          <div class="two-col-layout">
            <div class="detail-section">
              <div class="section-title">Member Information</div>
              <div class="info-grid">
                <InfoField label="Member #"       :value="loan.member?.member_no" />
                <InfoField label="Full Name"      :value="`${loan.member?.first_name} ${loan.member?.last_name}`" />
                <InfoField label="Company"        :value="loan.member?.company" />
                <InfoField label="Position"       :value="loan.member?.position" />
                <InfoField label="Contact"        :value="loan.member?.contact" />
                <InfoField label="Email"          :value="loan.member?.email" />
                <InfoField label="Monthly Salary" :value="formatCurrency(loan.member?.monthly_salary)" mono />
              </div>
            </div>

            <div class="detail-section">
              <div class="section-title">Loan Details</div>
              <div class="info-grid">
                <InfoField label="Loan Type"   :value="loan.loan_type?.label" />
                <InfoField label="Amount"      :value="formatCurrency(loan.amount)" mono />
                <InfoField label="Term"        :value="`${loan.term_months} months`" />
                <InfoField label="Frequency"   :value="freqLabel(loan.frequency)" />
                <InfoField label="Annual Rate" :value="`${(loan.annual_rate * 100).toFixed(0)}%`" />
                <InfoField label="Purpose"     :value="loan.purpose" />
                <InfoField label="Application Date" :value="formatDate(loan.application_date)" />
                <InfoField label="First Due Date"   :value="formatDate(loan.first_due_date)" />
                <InfoField label="End Date"         :value="formatDate(loan.end_date)" />
              </div>
            </div>
          </div>

          <!-- Payment Summary -->
          <div class="summary-bar">
            <div class="summary-item">
              <div class="summary-label">Principal</div>
              <div class="summary-val">{{ formatCurrency(loan.amount) }}</div>
            </div>
            <div class="summary-sep">+</div>
            <div class="summary-item">
              <div class="summary-label">Total Interest</div>
              <div class="summary-val">{{ formatCurrency(loan.total_interest) }}</div>
            </div>
            <div class="summary-sep">=</div>
            <div class="summary-item summary-total">
              <div class="summary-label">Total Payable</div>
              <div class="summary-val">{{ formatCurrency(loan.total_payment) }}</div>
            </div>
            <div class="summary-divider" />
            <div class="summary-item">
              <div class="summary-label">Periods</div>
              <div class="summary-val font-mono">{{ loan.n_periods }}</div>
            </div>
            <div class="summary-item">
              <div class="summary-label">First Payment</div>
              <div class="summary-val">{{ formatCurrency(loan.first_payment_amt) }}</div>
            </div>
            <div class="summary-item">
              <div class="summary-label">Last Payment</div>
              <div class="summary-val">{{ formatCurrency(loan.last_payment_amt) }}</div>
            </div>
          </div>

          <!-- Co-makers & Approval -->
          <div class="two-col-layout mt-16">
            <div class="detail-section" v-if="loan.co_maker_1 || loan.co_maker_2">
              <div class="section-title">Co-Makers</div>
              <div class="info-grid">
                <InfoField v-if="loan.co_maker_1"
                  label="Co-Maker 1"
                  :value="`${loan.co_maker_1.first_name} ${loan.co_maker_1.last_name} (${loan.co_maker_1.member_no})`" />
                <InfoField v-if="loan.co_maker_2"
                  label="Co-Maker 2"
                  :value="`${loan.co_maker_2.first_name} ${loan.co_maker_2.last_name} (${loan.co_maker_2.member_no})`" />
              </div>
            </div>

            <div class="detail-section" v-if="loan.approval_date">
              <div class="section-title">Approval</div>
              <div class="info-grid">
                <InfoField label="Approval Date"    :value="formatDate(loan.approval_date)" />
                <InfoField label="Approved by HR"   :value="loan.approved_by_hr" />
                <InfoField label="Approved by COOP" :value="loan.approved_by_coop" />
              </div>
            </div>
          </div>

          <!-- Signed copy -->
          <div class="signed-card" v-if="loan.signed_form_url">
            <i class="pi pi-paperclip"></i>
            <div class="signed-info">
              <div class="signed-title">Signed Loan Form Attached</div>
              <div class="signed-sub">{{ loan.signed_form_url }}</div>
            </div>
            <Button label="View" icon="pi pi-external-link" text size="small"
              :href="loan.signed_form_url" target="_blank" as="a" />
          </div>
        </TabPanel>

        <!-- ── Amortization Schedule ── -->
        <TabPanel header="Amortization Schedule">
          <div class="schedule-toolbar">
            <div class="text-color-secondary text-sm">
              {{ loan.n_periods }} periods ·
              {{ freqLabel(loan.frequency) }} ·
              Rate {{ (loan.annual_rate * 100).toFixed(0) }}% p.a.
            </div>
            <Button label="Print Schedule" icon="pi pi-print" size="small"
              outlined severity="secondary" @click="printSchedule" />
          </div>

          <DataTable :value="loan.amortization_schedules || []"
            class="crs-table" stripedRows scrollable scrollHeight="calc(100vh - 300px)">
            <Column field="period_no" header="#" style="width:60px">
              <template #body="{ data }">
                <span class="font-mono text-sm">{{ String(data.period_no).padStart(2,'0') }}</span>
              </template>
            </Column>
            <Column field="due_date" header="Due Date" style="width:130px">
              <template #body="{ data }">{{ formatDate(data.due_date) }}</template>
            </Column>
            <Column field="principal" header="Principal" style="width:130px">
              <template #body="{ data }">
                <span class="font-mono text-sm">{{ formatCurrency(data.principal) }}</span>
              </template>
            </Column>
            <Column field="interest" header="Interest" style="width:130px">
              <template #body="{ data }">
                <span class="font-mono text-sm">{{ formatCurrency(data.interest) }}</span>
              </template>
            </Column>
            <Column field="amount_due" header="Amount Due" style="width:140px">
              <template #body="{ data }">
                <span class="font-mono font-semibold">{{ formatCurrency(data.amount_due) }}</span>
              </template>
            </Column>
            <Column field="balance" header="Balance" style="width:140px">
              <template #body="{ data }">
                <span class="font-mono text-sm">{{ formatCurrency(data.balance) }}</span>
              </template>
            </Column>
            <Column field="status" header="Status" style="width:110px">
              <template #body="{ data }">
                <StatusBadge :status="data.status" />
              </template>
            </Column>
            <Column field="paid_amount" header="Paid" style="width:120px">
              <template #body="{ data }">
                <span v-if="data.paid_amount > 0" class="font-mono text-sm text-green-600">
                  {{ formatCurrency(data.paid_amount) }}
                </span>
                <span v-else class="text-color-secondary">—</span>
              </template>
            </Column>
            <Column field="or_number" header="O.R. #" style="width:110px">
              <template #body="{ data }">
                <span class="font-mono text-sm">{{ data.or_number || '—' }}</span>
              </template>
            </Column>

            <template #footer>
              <div class="schedule-footer">
                <span class="font-semibold">TOTALS</span>
                <span></span>
                <span class="font-mono font-semibold">{{ formatCurrency(loan.amount) }}</span>
                <span class="font-mono font-semibold">{{ formatCurrency(loan.total_interest) }}</span>
                <span class="font-mono font-bold text-primary">{{ formatCurrency(loan.total_payment) }}</span>
              </div>
            </template>
          </DataTable>
        </TabPanel>

      </TabView>
    </template>

    <ConfirmDialog />
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useConfirm } from 'primevue/useconfirm'
import Button          from 'primevue/button'
import TabView         from 'primevue/tabview'
import TabPanel        from 'primevue/tabpanel'
import DataTable       from 'primevue/datatable'
import Column          from 'primevue/column'
import ProgressSpinner from 'primevue/progressspinner'
import ConfirmDialog   from 'primevue/confirmdialog'
import StatusBadge from '@/components/common/StatusBadge.vue'
import InfoField   from '@/components/common/InfoField.vue'
import { useLoanStore }  from '@/stores/loan.store'
import { useCurrency }   from '@/composables/useCurrency'
import { useDate }       from '@/composables/useDate'
import { useToast }      from '@/composables/useToast'

const route     = useRoute()
const router    = useRouter()
const confirm   = useConfirm()
const loanStore = useLoanStore()
const { formatCurrency }   = useCurrency()
const { formatDate }       = useDate()
const { success, error }   = useToast()

const loan = computed(() => loanStore.selected)

const freqLabel = (f) =>
  ({ monthly: 'Monthly', bimonthly: 'Bi-Monthly', weekly: 'Weekly' })[f] || f

function confirmApprove() {
  confirm.require({
    message: `Approve loan ${loan.value.loan_no}?`,
    header: 'Confirm Approval', icon: 'pi pi-check-circle', acceptLabel: 'Approve',
    accept: async () => {
      try {
        await loanStore.approve(loan.value.id)
        success(`Loan ${loan.value.loan_no} approved!`)
        await loanStore.fetchOne(route.params.id)
      } catch (e) { error(e?.response?.data?.message || 'Approval failed.') }
    },
  })
}

function confirmDelete() {
  confirm.require({
    message: `Delete loan ${loan.value.loan_no}? This cannot be undone.`,
    header: 'Confirm Delete', icon: 'pi pi-exclamation-triangle', acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await loanStore.remove(loan.value.id)
        success('Loan deleted.')
        router.push('/loans')
      } catch (e) { error(e?.response?.data?.message || 'Delete failed.') }
    },
  })
}

function printSchedule() {
  window.print()
}

onMounted(() => loanStore.fetchOne(route.params.id))
</script>

<style scoped>
.page-wrap    { display: flex; flex-direction: column; height: 100%; overflow: hidden; }
.page-header  {
  display: flex; align-items: center; gap: 12px;
  padding: 18px 24px; border-bottom: 1px solid var(--surface-border); flex-shrink: 0;
}
.header-main  { display: flex; align-items: center; gap: 14px; flex: 1; }
.page-title   { font-size: 20px; font-weight: 700; margin: 0; }
.page-sub     { font-size: 12px; color: var(--text-color-secondary); margin: 2px 0 0; }
.header-actions { display: flex; gap: 8px; }
.status-large :deep(.p-tag) { font-size: 12px; padding: 4px 12px; }
.loading-state { display: flex; justify-content: center; padding: 60px; }

.detail-tabs { flex: 1; overflow: hidden; }
:deep(.p-tabview-panels) { overflow-y: auto; height: calc(100% - 46px); padding: 20px 24px; }

.two-col-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
.mt-16 { margin-top: 16px; }

.detail-section { background: var(--surface-card); border: 1px solid var(--surface-border); border-radius: 8px; padding: 18px 20px; }
.section-title  { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-color-secondary); margin-bottom: 14px; }
.info-grid      { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

.summary-bar {
  display: flex; align-items: center; gap: 20px;
  background: var(--surface-50); border: 1px solid var(--surface-border);
  border-left: 4px solid var(--primary-color);
  border-radius: 8px; padding: 16px 24px; margin: 20px 0;
}
.summary-item  { display: flex; flex-direction: column; gap: 2px; }
.summary-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-color-secondary); }
.summary-val   { font-size: 16px; font-weight: 600; font-family: monospace; }
.summary-sep   { font-size: 20px; color: var(--text-color-secondary); font-weight: 300; }
.summary-total .summary-val { font-size: 20px; color: var(--primary-color); }
.summary-divider { width: 1px; height: 40px; background: var(--surface-border); margin: 0 10px; }

.signed-card {
  display: flex; align-items: center; gap: 12px;
  background: var(--surface-card); border: 1px dashed var(--surface-border);
  border-radius: 8px; padding: 12px 16px; margin-top: 16px;
}
.signed-info { flex: 1; }
.signed-title { font-size: 13px; font-weight: 600; }
.signed-sub   { font-size: 11px; color: var(--text-color-secondary); }

.schedule-toolbar {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 14px; padding: 0 2px;
}
.schedule-footer {
  display: grid; grid-template-columns: 60px 130px 130px 130px 140px;
  gap: 0; padding: 10px 14px; border-top: 2px solid var(--surface-border);
  font-size: 13px;
}
</style>
