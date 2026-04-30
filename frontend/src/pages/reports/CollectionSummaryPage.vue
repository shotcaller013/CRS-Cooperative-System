<template>
  <div class="page-wrap">
    <header class="topbar">
      <router-link to="/reports" class="back-link">Reports</router-link>
      <span class="topbar-sep">/</span>
      <span class="topbar-page">Collection summary</span>
      <span class="topbar-sep">/</span>
      <span class="topbar-sub">Expected vs collected — by loan type and status</span>
    </header>

    <ReportFilterBar
      initial-date-mode="month"
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
          <StatCard label="Expected"        :value="peso(data.summary.total_expected)"  mono />
          <StatCard label="Collected"       :value="peso(data.summary.total_collected)" mono
            :accent="data.summary.collection_rate >= 90 ? 'success' : 'warning'" />
          <StatCard label="Shortfall"       :value="peso(data.summary.total_shortfall)" mono
            :accent="data.summary.total_shortfall > 0 ? 'danger' : 'success'" />
          <StatCard label="Penalty accrued" :value="peso(data.summary.total_penalty)"   mono />
          <StatCard label="Collection rate" :value="data.summary.collection_rate + '%'" mono
            :accent="data.summary.collection_rate >= 90 ? 'success' : 'warning'" />
          <StatCard label="Periods paid"
            :value="`${data.summary.paid_periods} / ${data.summary.total_periods}`" />
        </div>

        <!-- By loan type -->
        <div class="card breakdown-card">
          <div class="breakdown-title">By loan type</div>
          <table class="data-table">
            <thead>
              <tr>
                <th>Loan type</th>
                <th class="text-right">Periods</th>
                <th class="text-right">Paid</th>
                <th class="text-right">Expected (₱)</th>
                <th class="text-right">Collected (₱)</th>
                <th class="text-right">Shortfall (₱)</th>
                <th style="width:180px">Rate</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in data.by_loan_type" :key="r.label">
                <td>{{ r.label }}</td>
                <td class="text-right mono">{{ r.period_count }}</td>
                <td class="text-right mono">{{ r.paid_count }}</td>
                <td class="text-right mono">{{ peso(r.expected) }}</td>
                <td class="text-right mono" style="color:#16a34a">{{ peso(r.collected) }}</td>
                <td class="text-right mono" :style="r.shortfall > 0 ? 'color:var(--crs-red)' : ''">
                  {{ peso(r.shortfall) }}
                </td>
                <td><RateBar :rate="r.rate" /></td>
              </tr>
              <tr class="footer-row">
                <td><strong>Total</strong></td>
                <td class="text-right mono"><strong>{{ data.summary.total_periods }}</strong></td>
                <td class="text-right mono"><strong>{{ data.summary.paid_periods }}</strong></td>
                <td class="text-right mono"><strong>{{ peso(data.summary.total_expected) }}</strong></td>
                <td class="text-right mono" style="color:#16a34a"><strong>{{ peso(data.summary.total_collected) }}</strong></td>
                <td class="text-right mono" :style="data.summary.total_shortfall > 0 ? 'color:var(--crs-red)' : ''">
                  <strong>{{ peso(data.summary.total_shortfall) }}</strong>
                </td>
                <td class="mono"><strong>{{ data.summary.collection_rate }}%</strong></td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- By loan status -->
        <div class="card breakdown-card">
          <div class="breakdown-title">By loan status</div>
          <table class="data-table">
            <thead>
              <tr>
                <th>Loan status</th>
                <th class="text-right">Periods</th>
                <th class="text-right">Paid</th>
                <th class="text-right">Expected (₱)</th>
                <th class="text-right">Collected (₱)</th>
                <th class="text-right">Shortfall (₱)</th>
                <th style="width:180px">Rate</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in data.by_loan_status" :key="r.label">
                <td>{{ r.label }}</td>
                <td class="text-right mono">{{ r.period_count }}</td>
                <td class="text-right mono">{{ r.paid_count }}</td>
                <td class="text-right mono">{{ peso(r.expected) }}</td>
                <td class="text-right mono" style="color:#16a34a">{{ peso(r.collected) }}</td>
                <td class="text-right mono" :style="r.shortfall > 0 ? 'color:var(--crs-red)' : ''">
                  {{ peso(r.shortfall) }}
                </td>
                <td><RateBar :rate="r.rate" /></td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="period-note">Period: {{ data.date_from }} to {{ data.date_to }}</div>
      </template>

      <div v-else class="empty-state">
        <div class="empty-icon">◈</div>
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
import RateBar         from '@/components/reports/RateBar.vue'
import { useReportStore } from '@/stores/report.store'
import { useCurrency }    from '@/composables/useCurrency'
import { useToast }       from '@/composables/useToast'

const reportStore = useReportStore()
const { formatCurrency } = useCurrency()
const { error } = useToast()
const data = computed(() => reportStore.collection)
const peso = (n) => formatCurrency(n)

async function runReport(params) {
  try { await reportStore.fetchCollection(params) }
  catch (e) { error(e?.message || 'Report failed.') }
}

async function handleExport({ params, format }) {
  try { await reportStore.exportReport('collection', params, format) }
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
.breakdown-card { overflow: hidden; padding: 0; }
.breakdown-title { padding: 12px 16px; font-size: 13px; font-weight: 500; border-bottom: 1px solid var(--border); color: var(--ink-muted); }
.footer-row td { background: var(--surface); border-top: 1px solid var(--border); padding: 8px 12px; }
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
