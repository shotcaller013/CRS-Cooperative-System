<template>
  <div class="pipeline-wrap">
    <!-- Topbar -->
    <header class="topbar">
      <div>
        <div class="topbar-page">Loan Pipeline</div>
        <div class="topbar-sub">Track every loan from signed forms to closed account</div>
      </div>
      <div class="topbar-right">
        <button class="p-btn p-btn-outline p-btn-sm" @click="load">
          <svg class="ico" viewBox="0 0 24 24"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.5"/></svg>
          Refresh
        </button>
        <button class="p-btn p-btn-primary p-btn-sm" @click="$router.push('/loans')">
          <svg class="ico" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          New Loan
        </button>
      </div>
    </header>

    <div class="p-content">
      <!-- Stats strip -->
      <div class="stats-strip">
        <div class="stat-card">
          <div class="stat-lbl">Packets Uploaded</div>
          <div class="stat-val">{{ counts.DRAFT }}</div>
          <div class="stat-sub">awaiting review</div>
        </div>
        <div class="stat-card stat-accent">
          <div class="stat-lbl">In Review</div>
          <div class="stat-val">{{ counts.PENDING }}</div>
          <div class="stat-sub">at Loan Officer</div>
        </div>
        <div class="stat-card">
          <div class="stat-lbl">Ready to Release</div>
          <div class="stat-val">{{ counts.APPROVED }}</div>
          <div class="stat-sub">awaiting disbursement</div>
        </div>
        <div class="stat-card">
          <div class="stat-lbl">Released (MTD)</div>
          <div class="stat-val mono">{{ pesoK(releasedMTD) }}</div>
          <div class="stat-sub">{{ counts.ACTIVE }} loans active</div>
        </div>
        <div class="stat-card">
          <div class="stat-lbl">Active Portfolio</div>
          <div class="stat-val mono">{{ pesoK(activeTotal) }}</div>
          <div class="stat-sub">{{ counts.ACTIVE }} loans on books</div>
        </div>
      </div>

      <!-- Filters -->
      <div class="filters-bar">
        <div class="search-wrap">
          <svg class="ico" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input v-model="search" placeholder="Search member, ref #, or amount…" />
        </div>
        <button
          v-for="t in typeFilters" :key="t"
          :class="['pill', activeType === t && 'on']"
          @click="activeType = t"
        >{{ t }}</button>
        <div style="flex:1"></div>
        <div class="view-toggle">
          <button :class="['vt-btn', view === 'board' && 'active']" @click="view = 'board'">
            <svg class="ico" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="18"/><rect x="14" y="3" width="7" height="10"/></svg>
            Board
          </button>
          <button :class="['vt-btn', view === 'list' && 'active']" @click="view = 'list'">
            <svg class="ico" viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            List
          </button>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="p-loading">
        <div class="p-spinner"></div>
      </div>

      <!-- Board view -->
      <div v-else-if="view === 'board'" class="board">
        <section v-for="col in columns" :key="col.status" :class="['col', col.cls]">
          <header class="col-head">
            <div class="col-head-l">
              <span class="col-dot"></span>
              <span class="col-title">{{ col.label }}</span>
            </div>
            <span class="col-count">{{ filtered(col.status).length }}</span>
          </header>
          <div class="col-sum">
            {{ pesoCompact(sumOf(col.status)) }} total
          </div>
          <div class="col-body">
            <div v-if="!filtered(col.status).length" class="col-empty">No loans in this stage</div>
            <div
              v-for="loan in filtered(col.status)"
              :key="loan.id"
              class="kcard"
              @click="openLoan(loan)"
            >
              <div class="kcard-top">
                <span class="kcard-ref mono">{{ loan.loan_no }}</span>
                <span :class="['kcard-badge', col.badge]">{{ col.badgeLabel }}</span>
              </div>
              <div class="kcard-member">
                <div class="kcard-av">{{ initials(loan) }}</div>
                <div>
                  <div class="kcard-name">{{ loan.first_name }} {{ loan.last_name }}</div>
                  <div class="kcard-company">{{ loan.company_name || 'CRS Holdings' }}</div>
                </div>
              </div>
              <div class="kcard-type">
                <span>{{ loan.loan_type_label }} · {{ loan.term_months }} mo</span>
              </div>
              <div class="kcard-amt mono">{{ peso(loan.amount) }}</div>
              <div class="kcard-meta">
                <span class="kcard-timer">{{ timeAgo(loan.created_at) }}</span>
                <div class="kcard-progress">
                  <div class="kcard-progress-fill" :style="{ width: col.progress + '%' }"></div>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>

      <!-- List view -->
      <div v-else class="list-view">
        <table>
          <thead>
            <tr>
              <th>Ref #</th>
              <th>Member</th>
              <th>Loan Type / Term</th>
              <th style="text-align:right">Amount</th>
              <th>Stage</th>
              <th>Last Update</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!allFiltered.length">
              <td colspan="7" style="text-align:center; padding:32px; color:var(--ink-muted)">No loans found</td>
            </tr>
            <tr v-for="loan in allFiltered" :key="loan.id" @click="openLoan(loan)">
              <td class="list-ref mono">{{ loan.loan_no }}</td>
              <td>
                <div class="list-member">
                  <div class="list-av">{{ initials(loan) }}</div>
                  <div>
                    <div class="list-name">{{ loan.first_name }} {{ loan.last_name }}</div>
                    <div class="list-company">{{ loan.company_name || 'CRS Holdings' }}</div>
                  </div>
                </div>
              </td>
              <td style="font-size:12.5px; color:var(--ink-soft)">{{ loan.loan_type_label }} · {{ loan.term_months }}mo</td>
              <td style="text-align:right"><span class="list-amt mono">{{ peso(loan.amount) }}</span></td>
              <td>
                <span class="list-stage">
                  <span class="list-stage-dot" :style="{ background: statusColor(loan.status) }"></span>
                  {{ statusLabel(loan.status) }}
                </span>
              </td>
              <td style="font-size:12px; color:var(--ink-muted)">{{ formatDate(loan.updated_at || loan.created_at) }}</td>
              <td>
                <button class="p-btn p-btn-outline p-btn-xs" @click.stop="openLoan(loan)">View</button>
              </td>
            </tr>
          </tbody>
        </table>
        <div class="list-foot">
          <div>Showing <strong>{{ allFiltered.length }}</strong> loans in pipeline</div>
        </div>
      </div>
    </div>

    <!-- Loan detail modal -->
    <div v-if="activeLoan" class="p-overlay" @click.self="activeLoan = null">
      <div class="p-modal">
        <div class="p-modal-head">
          <div>
            <div class="p-modal-title mono">{{ activeLoan.loan_no }}</div>
            <div style="font-size:12px; color:var(--ink-muted); margin-top:2px">
              {{ activeLoan.first_name }} {{ activeLoan.last_name }} · {{ activeLoan.loan_type_label }}
            </div>
          </div>
          <button class="p-btn p-btn-ghost" @click="activeLoan = null">
            <svg class="ico" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div class="p-modal-body">
          <div class="detail-grid">
            <div class="d-row"><span class="d-lbl">Member</span><span>{{ activeLoan.first_name }} {{ activeLoan.last_name }}</span></div>
            <div class="d-row"><span class="d-lbl">Member #</span><span class="mono">{{ activeLoan.member_no }}</span></div>
            <div class="d-row"><span class="d-lbl">Loan Type</span><span>{{ activeLoan.loan_type_label }}</span></div>
            <div class="d-row"><span class="d-lbl">Amount</span><span class="mono">{{ peso(activeLoan.amount) }}</span></div>
            <div class="d-row"><span class="d-lbl">Term</span><span>{{ activeLoan.term_months }} months</span></div>
            <div class="d-row"><span class="d-lbl">Frequency</span><span>{{ activeLoan.frequency }}</span></div>
            <div class="d-row"><span class="d-lbl">Total Payable</span><span class="mono">{{ peso(activeLoan.total_payment) }}</span></div>
            <div class="d-row"><span class="d-lbl">Current Stage</span>
              <span class="list-stage">
                <span class="list-stage-dot" :style="{ background: statusColor(activeLoan.status) }"></span>
                {{ statusLabel(activeLoan.status) }}
              </span>
            </div>
          </div>

          <div class="status-change">
            <div style="flex:1">
              <div class="d-lbl" style="margin-bottom:6px">Move to Stage</div>
              <select v-model="newStatus" class="p-select">
                <option v-for="s in allStatuses" :key="s" :value="s">{{ statusLabel(s) }}</option>
              </select>
            </div>
            <button class="p-btn p-btn-primary" :disabled="saving" @click="updateStatus">
              {{ saving ? 'Saving…' : 'Update Stage' }}
            </button>
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

