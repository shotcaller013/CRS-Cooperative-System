<template>
  <div class="view-wrap">

    <!-- Top bar -->
    <div class="topbar">
      <div>
        <div class="topbar-page">Members</div>
        <div class="topbar-sub">Master directory · feeds Shared Capital, Loans &amp; Deposits</div>
      </div>
      <div class="topbar-right">
        <button class="btn btn-secondary btn-sm" @click="showSettings = true">
          <svg class="ico" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 8.57 19.5a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.25 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 8.57a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.25a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.5 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
          Settings
        </button>
        <button class="btn btn-secondary btn-sm" @click="showImport = true">
          <svg class="ico" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          Import Excel
        </button>
        <button class="btn btn-primary btn-sm" @click="openAdd">
          <svg class="ico" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add Member
        </button>
      </div>
    </div>

    <div class="content">
      <!-- Stats -->
      <div class="stats-row">
        <div :class="['stat-card stat-clickable', filterStatus === '' && 'stat-active']" @click="setFilter('')">
          <div class="stat-label">Total Members</div>
          <div class="stat-value mono">{{ members.length }}</div>
          <div class="stat-sub">CRS Holdings + Bellshayce</div>
        </div>
        <div :class="['stat-card stat-clickable', filterStatus === 'ACTIVE' && 'stat-active']" @click="setFilter('ACTIVE')">
          <div class="stat-label">Active</div>
          <div class="stat-value mono" style="color:var(--crs-red)">{{ activeCount }}</div>
          <div class="stat-sub">paying capital regularly</div>
        </div>
        <div :class="['stat-card stat-clickable', filterStatus === 'PENDING' && 'stat-active-amber']" @click="setFilter('PENDING')">
          <div class="stat-label">Pending</div>
          <div class="stat-value mono" style="color:var(--amber)">{{ pendingMembersCount }}</div>
          <div class="stat-sub">awaiting board approval</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Capital Pool</div>
          <div class="stat-value mono" style="font-size:18px">{{ capitalPool }}</div>
          <div class="stat-sub">across active members</div>
        </div>
        <div :class="['stat-card stat-clickable', filterStatus === '__mtd__' && 'stat-active']" @click="setFilter('__mtd__')">
          <div class="stat-label">Onboarded MTD</div>
          <div class="stat-value mono">{{ mtdCount }}</div>
          <div class="stat-sub">{{ currentMonth }}</div>
        </div>
      </div>

      <!-- Toolbar -->
      <div class="toolbar">
        <div class="search-box">
          <svg class="search-ico" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input v-model="search" @input="onSearch" class="search-input" placeholder="Search by name, member ID, or company…" />
        </div>
        <button v-for="s in statusPills" :key="s.value"
          :class="['pill', filterStatus === s.value && 'pill-on']"
          @click="setFilter(s.value)">
          {{ s.label }}
          <span class="pill-count">{{ s.count }}</span>
        </button>
        <div style="flex:1"></div>
        <button class="btn btn-secondary btn-sm">
          <svg class="ico" viewBox="0 0 24 24"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
          Filters
        </button>
        <button class="btn btn-secondary btn-sm">
          <svg class="ico" viewBox="0 0 24 24"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
          Export
        </button>
      </div>

      <!-- Table -->
      <div class="panel">
        <div v-if="loading" class="empty-state"><div class="spinner"></div></div>
        <table v-else class="tbl">
          <thead>
            <tr>
              <th style="width:36px"><input type="checkbox" /></th>
              <th>Member ID</th>
              <th>Name</th>
              <th>Company / Department</th>
              <th>Joined</th>
              <th style="text-align:right">Shared Capital</th>
              <th style="text-align:right">Active Loan</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="m in pagedMembers" :key="m.id" @click="openDrawer(m)">
              <td @click.stop><input type="checkbox" /></td>
              <td><span class="m-id">{{ m.member_no }}</span></td>
              <td>
                <div class="m-cell">
                  <div class="m-av" :style="{ background: avatarGrad(m.last_name) }">
                    {{ m.first_name?.[0] }}{{ m.last_name?.[0] }}
                  </div>
                  <div>
                    <div class="m-name">{{ m.first_name }} {{ m.last_name }}</div>
                    <div class="m-meta">{{ m.position || '—' }}</div>
                  </div>
                </div>
              </td>
              <td>
                <div style="font-size:13px;font-weight:500">{{ m.company || '—' }}</div>
                <div class="m-meta">{{ m.department || '' }}</div>
              </td>
              <td class="m-meta">{{ formatDate(m.date_hired) }}</td>
              <td style="text-align:right"><span class="m-amt">{{ peso(m.share_capital) }}</span></td>
              <td style="text-align:right">
                <span v-if="m.active_loans > 0" class="m-amt" style="color:var(--omni-orange)">●</span>
                <span v-else class="m-meta">—</span>
              </td>
              <td>
                <span :class="statusPillClass(m.member_status)">
                  <span class="dot"></span>
                  {{ m.member_status || 'ACTIVE' }}
                </span>
              </td>
              <td @click.stop>
                <button class="btn btn-ghost btn-sm" @click="openDrawer(m)" style="padding:4px 8px">View</button>
              </td>
            </tr>
            <tr v-if="!loading && !filteredMembers.length">
              <td colspan="9" class="empty-state" style="padding:48px">No members found</td>
            </tr>
          </tbody>
        </table>
        <div class="tbl-foot">
          <div>Showing <strong>{{ pageStart }}–{{ pageEnd }}</strong> of <strong>{{ filteredMembers.length }}</strong> members</div>
          <div class="pager">
            <button @click="page > 1 && page--">‹</button>
            <button v-for="p in pageCount" :key="p"
              :class="p === page && 'pager-on'" @click="page = p">{{ p }}</button>
            <button @click="page < pageCount && page++">›</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Drawer backdrop -->
    <div :class="['drawer-bg', drawerOpen && 'drawer-bg-on']" @click="closeDrawer"></div>

    <!-- Detail Drawer -->
    <aside :class="['drawer', drawerOpen && 'drawer-on']">
      <template v-if="selectedMember">
        <header class="drawer-head">
          <div class="m-av d-av" :style="{ background: avatarGrad(selectedMember.last_name) }">
            {{ selectedMember.first_name?.[0] }}{{ selectedMember.last_name?.[0] }}
          </div>
          <div>
            <div class="drawer-name">{{ selectedMember.first_name }} {{ selectedMember.last_name }}</div>
            <div class="drawer-id">
              {{ selectedMember.member_no }} ·
              <span :class="statusPillClass(selectedMember.member_status)">
                <span class="dot"></span>{{ selectedMember.member_status || 'ACTIVE' }}
              </span>
            </div>
          </div>
          <button class="drawer-close" @click="closeDrawer">
            <svg class="ico" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </header>

        <nav class="drawer-tabs">
          <button v-for="tab in drawerTabs" :key="tab"
            :class="['dtab', activeDrawerTab === tab && 'dtab-on']"
            @click="activeDrawerTab = tab">
            {{ tab }}
            <span v-if="tab === 'Loans' && detailLoans.length"
              class="dtab-badge">{{ detailLoans.length }}</span>
          </button>
        </nav>

        <div class="drawer-body">

          <!-- Profile -->
          <template v-if="activeDrawerTab === 'Profile'">
            <div class="mini-stats">
              <div class="mini-stat">
                <div class="mini-lbl">Shared Capital</div>
                <div class="mini-val mono">{{ peso(selectedMember.share_capital) }}</div>
              </div>
              <div class="mini-stat">
                <div class="mini-lbl">Active Loan</div>
                <div class="mini-val mono" :style="detailLoans.length ? 'color:var(--omni-orange)' : ''">
                  {{ detailLoans.length ? detailLoans.length + ' loan(s)' : '—' }}
                </div>
              </div>
              <div class="mini-stat">
                <div class="mini-lbl">Monthly Salary</div>
                <div class="mini-val mono">{{ peso(selectedMember.monthly_salary) }}</div>
              </div>
            </div>

            <div class="section-title">Personal Information</div>
            <dl class="info-grid">
              <dt>Full Name</dt>
              <dd>{{ selectedMember.first_name }} {{ selectedMember.middle_name || '' }} {{ selectedMember.last_name }}</dd>
              <dt>Civil Status</dt><dd>{{ selectedMember.civil_status || '—' }}</dd>
              <dt>TIN</dt><dd class="mono">{{ selectedMember.tin || '—' }}</dd>
              <dt>SSS</dt><dd class="mono">{{ selectedMember.sss || '—' }}</dd>
            </dl>

            <div class="section-title">Contact</div>
            <dl class="info-grid">
              <dt>Mobile</dt><dd>{{ selectedMember.contact || '—' }}</dd>
              <dt>Email</dt><dd>{{ selectedMember.email || '—' }}</dd>
              <dt>Address</dt><dd>{{ selectedMember.address || '—' }}</dd>
            </dl>

            <div class="section-title">Employment</div>
            <dl class="info-grid">
              <dt>Company</dt><dd>{{ selectedMember.company || '—' }}</dd>
              <dt>Department</dt><dd>{{ selectedMember.department || '—' }}</dd>
              <dt>Position</dt><dd>{{ selectedMember.position || '—' }}</dd>
              <dt>Employee #</dt><dd class="mono">{{ selectedMember.member_no }}</dd>
              <dt>Date Hired</dt><dd>{{ formatDate(selectedMember.date_hired) }}</dd>
              <dt>Status</dt><dd>{{ selectedMember.status || '—' }}</dd>
              <dt>Supervisor</dt><dd>{{ selectedMember.supervisor || '—' }}</dd>
            </dl>

            <div class="section-title">Beneficiary</div>
            <dl class="info-grid">
              <dt>Name</dt><dd>{{ selectedMember.beneficiary_name || '—' }}</dd>
              <dt>Contact</dt><dd>{{ selectedMember.beneficiary_contact || '—' }}</dd>
            </dl>

            <div style="margin-top:18px; display:flex; gap:8px">
              <button class="btn btn-secondary btn-sm" @click="openEdit(selectedMember)">Edit Profile</button>
              <button class="btn btn-primary btn-sm" @click="openNewLoan(selectedMember)">+ New Loan Application</button>
            </div>
          </template>

          <!-- Shared Capital -->
          <template v-if="activeDrawerTab === 'Shared Capital'">
            <div class="mini-stats">
              <div class="mini-stat">
                <div class="mini-lbl">Current Balance</div>
                <div class="mini-val mono">{{ peso(selectedMember.share_capital) }}</div>
              </div>
              <div class="mini-stat">
                <div class="mini-lbl">Monthly</div>
                <div class="mini-val mono">₱500</div>
              </div>
              <div class="mini-stat">
                <div class="mini-lbl">Member Since</div>
                <div class="mini-val mono" style="font-size:13px">{{ formatDate(selectedMember.date_hired) }}</div>
              </div>
            </div>
            <div class="empty-state" style="padding:32px 0">
              <div style="font-size:13px; color:var(--ink-muted)">Capital ledger coming in next module update.</div>
            </div>
          </template>

          <!-- Loans -->
          <template v-if="activeDrawerTab === 'Loans'">
            <div v-if="loadingLoans" class="empty-state" style="padding:32px 0"><div class="spinner"></div></div>
            <template v-else>
              <div class="section-title">Active Loans</div>
              <table v-if="activeLoans.length" class="mtbl">
                <thead><tr><th>Ref #</th><th>Type</th><th>Amount</th><th>Term</th><th>Status</th></tr></thead>
                <tbody>
                  <tr v-for="l in activeLoans" :key="l.id">
                    <td class="mono" style="font-size:11px">{{ l.loan_no }}</td>
                    <td>{{ l.loan_type_label }}</td>
                    <td class="mono">{{ peso(l.amount) }}</td>
                    <td>{{ l.term_months }} mo</td>
                    <td><span :class="`badge badge-${(l.status||'draft').toLowerCase()}`">{{ l.status }}</span></td>
                  </tr>
                </tbody>
              </table>
              <div v-else class="empty-state" style="padding:24px 0; font-size:12.5px">No active loans</div>

              <div class="section-title" style="margin-top:18px">Loan History</div>
              <table v-if="closedLoans.length" class="mtbl">
                <thead><tr><th>Ref #</th><th>Type</th><th>Amount</th><th>Released</th><th>Status</th></tr></thead>
                <tbody>
                  <tr v-for="l in closedLoans" :key="l.id">
                    <td class="mono" style="font-size:11px">{{ l.loan_no }}</td>
                    <td>{{ l.loan_type_label }}</td>
                    <td class="mono">{{ peso(l.amount) }}</td>
                    <td>{{ formatDate(l.created_at) }}</td>
                    <td><span :class="`badge badge-${(l.status||'closed').toLowerCase()}`">{{ l.status }}</span></td>
                  </tr>
                </tbody>
              </table>
              <div v-else class="empty-state" style="padding:24px 0; font-size:12.5px">No loan history</div>

              <div style="margin-top:12px">
                <button class="btn btn-primary btn-sm" @click="openNewLoan(selectedMember)">+ New Loan Application</button>
              </div>
            </template>
          </template>

          <!-- Deposits -->
          <template v-if="activeDrawerTab === 'Deposits'">
            <div class="info-banner" style="background:var(--blue-pale);border:1px solid #C7D6F2;border-radius:10px;padding:14px;font-size:12.5px;color:var(--blue);display:flex;gap:10px;margin-top:8px">
              <svg class="ico" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:2px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
              <div>
                <div style="font-weight:700;margin-bottom:2px">Deposits module · launching Q3 2026</div>
                Member balances are held here for reference. Full deposit ledger, withdrawals, and time-deposit certificates ship with the next phase.
              </div>
            </div>
          </template>

          <!-- Attachments -->
          <template v-if="activeDrawerTab === 'Attachments'">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
              <div class="section-title" style="margin:0">Member Documents</div>
            </div>
            <div class="empty-state" style="padding:32px 0; font-size:12.5px">No attachments on file.</div>
          </template>

          <!-- Activity -->
          <template v-if="activeDrawerTab === 'Activity'">
            <div class="empty-state" style="padding:32px 0; text-align:center">
              <div style="font-weight:700; color:var(--ink); margin-bottom:6px">Audit log</div>
              <div style="font-size:12.5px; color:var(--ink-muted)">Profile edits, attachments, capital changes, loan events.</div>
            </div>
          </template>

        </div>
      </template>
    </aside>

    <!-- Add/Edit Member Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal" style="max-width:920px; margin-top:0">
        <div class="modal-header">
          <div>
            <div class="modal-title">{{ editingMember?.id ? 'Edit Member' : 'Add New Member' }}</div>
            <div style="font-size:12px;color:var(--ink-muted);margin-top:2px">Required fields pull through to Loan, Capital &amp; Deposit ledgers</div>
          </div>
          <button class="btn btn-ghost btn-sm" @click="showModal = false">✕</button>
        </div>
        <div class="modal-body">
          <!-- Section 1: Personal -->
          <div class="form-section">
            <div class="form-section-title"><span class="sec-num">1</span>Personal Information</div>
            <div class="form-2col">
              <div class="form-group">
                <label class="form-label">Last Name <span class="req">*</span></label>
                <input v-model="form.last_name" class="form-input" placeholder="Santos" />
              </div>
              <div class="form-group">
                <label class="form-label">First Name <span class="req">*</span></label>
                <input v-model="form.first_name" class="form-input" placeholder="Maria Cristina" />
              </div>
              <div class="form-group">
                <label class="form-label">Middle Name</label>
                <input v-model="form.middle_name" class="form-input" />
              </div>
              <div class="form-group">
                <label class="form-label">Civil Status</label>
                <select v-model="form.civil_status" class="form-select">
                  <option value="">—</option>
                  <option>Single</option><option>Married</option>
                  <option>Widowed</option><option>Separated</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">TIN</label>
                <input v-model="form.tin" class="form-input mono" placeholder="000-000-000-000" />
              </div>
              <div class="form-group">
                <label class="form-label">SSS #</label>
                <input v-model="form.sss" class="form-input mono" placeholder="00-0000000-0" />
              </div>
            </div>
          </div>

          <!-- Section 2: Contact -->
          <div class="form-section">
            <div class="form-section-title"><span class="sec-num">2</span>Contact</div>
            <div class="form-2col">
              <div class="form-group">
                <label class="form-label">Mobile <span class="req">*</span></label>
                <input v-model="form.contact" class="form-input" placeholder="+63 9XX XXX XXXX" />
              </div>
              <div class="form-group">
                <label class="form-label">Email</label>
                <input v-model="form.email" class="form-input" type="email" />
              </div>
              <div class="form-group" style="grid-column:span 2">
                <label class="form-label">Home Address <span class="req">*</span></label>
                <input v-model="form.address" class="form-input" placeholder="Street, Barangay, City, Province, ZIP" />
              </div>
            </div>
          </div>

          <!-- Section 3: Employment -->
          <div class="form-section">
            <div class="form-section-title"><span class="sec-num">3</span>Employment</div>
            <div class="form-2col">
              <div class="form-group">
                <label class="form-label">Company <span class="req">*</span></label>
                <select v-model="form.company" class="form-select">
                  <option value="">Select…</option>
                  <option>CRS Holdings</option>
                  <option>Bellshayce Foods Corporation</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Department</label>
                <input v-model="form.department" class="form-input" />
              </div>
              <div class="form-group">
                <label class="form-label">Position</label>
                <input v-model="form.position" class="form-input" />
              </div>
              <div class="form-group">
                <label class="form-label">Direct Supervisor</label>
                <input v-model="form.supervisor" class="form-input" />
              </div>
              <div class="form-group">
                <label class="form-label">Date Hired</label>
                <input v-model="form.date_hired" class="form-input" type="date" />
              </div>
              <div class="form-group">
                <label class="form-label">Employment Status</label>
                <select v-model="form.status" class="form-select">
                  <option>REGULAR</option><option>PROBI</option>
                  <option>SUSPENDED</option><option>INACTIVE</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Monthly Salary (₱)</label>
                <input v-model="form.monthly_salary" class="form-input mono" type="number" />
              </div>
            </div>
          </div>

          <!-- Section 4: Co-op Setup -->
          <div class="form-section">
            <div class="form-section-title"><span class="sec-num">4</span>Co-op Setup</div>
            <div class="form-2col">
              <div class="form-group">
                <label class="form-label">Member #</label>
                <input v-model="form.member_no" class="form-input mono" placeholder="CRS-00XXX" :disabled="!!editingMember?.id" />
              </div>
              <div class="form-group">
                <label class="form-label">Member Status</label>
                <select v-model="form.member_status" class="form-select">
                  <option>ACTIVE</option><option>PENDING</option>
                  <option>INACTIVE</option><option>SUSPENDED</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Initial Share Capital (₱)</label>
                <input v-model="form.share_capital" class="form-input mono" type="number" />
              </div>
              <div class="form-group">
                <label class="form-label">Beneficiary Name</label>
                <input v-model="form.beneficiary_name" class="form-input" placeholder="Spouse / next of kin" />
              </div>
              <div class="form-group">
                <label class="form-label">Beneficiary Contact</label>
                <input v-model="form.beneficiary_contact" class="form-input" placeholder="+63 9XX XXX XXXX" />
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="showModal = false">Cancel</button>
          <button class="btn btn-primary" @click="saveMember" :disabled="saving">
            {{ saving ? 'Saving…' : (editingMember?.id ? 'Save Changes' : 'Save &amp; Submit') }}
          </button>
        </div>
      </div>
    </div>

    <!-- ── Import Excel Modal ──────────────────────────── -->
    <div v-if="showImport" class="modal-overlay" @click.self="showImport = false">
      <div class="modal" style="max-width:920px; margin-top:0">
        <div class="modal-header">
          <div style="width:38px;height:38px;border-radius:8px;background:var(--green-pale);color:var(--green);display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg class="ico" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
          </div>
          <div style="flex:1">
            <div class="modal-title">Import Members from Excel</div>
            <div style="font-size:12px;color:var(--ink-muted);margin-top:2px">Migrate your existing roster · we'll auto-detect columns and let you review before commit</div>
          </div>
          <button class="btn btn-ghost btn-sm" @click="showImport = false">✕</button>
        </div>

        <!-- Steps bar -->
        <div class="import-steps">
          <div class="istep" :class="importStep >= 1 && 'istep-done'">
            <div class="istep-num" :class="importStep > 1 ? 'istep-done-num' : importStep === 1 ? 'istep-on' : ''">
              {{ importStep > 1 ? '✓' : '1' }}
            </div>
            Upload
          </div>
          <div class="istep-line"></div>
          <div class="istep" :class="importStep >= 2 && 'istep-active'">
            <div class="istep-num" :class="importStep === 2 ? 'istep-on' : ''">2</div>
            Map columns
          </div>
          <div class="istep-line"></div>
          <div class="istep">
            <div class="istep-num" :class="importStep === 3 ? 'istep-on' : ''">3</div>
            Review &amp; commit
          </div>
        </div>

        <div class="modal-body">
          <!-- Step 1: Upload -->
          <template v-if="importStep === 1">
            <div class="drop-zone" @click="$refs.xlsxInput.click()" @dragover.prevent @drop.prevent="handleDrop">
              <div class="drop-ico">
                <svg style="width:24px;height:24px;stroke:var(--crs-red);fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
              </div>
              <div class="drop-title">Drop your Excel file here</div>
              <div class="drop-sub">XLSX or CSV · up to 50 MB · first row must be headers</div>
              <button class="btn btn-secondary btn-sm drop-btn" style="margin-top:12px" @click.stop="$refs.xlsxInput.click()">Browse files</button>
              <input ref="xlsxInput" type="file" accept=".xlsx,.csv" style="display:none" @change="handleFileSelect" />
            </div>
            <div style="margin-top:14px;padding:12px;background:var(--blue-pale);border:1px solid #C7D6F2;border-radius:8px;font-size:12px;color:var(--blue);display:flex;gap:8px">
              <svg class="ico" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
              <div>We auto-detect column names and pre-fill the mapping. You can adjust before committing to the database.</div>
            </div>
          </template>

          <!-- Step 2: Map columns -->
          <template v-if="importStep === 2">
            <div style="display:flex;gap:12px;align-items:center;padding:12px 14px;background:var(--green-pale);border:1px solid #BFE3CC;border-radius:10px;margin-bottom:18px">
              <div style="width:32px;height:38px;border-radius:4px;background:#1A6B3C;color:white;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;flex-shrink:0">XLSX</div>
              <div style="flex:1">
                <div style="font-size:13px;font-weight:700">{{ importFile?.name || 'members.xlsx' }}</div>
                <div style="font-size:11.5px;color:var(--green);font-weight:600">File uploaded · awaiting column mapping</div>
              </div>
              <button class="btn btn-secondary btn-sm" @click="importStep = 1">Replace file</button>
            </div>
            <div style="font-size:11px;font-weight:700;color:var(--ink-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:8px">Column Mapping</div>
            <div style="font-size:12px;color:var(--ink-muted);margin-bottom:12px">Match each column from your spreadsheet to a member field.</div>
            <div class="panel">
              <table class="map-tbl">
                <thead><tr><th style="width:32%">Spreadsheet Column</th><th style="width:32%">Sample Value</th><th style="width:28%">→ Map To</th><th>Status</th></tr></thead>
                <tbody>
                  <tr v-for="col in columnMappings" :key="col.src">
                    <td><span class="src-col">{{ col.src }}</span></td>
                    <td class="preview-cell">{{ col.sample }}</td>
                    <td>
                      <select v-model="col.target" class="form-select" style="padding:5px 8px;font-size:12px">
                        <option value="">— Skip —</option>
                        <option v-for="f in memberFields" :key="f" :value="f">{{ f }}</option>
                      </select>
                    </td>
                    <td>
                      <span class="map-status" :class="col.target ? 'map-ok' : 'map-skip'">
                        {{ col.target ? '✓ Auto' : 'Skip' }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </template>

          <!-- Step 3: Review -->
          <template v-if="importStep === 3">
            <div class="mig-grid">
              <div class="mig-stat success"><div class="mig-val">—</div><div class="mig-lbl">Ready to import</div></div>
              <div class="mig-stat warn"><div class="mig-val">—</div><div class="mig-lbl">Need review</div></div>
              <div class="mig-stat error"><div class="mig-val">—</div><div class="mig-lbl">Errors / duplicates</div></div>
              <div class="mig-stat"><div class="mig-val">—</div><div class="mig-lbl">Total rows</div></div>
            </div>
            <div style="padding:32px;text-align:center;color:var(--ink-muted);font-size:13px">Review &amp; commit functionality coming soon.</div>
          </template>
        </div>

        <div class="modal-footer">
          <div style="font-size:11.5px;color:var(--ink-muted)">Step <strong>{{ importStep }} of 3</strong></div>
          <div style="display:flex;gap:8px">
            <button class="btn btn-secondary" @click="showImport = false">Cancel</button>
            <button v-if="importStep > 1" class="btn btn-secondary" @click="importStep--">← Back</button>
            <button v-if="importStep < 3" class="btn btn-primary" :disabled="importStep === 1" @click="importStep++">Continue →</button>
            <button v-if="importStep === 3" class="btn btn-primary">Commit Import</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Settings Modal ──────────────────────────────── -->
    <div v-if="showSettings" class="modal-overlay" @click.self="showSettings = false">
      <div class="modal" style="max-width:680px; margin-top:0">
        <div class="modal-header">
          <div style="width:38px;height:38px;border-radius:8px;background:var(--purple-pale);color:var(--purple);display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg class="ico" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 8.57 19.5a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.25 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 8.57a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.25a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.5 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
          </div>
          <div style="flex:1">
            <div class="modal-title">Member Attachment Types</div>
            <div style="font-size:12px;color:var(--ink-muted);margin-top:2px">Define which documents members must / can upload to their profile</div>
          </div>
          <button class="btn btn-ghost btn-sm" @click="showSettings = false">✕</button>
        </div>
        <div class="modal-body">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
            <div style="font-size:11px;font-weight:700;color:var(--ink-muted);text-transform:uppercase;letter-spacing:0.06em">{{ attachTypes.length }} types · {{ attachTypes.filter(t=>t.required==='Required').length }} required</div>
            <button class="btn btn-secondary btn-sm">
              <svg class="ico" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Add Type
            </button>
          </div>

          <div v-for="t in attachTypes" :key="t.name" class="settings-row">
            <svg class="ico" style="color:var(--ink-faint);flex-shrink:0" viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            <div style="flex:1">
              <div class="settings-name">{{ t.name }}</div>
              <div class="settings-meta">{{ t.meta }}</div>
            </div>
            <span class="pill-status" :class="t.required === 'Required' ? 'pill-suspended' : 'pill-inactive'">{{ t.required }}</span>
            <label class="tog">
              <input type="checkbox" v-model="t.enabled" />
              <span class="tog-track"><span class="tog-thumb"></span></span>
            </label>
          </div>

          <div style="margin-top:14px;padding:12px;background:var(--surface);border:1px solid var(--border);border-radius:8px;font-size:12px;color:var(--ink-muted);display:flex;gap:8px">
            <svg class="ico" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            <div>Required types must be uploaded before a member can be marked Active. Conditional types only appear when the trigger condition is met.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="showSettings = false">Cancel</button>
          <button class="btn btn-primary" @click="showSettings = false">Save Changes</button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../composables/useApi'
import { useToast } from '../composables/useToast'
import { peso } from '../composables/useLoanCalc'
import { useMembersStats } from '../composables/useMembersStats'

const router = useRouter()
const { success, error } = useToast()
const { pendingCount: sharedPendingCount } = useMembersStats()

// Core state
const members = ref([])
const selectedMember = ref(null)
const detailLoans = ref([])
const search = ref('')
const filterStatus = ref('')
const loading = ref(false)
const loadingLoans = ref(false)
const showModal = ref(false)
const showImport = ref(false)
const showSettings = ref(false)
const saving = ref(false)
const editingMember = ref(null)
const drawerOpen = ref(false)
const activeDrawerTab = ref('Profile')
const drawerTabs = ['Profile', 'Shared Capital', 'Loans', 'Deposits', 'Attachments', 'Activity']
const page = ref(1)
const perPage = 10
const form = ref({})

// Import wizard state
const importStep = ref(1)
const importFile = ref(null)
const columnMappings = ref([])
const memberFields = ['Member ID','Last Name','First Name','Middle Name','Birthdate','Mobile','Email','Home Address','Company','Department / Position','Employee #','Date Hired','Monthly Salary','Shared Capital (opening balance)','Monthly Capital Deduction','Date Joined','Status']

// Settings attachment types
const attachTypes = ref([
  { name: 'Government ID', meta: 'JPG, PNG, PDF · max 10 MB · expiry tracked · multi-upload (up to 2)', required: 'Required', enabled: true },
  { name: 'Membership Application Form', meta: 'PDF only · max 5 MB · single file · signed copy', required: 'Required', enabled: true },
  { name: '2x2 Photo', meta: 'JPG, PNG · max 2 MB · single file', required: 'Required', enabled: true },
  { name: 'Birth Certificate (PSA)', meta: 'PDF, JPG · max 5 MB · single file', required: 'Optional', enabled: true },
  { name: 'Marriage Certificate', meta: 'PDF, JPG · max 5 MB · only if Married', required: 'Conditional', enabled: true },
  { name: 'NBI Clearance', meta: 'PDF, JPG · max 5 MB · expiry tracked · renew yearly', required: 'Optional', enabled: true },
  { name: 'Latest Payslip', meta: 'PDF, JPG · max 3 MB · refresh every 6 months', required: 'Optional', enabled: true },
])

const currentMonth = new Date().toLocaleDateString('en-PH', { month: 'long', year: 'numeric' })
const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-PH') : '—'
const isThisMonth = (d) => {
  if (!d) return false
  const dt = new Date(d), now = new Date()
  return dt.getMonth() === now.getMonth() && dt.getFullYear() === now.getFullYear()
}

const avatarGrads = [
  'linear-gradient(135deg,#E8591A,#8B1A1A)',
  'linear-gradient(135deg,#1A3A8B,#2980B9)',
  'linear-gradient(135deg,#1A6B3C,#27AE60)',
  'linear-gradient(135deg,#5B2AA7,#8E44AD)',
  'linear-gradient(135deg,#92620A,#D35400)',
]
const avatarGrad = (name) => avatarGrads[(name?.charCodeAt(0) || 0) % avatarGrads.length]

// Computed stats
const activeCount = computed(() => members.value.filter(m => (m.member_status || 'ACTIVE').toUpperCase() === 'ACTIVE').length)
const pendingMembersCount = computed(() => members.value.filter(m => (m.member_status || '').toUpperCase() === 'PENDING').length)
const mtdCount = computed(() => members.value.filter(m => isThisMonth(m.created_at)).length)
const capitalPool = computed(() => {
  const total = members.value.reduce((s, m) => s + (parseFloat(m.share_capital) || 0), 0)
  if (total >= 1_000_000) return `₱${(total / 1_000_000).toFixed(2)}M`
  if (total >= 1_000) return `₱${(total / 1_000).toFixed(1)}K`
  return `₱${total.toLocaleString()}`
})

// Keep shared badge in sync
watch(pendingMembersCount, (val) => { sharedPendingCount.value = val }, { immediate: true })

const filteredMembers = computed(() => {
  let list = members.value
  if (filterStatus.value === '__mtd__') {
    list = list.filter(m => isThisMonth(m.created_at))
  } else if (filterStatus.value) {
    list = list.filter(m => (m.member_status || 'ACTIVE').toUpperCase() === filterStatus.value.toUpperCase())
  }
  if (search.value) {
    const q = search.value.toLowerCase()
    list = list.filter(m =>
      `${m.first_name} ${m.last_name}`.toLowerCase().includes(q) ||
      (m.member_no || '').toLowerCase().includes(q) ||
      (m.company || '').toLowerCase().includes(q)
    )
  }
  return list
})

const pageCount = computed(() => Math.max(1, Math.ceil(filteredMembers.value.length / perPage)))
const pageStart = computed(() => filteredMembers.value.length ? (page.value - 1) * perPage + 1 : 0)
const pageEnd = computed(() => Math.min(page.value * perPage, filteredMembers.value.length))
const pagedMembers = computed(() => filteredMembers.value.slice((page.value - 1) * perPage, page.value * perPage))

const statusPills = computed(() => [
  { label: 'All', value: '', count: members.value.length },
  { label: 'Active', value: 'ACTIVE', count: activeCount.value },
  { label: 'Pending', value: 'PENDING', count: pendingMembersCount.value },
  { label: 'Inactive', value: 'INACTIVE', count: members.value.filter(m => (m.member_status || '').toUpperCase() === 'INACTIVE').length },
  { label: 'Suspended', value: 'SUSPENDED', count: members.value.filter(m => (m.member_status || '').toUpperCase() === 'SUSPENDED').length },
])

const activeLoans = computed(() => detailLoans.value.filter(l => !['CLOSED', 'REJECTED'].includes((l.status || '').toUpperCase())))
const closedLoans = computed(() => detailLoans.value.filter(l => ['CLOSED', 'REJECTED'].includes((l.status || '').toUpperCase())))

function statusPillClass(s) {
  const map = { ACTIVE: 'pill-status pill-active', PENDING: 'pill-status pill-pending', INACTIVE: 'pill-status pill-inactive', SUSPENDED: 'pill-status pill-suspended' }
  return map[(s || 'ACTIVE').toUpperCase()] || 'pill-status pill-active'
}

function setFilter(val) { filterStatus.value = val; page.value = 1 }
function onSearch() { page.value = 1 }

function handleFileSelect(e) {
  importFile.value = e.target.files[0]
  if (importFile.value) importStep.value = 2
}
function handleDrop(e) {
  importFile.value = e.dataTransfer.files[0]
  if (importFile.value) importStep.value = 2
}

async function fetchMembers() {
  loading.value = true
  try { members.value = await api.getMembers() }
  catch (e) { error(e.message) }
  finally { loading.value = false }
}

async function openDrawer(m) {
  selectedMember.value = m
  activeDrawerTab.value = 'Profile'
  detailLoans.value = []
  drawerOpen.value = true
  loadingLoans.value = true
  try {
    const full = await api.getMember(m.id)
    detailLoans.value = full.loans || []
  } catch {}
  finally { loadingLoans.value = false }
}

function closeDrawer() { drawerOpen.value = false }

function openAdd() {
  editingMember.value = null
  form.value = { status: 'PROBI', member_status: 'ACTIVE', monthly_salary: 0, share_capital: 0 }
  showModal.value = true
}

function openEdit(m) {
  editingMember.value = m
  form.value = { ...m }
  showModal.value = true
  drawerOpen.value = false
}

function openNewLoan(m) {
  router.push(m ? { name: 'loans', query: { member_id: m.id } } : { name: 'loans' })
}

async function saveMember() {
  saving.value = true
  try {
    if (editingMember.value?.id) {
      await api.updateMember(editingMember.value.id, form.value)
      success('Member updated!')
    } else {
      await api.createMember(form.value)
      success('Member added!')
    }
    showModal.value = false
    await fetchMembers()
  } catch (e) { error(e.message) }
  finally { saving.value = false }
}

onMounted(fetchMembers)
</script>

<style scoped>
.view-wrap { display:flex; flex-direction:column; height:100%; overflow:hidden; background:var(--surface); }

/* Topbar */
.topbar {
  background:white; border-bottom:1px solid var(--border);
  padding:0 28px; height:58px; display:flex; align-items:center;
  gap:16px; flex-shrink:0; box-shadow:var(--shadow-xs);
}
.topbar-page { font-size:18px; font-weight:700; color:var(--ink); }
.topbar-sub { font-size:12px; color:var(--ink-muted); margin-top:1px; }
.topbar-right { margin-left:auto; display:flex; gap:8px; align-items:center; }
.ico { width:14px; height:14px; stroke:currentColor; fill:none; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }

/* Content */
.content { flex:1; overflow-y:auto; padding:20px 28px 48px; display:flex; flex-direction:column; gap:14px; }

/* Stats */
.stats-row { display:grid; grid-template-columns:repeat(5,1fr); gap:12px; }
/* Toolbar */
.toolbar { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.search-box { flex:0 0 320px; position:relative; }
.search-input {
  width:100%; padding:8px 12px 8px 32px;
  border:1.5px solid var(--border); border-radius:8px;
  font-size:13px; outline:none; font-family:inherit;
  background:white; color:var(--ink);
}
.search-input:focus { border-color:var(--crs-red); }
.search-ico {
  position:absolute; left:10px; top:50%; transform:translateY(-50%);
  width:14px; height:14px; stroke:var(--ink-muted); fill:none;
  stroke-width:2; stroke-linecap:round; stroke-linejoin:round;
}
.pill {
  padding:6px 12px; border:1.5px solid var(--border); border-radius:99px;
  background:white; font-size:12px; font-weight:600; color:var(--ink-soft);
  cursor:pointer; display:inline-flex; align-items:center; gap:4px;
  font-family:inherit; transition:all var(--tx);
}
.pill:hover { border-color:var(--crs-red); color:var(--crs-red); }
.pill-on { background:var(--crs-red); border-color:var(--crs-red); color:white; }
.pill-count { opacity:0.65; font-size:11px; }

/* Panel / table */
.panel { background:white; border:1px solid var(--border); border-radius:12px; overflow:hidden; box-shadow:var(--shadow-xs); }
.tbl { width:100%; border-collapse:collapse; }
.tbl thead { background:var(--surface); }
.tbl th {
  text-align:left; padding:11px 14px;
  font-size:10.5px; font-weight:700; color:var(--ink-muted);
  text-transform:uppercase; letter-spacing:0.06em;
  border-bottom:1px solid var(--border); white-space:nowrap;
}
.tbl td { padding:11px 14px; border-bottom:1px solid var(--border); font-size:13px; vertical-align:middle; color:var(--ink); }
.tbl tbody tr { cursor:pointer; transition:background var(--tx); }
.tbl tbody tr:hover { background:var(--surface); }
.tbl tbody tr:last-child td { border-bottom:none; }
.m-id { font-family:var(--font-mono); font-size:11px; color:var(--ink-muted); font-weight:600; }
.m-cell { display:flex; align-items:center; gap:10px; }
.m-av {
  width:34px; height:34px; border-radius:50%; flex-shrink:0;
  display:flex; align-items:center; justify-content:center;
  font-size:12px; font-weight:700; color:white;
}
.m-name { font-size:13px; font-weight:700; color:var(--ink); }
.m-meta { font-size:11px; color:var(--ink-muted); margin-top:1px; }
.m-amt { font-family:var(--font-mono); font-weight:700; font-size:13px; color:var(--ink); }
.pill-status { display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:99px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; white-space:nowrap; }
.pill-active   { background:var(--green-pale);  color:var(--green); }
.pill-inactive { background:var(--surface-2);   color:var(--ink-muted); }
.pill-pending  { background:var(--amber-pale);  color:var(--amber); }
.pill-suspended{ background:var(--crs-red-pale);color:var(--crs-red); }
.dot { width:6px; height:6px; border-radius:50%; background:currentColor; flex-shrink:0; }

.tbl-foot {
  padding:11px 14px; display:flex; justify-content:space-between; align-items:center;
  font-size:12px; color:var(--ink-muted); border-top:1px solid var(--border); background:var(--surface);
}
.pager { display:flex; gap:5px; }
.pager button {
  padding:4px 10px; font-size:11px; border:1px solid var(--border-dk);
  background:white; border-radius:6px; font-weight:600; cursor:pointer;
  color:var(--ink-soft); font-family:inherit; transition:all var(--tx);
}
.pager button:hover { border-color:var(--crs-red); color:var(--crs-red); }
.pager-on { background:var(--crs-red) !important; border-color:var(--crs-red) !important; color:white !important; }

/* Drawer */
.drawer-bg { position:fixed; inset:0; background:rgba(20,20,30,0.42); z-index:200; display:none; }
.drawer-bg-on { display:block; }
.drawer {
  position:fixed; top:0; right:0; bottom:0; width:720px; max-width:100vw;
  background:white; box-shadow:0 16px 50px rgba(0,0,0,0.18); z-index:201;
  transform:translateX(100%); transition:transform 0.24s ease;
  display:flex; flex-direction:column;
}
.drawer-on { transform:translateX(0); }
.drawer-head {
  padding:18px 24px; border-bottom:1px solid var(--border);
  display:flex; align-items:center; gap:14px; flex-shrink:0;
}
.d-av { width:52px; height:52px; font-size:16px; border-radius:50%; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-weight:700; color:white; }
.drawer-name { font-size:18px; font-weight:700; color:var(--ink); }
.drawer-id { font-size:11px; color:var(--ink-muted); margin-top:3px; display:flex; align-items:center; gap:6px; }
.drawer-close {
  margin-left:auto; width:34px; height:34px; border-radius:50%;
  background:var(--surface); border:none; display:flex;
  align-items:center; justify-content:center; cursor:pointer; color:var(--ink-soft);
}
.drawer-close:hover { background:var(--surface-2); }
.drawer-tabs { display:flex; padding:0 24px; border-bottom:1px solid var(--border); flex-shrink:0; }
.dtab {
  padding:11px 14px; font-size:12.5px; font-weight:600; color:var(--ink-muted);
  cursor:pointer; border:none; background:none; border-bottom:2px solid transparent;
  font-family:inherit; white-space:nowrap; transition:all var(--tx);
}
.dtab:hover { color:var(--ink); }
.dtab-on { color:var(--crs-red); border-bottom-color:var(--crs-red); }
.dtab-badge {
  display:inline-block; background:var(--crs-red); color:white;
  font-size:9px; padding:1px 5px; border-radius:99px; margin-left:4px;
}
.drawer-body { flex:1; overflow-y:auto; padding:22px 24px; }

/* Inside drawer */
.mini-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:18px; }
.mini-stat { background:var(--surface); border:1px solid var(--border); border-radius:10px; padding:11px 13px; }
.mini-lbl { font-size:10px; font-weight:700; color:var(--ink-muted); text-transform:uppercase; letter-spacing:0.06em; }
.mini-val { font-size:16px; font-weight:700; margin-top:2px; color:var(--ink); }
.section-title { font-size:11px; font-weight:700; color:var(--ink-muted); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:10px; margin-top:18px; }
.section-title:first-child { margin-top:0; }
.info-grid { display:grid; grid-template-columns:130px 1fr; gap:6px 16px; font-size:13px; }
.info-grid dt { color:var(--ink-muted); font-size:12px; padding-top:1px; }
.info-grid dd { margin:0; font-weight:500; color:var(--ink); }
.mtbl { width:100%; border-collapse:collapse; font-size:12.5px; }
.mtbl th { text-align:left; padding:7px 10px; font-size:9.5px; font-weight:700; color:var(--ink-muted); text-transform:uppercase; letter-spacing:0.06em; border-bottom:1px solid var(--border); background:var(--surface); }
.mtbl td { padding:9px 10px; border-bottom:1px solid var(--border); color:var(--ink); }
.mtbl tr:last-child td { border-bottom:none; }

/* Modal */
.form-section { margin-bottom:22px; }
.form-section-title {
  font-size:12px; font-weight:700; color:var(--ink); text-transform:uppercase;
  letter-spacing:0.06em; padding-bottom:8px; border-bottom:1px solid var(--border);
  margin-bottom:14px; display:flex; align-items:center; gap:8px;
}
.sec-num {
  width:22px; height:22px; border-radius:50%; background:var(--crs-red);
  color:white; font-size:11px; display:flex; align-items:center;
  justify-content:center; font-family:var(--font-mono); flex-shrink:0;
}
.form-2col { display:grid; grid-template-columns:1fr 1fr; gap:14px 20px; }
.req { color:var(--crs-red); }

/* Clickable stat cards */
.stat-clickable { cursor:pointer; transition:all var(--tx); }
.stat-clickable:hover { border-color:var(--crs-red); box-shadow:var(--shadow-sm); }
.stat-active { background:white !important; border-color:var(--crs-red) !important; box-shadow:0 0 0 2px var(--crs-red-muted); }
.stat-active-amber { background:white !important; border-color:var(--amber) !important; box-shadow:0 0 0 2px var(--amber-pale); }

/* Import wizard steps */
.import-steps {
  padding:12px 24px; border-bottom:1px solid var(--border);
  background:var(--surface); display:flex; align-items:center; gap:8px;
}
.istep { display:flex; align-items:center; gap:6px; font-size:11.5px; color:var(--ink-muted); font-weight:600; }
.istep-num {
  width:22px; height:22px; border-radius:50%; background:var(--surface-2);
  color:var(--ink-muted); display:flex; align-items:center; justify-content:center;
  font-size:11px; font-weight:700; flex-shrink:0;
}
.istep-on { background:var(--crs-red) !important; color:white !important; }
.istep-done-num { background:var(--green) !important; color:white !important; }
.istep-line { width:28px; height:2px; background:var(--border); }

/* Drop zone */
.drop-zone {
  border:2px dashed var(--border-dk); border-radius:12px;
  padding:40px 24px; text-align:center; background:var(--surface);
  cursor:pointer; transition:all var(--tx);
}
.drop-zone:hover { border-color:var(--crs-red); background:var(--crs-red-pale); }
.drop-ico { width:52px; height:52px; border-radius:50%; background:white; border:1px solid var(--border); display:flex; align-items:center; justify-content:center; margin:0 auto 12px; }
.drop-title { font-size:15px; font-weight:700; color:var(--ink); }
.drop-sub { font-size:12px; color:var(--ink-muted); margin-top:4px; }

/* Column mapping table */
.map-tbl { width:100%; border-collapse:collapse; }
.map-tbl th { text-align:left; padding:9px 12px; font-size:10.5px; font-weight:700; color:var(--ink-muted); text-transform:uppercase; letter-spacing:0.06em; border-bottom:1px solid var(--border); background:var(--surface); }
.map-tbl td { padding:9px 12px; border-bottom:1px solid var(--border); font-size:13px; vertical-align:middle; color:var(--ink); }
.map-tbl tr:last-child td { border-bottom:none; }
.src-col { font-family:var(--font-mono); font-size:11.5px; padding:3px 8px; background:var(--blue-pale); color:var(--blue); border-radius:5px; font-weight:600; display:inline-block; }
.preview-cell { font-size:11.5px; color:var(--ink-soft); font-family:var(--font-mono); }
.map-status { font-size:11px; font-weight:700; padding:2px 7px; border-radius:4px; display:inline-block; }
.map-ok   { background:var(--green-pale); color:var(--green); }
.map-skip { background:var(--surface-2); color:var(--ink-muted); }

/* Migration summary grid */
.mig-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:18px; }
.mig-stat { background:var(--surface); border:1px solid var(--border); border-radius:10px; padding:13px 14px; }
.mig-stat.success { background:var(--green-pale); border-color:var(--green); }
.mig-stat.warn    { background:var(--amber-pale); border-color:var(--amber); }
.mig-stat.error   { background:var(--crs-red-pale); border-color:var(--crs-red); }
.mig-val { font-family:var(--font-mono); font-size:22px; font-weight:700; line-height:1; color:var(--ink); }
.mig-lbl { font-size:10.5px; font-weight:700; color:var(--ink-soft); text-transform:uppercase; letter-spacing:0.05em; margin-top:4px; }
.mig-stat.success .mig-val, .mig-stat.success .mig-lbl { color:var(--green); }
.mig-stat.warn .mig-val,    .mig-stat.warn .mig-lbl    { color:var(--amber); }
.mig-stat.error .mig-val,   .mig-stat.error .mig-lbl   { color:var(--crs-red); }

/* Settings rows */
.settings-row { display:flex; align-items:center; gap:10px; padding:9px 12px; background:white; border:1px solid var(--border); border-radius:8px; margin-bottom:6px; }
.settings-name { font-size:13px; font-weight:600; color:var(--ink); }
.settings-meta { font-size:11px; color:var(--ink-muted); margin-top:1px; }

/* Toggle switch */
.tog { display:inline-flex; align-items:center; cursor:pointer; flex-shrink:0; }
.tog input { display:none; }
.tog-track { width:36px; height:20px; background:var(--surface-2); border-radius:99px; position:relative; transition:background 0.16s; }
.tog-thumb { position:absolute; top:2px; left:2px; width:16px; height:16px; background:white; border-radius:50%; box-shadow:var(--shadow-xs); transition:left 0.16s; }
.tog input:checked + .tog-track { background:var(--green); }
.tog input:checked + .tog-track .tog-thumb { left:18px; }
</style>
