<template>
  <div class="page-wrap">
    <header class="topbar">
      <router-link to="/reports" class="back-link">Reports</router-link>
      <span class="topbar-sep">/</span>
      <span class="topbar-page">Outstanding balance</span>
      <span class="topbar-sep">/</span>
      <span class="topbar-sub">Remaining loan balance per member</span>
    </header>

    <ReportFilterBar
      initial-date-mode="range"
      :show-loan-type="true"
      :show-status="true"
      :loading="reportStore.loading"
      :exporting="reportStore.exporting"
      @run="runReport"
      @export="handleExport"
    />

    <div class="page-content">
      <div v-if="reportStore.loading" class="loading-state">
        <div class="spinner"></div>
      </div>

      <template v-else-if="data">
        <div class="stats-row">
          <StatCard label="Active loans"       :value="data.summary.total_loans" />
          <StatCard label="Total outstanding"  :value="peso(data.summary.total_outstanding)"  mono />
          <StatCard label="Total paid to date" :value="peso(data.summary.total_paid)"         mono accent="success" />
          <StatCard label="Overdue balance"    :value="peso(data.summary.total_overdue)"      mono accent="danger" />
          <StatCard label="Penalty accrued"    :value="peso(data.summary.total_penalty)"      mono accent="danger" />
          <StatCard label="Loans w/ overdue"   :value="data.summary.loans_with_overdue"       accent="danger" />
        </div>

        <!-- Search bar -->
        <div class="search-bar">
          <div class="search-box">
            <svg class="ico" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input v-model="search" placeholder="Search member, loan #…" />
          </div>
          <span class="row-count">{{ filteredRows.length }} loans</span>
        </div>

        <div class="card table-card">
          <table class="data-table">
            <thead>
              <tr>
                <th>Member</th>
                <th>Company</th>
                <th>Loan #</th>
                <th>Type</th>
                <th>Status</th>
                <th class="text-right">Original (₱)</th>
                <th class="text-right">Paid (₱)</th>
                <th class="text-right">Balance (₱)</th>
                <th class="text-right">Overdue (₱)</th>
                <th class="text-right">Periods</th>
                <th>End date</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!filteredRows.length">
                <td colspan="11" class="empty-row">No loans match your filters.</td>
              </tr>
              <tr v-for="r in filteredRows" :key="r.loan_id">
                <td>
                  <div style="font-weight:600">{{ r.member_name }}</div>
                  <div class="mono" style="font-size:11px;color:var(--ink-muted)">{{ r.member_no }}</div>
                </td>
                <td style="font-size:12px">{{ r.company || '—' }}</td>
                <td class="mono">{{ r.loan_no }}</td>
                <td style="font-size:12px">{{ r.loan_type_label }}</td>
                <td>
                  <span :class="['badge', loanStatusBadge(r.loan_status)]">{{ r.loan_status }}</span>
                </td>
                <td class="text-right mono">{{ peso(r.original_amount) }}</td>
                <td class="text-right mono" style="color:#16a34a">{{ peso(r.total_paid) }}</td>
                <td class="text-right mono" style="font-weight:600">{{ peso(r.remaining_balance) }}</td>
                <td class="text-right mono" :style="r.overdue_balance > 0 ? 'color:var(--crs-red)' : 'color:var(--ink-muted)'">
                  {{ r.overdue_balance > 0 ? peso(r.overdue_balance) : '—' }}
                </td>
                <td class="text-right">
                  <span v-if="r.overdue_count > 0" class="badge badge-rejected">{{ r.overdue_count }}</span>
                  <span v-else class="ink-muted">—</span>
                </td>
                <td class="mono" style="font-size:12px">{{ formatDate(r.end_date) }}</td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="footer-row">
                <td colspan="5"><strong>Total ({{ data.summary.total_loans }} loans)</strong></td>
                <td class="text-right mono"><strong>{{ peso(data.summary.total_original) }}</strong></td>
                <td class="text-right mono" style="color:#16a34a"><strong>{{ peso(data.summary.total_paid) }}</strong></td>
                <td class="text-right mono"><strong>{{ peso(data.summary.total_outstanding) }}</strong></td>
                <td class="text-right mono" style="color:var(--crs-red)"><strong>{{ peso(data.summary.total_overdue) }}</strong></td>
                <td></td><td></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </template>

      <div v-else class="empty-state">
        <div class="empty-icon">◉</div>
        <div class="empty-title">Run the report</div>
        <div class="empty-sub">Set filters above and click Run report</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import ReportFilterBar from '@/components/reports/ReportFilterBar.vue'
