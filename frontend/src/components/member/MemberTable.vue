<template>
  <DataTable
    :value="members"
    lazy paginator
    :rows="pagination.per_page"
    :totalRecords="pagination.total"
    :loading="loading"
    @page="emit('page', $event)"
    @sort="emit('sort', $event)"
    removableSort
    stripedRows
    class="crs-table"
    :rowClass="() => 'cursor-pointer'"
    @row-click="emit('row-click', $event.data)"
  >
    <Column field="member_no" header="Member #" sortable style="width:130px">
      <template #body="{ data }">
        <span class="font-mono text-sm font-semibold">{{ data.member_no }}</span>
      </template>
    </Column>

    <Column header="Name" sortable field="last_name">
      <template #body="{ data }">
        <div class="member-cell">
          <Avatar :label="data.first_name[0] + data.last_name[0]"
            :style="{ background: avatarColor(data.last_name) }"
            class="member-avatar" shape="circle" size="small" />
          <div>
            <div class="font-semibold">{{ data.first_name }} {{ data.last_name }}</div>
            <div class="text-xs text-color-secondary">{{ data.email }}</div>
          </div>
        </div>
      </template>
    </Column>

    <Column field="company" header="Company" />
    <Column field="position" header="Position" />

    <Column field="status" header="Emp. Status" style="width:110px">
      <template #body="{ data }">
        <StatusBadge :status="data.status" />
      </template>
    </Column>

    <Column field="monthly_salary" header="Salary" style="width:140px">
      <template #body="{ data }">
        <span class="font-mono">{{ formatCurrency(data.monthly_salary) }}</span>
      </template>
    </Column>

    <Column field="member_status" header="Status" style="width:100px">
      <template #body="{ data }">
        <StatusBadge :status="data.member_status" />
      </template>
    </Column>

    <Column field="active_loans_count" header="Active Loans" style="width:110px">
      <template #body="{ data }">
        <Tag v-if="data.active_loans_count > 0"
          :value="data.active_loans_count + ' loan(s)'"
          severity="info" />
        <span v-else class="text-color-secondary text-sm">None</span>
      </template>
    </Column>

    <Column header="Actions" style="width:120px">
      <template #body="{ data }">
        <div class="action-btns">
          <Button icon="pi pi-eye" text rounded size="small"
            v-tooltip="'View'" @click.stop="emit('view', data)" />
          <Button icon="pi pi-pencil" text rounded size="small"
            v-tooltip="'Edit'" @click.stop="emit('edit', data)" />
          <Button icon="pi pi-trash" text rounded size="small" severity="danger"
            v-tooltip="'Delete'" @click.stop="emit('delete', data)" />
        </div>
      </template>
    </Column>

    <template #empty>
      <div class="table-empty">
        <i class="pi pi-users text-4xl text-color-secondary mb-3"></i>
        <p>No members found.</p>
      </div>
    </template>
  </DataTable>
</template>

<script setup>
import DataTable  from 'primevue/datatable'
import Column     from 'primevue/column'
import Button     from 'primevue/button'
import Avatar     from 'primevue/avatar'
import Tag        from 'primevue/tag'
import StatusBadge from '@/components/common/StatusBadge.vue'
import { useCurrency } from '@/composables/useCurrency'

const props = defineProps({
  members:    { type: Array,   default: () => [] },
  pagination: { type: Object,  default: () => ({ total: 0, per_page: 15 }) },
  loading:    { type: Boolean, default: false },
})
const emit = defineEmits(['page', 'sort', 'view', 'edit', 'delete', 'row-click'])

const { formatCurrency } = useCurrency()

const avatarColors = ['#C0392B','#2980B9','#27AE60','#8E44AD','#D35400','#1A252F']
const avatarColor  = (name) => avatarColors[(name?.charCodeAt(0) ?? 0) % avatarColors.length]
</script>

<style scoped>
.member-cell { display: flex; align-items: center; gap: 10px; }
.member-avatar { flex-shrink: 0; color: #fff; font-size: 11px; font-weight: 600; }
.action-btns { display: flex; gap: 2px; }
.table-empty { display: flex; flex-direction: column; align-items: center; padding: 48px; color: var(--text-color-secondary); }
:deep(.p-datatable-tbody > tr) { cursor: pointer; }
</style>
