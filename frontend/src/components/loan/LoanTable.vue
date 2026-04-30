<template>
  <DataTable
    :value="loans"
    lazy paginator
    :rows="pagination.per_page"
    :totalRecords="pagination.total"
    :loading="loading"
    @page="emit('page', $event)"
    @sort="emit('sort', $event)"
    removableSort stripedRows
    class="crs-table"
    @row-click="emit('row-click', $event.data)"
  >
    <Column field="loan_no" header="Loan #" sortable style="width:150px">
      <template #body="{ data }">
        <span class="font-mono text-sm font-semibold">{{ data.loan_no }}</span>
      </template>
    </Column>

    <Column header="Member" sortable field="member.last_name">
      <template #body="{ data }">
        <div v-if="data.member">
          <div class="font-semibold">{{ data.member.first_name }} {{ data.member.last_name }}</div>
          <div class="text-xs text-color-secondary font-mono">{{ data.member.member_no }}</div>
        </div>
      </template>
    </Column>

    <Column header="Loan Type" field="loan_type.label">
      <template #body="{ data }">{{ data.loan_type?.label }}</template>
    </Column>

    <Column field="amount" header="Amount" sortable style="width:140px">
      <template #body="{ data }">
        <span class="font-mono font-semibold">{{ formatCurrency(data.amount) }}</span>
      </template>
    </Column>

    <Column field="term_months" header="Term" style="width:90px">
      <template #body="{ data }">{{ data.term_months }} mo</template>
    </Column>

    <Column field="frequency" header="Frequency" style="width:110px">
      <template #body="{ data }">
        <Tag :value="freqLabel(data.frequency)" severity="secondary" />
      </template>
    </Column>

    <Column field="total_payment" header="Total Payable" sortable style="width:140px">
      <template #body="{ data }">
        <span class="font-mono">{{ formatCurrency(data.total_payment) }}</span>
      </template>
    </Column>

    <Column field="status" header="Status" style="width:110px">
      <template #body="{ data }">
        <StatusBadge :status="data.status" />
      </template>
    </Column>

    <Column field="application_date" header="Applied" sortable style="width:110px">
      <template #body="{ data }">
        <span class="text-sm">{{ formatDate(data.application_date) }}</span>
      </template>
    </Column>

    <Column header="Actions" style="width:130px">
      <template #body="{ data }">
        <div class="action-btns">
          <Button icon="pi pi-eye" text rounded size="small"
            v-tooltip="'View'" @click.stop="emit('view', data)" />
          <Button icon="pi pi-pencil" text rounded size="small"
            v-tooltip="'Edit'" @click.stop="emit('edit', data)"
            :disabled="['ACTIVE','CLOSED','REJECTED'].includes(data.status)" />
          <Button icon="pi pi-check-circle" text rounded size="small"
            severity="success" v-tooltip="'Approve'"
            @click.stop="emit('approve', data)"
            v-if="data.status === 'PENDING'" />
          <Button icon="pi pi-trash" text rounded size="small" severity="danger"
            v-tooltip="'Delete'" @click.stop="emit('delete', data)" />
        </div>
      </template>
    </Column>

    <template #empty>
      <div class="table-empty">
        <i class="pi pi-file text-4xl text-color-secondary mb-3"></i>
        <p>No loan applications found.</p>
      </div>
    </template>
  </DataTable>
</template>

<script setup>
import DataTable  from 'primevue/datatable'
import Column     from 'primevue/column'
import Button     from 'primevue/button'
import Tag        from 'primevue/tag'
import StatusBadge from '@/components/common/StatusBadge.vue'
import { useCurrency } from '@/composables/useCurrency'
import { useDate }     from '@/composables/useDate'

const props = defineProps({
  loans:      { type: Array,   default: () => [] },
  pagination: { type: Object,  default: () => ({ total: 0, per_page: 15 }) },
  loading:    { type: Boolean, default: false },
})
const emit = defineEmits(['page', 'sort', 'view', 'edit', 'delete', 'approve', 'row-click'])

const { formatCurrency } = useCurrency()
const { formatDate }     = useDate()

const freqLabel = (f) => ({ monthly: 'Monthly', bimonthly: 'Bi-Monthly', weekly: 'Weekly' })[f] || f
</script>

<style scoped>
.action-btns  { display: flex; gap: 2px; }
.table-empty  { display: flex; flex-direction: column; align-items: center; padding: 48px; color: var(--text-color-secondary); }
:deep(.p-datatable-tbody > tr) { cursor: pointer; }
</style>
