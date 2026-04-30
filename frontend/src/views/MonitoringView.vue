<template>
  <div class="mon-wrap">
    <!-- Topbar -->
    <header class="topbar">
      <span class="topbar-page">Loan Monitoring</span>
      <span class="topbar-sep">/</span>
      <span class="topbar-sub">Active loans · Balances · Aging · Payments</span>
      <div class="topbar-right">
        <button class="m-btn m-btn-outline m-btn-sm" @click="fetchLoans">
          <svg class="ico" viewBox="0 0 24 24"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.5"/></svg>
          Refresh
        </button>
      </div>
    </header>

    <div class="m-content">
      <!-- KPI row -->
      <div class="kpi-row">
        <div class="kpi">
          <div class="kpi-label">Active Loans</div>
          <div class="kpi-value mono">{{ kpis.activeCount }}</div>
          <div class="kpi-meta">loans on books</div>
        </div>
        <div class="kpi kpi-blue">
          <div class="kpi-label">Outstanding Balance</div>
          <div class="kpi-value mono" style="color:var(--blue)">{{ pesoK(kpis.outstanding) }}</div>
          <div class="kpi-meta">Principal + Interest</div>
        </div>
        <div class="kpi kpi-green">
          <div class="kpi-label">Total Principal</div>
          <div class="kpi-value mono" style="color:var(--green)">{{ pesoK(kpis.totalPrincipal) }}</div>
          <div class="kpi-meta">released to members</div>
        </div>
        <div class="kpi kpi-amber">
          <div class="kpi-label">Past Due</div>
          <div class="kpi-value mono" style="color:var(--amber)">{{ kpis.pastDue }}</div>
          <div class="kpi-meta">borrowers behind</div>
        </div>
        <div class="kpi kpi-red">
          <div class="kpi-label">Closed Loans</div>
          <div class="kpi-value mono" style="color:var(--red)">{{ kpis.closedCount }}</div>
          <div class="kpi-meta">fully paid off</div>
        </div>
      </div>

      <!-- Tab strip -->
      <div class="tab-strip">
        <div
          v-for="tab in tabs"
          :key="tab.key"
          :class="['tab', activeTab === tab.key && 'active']"
          @click="setTab(tab.key)"
        >
          {{ tab.label }}
          <span class="tab-count">{{ tabCount(tab.key) }}</span>
        </div>
      </div>

      <!-- Toolbar -->
      <div class="toolbar">
        <div class="search-box">
          <svg class="ico" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input v-model="search" placeholder="Search member, ID or loan #…" />
        </div>
        <select v-model="filterType" class="m-sel">
          <option value="">All Loan Types</option>
          <option value="Commodity">Commodity</option>
          <option value="Salary">Salary</option>
          <option value="Emergency">Emergency</option>
          <option value="Multi-purpose">Multi-purpose</option>
        </select>
      </div>

      <!-- Main grid -->
      <div class="grid-mon">
        <!-- Left: table -->
        <div class="m-card">
          <div v-if="loading" class="m-loading">
            <div class="m-spinner"></div>
          </div>
          <table v-else>
            <thead>
              <tr>
                <th>Member</th>
                <th>Loan</th>
                <th>Outstanding</th>
                <th>Progress</th>
                <th>Next Due</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!filteredLoans.length">
                <td colspan="7" style="text-align:center; padding:32px; color:var(--ink-muted)">No loans found</td>
              </tr>
              <tr
                v-for="loan in filteredLoans"
                :key="loan.id"
                :class="['', selectedLoan?.id === loan.id && 'selected']"
                @click="selectLoan(loan)"
              >
                <td>
                  <div class="member-cell">
                    <div class="m-av" :style="{ background: avatarGrad(loan) }">{{ initials(loan) }}</div>
                    <div>
                      <div class="m-name">{{ loan.first_name }} {{ loan.last_name }}</div>
                      <div class="m-id mono">{{ loan.member_no }}</div>
                    </div>
                  </div>
                </td>
                <td>
                  <div style="font-weight:600; font-size:13px">{{ loan.loan_type_label }} · {{ pesoK(loan.amount) }}</div>
                  <div style="font-size:11px; color:var(--ink-muted)" class="mono">{{ loan.loan_no }} · {{ loan.term_months }}mo</div>
                </td>
                <td>
                  <span class="amount mono">{{ peso(loan.amount) }}</span>
                  <div style="font-size:11px; color:var(--ink-muted)" class="mono">of {{ peso(loan.total_payment) }}</div>
                </td>
                <td style="min-width:120px">
                  <div class="pbar"><div class="pbar-fill" :style="pbarStyle(loan)"></div></div>
                  <div class="pbar-meta mono">{{ loan.n_periods }} periods</div>
                </td>
                <td>
                  <div style="font-weight:600; font-size:12.5px">—</div>
                  <div style="font-size:11px; color:var(--ink-muted)">schedule needed</div>
                </td>
                <td><span :class="['m-badge', statusBadge(loan.status)]">{{ loan.status }}</span></td>
                <td>
                  <button class="m-btn m-btn-ghost m-btn-xs" @click.stop="selectLoan(loan)">View →</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Right: detail panel -->
        <div v-if="selectedLoan" class="detail-card">
          <!-- Red header -->
          <div class="detail-head">
            <div class="dh-row">
              <div>
                <div class="dh-name">{{ selectedLoan.first_name }} {{ selectedLoan.last_name }}</div>
                <div class="dh-meta">{{ selectedLoan.loan_type_label }} · {{ selectedLoan.loan_no }}</div>
              </div>
              <button class="m-btn m-btn-ghost" style="color:rgba(255,255,255,0.6)" @click="selectedLoan = null">
                <svg class="ico" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
            <div class="dh-amt-lbl">Outstanding Balance</div>
            <div class="dh-amt mono">{{ peso(selectedLoan.amount) }}</div>
            <div class="dh-progress-wrap">
              <div class="dh-progress-meta">
                <span>0 paid</span>
                <span>{{ selectedLoan.term_months }} months total</span>
              </div>
              <div class="dh-progress">
                <div class="dh-progress-fill" style="width:10%"></div>
              </div>
            </div>
          </div>

          <!-- Quick stats -->
          <div class="quick-stats">
            <div class="qs">
              <div class="qs-l">Released</div>
              <div class="qs-v mono">{{ peso(selectedLoan.amount) }}</div>
            </div>
            <div class="qs">
              <div class="qs-l">Total Payable</div>
              <div class="qs-v mono">{{ peso(selectedLoan.total_payment) }}</div>
            </div>
            <div class="qs">
              <div class="qs-l">Total Interest</div>
              <div class="qs-v mono green">{{ peso(selectedLoan.total_interest) }}</div>
            </div>
            <div class="qs">
              <div class="qs-l">Periods</div>
              <div class="qs-v mono">{{ selectedLoan.n_periods }}</div>
            </div>
            <div class="qs">
              <div class="qs-l">Frequency</div>
              <div class="qs-v">{{ selectedLoan.frequency }}</div>
            </div>
            <div class="qs">
              <div class="qs-l">1st Payment</div>
              <div class="qs-v mono">{{ peso(selectedLoan.first_payment_amt) }}</div>
            </div>
          </div>

          <!-- Detail tabs -->
          <div class="dtl-tabs">
            <div
              v-for="t in detailTabs"
              :key="t"
              :class="['dtl-tab', activeDetailTab === t && 'active']"
              @click="activeDetailTab = t"
            >{{ t }}</div>
          </div>

          <!-- Schedule -->
          <div v-if="activeDetailTab === 'Schedule'">
            <div v-if="loadingSchedule" class="m-loading" style="padding:24px">
              <div class="m-spinner"></div>
            </div>
            <div v-else-if="!schedule.length" style="padding:24px; text-align:center; color:var(--ink-muted); font-size:13px">
              No schedule generated yet
            </div>
            <div v-else class="sched-list">
              <div
                v-for="p in schedule"
                :key="p.id"
                :class="['sched-row', p.status === 'PAID' && 'paid']"
              >
                <div :class="['sched-icon', p.status === 'PAID' ? 'paid' : p.status === 'PENDING' ? 'next' : 'future']">
                  {{ p.status === 'PAID' ? '✓' : p.period_no }}
                </div>
                <div>
                  <div class="sched-date">{{ formatDate(p.due_date) }}</div>
                  <div class="sched-date-sub">Period {{ p.period_no }}</div>
                </div>
                <div class="sched-amt mono">{{ p.amount_due }}</div>
                <div class="sched-amt mono" style="color:var(--ink-muted); font-size:11px">{{ p.balance }}</div>
              </div>
            </div>
          </div>

          <!-- Payments placeholder -->
          <div v-else-if="activeDetailTab === 'Payments'" style="padding:24px; text-align:center; color:var(--ink-muted); font-size:13px">
            Payment records coming soon
          </div>

          <!-- Documents placeholder -->
          <div v-else style="padding:24px; text-align:center; color:var(--ink-muted); font-size:13px">
            No documents uploaded
          </div>

          <!-- Quick actions -->
          <div class="quick-action">
            <button class="m-btn m-btn-orange m-btn-sm" style="flex:1; justify-content:center">
              <svg class="ico" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Record Payment
            </button>
            <button class="m-btn m-btn-outline m-btn-sm" style="flex:1; justify-content:center">
              <svg class="ico" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              Statement
            </button>
          </div>
        </div>

        <!-- Empty detail state -->
        <div v-else class="detail-card detail-empty">
          <div class="detail-empty-inner">
            <svg style="width:40px;height:40px;stroke:var(--ink-faint);fill:none;stroke-width:1.5" viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
            <div style="font-size:15px; font-weight:600; color:var(--ink-soft); margin-top:12px">Select a loan</div>
            <div style="font-size:12px; color:var(--ink-muted); margin-top:4px">Click a row to view the amortization schedule and details</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { api } from '../composables/useApi'
