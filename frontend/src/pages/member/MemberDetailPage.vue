<template>
  <div class="page-wrap">
    <div class="page-header">
      <Button icon="pi pi-arrow-left" text @click="$router.push('/members')" />
      <div class="header-info" v-if="member">
        <div class="member-avatar-lg" :style="{ background: avatarColor(member.last_name) }">
          {{ member.first_name?.[0] }}{{ member.last_name?.[0] }}
        </div>
        <div>
          <h1 class="page-title">{{ member.first_name }} {{ member.last_name }}</h1>
          <p class="page-sub font-mono">{{ member.member_no }} · {{ member.company }}</p>
        </div>
      </div>
      <div class="header-actions" v-if="member">
        <Button label="Edit Member" icon="pi pi-pencil" outlined
          @click="$router.push(`/members/${member.id}/edit`)" />
        <Button label="New Loan" icon="pi pi-plus"
          @click="$router.push({ path: '/loans/create', query: { member_id: member.id } })" />
      </div>
    </div>

    <div v-if="memberStore.loading" class="loading-state"><ProgressSpinner /></div>

    <template v-else-if="member">
      <TabView class="detail-tabs">
        <TabPanel header="Basic Info">
          <div class="info-grid">
            <InfoField label="Member #"         :value="member.member_no" />
            <InfoField label="Full Name"         :value="member.full_name" />
            <InfoField label="Contact"           :value="member.contact" />
            <InfoField label="Email"             :value="member.email" />
            <InfoField label="Address"           :value="member.address" span2 />
            <InfoField label="Company"           :value="member.company" />
            <InfoField label="Branch"            :value="member.branch" />
            <InfoField label="Department"        :value="member.department" />
            <InfoField label="Position"          :value="member.position" />
            <InfoField label="Emp. Status">
              <StatusBadge :status="member.status" />
            </InfoField>
            <InfoField label="Direct Supervisor" :value="member.supervisor" />
            <InfoField label="Date Hired"        :value="formatDate(member.date_hired)" />
            <InfoField label="Monthly Salary"    :value="formatCurrency(member.monthly_salary)" mono />
            <InfoField label="Share Capital"     :value="formatCurrency(member.share_capital)" mono />
            <InfoField label="Member Status">
              <StatusBadge :status="member.member_status" />
            </InfoField>
          </div>
        </TabPanel>

        <TabPanel header="Loans">
          <div class="tab-action-row">
            <Button label="+ New Loan Application" size="small"
              @click="$router.push({ path: '/loans/create', query: { member_id: member.id } })" />
          </div>
          <DataTable :value="member.loans || []" class="crs-table" stripedRows>
            <Column field="loan_no"    header="Loan #">
              <template #body="{ data }"><span class="font-mono text-sm">{{ data.loan_no }}</span></template>
            </Column>
            <Column field="loan_type_label" header="Type" />
            <Column field="amount"     header="Amount">
              <template #body="{ data }"><span class="font-mono">{{ formatCurrency(data.amount) }}</span></template>
            </Column>
            <Column field="term_months" header="Term">
              <template #body="{ data }">{{ data.term_months }} mo</template>
            </Column>
            <Column field="status"     header="Status">
              <template #body="{ data }"><StatusBadge :status="data.status" /></template>
            </Column>
            <Column field="created_at" header="Date">
              <template #body="{ data }">{{ formatDate(data.created_at) }}</template>
            </Column>
            <Column header="">
              <template #body="{ data }">
                <Button icon="pi pi-eye" text rounded size="small"
                  @click="$router.push(`/loans/${data.id}`)" />
              </template>
            </Column>
            <template #empty>
              <div class="table-empty">
                <i class="pi pi-file text-3xl text-color-secondary"></i>
                <p>No loan applications yet.</p>
              </div>
            </template>
          </DataTable>
        </TabPanel>
      </TabView>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Button        from 'primevue/button'
import TabView       from 'primevue/tabview'
import TabPanel      from 'primevue/tabpanel'
import DataTable     from 'primevue/datatable'
import Column        from 'primevue/column'
import ProgressSpinner from 'primevue/progressspinner'
import StatusBadge   from '@/components/common/StatusBadge.vue'
import InfoField     from '@/components/common/InfoField.vue'
import { useMemberStore } from '@/stores/member.store'
import { useCurrency } from '@/composables/useCurrency'
import { useDate }     from '@/composables/useDate'

const route       = useRoute()
const router      = useRouter()
const memberStore = useMemberStore()
const { formatCurrency } = useCurrency()
const { formatDate }     = useDate()
const member = computed(() => memberStore.selected)

const avatarColors = ['#C0392B','#2980B9','#27AE60','#8E44AD','#D35400']
const avatarColor  = (n) => avatarColors[(n?.charCodeAt(0) ?? 0) % avatarColors.length]

onMounted(() => memberStore.fetchOne(route.params.id))
</script>

<style scoped>
.page-wrap    { display: flex; flex-direction: column; height: 100%; overflow: hidden; }
.page-header  { display: flex; align-items: center; gap: 12px; padding: 18px 24px; border-bottom: 1px solid var(--surface-border); flex-shrink: 0; }
.header-info  { display: flex; align-items: center; gap: 14px; flex: 1; }
.header-actions { display: flex; gap: 10px; }
.page-title   { font-size: 20px; font-weight: 700; margin: 0; }
.page-sub     { font-size: 12px; color: var(--text-color-secondary); margin: 2px 0 0; }
.member-avatar-lg {
  width: 48px; height: 48px; border-radius: 50%; color: #fff;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px; font-weight: 700;
}
.loading-state { display: flex; justify-content: center; padding: 60px; }
.detail-tabs { flex: 1; overflow: hidden; }
:deep(.p-tabview-panels) { overflow-y: auto; height: calc(100% - 45px); padding: 20px 24px; }
.info-grid    { display: grid; grid-template-columns: 1fr 1fr; gap: 18px 28px; }
.tab-action-row { display: flex; justify-content: flex-end; margin-bottom: 14px; }
.table-empty  { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 32px; color: var(--text-color-secondary); }
</style>