const { success, error } = useToast()

const pipeline   = ref({})
const loading    = ref(false)
const saving     = ref(false)
const activeLoan = ref(null)
const newStatus  = ref('')
const search     = ref('')
const activeType = ref('All types')
const view       = ref('board')

const typeFilters = ['All types', 'Commodity', 'Salary', 'Emergency', 'Multi-purpose']

const allStatuses = ['DRAFT', 'PENDING', 'APPROVED', 'ACTIVE', 'CLOSED', 'REJECTED']

const columns = [
  { status: 'DRAFT',    label: 'Signed & Uploaded', cls: 'c-draft',    badge: 'b-amber',  badgeLabel: 'NEW',      progress: 20 },
  { status: 'PENDING',  label: 'In Review',          cls: 'c-pending',  badge: 'b-blue',   badgeLabel: 'REVIEW',   progress: 40 },
  { status: 'APPROVED', label: 'Approved',           cls: 'c-approved', badge: 'b-purple', badgeLabel: 'APPROVED', progress: 60 },
  { status: 'ACTIVE',   label: 'Active · Paying',    cls: 'c-active',   badge: 'b-orange', badgeLabel: 'ACTIVE',   progress: 80 },
  { status: 'CLOSED',   label: 'Closed',             cls: 'c-closed',   badge: 'b-gray',   badgeLabel: 'CLOSED',   progress: 100 },
]