import StatCard        from '@/components/reports/StatCard.vue'
import { useReportStore } from '@/stores/report.store'
import { useCurrency }    from '@/composables/useCurrency'
import { useDate }        from '@/composables/useDate'
import { useToast }       from '@/composables/useToast'

const reportStore = useReportStore()
const { formatCurrency } = useCurrency()
const { formatDate }     = useDate()
const { error }  = useToast()
const data       = computed(() => reportStore.outstanding)
const search     = ref('')
const peso       = (n) => formatCurrency(n)

const filteredRows = computed(() => {
  if (!data.value?.rows) return []
  const q = search.value.toLowerCase()
  if (!q) return data.value.rows
  return data.value.rows.filter(r =>
    r.member_name?.toLowerCase().includes(q) ||
    r.member_no?.toLowerCase().includes(q) ||
    r.loan_no?.toLowerCase().includes(q)
  )
})

const loanStatusBadge = (s) => ({
  ACTIVE: 'badge-active', APPROVED: 'badge-approved',
  CLOSED: 'badge-draft',  REJECTED: 'badge-rejected',
}[s] || 'badge-draft')

async function runReport(params) {
  search.value = ''
  try { await reportStore.fetchOutstanding(params) }
  catch (e) { error(e?.message || 'Report failed.') }
}

async function handleExport({ params, format }) {
  try { await reportStore.exportReport('outstanding', params, format) }
  catch { error('Export failed.') }
}
</script>

<style scoped>
.page-wrap    { display: flex; flex-direction: column; height: 100%; overflow: hidden; }
.topbar {
  display: flex; align-items: center; gap: 8px;
  padding: 14px 24px; border-bottom: 1px solid var(--border);
  background: white; flex-shrink: 0;
}
.back-link   { font-weight: 600; font-size: 14px; color: var(--crs-red); text-decoration: none; }
.back-link:hover { text-decoration: underline; }
.topbar-page { font-weight: 600; font-size: 14px; }
.topbar-sep  { color: var(--ink-muted); }
.topbar-sub  { font-size: 12px; color: var(--ink-muted); }
.page-content { flex: 1; overflow-y: auto; padding: 20px 24px; display: flex; flex-direction: column; gap: 16px; }
.stats-row    { display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px; }
.table-card   { overflow: hidden; padding: 0; }
.text-right   { text-align: right; }
.footer-row td { background: var(--surface); border-top: 1px solid var(--border); padding: 8px 12px; }
.empty-row    { text-align: center; padding: 40px; color: var(--ink-muted); font-size: 13px; }

.search-bar {
  display: flex; align-items: center; gap: 12px;
}
.search-box {
  display: flex; align-items: center; gap: 6px;
  background: var(--surface-2); border: 1.5px solid var(--border);
  border-radius: 7px; padding: 6px 12px; flex: 0 0 280px;
}
.search-box input { border: none; background: transparent; outline: none; font-size: 13px; width: 100%; }
.ico { width: 15px; height: 15px; stroke: var(--ink-muted); fill: none; stroke-width: 2; stroke-linecap: round; }
.row-count { font-size: 12px; color: var(--ink-muted); }

.loading-state { display: flex; justify-content: center; padding: 60px; }
.spinner { width: 28px; height: 28px; border: 3px solid var(--border); border-top-color: var(--crs-red); border-radius: 50%; animation: spin .7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.empty-state { display: flex; flex-direction: column; align-items: center; padding: 60px 20px; gap: 8px; }
.empty-icon  { font-size: 32px; color: var(--ink-muted); }
.empty-title { font-size: 16px; font-weight: 500; color: var(--ink); }
.empty-sub   { font-size: 13px; color: var(--ink-muted); }
.ink-muted   { color: var(--ink-muted); }
</style>
