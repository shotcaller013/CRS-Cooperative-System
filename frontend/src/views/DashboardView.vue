<template>
  <div class="view-wrap">
    <div class="view-header">
      <div>
        <div class="view-title serif">Dashboard</div>
        <div class="view-sub">CRS Holdings · Employees Credit Cooperative</div>
      </div>
      <div class="header-actions">
        <span class="text-muted" style="font-size:12px">{{ today }}</span>
      </div>
    </div>

    <div class="dashboard-body">
      <!-- Stats row -->
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-label">Active Members</div>
          <div class="stat-value">248</div>
          <div class="stat-sub">↑ 4 new this month</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Active Loans</div>
          <div class="stat-value" style="color:var(--coop-red-soft)">37</div>
          <div class="stat-sub">₱2.1M outstanding</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Pending Applications</div>
          <div class="stat-value" style="color:var(--status-pending)">7</div>
          <div class="stat-sub">Awaiting review</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Collections This Month</div>
          <div class="stat-value">₱184k</div>
          <div class="stat-sub">92% collection rate</div>
        </div>
      </div>

      <!-- Quick actions -->
      <div class="quick-section">
        <div class="section-label serif">Quick Actions</div>
        <div class="quick-grid">
          <router-link to="/loans" class="quick-card">
            <div class="quick-icon">✦</div>
            <div class="quick-title">New Loan Application</div>
            <div class="quick-sub">Process a new loan for a member</div>
          </router-link>
          <router-link to="/members" class="quick-card">
            <div class="quick-icon">◉</div>
            <div class="quick-title">Member Lookup</div>
            <div class="quick-sub">Search the members database</div>
          </router-link>
          <router-link to="/pipeline" class="quick-card">
            <div class="quick-icon">⟶</div>
            <div class="quick-title">Loan Pipeline</div>
            <div class="quick-sub">Track pending & approved loans</div>
          </router-link>
          <router-link to="/monitoring" class="quick-card">
            <div class="quick-icon">◈</div>
            <div class="quick-title">Monitoring</div>
            <div class="quick-sub">Amortization & collections</div>
          </router-link>
        </div>
      </div>

      <!-- Recent loans -->
      <div class="recent-section">
        <div class="section-label serif">Recent Applications</div>
        <div class="card">
          <table class="data-table">
            <thead>
              <tr>
                <th>Loan #</th><th>Member</th><th>Type</th>
                <th>Amount</th><th>Status</th><th>Date</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="l in recentLoans" :key="l.id" @click="$router.push('/loans')">
                <td><span class="mono" style="font-size:12px">{{ l.loan_no }}</span></td>
                <td>
                  <div class="fw-600">{{ l.first_name }} {{ l.last_name }}</div>
                  <div class="text-muted" style="font-size:11px">{{ l.member_no }}</div>
                </td>
                <td>{{ l.loan_type_label }}</td>
                <td><span class="peso">{{ formatPeso(l.amount) }}</span></td>
                <td><span :class="badgeClass(l.status)">{{ l.status }}</span></td>
                <td class="text-muted" style="font-size:12px">{{ formatDate(l.created_at) }}</td>
              </tr>
              <tr v-if="!recentLoans.length">
                <td colspan="6" style="text-align:center; padding:32px; color:var(--ink-muted)">
                  No recent loan applications
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { api } from '../composables/useApi'
import { peso } from '../composables/useLoanCalc'

const recentLoans = ref([])
const today = new Date().toLocaleDateString('en-PH', { weekday:'long', year:'numeric', month:'long', day:'numeric' })

const formatPeso = (n) => peso(n)
const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-PH') : '—'
const badgeClass = (s) => `badge badge-${(s || 'draft').toLowerCase()}`

onMounted(async () => {
  try { recentLoans.value = (await api.getLoans()).slice(0, 8) } catch {}
})
</script>

<style scoped>
.view-wrap { display:flex; flex-direction:column; height:100%; overflow:hidden; background:var(--surface); }
.view-header {
  padding: 20px 28px; border-bottom: 1px solid var(--border);
  background: white; box-shadow: var(--shadow-xs);
  display:flex; justify-content:space-between; align-items:flex-end;
  flex-shrink:0;
}
.view-title { font-size:26px; font-weight:800; color:var(--ink); }
.view-sub   { font-size:12px; color:var(--ink-muted); margin-top:2px; }

.dashboard-body { flex:1; overflow-y:auto; padding:24px 28px; display:flex; flex-direction:column; gap:24px; }

.stats-row { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; }

.quick-section, .recent-section { display:flex; flex-direction:column; gap:12px; }
.section-label { font-size:16px; font-weight:700; color:var(--ink); }

.quick-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; }
.quick-card {
  background: white; border: 1px solid var(--border);
  border-radius:10px; padding:20px 18px;
  text-decoration:none; color:inherit;
  transition: all var(--tx); cursor:pointer;
  box-shadow: var(--shadow-xs);
}
.quick-card:hover { border-color:var(--crs-red); background:var(--crs-red-pale); }
.quick-icon { font-size:22px; color:var(--crs-red); margin-bottom:10px; }
.quick-title { font-weight:700; font-size:14px; color:var(--ink); margin-bottom:4px; }
.quick-sub { font-size:12px; color:var(--ink-muted); }

.header-actions { display:flex; align-items:center; gap:10px; }
</style>