const statusMap = {
  DRAFT:    { label: 'Signed & Uploaded', color: 'var(--amber)' },
  PENDING:  { label: 'In Review',          color: 'var(--blue)' },
  APPROVED: { label: 'Approved',           color: 'var(--purple)' },
  ACTIVE:   { label: 'Active · Paying',    color: 'var(--omni-orange)' },
  CLOSED:   { label: 'Closed',             color: 'var(--ink-faint)' },
  REJECTED: { label: 'Rejected',           color: 'var(--red)' },
}

const statusLabel = (s) => statusMap[s]?.label ?? s
const statusColor = (s) => statusMap[s]?.color ?? 'var(--ink-faint)'

const allLoans = computed(() => Object.values(pipeline.value).flat())

const counts = computed(() => {
  const c = {}
  for (const s of allStatuses) c[s] = pipeline.value[s]?.length ?? 0
  return c
})

const releasedMTD = computed(() =>
  (pipeline.value['ACTIVE'] ?? []).reduce((a, l) => a + Number(l.amount), 0)
)
const activeTotal = computed(() => releasedMTD.value)

function matchesFilter(loan) {
  const q = search.value.trim().toLowerCase()
  const matchQ = !q || `${loan.first_name} ${loan.last_name} ${loan.loan_no} ${loan.amount}`.toLowerCase().includes(q)
  const matchType = activeType.value === 'All types' ||
    (loan.loan_type_label ?? '').toLowerCase().includes(activeType.value.toLowerCase())
  return matchQ && matchType
}

function filtered(status) {
  return (pipeline.value[status] ?? []).filter(matchesFilter)
}

const allFiltered = computed(() => allLoans.value.filter(matchesFilter))

function sumOf(status) {
  return (pipeline.value[status] ?? []).reduce((a, l) => a + Number(l.amount), 0)
}

function initials(loan) {
  return ((loan.first_name?.[0] ?? '') + (loan.last_name?.[0] ?? '')).toUpperCase() || '?'
}

function pesoK(n) {
  if (n >= 1_000_000) return '₱' + (n / 1_000_000).toFixed(1) + 'M'
  if (n >= 1_000) return '₱' + (n / 1_000).toFixed(0) + 'k'
  return '₱' + n
}

function pesoCompact(n) {
  if (n >= 1_000_000) return '₱' + (n / 1_000_000).toFixed(2) + 'M'
  if (n >= 1_000) return '₱' + (n / 1_000).toFixed(0) + 'k'
  return peso(n)
}

