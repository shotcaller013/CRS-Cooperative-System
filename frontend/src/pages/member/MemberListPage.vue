<template>
  <div class="page-wrap">
    <div class="page-header">
      <div>
        <h1 class="page-title">Members & 201 File</h1>
        <p class="page-sub">{{ memberStore.pagination.total }} members registered</p>
      </div>
      <div class="header-actions">
        <Button label="Import" icon="pi pi-upload" severity="secondary" outlined
          @click="$router.push('/members')" />
        <Button label="Add Member" icon="pi pi-plus" @click="$router.push('/members/create')" />
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
      <span class="p-input-icon-left search-wrap">
        <i class="pi pi-search" />
        <InputText v-model="filters.search" placeholder="Search by name or member #…"
          class="search-input" @input="debouncedFetch" />
      </span>
      <Dropdown v-model="filters.status" :options="memberStatuses"
        placeholder="All Statuses" showClear class="filter-select" @change="fetchData" />
      <Dropdown v-model="filters.emp_status" :options="empStatuses"
        placeholder="All Emp. Status" showClear class="filter-select" @change="fetchData" />
    </div>

    <!-- Table -->
    <div class="page-content">
      <MemberTable
        :members="memberStore.list"
        :pagination="memberStore.pagination"
        :loading="memberStore.loading"
        @page="onPage"
        @sort="onSort"
        @view="goDetail"
        @edit="goEdit"
        @delete="confirmDelete"
        @row-click="goDetail"
      />
    </div>

    <!-- Confirm Delete -->
    <ConfirmDialog />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useConfirm } from 'primevue/useconfirm'
import InputText  from 'primevue/inputtext'
import Dropdown   from 'primevue/dropdown'
import Button     from 'primevue/button'
import ConfirmDialog from 'primevue/confirmdialog'
import MemberTable from '@/components/member/MemberTable.vue'
import { useMemberStore } from '@/stores/member.store'
import { useToast } from '@/composables/useToast'

const router      = useRouter()
const confirm     = useConfirm()
const memberStore = useMemberStore()
const { success, error } = useToast()

const filters = ref({ search: '', status: null, emp_status: null })
const sortField = ref('member_no')
const sortDir   = ref('asc')
const page      = ref(1)

let fetchTimer = null

function debouncedFetch() {
  clearTimeout(fetchTimer)
  fetchTimer = setTimeout(fetchData, 350)
}

async function fetchData() {
  page.value = 1
  await memberStore.fetchList({
    search:     filters.value.search,
    status:     filters.value.status,
    emp_status: filters.value.emp_status,
    sort_by:    sortField.value,
    sort_dir:   sortDir.value,
    page:       page.value,
  })
}

function onPage(e) {
  page.value = e.page + 1
  memberStore.fetchList({ ...filters.value, page: page.value, sort_by: sortField.value, sort_dir: sortDir.value })
}

function onSort(e) {
  sortField.value = e.sortField
  sortDir.value   = e.sortOrder === 1 ? 'asc' : 'desc'
  fetchData()
}

function goDetail(member) { router.push(`/members/${member.id}`) }
function goEdit(member)   { router.push(`/members/${member.id}/edit`) }

function confirmDelete(member) {
  confirm.require({
    message: `Delete ${member.first_name} ${member.last_name}? This cannot be undone.`,
    header:  'Confirm Delete',
    icon:    'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await memberStore.remove(member.id)
        success('Member deleted successfully.')
      } catch (e) {
        error(e?.response?.data?.message || 'Failed to delete member.')
      }
    },
  })
}

const memberStatuses = ['ACTIVE', 'INACTIVE', 'RESIGNED']
const empStatuses    = ['REGULAR', 'PROBI', 'SUSPENDED', 'INACTIVE']

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