import { peso } from '../composables/useLoanCalc'
import { useToast } from '../composables/useToast'

const { error } = useToast()

const loans          = ref([])
const selectedLoan   = ref(null)
const schedule       = ref([])
const loading        = ref(false)
const loadingSchedule = ref(false)
const search         = ref('')
const filterType     = ref('')
const activeTab      = ref('ACTIVE')
const activeDetailTab = ref('Schedule')

const detailTabs = ['Schedule', 'Payments', 'Documents']

const tabs = [
  { key: 'ACTIVE',   label: 'All Active' },
  { key: 'PENDING',  label: 'Pending' },
  { key: 'APPROVED', label: 'Approved' },
  { key: 'CLOSED',   label: 'Closed' },
]

const kpis = computed(() => {
  const active = loans.value.filter(l => l.status === 'ACTIVE')
  const closed = loans.value.filter(l => l.status === 'CLOSED')
  return {
    activeCount:   active.length,
    outstanding:   active.reduce((a, l) => a + Number(l.amount), 0),
    totalPrincipal: loans.value.reduce((a, l) => a + Number(l.amount), 0),
    pastDue:       0,
    closedCount:   closed.length,
  }
})

function tabCount(key) {
  return loans.value.filter(l => l.status === key).length
}

const filteredLoans = computed(() => {
  const q = search.value.trim().toLowerCase()
  return loans.value.filter(l => {
    if (l.status !== activeTab.value) return false
    if (filterType.value && !(l.loan_type_label ?? '').toLowerCase().includes(filterType.value.toLowerCase())) return false
    if (q && !`${l.first_name} ${l.last_name} ${l.member_no} ${l.loan_no}`.toLowerCase().includes(q)) return false
    return true
  })
})