function timeAgo(d) {
  if (!d) return '—'
  const diff = Date.now() - new Date(d).getTime()
  const m = Math.floor(diff / 60000)
  if (m < 60) return `${m}m ago`
  const h = Math.floor(m / 60)
  if (h < 24) return `${h}h ago`
  return `${Math.floor(h / 24)}d ago`
}

function formatDate(d) {
  return d ? new Date(d).toLocaleDateString('en-PH', { month: 'short', day: 'numeric' }) : '—'
}

function openLoan(loan) {
  activeLoan.value = loan
  newStatus.value = loan.status
}

async function updateStatus() {
  if (!newStatus.value || saving.value) return
  saving.value = true
  try {
    await api.updateLoan(activeLoan.value.id, { status: newStatus.value })
    success(`Moved to ${statusLabel(newStatus.value)}`)
    activeLoan.value = null
    await load()
  } catch (e) { error(e.message) }
  finally { saving.value = false }
}

async function load() {
  loading.value = true
  try { pipeline.value = await api.getPipeline() }
  catch (e) { error(e.message) }
  finally { loading.value = false }
}

onMounted(load)
</script>

<style scoped>
.pipeline-wrap { display: flex; flex-direction: column; height: 100%; overflow: hidden; background: var(--surface); }

/* Topbar */
.topbar {
  background: white; border-bottom: 1px solid var(--border);
  height: 58px; display: flex; align-items: center;
  padding: 0 28px; gap: 16px; flex-shrink: 0;
  box-shadow: var(--shadow-xs);
}
.topbar-page { font-size: 18px; font-weight: 700; color: var(--ink); }
.topbar-sub  { font-size: 13px; color: var(--ink-muted); }
.topbar-right { margin-left: auto; display: flex; gap: 10px; align-items: center; }

/* Buttons */
.p-btn {
  display: inline-flex; align-items: center; gap: 6px;
  border-radius: 8px; font-size: 13px; font-weight: 600;
  cursor: pointer; border: none; font-family: inherit;
  transition: all var(--tx); padding: 8px 16px;
}
.p-btn-primary { background: var(--crs-red); color: white; }
.p-btn-primary:hover { background: var(--crs-red-mid); }
.p-btn-outline { background: white; color: var(--ink-soft); border: 1.5px solid var(--border-dk); }
.p-btn-outline:hover { border-color: var(--crs-red); color: var(--crs-red); }
.p-btn-ghost { background: transparent; color: var(--ink-muted); padding: 6px 8px; }
.p-btn-ghost:hover { background: var(--surface); color: var(--ink); }
.p-btn-sm { padding: 6px 12px; font-size: 12px; }
.p-btn-xs { padding: 4px 10px; font-size: 11px; border-radius: 6px; }
.p-btn:disabled { opacity: 0.5; cursor: not-allowed; }

.ico { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; }

/* Content area */
.p-content {
  flex: 1; overflow: hidden;
  padding: 20px 28px;
  display: flex; flex-direction: column; gap: 14px;
  min-height: 0;
}

/* Stats */
.stats-strip { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; flex-shrink: 0; }
.stat-card {
  background: white; border: 1px solid var(--border);
  border-radius: 10px; padding: 12px 14px;
  box-shadow: var(--shadow-xs);
}
.stat-lbl { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--ink-muted); }
.stat-val { font-size: 20px; font-weight: 700; margin-top: 4px; color: var(--ink); }
.stat-sub { font-size: 11px; color: var(--ink-muted); margin-top: 2px; }
.stat-accent .stat-val { color: var(--omni-orange); }
.mono { font-family: var(--font-mono); }

