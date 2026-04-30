<template>
  <div class="page-wrap">
    <div class="page-header">
      <div>
        <h1 class="page-title">Loan Applications</h1>
        <p class="page-sub">{{ loanStore.pagination.total }} total records</p>
      </div>
      <div class="header-actions">
        <Button label="Pipeline View" icon="pi pi-th-large" severity="secondary" outlined
          @click="$router.push('/loans/pipeline')" />
        <Button label="New Application" icon="pi pi-plus" @click="$router.push('/loans/create')" />
      </div>
    </div>

    <div class="filter-bar">
      <span class="p-input-icon-left search-wrap">
        <i class="pi pi-search" />
        <InputText v-model="filters.search" placeholder="Search by loan #…"
          class="search-input" @input="debouncedFetch" />
      </span>
      <Dropdown v-model="filters.status" :options="loanStatuses"
        placeholder="All Statuses" showClear class="filter-select" @change="fetchData" />
    </div>

    <div class="page-content">
      <LoanTable
        :loans="loanStore.list"
        :pagination="loanStore.pagination"
        :loading="loanStore.loading"
        @page="onPage"
        @sort="onSort"
        @view="goDetail"
        @edit="goEdit"
        @delete="confirmDelete"
        @approve="confirmApprove"
        @row-click="goDetail"
      />
    </div>

    <ConfirmDialog />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useConfirm } from 'primevue/useconfirm'
import InputText   from 'primevue/inputtext'
import Dropdown    from 'primevue/dropdown'
import Button      from 'primevue/button'
import ConfirmDialog from 'primevue/confirmdialog'
import LoanTable   from '@/components/loan/LoanTable.vue'
import { useLoanStore } from '@/stores/loan.store'
import { useToast } from '@/composables/useToast'

const router    = useRouter()
const confirm   = useConfirm()
const loanStore = useLoanStore()
const { success, error } = useToast()

const filters  = ref({ search: '', status: null })
const sortField = ref('created_at')
const sortDir   = ref('desc')
const page      = ref(1)

let fetchTimer = null
function debouncedFetch() { clearTimeout(fetchTimer); fetchTimer = setTimeout(fetchData, 350) }

async function fetchData() {
  page.value = 1
  await loanStore.fetchList({ ...filters.value, sort_by: sortField.value, sort_dir: sortDir.value, page: page.value })
}
function onPage(e) { page.value = e.page + 1; loanStore.fetchList({ ...filters.value, page: page.value }) }
function onSort(e) { sortField.value = e.sortField; sortDir.value = e.sortOrder === 1 ? 'asc' : 'desc'; fetchData() }
function goDetail(loan) { router.push(`/loans/${loan.id}`) }
function goEdit(loan)   { router.push(`/loans/${loan.id}/edit`) }

function confirmDelete(loan) {
  confirm.require({
    message: `Delete loan ${loan.loan_no}?`,
    header: 'Confirm Delete', icon: 'pi pi-exclamation-triangle', acceptClass: 'p-button-danger',
    accept: async () => {
      try { await loanStore.remove(loan.id); success('Loan deleted.') }
      catch (e) { error(e?.response?.data?.message || 'Delete failed.') }
    },
  })
}

function confirmApprove(loan) {
  confirm.require({
    message: `Approve loan ${loan.loan_no} for ${loan.member?.first_name} ${loan.member?.last_name}?`,
    header: 'Confirm Approval', icon: 'pi pi-check-circle', acceptLabel: 'Approve',
    accept: async () => {
      try { await loanStore.approve(loan.id); success('Loan approved!'); fetchData() }
      catch (e) { error(e?.response?.data?.message || 'Approval failed.') }
    },
  })
}

const loanStatuses = ['DRAFT', 'PENDING', 'APPROVED', 'ACTIVE', 'CLOSED', 'REJECTED']
onMounted(fetchData)
</script>

<style scoped>
.page-wrap    { display: flex; flex-direction: column; height: 100%; overflow: hidden; }
.page-header  { display: flex; justify-content: space-between; align-items: flex-end; padding: 20px 24px; border-bottom: 1px solid var(--surface-border); flex-shrink: 0; }
.page-title   { font-size: 22px; font-weight: 700; margin: 0; }
.page-sub     { font-size: 12px; color: var(--text-color-secondary); margin: 3px 0 0; }
.header-actions { display: flex; gap: 10px; }
.filter-bar   { display: flex; gap: 10px; padding: 14px 24px; border-bottom: 1px solid var(--surface-border); flex-shrink: 0; flex-wrap: wrap; }
.search-wrap  { flex: 1; min-width: 220px; }
.search-input { width: 100%; }
.filter-select { width: 180px; }
.page-content { flex: 1; overflow-y: auto; }
</style>