function setTab(key) {
  activeTab.value = key
  selectedLoan.value = null
  schedule.value = []
}

function initials(loan) {
  return ((loan.first_name?.[0] ?? '') + (loan.last_name?.[0] ?? '')).toUpperCase() || '?'
}

const gradients = [
  'linear-gradient(135deg,#1A6B3C,#27AE60)',
  'linear-gradient(135deg,#1A3A8B,#2563EB)',
  'linear-gradient(135deg,#E8591A,#FF8C42)',
  'linear-gradient(135deg,#5B2AA7,#8B5CF6)',
  'linear-gradient(135deg,#8B1A1A,#C0392B)',
]
function avatarGrad(loan) {
  return gradients[(loan.id ?? 0) % gradients.length]
}

function pesoK(n) {
  const v = Number(n) || 0
  if (v >= 1_000_000) return '₱' + (v / 1_000_000).toFixed(1) + 'M'
  if (v >= 1_000) return '₱' + (v / 1_000).toFixed(0) + 'k'
  return '₱' + v
}

function pbarStyle(loan) {
  const pct = Math.min(100, Math.max(5, 10))
  return { width: pct + '%', background: 'var(--green)' }
}

function formatDate(d) {
  return d ? new Date(d).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'
}

function statusBadge(s) {
  const map = { ACTIVE: 'b-active', PENDING: 'b-pending', APPROVED: 'b-approved', CLOSED: 'b-closed', REJECTED: 'b-rejected' }
  return map[s] || 'b-active'
}