/* Filters */
.filters-bar { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; flex-shrink: 0; }
.search-wrap {
  flex: 0 0 280px; position: relative;
  display: flex; align-items: center;
}
.search-wrap .ico { position: absolute; left: 10px; color: var(--ink-muted); }
.search-wrap input {
  width: 100%; padding: 8px 12px 8px 34px;
  border: 1.5px solid var(--border); border-radius: 8px;
  font-size: 13px; outline: none; font-family: inherit;
  background: white; color: var(--ink);
}
.search-wrap input:focus { border-color: var(--crs-red); }
.pill {
  padding: 6px 12px; border: 1.5px solid var(--border);
  border-radius: 99px; background: white;
  font-size: 12px; font-weight: 600; color: var(--ink-soft);
  cursor: pointer; transition: all var(--tx);
}
.pill.on { background: var(--crs-red); border-color: var(--crs-red); color: white; }
.view-toggle { display: inline-flex; background: var(--surface-2); border-radius: 8px; padding: 3px; gap: 2px; }
.vt-btn {
  padding: 6px 12px; font-size: 12px; font-weight: 600;
  color: var(--ink-muted); background: transparent;
  border: none; border-radius: 6px; cursor: pointer;
  display: inline-flex; align-items: center; gap: 5px;
  font-family: inherit;
}
.vt-btn.active { background: white; color: var(--ink); box-shadow: var(--shadow-xs); }
.vt-btn .ico { width: 13px; height: 13px; }

