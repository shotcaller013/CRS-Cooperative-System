<template>
  <div class="rel-wrap">
    <!-- Topbar -->
    <header class="topbar">
      <span class="topbar-page">Loan Releasing</span>
      <span class="topbar-sep">/</span>
      <span class="topbar-sub">Disburse approved loans · Generate vouchers · Record release</span>
      <div class="topbar-right">
        <button class="r-btn r-btn-outline r-btn-sm" @click="fetchApproved">
          <svg class="ico" viewBox="0 0 24 24"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.5"/></svg>
          Refresh
        </button>
      </div>
    </header>

    <div class="r-content">
      <!-- Stat row -->
      <div class="stat-row">
        <div class="stat-card">
          <div class="stat-label">Ready for Release</div>
          <div class="stat-value">{{ loans.length }}</div>
          <div class="stat-meta">Approved loans awaiting disbursement</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Pending Disbursement</div>
          <div class="stat-value orange mono">{{ pesoK(grossTotal) }}</div>
          <div class="stat-meta">Gross loan amount</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Net to Release</div>
          <div class="stat-value green mono">{{ pesoK(netTotal) }}</div>
          <div class="stat-meta">After fees &amp; deductions</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Released This Month</div>
          <div class="stat-value red mono">{{ pesoK(releasedMTD) }}</div>
          <div class="stat-meta">{{ activeLoanCount }} vouchers issued</div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="tab-strip">
        <div
          v-for="tab in tabs"
          :key="tab"
          :class="['tab', activeTab === tab && 'active']"
          @click="activeTab = tab"
        >
          {{ tab }}
          <span class="tab-count">{{ tab === 'Ready for Release' ? loans.length : 0 }}</span>
        </div>
      </div>

      <!-- Main grid -->
      <div class="grid-rel">

        <!-- Left: queue -->
        <div>
          <div class="r-card">
            <!-- Filter row -->
            <div class="filter-row">
              <div class="r-search">
                <svg class="ico" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input v-model="search" placeholder="Search by name, ID or loan #…" />
              </div>
              <select v-model="filterType" class="r-sel">
                <option value="">All Loan Types</option>
                <option value="Commodity">Commodity</option>
                <option value="Salary">Salary</option>
                <option value="Emergency">Emergency</option>
                <option value="Multi-purpose">Multi-purpose</option>
              </select>
            </div>

            <!-- Loading -->
            <div v-if="loading" style="padding:40px; display:flex; justify-content:center">
              <div class="r-spinner"></div>
            </div>

            <!-- Empty state -->
            <div v-else-if="!filteredLoans.length" style="padding:40px; text-align:center; color:var(--ink-muted)">
              <svg style="width:36px;height:36px;stroke:var(--ink-faint);fill:none;stroke-width:1.5;margin-bottom:8px" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              <div style="font-size:14px; font-weight:600; color:var(--ink-soft)">No approved loans</div>
              <div style="font-size:12px; margin-top:4px">Loans move here once approved in the pipeline</div>
            </div>

            <!-- Queue rows -->
            <div v-else>
              <div
                v-for="loan in filteredLoans"
                :key="loan.id"
                :class="['queue-row', selected?.id === loan.id && 'selected']"
                @click="selected = loan"
              >
                <div class="qr-av" :style="{ background: avatarGrad(loan) }">{{ initials(loan) }}</div>
                <div>
                  <div class="qr-name">
                    {{ loan.first_name }} {{ loan.last_name }}
                    <span class="r-badge b-approved">Approved</span>
                  </div>
                  <div class="qr-meta">
                    <span class="mono">{{ loan.member_no }}</span>
                    <span>·</span>
                    <span>{{ loan.loan_type_label }} · {{ loan.term_months }}mo</span>
                    <span>·</span>
                    <span class="mono">{{ loan.loan_no }}</span>
                  </div>
                </div>
                <div class="qr-amt">
                  <span class="mono">{{ peso(loan.amount) }}</span>
                  <div class="qr-amt-net">Net: {{ peso(netAmount(loan)) }}</div>
                </div>
                <button
                  v-if="selected?.id === loan.id"
                  class="r-btn r-btn-orange r-btn-sm"
                  @click.stop
                >Process →</button>
                <button
                  v-else
                  class="r-btn r-btn-outline r-btn-sm"
                  @click.stop="selected = loan"
                >Open</button>
              </div>
            </div>
          </div>

          <!-- Approval trail -->
          <div v-if="selected" class="r-card" style="margin-top:0">
            <div class="r-card-head">
              <div>
                <div class="r-card-title">Approval Trail · {{ selected.first_name }} {{ selected.last_name }}</div>
                <div class="r-card-sub">{{ selected.loan_no }} · {{ selected.loan_type_label }}</div>
              </div>
            </div>
            <div class="timeline">
              <div class="tl-item">
                <div class="tl-dot done">✓</div>
                <div class="tl-content">
                  <div class="tl-title">Application submitted</div>
                  <div class="tl-meta">{{ formatDate(selected.created_at) }} · by member</div>
                </div>
              </div>
              <div class="tl-item">
                <div class="tl-dot done">✓</div>
                <div class="tl-content">
                  <div class="tl-title">Reviewed by Loan Officer</div>
                  <div class="tl-meta">Documents verified · all signatures present</div>
                </div>
              </div>
              <div class="tl-item">
                <div class="tl-dot done">✓</div>
                <div class="tl-content">
                  <div class="tl-title">Approved</div>
                  <div class="tl-meta">{{ formatDate(selected.updated_at) }} · status changed to APPROVED</div>
                </div>
              </div>
              <div class="tl-item">
                <div class="tl-dot tl-active">→</div>
                <div class="tl-content">
                  <div class="tl-title">Awaiting Disbursement</div>
                  <div class="tl-meta">Ready to release</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: voucher card -->
        <div v-if="selected">
          <!-- Member header -->
          <div class="r-card">
            <div class="v-member">
              <div class="v-av" :style="{ background: avatarGrad(selected) }">{{ initials(selected) }}</div>
              <div>
                <div class="v-mname">{{ selected.first_name }} {{ selected.last_name }}</div>
                <div class="v-mmeta">{{ selected.member_no }} · {{ selected.company_name || 'CRS Holdings' }}</div>
              </div>
            </div>

            <!-- Voucher head -->
            <div class="v-head">
              <div class="v-head-row">
                <div>
                  <div class="v-title">Release Voucher</div>
                  <div class="v-no mono">{{ selected.loan_no }}</div>
                </div>
                <span class="r-badge b-approved">Approved</span>
              </div>
              <div class="v-amount-lbl">Gross Loan Amount</div>
              <div class="v-amount-big mono">{{ peso(selected.amount) }}</div>
            </div>

            <!-- Loan details -->
            <div class="v-section">
              <div class="v-sec-lbl">Loan Details</div>
              <div class="v-row"><span class="k">Loan Type</span><span class="v">{{ selected.loan_type_label }}</span></div>
              <div class="v-row"><span class="k">Term</span><span class="v">{{ selected.term_months }} months</span></div>
              <div class="v-row"><span class="k">Frequency</span><span class="v">{{ selected.frequency }}</span></div>
              <div class="v-row"><span class="k">Total Payable</span><span class="v mono">{{ peso(selected.total_payment) }}</span></div>
              <div class="v-row"><span class="k">Total Interest</span><span class="v mono">{{ peso(selected.total_interest) }}</span></div>
            </div>

            <!-- Computation -->
            <div class="v-section">
              <div class="v-sec-lbl">Disbursement Computation</div>
              <div class="v-row"><span class="k">Gross Amount</span><span class="v mono">{{ peso(selected.amount) }}</span></div>
              <div class="v-row deduct"><span class="k">Service Fee (1%)</span><span class="v mono">— {{ peso(serviceFee(selected)) }}</span></div>
              <div class="v-row deduct"><span class="k">Share Capital (1%)</span><span class="v mono">— {{ peso(shareCap(selected)) }}</span></div>
              <div class="v-row total">
                <span class="k">Net to Release</span>
                <span class="v mono" style="color:var(--green)">{{ peso(netAmount(selected)) }}</span>
              </div>
            </div>

            <!-- Disbursement method -->
            <div class="v-section">
              <div class="v-sec-lbl">Disbursement Method</div>
              <div class="pay-grid">
                <div
                  v-for="m in disburseMethods"
                  :key="m.key"
                  :class="['pay-opt', disburseMethod === m.key && 'on']"
                  @click="disburseMethod = m.key"
                >
                  <svg class="ico" viewBox="0 0 24 24" v-html="m.icon"></svg>
                  <div class="pay-l">{{ m.label }}</div>
                  <div class="pay-s">{{ m.sub }}</div>
                </div>
              </div>

              <div v-if="disburseMethod === 'bank'" class="r-fg">
                <div class="r-fgl">Bank / Account #</div>
                <input v-model="bankAccount" class="r-inp" placeholder="e.g. BDO 1234-5678-90" />
              </div>
              <div v-if="disburseMethod === 'check'" class="r-fg">
                <div class="r-fgl">Check Number</div>
                <input v-model="checkNo" class="r-inp" placeholder="e.g. Check #4872" />
              </div>
            </div>

            <!-- Action buttons -->
            <div class="v-section" style="border-bottom:none">
              <div style="display:flex; gap:8px">
                <button class="r-btn r-btn-outline r-btn-sm" style="flex:1; justify-content:center">
                  <svg class="ico" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                  Print Voucher
                </button>
                <button
                  class="r-btn r-btn-green r-btn-sm"
                  style="flex:1; justify-content:center"
                  :disabled="releasing"
                  @click="confirmRelease = true"
                >
                  <svg class="ico" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                  {{ releasing ? 'Releasing…' : 'Confirm Release' }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty right panel -->
        <div v-else class="r-card r-empty">
          <div style="text-align:center; padding:48px 24px">
            <svg style="width:44px;height:44px;stroke:var(--ink-faint);fill:none;stroke-width:1.5" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            <div style="font-size:15px; font-weight:600; color:var(--ink-soft); margin-top:12px">Select a loan to process</div>
            <div style="font-size:12px; color:var(--ink-muted); margin-top:4px">Choose an approved loan from the queue to generate a release voucher</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Confirm release modal -->
    <div v-if="confirmRelease && selected" class="r-overlay" @click.self="confirmRelease = false">
      <div class="r-modal">
        <div class="r-modal-head">
          <div style="font-size:17px; font-weight:800">Confirm Loan Release</div>
          <button class="r-btn r-btn-ghost" @click="confirmRelease = false">
            <svg class="ico" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div style="padding:22px">
          <div class="release-summary">
            <div class="rs-row"><span class="k">Borrower</span><span class="v">{{ selected.first_name }} {{ selected.last_name }}</span></div>
            <div class="rs-row"><span class="k">Loan Type</span><span class="v">{{ selected.loan_type_label }}</span></div>
            <div class="rs-row"><span class="k">Gross Amount</span><span class="v mono">{{ peso(selected.amount) }}</span></div>
            <div class="rs-row"><span class="k">Service Fee</span><span class="v mono">— {{ peso(serviceFee(selected)) }}</span></div>
            <div class="rs-row big"><span class="k">Net to Release</span><span class="v mono">{{ peso(netAmount(selected)) }}</span></div>
          </div>

          <div class="check-block">
            <input type="checkbox" v-model="chk1" id="chk1" />
            <label for="chk1">I have verified all documents and signatures are complete</label>
          </div>
          <div class="check-block">
            <input type="checkbox" v-model="chk2" id="chk2" />
            <label for="chk2">The disbursement method ({{ disburseMethods.find(m=>m.key===disburseMethod)?.label ?? 'Bank' }}) has been confirmed with the borrower</label>
          </div>
          <div class="check-block">
            <input type="checkbox" v-model="chk3" id="chk3" />
            <label for="chk3">I authorize the release of this loan under CRS Coop policies</label>
          </div>
        </div>
        <div class="r-modal-foot">
          <button class="r-btn r-btn-outline r-btn-sm" @click="confirmRelease = false">Cancel</button>
          <button
            class="r-btn r-btn-green r-btn-sm"
            :disabled="!chk1 || !chk2 || !chk3 || releasing"
            @click="doRelease"
          >
            {{ releasing ? 'Processing…' : 'Release Loan' }}
          </button>
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

const loans        = ref([])
const selected     = ref(null)
const loading      = ref(false)
const releasing    = ref(false)
const search       = ref('')
const filterType   = ref('')
const activeTab    = ref('Ready for Release')
const disburseMethod = ref('bank')
const bankAccount  = ref('')
const checkNo      = ref('')
const confirmRelease = ref(false)
const chk1 = ref(false)
const chk2 = ref(false)
const chk3 = ref(false)

const tabs = ['Ready for Release', 'Scheduled', 'Released', 'Hold']

const disburseMethods = [
  {
    key: 'bank',
    label: 'Bank Transfer',
    sub: 'InstaPay / PESONet',
    icon: '<rect x="2" y="6" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
  },
  {
    key: 'check',
    label: 'Check',
    sub: 'Manager\'s check',
    icon: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>',
  },
  {
    key: 'cash',
    label: 'Cash',
    sub: 'Over the counter',
    icon: '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
  },
]

const grossTotal = computed(() => loans.value.reduce((a, l) => a + Number(l.amount), 0))
const netTotal   = computed(() => loans.value.reduce((a, l) => a + netAmount(l), 0))
const releasedMTD  = ref(0)
const activeLoanCount = ref(0)

const filteredLoans = computed(() => {
  const q = search.value.trim().toLowerCase()
  return loans.value.filter(l => {
    if (filterType.value && !(l.loan_type_label ?? '').toLowerCase().includes(filterType.value.toLowerCase())) return false
    if (q && !`${l.first_name} ${l.last_name} ${l.member_no} ${l.loan_no}`.toLowerCase().includes(q)) return false
    return true
  })
})

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

function serviceFee(loan) { return Math.round(Number(loan.amount) * 0.01) }
function shareCap(loan)   { return Math.round(Number(loan.amount) * 0.01) }
function netAmount(loan)  { return Number(loan.amount) - serviceFee(loan) - shareCap(loan) }

function formatDate(d) {
  return d ? new Date(d).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'
}

async function fetchApproved() {
  loading.value = true
  try {
    const all = await api.getLoans()
    loans.value = all.filter(l => l.status === 'APPROVED')
    activeLoanCount.value = all.filter(l => l.status === 'ACTIVE').length
    releasedMTD.value = all.filter(l => l.status === 'ACTIVE').reduce((a, l) => a + Number(l.amount), 0)
  } catch (e) { error(e.message) }
  finally { loading.value = false }
}

async function doRelease() {
  if (!selected.value || releasing.value) return
  releasing.value = true
  try {
    await api.updateLoan(selected.value.id, { status: 'ACTIVE' })
    success(`Loan released to ${selected.value.first_name} ${selected.value.last_name}`)
    confirmRelease.value = false
    chk1.value = false; chk2.value = false; chk3.value = false
    selected.value = null
    await fetchApproved()
  } catch (e) { error(e.message) }
  finally { releasing.value = false }
}

onMounted(fetchApproved)
</script>

<style scoped>
.rel-wrap { display: flex; flex-direction: column; height: 100%; overflow: hidden; background: var(--surface); }

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
.topbar-right { display: flex; gap: 10px; }

/* Buttons */
.r-btn {
  display: inline-flex; align-items: center; gap: 6px;
  border-radius: 8px; font-size: 13px; font-weight: 600;
  cursor: pointer; border: none; font-family: inherit;
  transition: all var(--tx); padding: 8px 16px; white-space: nowrap;
}
.r-btn-outline { background: white; color: var(--ink-soft); border: 1.5px solid var(--border-dk); }
.r-btn-outline:hover { border-color: var(--crs-red); color: var(--crs-red); }
.r-btn-orange { background: var(--omni-orange); color: white; }
.r-btn-orange:hover { background: var(--omni-orange-lt); }
.r-btn-green { background: var(--green); color: white; }
.r-btn-green:hover { background: #155c30; }
.r-btn-ghost { background: transparent; color: var(--ink-muted); padding: 6px 8px; }
.r-btn-ghost:hover { background: var(--surface); color: var(--ink); }
.r-btn-sm { padding: 6px 12px; font-size: 12px; }
.r-btn:disabled { opacity: 0.45; cursor: not-allowed; }

.ico { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; }
.mono { font-family: var(--font-mono); }

/* Content */
.r-content {
  flex: 1; overflow-y: auto; overflow-x: hidden;
  padding: 20px 28px 48px;
  display: flex; flex-direction: column; gap: 14px;
}

/* Stat row */
.stat-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
.stat-card {
  background: white; border: 1px solid var(--border);
  border-radius: 12px; padding: 14px 16px; box-shadow: var(--shadow-xs);
}
.stat-label { font-size: 10.5px; font-weight: 700; color: var(--ink-muted); text-transform: uppercase; letter-spacing: 0.08em; }
.stat-value { font-size: 26px; font-weight: 800; margin-top: 4px; letter-spacing: -0.01em; color: var(--ink); }
.stat-value.red    { color: var(--crs-red); }
.stat-value.green  { color: var(--green); }
.stat-value.orange { color: var(--omni-orange); }
.stat-meta { font-size: 11.5px; color: var(--ink-muted); margin-top: 6px; }

/* Tabs */
.tab-strip { display: flex; gap: 4px; padding: 6px; background: var(--surface-2); border-radius: 10px; width: fit-content; }
.tab {
  padding: 7px 14px; font-size: 12.5px; font-weight: 600;
  color: var(--ink-muted); border-radius: 7px; cursor: pointer;
  display: flex; align-items: center; gap: 6px; transition: all var(--tx);
}
.tab.active { background: white; color: var(--ink); box-shadow: var(--shadow-xs); }
.tab-count { background: var(--surface-2); color: var(--ink-muted); padding: 1px 7px; border-radius: 99px; font-size: 11px; font-weight: 700; }
.tab.active .tab-count { background: var(--crs-red); color: white; }

/* Grid */
.grid-rel { display: grid; grid-template-columns: 1fr 440px; gap: 18px; align-items: start; }

/* Cards */
.r-card {
  background: white; border: 1px solid var(--border);
  border-radius: 12px; overflow: hidden; box-shadow: var(--shadow-xs);
  margin-bottom: 16px;
}
.r-card-head {
  padding: 13px 18px; border-bottom: 1px solid var(--border);
  display: flex; align-items: center; justify-content: space-between; gap: 10px;
}
.r-card-title { font-size: 14px; font-weight: 700; color: var(--ink); }
.r-card-sub   { font-size: 12px; color: var(--ink-muted); margin-top: 2px; }
.r-empty { display: flex; align-items: center; justify-content: center; min-height: 280px; }

/* Filter row */
.filter-row {
  display: flex; gap: 10px; padding: 12px 16px;
  border-bottom: 1px solid var(--border); align-items: center; flex-wrap: wrap;
}
.r-search {
  flex: 1; min-width: 200px; display: flex; align-items: center; gap: 8px;
  padding: 7px 12px; background: var(--surface); border-radius: 8px;
}
.r-search .ico { color: var(--ink-muted); flex-shrink: 0; }
.r-search input {
  flex: 1; border: none; outline: none; font-family: inherit;
  font-size: 13px; color: var(--ink); background: transparent;
}
.r-search input::placeholder { color: var(--ink-muted); }
.r-sel {
  padding: 7px 11px; font-size: 12.5px;
  border: 1px solid var(--border-dk); border-radius: 7px;
  background: white; font-family: inherit; color: var(--ink-soft); outline: none;
}
.r-sel:focus { border-color: var(--crs-red); }

/* Queue rows */
.queue-row {
  padding: 14px 18px; border-bottom: 1px solid var(--border);
  display: grid; grid-template-columns: 38px 1fr auto auto;
  gap: 12px; align-items: center; cursor: pointer; transition: background var(--tx);
}
.queue-row:hover { background: var(--surface); }
.queue-row.selected { background: var(--crs-red-pale); border-left: 3px solid var(--crs-red); padding-left: 15px; }
.queue-row:last-child { border-bottom: none; }
.qr-av {
  width: 38px; height: 38px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 700; color: white; flex-shrink: 0;
}
.qr-name { font-size: 14px; font-weight: 700; color: var(--ink); display: flex; align-items: center; gap: 6px; }
.qr-meta { font-size: 11.5px; color: var(--ink-muted); margin-top: 2px; display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
.qr-amt { text-align: right; font-weight: 700; font-size: 14px; color: var(--ink); }
.qr-amt-net { font-size: 11px; color: var(--green); margin-top: 1px; font-weight: 500; }

/* Badges */
.r-badge {
  display: inline-flex; align-items: center; padding: 2px 8px;
  border-radius: 99px; font-size: 10px; font-weight: 700;
  text-transform: uppercase; letter-spacing: 0.03em;
}
.b-approved { background: var(--green-pale); color: var(--green); }
.b-pending  { background: var(--amber-pale); color: var(--amber); }

/* Timeline */
.timeline { padding: 6px 18px 14px; }
.tl-item { display: flex; gap: 12px; padding: 10px 0; position: relative; }
.tl-item:not(:last-child)::after {
  content: ''; position: absolute; left: 11px; top: 28px; bottom: -6px;
  width: 2px; background: var(--border);
}
.tl-dot {
  width: 24px; height: 24px; border-radius: 50%; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  background: var(--surface-2); color: var(--ink-muted);
  position: relative; z-index: 1; font-size: 11px; font-weight: 700;
}
.tl-dot.done   { background: var(--green); color: white; }
.tl-active { background: var(--omni-orange); color: white; box-shadow: 0 0 0 4px var(--omni-orange-pale); }
.tl-content { flex: 1; }
.tl-title { font-size: 13px; font-weight: 600; color: var(--ink); }
.tl-meta  { font-size: 11.5px; color: var(--ink-muted); margin-top: 1px; }

/* Voucher card */
.v-member {
  display: flex; gap: 12px; align-items: center;
  padding: 14px 20px; background: var(--surface);
  border-bottom: 1px solid var(--border);
}
.v-av {
  width: 44px; height: 44px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 14px; color: white; flex-shrink: 0;
}
.v-mname { font-size: 14px; font-weight: 700; color: var(--ink); }
.v-mmeta { font-size: 11.5px; color: var(--ink-muted); margin-top: 2px; }
.v-head {
  padding: 16px 20px;
  background: linear-gradient(135deg, var(--crs-red) 0%, var(--crs-red-mid) 100%);
  color: white;
}
.v-head-row { display: flex; justify-content: space-between; align-items: flex-start; }
.v-title { font-size: 11px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; opacity: 0.85; }
.v-no { font-size: 13px; margin-top: 4px; opacity: 0.95; }
.v-amount-big { font-size: 28px; font-weight: 800; margin-top: 12px; letter-spacing: -0.02em; }
.v-amount-lbl { font-size: 11px; opacity: 0.75; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700; margin-top: 8px; }
.v-section { padding: 14px 20px; border-bottom: 1px dashed var(--border); }
.v-section:last-child { border-bottom: none; }
.v-sec-lbl { font-size: 10px; font-weight: 700; color: var(--ink-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px; }
.v-row { display: flex; justify-content: space-between; align-items: baseline; padding: 4px 0; font-size: 13px; }
.v-row .k { color: var(--ink-soft); }
.v-row .v { font-weight: 600; }
.v-row.total { border-top: 1.5px solid var(--ink); margin-top: 6px; padding-top: 8px; }
.v-row.total .k { font-weight: 700; color: var(--ink); }
.v-row.deduct .v { color: var(--crs-red); }

/* Disbursement picker */
.pay-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin-bottom: 12px; }
.pay-opt {
  border: 1.5px solid var(--border); padding: 10px 8px;
  border-radius: 9px; cursor: pointer; text-align: center;
  transition: all var(--tx); background: white;
}
.pay-opt:hover { border-color: var(--crs-red); }
.pay-opt.on { border-color: var(--crs-red); background: var(--crs-red-pale); }
.pay-opt .ico { width: 18px; height: 18px; color: var(--crs-red); display: block; margin: 0 auto 4px; }
.pay-l { font-size: 11.5px; font-weight: 700; color: var(--ink); }
.pay-s { font-size: 10px; color: var(--ink-muted); margin-top: 1px; }

/* Form fields */
.r-fg  { margin-top: 10px; }
.r-fgl { font-size: 11px; font-weight: 700; color: var(--ink-muted); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 5px; }
.r-inp {
  width: 100%; padding: 8px 11px; border: 1.5px solid var(--border);
  border-radius: 7px; font-family: inherit; font-size: 13px;
  background: white; color: var(--ink); outline: none;
}
.r-inp:focus { border-color: var(--crs-red); }

/* Spinner */
.r-spinner {
  width: 24px; height: 24px; border: 2.5px solid var(--border);
  border-top-color: var(--crs-red); border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Modal */
.r-overlay {
  position: fixed; inset: 0; background: rgba(26,27,31,0.5);
  backdrop-filter: blur(4px); z-index: 1000;
  display: flex; align-items: center; justify-content: center; padding: 20px;
}
.r-modal {
  background: white; border-radius: 14px; max-width: 560px;
  width: 100%; box-shadow: var(--shadow-md); overflow: hidden;
  animation: modal-in 0.2s ease;
}
@keyframes modal-in { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:none; } }
.r-modal-head {
  padding: 18px 22px; border-bottom: 1px solid var(--border);
  display: flex; justify-content: space-between; align-items: center;
}
.r-modal-foot {
  padding: 14px 22px; border-top: 1px solid var(--border);
  display: flex; justify-content: flex-end; gap: 8px;
  background: var(--surface);
}

/* Release summary */
.release-summary {
  background: var(--green-pale); border: 1px solid var(--green);
  border-radius: 10px; padding: 14px; margin-bottom: 16px;
}
.rs-row { display: flex; justify-content: space-between; padding: 3px 0; font-size: 13px; }
.rs-row .k { color: var(--ink-soft); }
.rs-row .v { font-family: var(--font-mono); font-weight: 700; }
.rs-row.big .v { font-size: 18px; color: var(--green); }

.check-block { display: flex; gap: 10px; padding: 10px 0; align-items: flex-start; }
.check-block input { margin-top: 3px; flex-shrink: 0; accent-color: var(--crs-red); }
.check-block label { font-size: 13px; line-height: 1.5; color: var(--ink-soft); cursor: pointer; }
</style>