async function fetchLoans() {
  loading.value = true
  try { loans.value = await api.getLoans() }
  catch (e) { error(e.message) }
  finally { loading.value = false }
}

async function selectLoan(loan) {
  selectedLoan.value = loan
  activeDetailTab.value = 'Schedule'
  schedule.value = []
  loadingSchedule.value = true
  try {
    const full = await api.getLoan(loan.id)
    schedule.value = full.schedule || []
    selectedLoan.value = full
  } catch (e) { error(e.message) }
  finally { loadingSchedule.value = false }
}

onMounted(fetchLoans)
</script>

<style scoped>
.mon-wrap { display: flex; flex-direction: column; height: 100%; overflow: hidden; background: var(--surface); }

/* Topbar */
.topbar {
  background: white; border-bottom: 1px solid var(--border);
  height: 58px; display: flex; align-items: center;
  padding: 0 28px; gap: 12px; flex-shrink: 0;
  box-shadow: var(--shadow-xs);
}
.topbar-page { font-size: 18px; font-weight: 700; color: var(--ink); }
.topbar-sep  { color: var(--border-dk); }
.topbar-sub  { font-size: 13px; color: var(--ink-muted); flex: 1; }
.topbar-right { display: flex; gap: 10px; align-items: center; }

/* Buttons */
.m-btn {
  display: inline-flex; align-items: center; gap: 6px;
  border-radius: 8px; font-size: 13px; font-weight: 600;
  cursor: pointer; border: none; font-family: inherit;
  transition: all var(--tx); padding: 8px 16px; white-space: nowrap;
}
.m-btn-outline { background: white; color: var(--ink-soft); border: 1.5px solid var(--border-dk); }
.m-btn-outline:hover { border-color: var(--crs-red); color: var(--crs-red); }
.m-btn-orange { background: var(--omni-orange); color: white; }
.m-btn-orange:hover { background: var(--omni-orange-lt); }
.m-btn-ghost { background: transparent; color: var(--ink-muted); padding: 6px 8px; }
.m-btn-ghost:hover { background: var(--surface); color: var(--ink); }
.m-btn-sm { padding: 6px 12px; font-size: 12px; }
.m-btn-xs { padding: 4px 10px; font-size: 11px; border-radius: 6px; }

.ico { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; }
.mono { font-family: var(--font-mono); }

/* Content */
.m-content {
  flex: 1; overflow-y: auto; overflow-x: hidden;
  padding: 20px 28px 48px;
  display: flex; flex-direction: column; gap: 14px;
}