/* Loading */
.p-loading { display: flex; justify-content: center; align-items: center; flex: 1; }
.p-spinner {
  width: 28px; height: 28px; border: 3px solid var(--border);
  border-top-color: var(--crs-red); border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Board */
.board {
  display: grid; grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 12px; flex: 1; min-height: 0; overflow: hidden;
}
.col {
  background: var(--surface-2); border: 1px solid var(--border);
  border-radius: 12px; display: flex; flex-direction: column;
  overflow: hidden;
}
.col-head {
  padding: 12px 14px; border-bottom: 1px solid var(--border);
  background: white; display: flex; align-items: center;
  justify-content: space-between; gap: 8px; flex-shrink: 0;
}
.col-head-l { display: flex; align-items: center; gap: 8px; }
.col-dot { width: 10px; height: 10px; border-radius: 50%; }
.col-title { font-size: 12.5px; font-weight: 700; color: var(--ink); }
.col-count {
  font-family: var(--font-mono); font-size: 11px; font-weight: 700;
  background: var(--surface-2); color: var(--ink-soft);
  padding: 2px 8px; border-radius: 99px;
}
.col-sum { font-family: var(--font-mono); font-size: 10.5px; color: var(--ink-muted); padding: 6px 14px 0; flex-shrink: 0; }
.col-body { padding: 10px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; flex: 1; min-height: 0; }
.col-empty { font-size: 12px; color: var(--ink-faint); text-align: center; padding: 24px 8px; }

/* Column accent colors */
.c-draft .col-dot   { background: var(--amber); }
.c-pending .col-dot { background: var(--blue); }
.c-approved .col-dot{ background: var(--purple); }
.c-active .col-dot  { background: var(--omni-orange); }
.c-closed .col-dot  { background: var(--ink-faint); }

/* Kanban card */
.kcard {
  background: white; border: 1px solid var(--border);
  border-radius: 10px; padding: 11px 12px;
  cursor: pointer; transition: all var(--tx);
  box-shadow: var(--shadow-xs);
}
.kcard:hover { border-color: var(--crs-red); box-shadow: var(--shadow-sm); transform: translateY(-1px); }
.kcard-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 8px; }
.kcard-ref { font-size: 10px; color: var(--ink-muted); font-weight: 600; }
.kcard-badge { font-size: 9.5px; font-weight: 700; padding: 2px 7px; border-radius: 99px; text-transform: uppercase; letter-spacing: 0.04em; }
.b-amber  { background: var(--amber-pale);       color: var(--amber); }
.b-blue   { background: var(--blue-pale);        color: var(--blue); }
.b-purple { background: var(--purple-pale);      color: var(--purple); }
.b-orange { background: var(--omni-orange-pale); color: var(--omni-orange); }
.b-gray   { background: var(--surface-2);        color: var(--ink-muted); }
.kcard-member { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
.kcard-av {
  width: 28px; height: 28px; border-radius: 50%;
  background: linear-gradient(135deg, var(--omni-orange), var(--crs-red));
  color: white; display: flex; align-items: center; justify-content: center;
  font-size: 11px; font-weight: 700; flex-shrink: 0;
}
.kcard-name { font-size: 12.5px; font-weight: 700; color: var(--ink); line-height: 1.2; }
.kcard-company { font-size: 10.5px; color: var(--ink-muted); margin-top: 1px; }
.kcard-type { font-size: 10.5px; color: var(--ink-soft); font-weight: 600; margin-bottom: 6px; }
.kcard-amt { font-size: 15px; font-weight: 700; color: var(--ink); }
.kcard-meta {
  display: flex; justify-content: space-between; align-items: center;
  margin-top: 8px; padding-top: 8px; border-top: 1px dashed var(--border);
}
.kcard-timer { font-size: 10px; color: var(--ink-muted); }
.kcard-progress { flex: 1; height: 4px; background: var(--surface-2); border-radius: 99px; overflow: hidden; margin-left: 10px; max-width: 60px; }
.kcard-progress-fill { height: 100%; background: var(--omni-orange); border-radius: 99px; }

/* List view */
.list-view {
  background: white; border: 1px solid var(--border);
  border-radius: 12px; overflow: hidden;
  box-shadow: var(--shadow-xs); flex: 1; min-height: 0;
  display: flex; flex-direction: column;
}
.list-view table { width: 100%; border-collapse: collapse; }
.list-view thead { background: var(--surface); }
.list-view th {
  text-align: left; padding: 10px 14px;
  font-size: 10.5px; font-weight: 700; color: var(--ink-muted);
  text-transform: uppercase; letter-spacing: 0.06em;
  border-bottom: 1px solid var(--border); white-space: nowrap;
}
.list-view td { padding: 11px 14px; border-bottom: 1px solid var(--border); font-size: 13px; vertical-align: middle; }
.list-view tbody tr { cursor: pointer; transition: background var(--tx); }
.list-view tbody tr:hover { background: var(--surface); }
.list-view tbody tr:last-child td { border-bottom: none; }
.list-ref { font-size: 11px; color: var(--ink-muted); font-weight: 600; }
.list-member { display: flex; align-items: center; gap: 10px; }
.list-av {
  width: 30px; height: 30px; border-radius: 50%;
  background: linear-gradient(135deg, var(--omni-orange), var(--crs-red));
  color: white; display: flex; align-items: center; justify-content: center;
  font-size: 11px; font-weight: 700; flex-shrink: 0;
}
.list-name { font-size: 13px; font-weight: 700; color: var(--ink); line-height: 1.2; }
.list-company { font-size: 11px; color: var(--ink-muted); margin-top: 1px; }
.list-amt { font-weight: 700; font-size: 13px; color: var(--ink); }
.list-stage { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 700; }
.list-stage-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.list-foot {
  padding: 12px 16px; display: flex; justify-content: space-between; align-items: center;
  font-size: 12px; color: var(--ink-muted);
  border-top: 1px solid var(--border); background: var(--surface); flex-shrink: 0;
}

/* Modal */
.p-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,0.5);
  display: flex; align-items: center; justify-content: center;
  z-index: 1000; padding: 20px;
}
.p-modal {
  background: white; border-radius: 12px;
  width: 100%; max-width: 560px;
  max-height: 90vh; display: flex; flex-direction: column;
  box-shadow: var(--shadow-md);
  animation: modal-in 0.2s ease;
}
@keyframes modal-in { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:none; } }
.p-modal-head {
  padding: 18px 22px; border-bottom: 1px solid var(--border);
  display: flex; justify-content: space-between; align-items: flex-start;
}
.p-modal-title { font-size: 18px; font-weight: 700; color: var(--ink); }
.p-modal-body { padding: 22px; overflow-y: auto; flex: 1; }
.detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 24px; margin-bottom: 20px; }
.d-row { display: flex; flex-direction: column; gap: 3px; }
.d-lbl { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--ink-muted); }
.d-row span:last-child { font-size: 13.5px; font-weight: 600; color: var(--ink); }
.status-change { display: flex; gap: 12px; align-items: flex-end; padding-top: 16px; border-top: 1px solid var(--border); }
.p-select {
  width: 100%; padding: 9px 12px; border: 1.5px solid var(--border-dk);
  border-radius: 8px; font-family: inherit; font-size: 13px;
  color: var(--ink); background: white; outline: none;
}
.p-select:focus { border-color: var(--crs-red); }
</style>
