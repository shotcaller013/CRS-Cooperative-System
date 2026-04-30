<template>
  <div class="page-wrap">
    <div class="page-header">
      <div>
        <h1 class="page-title">Loan Pipeline</h1>
        <p class="page-sub">Track all loan applications by status</p>
      </div>
      <div class="header-actions">
        <Button label="List View" icon="pi pi-list" severity="secondary" outlined
          @click="$router.push('/loans')" />
        <Button label="New Application" icon="pi pi-plus"
          @click="$router.push('/loans/create')" />
      </div>
    </div>

    <div v-if="loanStore.loading" class="loading-state"><ProgressSpinner /></div>

    <div v-else class="pipeline-board">
      <div v-for="(col, status) in columns" :key="status" class="pipeline-col">
        <div class="col-header" :style="{ borderTopColor: col.color }">
          <span class="col-title">{{ col.label }}</span>
          <Tag :value="String(pipelineLoans(status).length)"
            :severity="col.severity" rounded />
        </div>

        <div class="col-body">
          <div v-if="!pipelineLoans(status).length" class="col-empty">
            No loans
          </div>

          <div v-for="loan in pipelineLoans(status)" :key="loan.id"
            class="loan-card" @click="$router.push(`/loans/${loan.id}`)">
            <div class="lc-no font-mono">{{ loan.loan_no }}</div>
            <div class="lc-name">{{ loan.member?.first_name }} {{ loan.member?.last_name }}</div>
            <div class="lc-type">{{ loan.loan_type?.label }}</div>
            <div class="lc-amount font-mono">{{ formatCurrency(loan.amount) }}</div>
            <div class="lc-meta">
              <span>{{ loan.term_months }}mo · {{ freqLabel(loan.frequency) }}</span>
              <span>{{ formatDate(loan.application_date) }}</span>
            </div>

            <!-- Quick action buttons -->
            <div class="lc-actions" v-if="status === 'PENDING'" @click.stop>
              <Button label="Approve" icon="pi pi-check" size="small"
                severity="success" @click="quickApprove(loan)" :loading="approving === loan.id" />
              <Button label="Reject" size="small" severity="danger" outlined
                @click="quickReject(loan)" />
            </div>
            <div class="lc-actions" v-if="status === 'APPROVED'" @click.stop>
              <Button label="Activate" icon="pi pi-play" size="small"
                severity="info" @click="quickActivate(loan)" :loading="approving === loan.id" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <ConfirmDialog />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useConfirm } from 'primevue/useconfirm'
import Button          from 'primevue/button'
import Tag             from 'primevue/tag'
import ProgressSpinner from 'primevue/progressspinner'
import ConfirmDialog   from 'primevue/confirmdialog'
import { useLoanStore } from '@/stores/loan.store'
import { useCurrency }  from '@/composables/useCurrency'
import { useDate }      from '@/composables/useDate'
import { useToast }     from '@/composables/useToast'

const loanStore = useLoanStore()
const confirm   = useConfirm()
const { formatCurrency } = useCurrency()
const { formatDate }     = useDate()
const { success, error } = useToast()
const approving = ref(null)

const columns = {
  DRAFT:    { label: 'Draft',    color: '#8B9CB8', severity: 'secondary' },
  PENDING:  { label: 'Pending',  color: '#E6A817', severity: 'warning'   },
  APPROVED: { label: 'Approved', color: '#27AE60', severity: 'success'   },
  ACTIVE:   { label: 'Active',   color: '#2980B9', severity: 'info'      },
  CLOSED:   { label: 'Closed',   color: '#6B7280', severity: 'secondary' },
  REJECTED: { label: 'Rejected', color: '#E74C3C', severity: 'danger'    },
}

const freqLabel = (f) => ({ monthly: 'Mo', bimonthly: 'BiMo', weekly: 'Wk' })[f] || f

function pipelineLoans(status) {
  return loanStore.pipeline[status] || []
}

async function quickApprove(loan) {
  approving.value = loan.id
  try {
    await loanStore.approve(loan.id)
    success(`Loan ${loan.loan_no} approved!`)
    await loanStore.fetchPipeline()
  } catch (e) { error(e?.response?.data?.message || 'Failed.') }
  finally { approving.value = null }
}

async function quickActivate(loan) {
  approving.value = loan.id
  try {
    await loanStore.update(loan.id, { status: 'ACTIVE' })
    success(`Loan ${loan.loan_no} activated!`)
    await loanStore.fetchPipeline()
  } catch (e) { error(e?.response?.data?.message || 'Failed.') }
  finally { approving.value = null }
}

function quickReject(loan) {
  confirm.require({
    message: `Reject loan ${loan.loan_no}?`,
    header: 'Confirm Rejection', acceptClass: 'p-button-danger', acceptLabel: 'Reject',
    accept: async () => {
      try {
        await loanStore.update(loan.id, { status: 'REJECTED' })
        success(`Loan ${loan.loan_no} rejected.`)
        await loanStore.fetchPipeline()
      } catch (e) { error(e?.response?.data?.message || 'Failed.') }
    },
  })
}

onMounted(() => loanStore.fetchPipeline())
</script>

<style scoped>
.page-wrap    { display: flex; flex-direction: column; height: 100%; overflow: hidden; }
.page-header  { display: flex; justify-content: space-between; align-items: flex-end; padding: 18px 24px; border-bottom: 1px solid var(--surface-border); flex-shrink: 0; }
.page-title   { font-size: 22px; font-weight: 700; margin: 0; }
.page-sub     { font-size: 12px; color: var(--text-color-secondary); margin: 3px 0 0; }
.header-actions { display: flex; gap: 10px; }
.loading-state  { display: flex; justify-content: center; padding: 60px; }

.pipeline-board {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  flex: 1; overflow: hidden;
  border-top: 1px solid var(--surface-border);
}

.pipeline-col {
  display: flex; flex-direction: column;
  border-right: 1px solid var(--surface-border); overflow: hidden;
}
.pipeline-col:last-child { border-right: none; }

.col-header {
  padding: 12px 14px; border-top: 3px solid;
  display: flex; justify-content: space-between; align-items: center;
  flex-shrink: 0;
  background: var(--surface-ground);
  border-bottom: 1px solid var(--surface-border);
}
.col-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }

.col-body  { flex: 1; overflow-y: auto; padding: 10px 8px; display: flex; flex-direction: column; gap: 8px; }
.col-empty { font-size: 12px; color: var(--text-color-secondary); text-align: center; padding: 24px; }

.loan-card {
  background: var(--surface-card); border: 1px solid var(--surface-border);
  border-radius: 8px; padding: 12px; cursor: pointer;
  transition: all 0.15s ease; display: flex; flex-direction: column; gap: 4px;
}
.loan-card:hover { border-color: var(--primary-color); box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
.lc-no     { font-size: 10px; color: var(--text-color-secondary); }
.lc-name   { font-size: 13px; font-weight: 600; }
.lc-type   { font-size: 11px; color: var(--text-color-secondary); }
.lc-amount { font-size: 15px; font-weight: 700; color: var(--primary-color); margin: 2px 0; }
.lc-meta   { display: flex; justify-content: space-between; font-size: 10px; color: var(--text-color-secondary); }
.lc-actions { display: flex; gap: 4px; margin-top: 6px; }
.lc-actions :deep(.p-button) { flex: 1; justify-content: center; font-size: 11px; }
</style>