/* KPI row */
.kpi-row { display: grid; grid-template-columns: repeat(5, 1fr); gap: 14px; }
.kpi {
  background: white; border: 1px solid var(--border);
  border-radius: 12px; padding: 14px 16px;
  box-shadow: var(--shadow-xs); position: relative; overflow: hidden;
}
.kpi::before {
  content: ''; position: absolute; left: 0; top: 0; bottom: 0;
  width: 3px; background: var(--crs-red);
}
.kpi-blue::before  { background: var(--blue); }
.kpi-green::before { background: var(--green); }
.kpi-amber::before { background: var(--amber); }
.kpi-red::before   { background: var(--red); }
.kpi-label { font-size: 10.5px; font-weight: 700; color: var(--ink-muted); text-transform: uppercase; letter-spacing: 0.08em; }
.kpi-value { font-size: 22px; font-weight: 800; margin-top: 4px; letter-spacing: -0.01em; color: var(--ink); }
.kpi-meta  { font-size: 11px; color: var(--ink-muted); margin-top: 4px; }

/* Tabs */
.tab-strip {
  display: flex; gap: 4px; padding: 6px;
  background: var(--surface-2); border-radius: 10px;
  width: fit-content;
}
.tab {
  padding: 7px 14px; font-size: 12.5px; font-weight: 600;
  color: var(--ink-muted); border-radius: 7px; cursor: pointer;
  display: flex; align-items: center; gap: 6px;
  transition: all var(--tx);
}
.tab.active { background: white; color: var(--ink); box-shadow: var(--shadow-xs); }
.tab-count {
  background: var(--surface-2); color: var(--ink-muted);
  padding: 1px 7px; border-radius: 99px; font-size: 11px; font-weight: 700;
}
.tab.active .tab-count { background: var(--crs-red); color: white; }

/* Toolbar */
.toolbar {
  background: white; border: 1px solid var(--border);
  border-radius: 12px; padding: 12px 14px;
  display: flex; gap: 10px; align-items: center; flex-wrap: wrap;
  box-shadow: var(--shadow-xs);
}
.search-box {
  flex: 1; min-width: 220px; display: flex; align-items: center; gap: 8px;
}
.search-box .ico { color: var(--ink-muted); flex-shrink: 0; }
.search-box input {
  flex: 1; border: none; outline: none; font-family: inherit;
  font-size: 13px; color: var(--ink); background: transparent;
}
.search-box input::placeholder { color: var(--ink-muted); }
.m-sel {
  padding: 7px 11px; font-size: 12.5px;
  border: 1px solid var(--border-dk); border-radius: 7px;
  background: white; font-family: inherit; color: var(--ink-soft);
  outline: none;
}
.m-sel:focus { border-color: var(--crs-red); }

/* Main grid */
.grid-mon { display: grid; grid-template-columns: 1fr 420px; gap: 18px; align-items: start; }

