<template>
  <div class="page-wrap">
    <header class="topbar">
      <router-link to="/reports" class="back-link">Reports</router-link>
      <span class="topbar-sep">/</span>
      <span class="topbar-page">Aging report</span>
      <span class="topbar-sep">/</span>
      <span class="topbar-sub">Overdue periods grouped by age bucket</span>
    </header>

    <ReportFilterBar
      initial-date-mode="as_of"
      :show-loan-type="true"
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
          <StatCard label="0–30 days"
            :value="peso(data.summary['0_30'].balance)" mono
            :sub="data.summary['0_30'].count + ' period(s)'"
            accent="success" />
          <StatCard label="31–60 days"
            :value="peso(data.summary['31_60'].balance)" mono
            :sub="data.summary['31_60'].count + ' period(s)'"
            accent="warning" />
          <StatCard label="61–90 days"
            :value="peso(data.summary['61_90'].balance)" mono
            :sub="data.summary['61_90'].count + ' period(s)'"
            accent="warning" />
          <StatCard label="90+ days (critical)"
            :value="peso(data.summary['90_plus'].balance)" mono
            :sub="data.summary['90_plus'].count + ' period(s)'"
            accent="danger" />
          <StatCard label="Total overdue balance"
            :value="peso(data.summary.total_balance)" mono accent="danger" />
          <StatCard label="Total penalty accrued"
            :value="peso(data.summary.total_penalty)" mono accent="danger" />
        </div>

        <div v-for="bucket in bucketDefs" :key="bucket.key">
          <div v-if="data.buckets[bucket.key]?.length" class="bucket-section">
            <div class="bucket-header" :class="`bh-${bucket.cls}`">
              <span>{{ bucket.label }}</span>
              <span class="mono">
                {{ data.buckets[bucket.key].length }} period(s) ·
                {{ peso(data.summary[bucket.key].balance) }}
              </span>
            </div>
            <table class="data-table">
              <thead>
                <tr>
                  <th>Member</th>
                  <th>Loan #</th>
                  <th>Type</th>
                  <th class="text-right">Due date</th>
                  <th class="text-right">Balance (₱)</th>
                  <th class="text-right">Penalty (₱)</th>
                  <th class="text-right">Days overdue</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(r, i) in data.buckets[bucket.key]" :key="i">
                  <td>
                    <div style="font-weight:600">{{ r.member_name }}</div>
                    <div class="mono" style="font-size:11px;color:var(--ink-muted)">{{ r.member_no }}</div>
                  </td>
                  <td class="mono">{{ r.loan_no }}</td>
                  <td>{{ r.loan_type_label }}</td>
                  <td class="text-right mono" style="font-size:12px">{{ formatDate(r.due_date) }}</td>
                  <td class="text-right mono" style="font-weight:600">{{ peso(r.balance) }}</td>
                  <td class="text-right mono" :style="r.penalty_amount > 0 ? 'color:var(--crs-red)' : 'color:var(--ink-muted)'">
                    {{ r.penalty_amount > 0 ? peso(r.penalty_amount) : '—' }}
                  </td>
                  <td class="text-right">
                    <span :class="['overdue-badge', overdueClass(r.days_overdue)]">
                      {{ r.days_overdue }} days
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="period-note">As of: {{ data.as_of_date }}</div>
      </template>

      <div v-else class="empty-state">
        <div class="empty-icon">⊘</div>
        <div class="empty-title">Run the report</div>
        <div class="empty-sub">Set filters above and click Run report</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import ReportFilterBar from '@/components/reports/ReportFilterBar.vue'
import StatCard        from '@/components/reports/StatCard.vue'
import { useReportStore } from '@/stores/report.store'
import { useCurrency }    from '@/composables/useCurrency'
import { useDate }        from '@/composables/useDate'
import { useToast }       from '@/composables/useToast'

const reportStore = useReportStore()
const { formatCurrency } = useCurrency()
const { formatDate }     = useDate()
const { error } = useToast()
const data = computed(() => reportStore.aging)
const peso = (n) => formatCurrency(n)

const bucketDefs = [
  { key: '0_30',    label: '0–30 days',             cls: 'green' },
  { key: '31_60',   label: '31–60 days',             cls: 'amber' },
  { key: '61_90',   label: '61–90 days',             cls: 'amber' },
  { key: '90_plus', label: '90+ days — critical',    cls: 'red'   },
]

function overdueClass(days) {
  if (days > 90) return 'overdue-red'
  if (days > 30) return 'overdue-amber'
  return 'overdue-green'
}

async function runReport(params) {
  try { await reportStore.fetchAging(params) }
  catch (e) { error(e?.message || 'Report failed.') }
}

async function handleExport({ params, format }) {
  try { await reportStore.exportReport('aging', params, format) }
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

.bucket-section { border: 1px solid var(--border); border-radius: 10px; overflow: hidden; }
.bucket-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 10px 16px; font-size: 13px; font-weight: 500;
}
.bh-green { background: rgba(22,163,74,.1);  color: #15803d; }
.bh-amber { background: rgba(217,119,6,.1);  color: #92400e; }
.bh-red   { background: rgba(185,28,28,.1);  color: #991b1b; }

.overdue-badge { font-size: 11px; font-weight: 600; padding: 2px 7px; border-radius: 99px; }
.overdue-green { background: rgba(22,163,74,.12); color: #15803d; }
.overdue-amber { background: rgba(217,119,6,.12); color: #92400e; }
.overdue-red   { background: rgba(185,28,28,.12); color: #991b1b; }

.text-right  { text-align: right; }
.period-note { font-size: 12px; color: var(--ink-muted); text-align: right; }
.loading-state { display: flex; justify-content: center; padding: 60px; }
.spinner { width: 28px; height: 28px; border: 3px solid var(--border); border-top-color: var(--crs-red); border-radius: 50%; animation: spin .7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.empty-state { display: flex; flex-direction: column; align-items: center; padding: 60px 20px; gap: 8px; }
.empty-icon  { font-size: 32px; color: var(--ink-muted); }
.empty-title { font-size: 16px; font-weight: 500; color: var(--ink); }
.empty-sub   { font-size: 13px; color: var(--ink-muted); }
</style>