/* Left card */
.m-card {
  background: white; border: 1px solid var(--border);
  border-radius: 12px; overflow: hidden; box-shadow: var(--shadow-xs);
}
.m-loading { display: flex; justify-content: center; padding: 40px; }
.m-spinner {
  width: 24px; height: 24px; border: 2.5px solid var(--border);
  border-top-color: var(--crs-red); border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

table { width: 100%; border-collapse: collapse; }
thead { background: var(--surface); }
th {
  text-align: left; padding: 10px 14px;
  font-size: 10.5px; font-weight: 700; color: var(--ink-muted);
  text-transform: uppercase; letter-spacing: 0.06em;
  border-bottom: 1px solid var(--border);
}
td { padding: 12px 14px; border-bottom: 1px solid var(--border); font-size: 13px; vertical-align: middle; }
tbody tr { cursor: pointer; transition: background var(--tx); }
tbody tr:hover { background: var(--surface); }
tbody tr.selected { background: var(--crs-red-pale); }
tbody tr:last-child td { border-bottom: none; }

.member-cell { display: flex; align-items: center; gap: 10px; }
.m-av {
  width: 32px; height: 32px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 11px; font-weight: 700; color: white; flex-shrink: 0;
}
.m-name { font-weight: 600; font-size: 13px; color: var(--ink); }
.m-id { font-size: 11px; color: var(--ink-muted); }

.m-badge {
  display: inline-flex; align-items: center; padding: 3px 9px;
  border-radius: 99px; font-size: 10.5px; font-weight: 700;
  text-transform: uppercase; letter-spacing: 0.02em;
}
.b-active   { background: var(--blue-pale);   color: var(--blue); }
.b-pending  { background: var(--amber-pale);  color: var(--amber); }
.b-approved { background: var(--green-pale);  color: var(--green); }
.b-closed   { background: var(--surface-2);   color: var(--ink-muted); }
.b-rejected { background: var(--red-pale, #FEE2E2); color: var(--red); }

.amount { font-weight: 600; font-size: 13px; color: var(--crs-red); }
.pbar { width: 100%; height: 6px; background: var(--surface-2); border-radius: 99px; overflow: hidden; }
.pbar-fill { height: 100%; border-radius: 99px; transition: width 0.3s; }
.pbar-meta { font-size: 10.5px; color: var(--ink-muted); margin-top: 3px; }

/* Detail panel */
.detail-card {
  background: white; border: 1px solid var(--border);
  border-radius: 12px; overflow: hidden; box-shadow: var(--shadow-xs);
}
.detail-empty {
  display: flex; align-items: center; justify-content: center;
  min-height: 300px;
}
.detail-empty-inner { text-align: center; padding: 40px; }

.detail-head {
  padding: 16px 20px;
  background: linear-gradient(135deg, var(--crs-red) 0%, var(--crs-red-mid) 100%);
  color: white;
}
.dh-row { display: flex; justify-content: space-between; align-items: flex-start; }
.dh-name { font-size: 17px; font-weight: 800; }
.dh-meta { font-size: 12px; opacity: 0.85; margin-top: 2px; }
.dh-amt-lbl { font-size: 10.5px; opacity: 0.75; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700; margin-top: 12px; }
.dh-amt { font-size: 26px; font-weight: 800; margin-top: 4px; letter-spacing: -0.02em; }
.dh-progress-wrap { margin-top: 12px; }
.dh-progress-meta { display: flex; justify-content: space-between; font-size: 11px; opacity: 0.85; margin-bottom: 6px; }
.dh-progress { height: 8px; background: rgba(255,255,255,0.2); border-radius: 99px; overflow: hidden; }
.dh-progress-fill { height: 100%; background: white; border-radius: 99px; }

.quick-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 1px; background: var(--border); }
.qs { background: white; padding: 12px 16px; }
.qs-l { font-size: 10.5px; color: var(--ink-muted); text-transform: uppercase; letter-spacing: 0.06em; font-weight: 700; }
.qs-v { font-size: 15px; font-weight: 700; margin-top: 3px; color: var(--ink); }
.qs-v.green { color: var(--green); }
.qs-v.red   { color: var(--crs-red); }
.qs-v.amber { color: var(--amber); }

.dtl-tabs { display: flex; border-bottom: 1px solid var(--border); }
.dtl-tab {
  flex: 1; padding: 10px; font-size: 12px; font-weight: 600;
  color: var(--ink-muted); cursor: pointer; text-align: center;
  border-bottom: 2px solid transparent; transition: all var(--tx);
}
.dtl-tab.active { color: var(--crs-red); border-bottom-color: var(--crs-red); background: var(--crs-red-pale); }

.sched-list { max-height: 320px; overflow-y: auto; }
.sched-row {
  display: grid; grid-template-columns: 28px 1fr auto auto;
  gap: 10px; padding: 10px 18px;
  border-bottom: 1px solid var(--border); align-items: center;
  font-size: 12.5px;
}
.sched-row:last-child { border-bottom: none; }
.sched-row.paid { opacity: 0.55; }
.sched-date { font-weight: 600; font-size: 12.5px; color: var(--ink); }
.sched-date-sub { font-size: 10.5px; color: var(--ink-muted); margin-top: 1px; }
.sched-amt { font-weight: 700; text-align: right; font-size: 12.5px; color: var(--ink); }
.sched-icon {
  width: 22px; height: 22px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 10px; font-weight: 700; flex-shrink: 0;
}
.sched-icon.paid   { background: var(--green);   color: white; }
.sched-icon.next   { background: var(--amber);   color: white; }
.sched-icon.future { background: var(--surface-2); color: var(--ink-muted); }

.quick-action {
  display: flex; gap: 8px; padding: 14px 18px;
  border-top: 1px solid var(--border);
}
</style>
