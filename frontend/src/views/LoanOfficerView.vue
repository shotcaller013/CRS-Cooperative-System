<template>
  <div class="view-wrap">

    <!-- Topbar -->
    <div class="topbar">
      <div class="topbar-breadcrumb">
        <span class="topbar-page">Loan Application</span>
        <span v-if="selectedMember" class="topbar-sep">/</span>
        <span v-if="selectedMember" class="topbar-sub">
          New · {{ selectedMember.first_name }} {{ selectedMember.last_name }} · {{ selectedMember.member_no }}
        </span>
      </div>
      <div class="topbar-right">
        <button class="btn btn-secondary btn-sm" @click="showFindMember = true">
          <svg class="ico" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m20 20-3-3"/></svg>
          Find member
        </button>
      </div>
    </div>

    <div class="content">
      <!-- Page head -->
      <div class="page-head">
        <div class="page-title-block">
          <h1>New Loan Application</h1>
          <div class="page-sub">
            <span class="ref-chip mono">REF · {{ refNo }}</span>
            <span>Auto-generated from member 201 file</span>
            <span class="badge b-amber">Draft</span>
          </div>
        </div>
        <div class="page-actions">
          <button class="btn btn-secondary btn-sm" @click="saveLoan('DRAFT')" :disabled="saving">
            <svg class="ico" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Save Draft
          </button>
          <button class="btn btn-secondary btn-sm" @click="pdfPage = 1; showPdfModal = true" :disabled="!selectedMember">
            <svg class="ico" viewBox="0 0 24 24"><rect x="6" y="3" width="12" height="18" rx="2"/><path d="M9 7h6M9 11h6M9 15h4"/></svg>
            Preview PDF
          </button>
          <button class="btn btn-primary btn-sm" @click="saveLoan('PENDING')" :disabled="saving || !selectedMember">
            <svg class="ico" viewBox="0 0 24 24"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            Generate &amp; Print
          </button>
        </div>
      </div>

      <!-- Stepper -->
      <div class="card step-card">
        <div class="steps">
          <div v-for="(s, i) in steps" :key="s.label"
            :class="['step', i < currentStep ? 'done' : i === currentStep ? 'active' : '']">
            <div class="step-dot">{{ i < currentStep ? '✓' : i + 1 }}</div>
            <div class="step-lbl">{{ s.label }}</div>
          </div>
        </div>
      </div>

      <!-- No member state -->
      <div v-if="!selectedMember" class="no-member">
        <div class="no-member-icon">
          <svg style="width:32px;height:32px;stroke:var(--crs-red);fill:none;stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m20 20-3-3"/></svg>
        </div>
        <div class="no-member-title">Search for a member to begin</div>
        <div class="no-member-sub">Use the Find Member button in the top right to look up an employee</div>
        <button class="btn btn-primary" @click="showFindMember = true" style="margin-top:16px">
          Find Member
        </button>
      </div>

      <template v-else>
        <!-- Member banner -->
        <div class="card">
          <div class="member-banner">
            <div class="banner-av">{{ selectedMember.first_name?.[0] }}{{ selectedMember.last_name?.[0] }}</div>
            <div class="banner-info">
              <div class="banner-name">
                {{ selectedMember.first_name }} {{ selectedMember.last_name }}
                <span class="badge b-green">{{ selectedMember.status || 'Regular' }} · Active</span>
              </div>
              <div class="banner-meta">
                <span><strong>{{ selectedMember.member_no }}</strong></span>
                <span>{{ selectedMember.position || '—' }}</span>
                <span>{{ selectedMember.company || '—' }}</span>
                <span>{{ selectedMember.contact || '' }}</span>
              </div>
            </div>
            <div class="banner-stats">
              <div>
                <div class="bstat-lbl">Share Capital</div>
                <div class="bstat-val orange">{{ peso(selectedMember.share_capital || 0) }}</div>
              </div>
              <div>
                <div class="bstat-lbl">Monthly Salary</div>
                <div class="bstat-val">{{ peso(selectedMember.monthly_salary || 0) }}</div>
              </div>
              <div>
                <div class="bstat-lbl">Outstanding Loan</div>
                <div class="bstat-val green">₱0.00</div>
              </div>
              <div>
                <div class="bstat-lbl">Eligible Up To</div>
                <div class="bstat-val">{{ eligibleCeiling }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Two-column layout -->
        <div class="app-grid">

          <!-- ── LEFT: FORM ── -->
          <div>

            <!-- Loan Type -->
            <div class="card">
              <div class="card-head">
                <div class="card-head-l">
                  <svg class="ico" style="color:var(--crs-red)" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                  <div>
                    <div class="card-title">Loan Type</div>
                    <div class="card-sub">Each type has its own limits, fees, and interest rate</div>
                  </div>
                </div>
              </div>
              <div class="card-body">
                <div class="ltype-grid">
                  <div v-for="lt in loanTypes" :key="lt.id"
                    :class="['ltype', form.loan_type_id === lt.id && 'on']"
                    @click="selectLoanType(lt)">
                    <div class="ltype-name">{{ lt.label }}</div>
                    <div class="ltype-meta">
                      {{ pesoShort(lt.min_amount) }}–{{ pesoShort(lt.max_amount) }} · up to {{ lt.max_term }} mo<br>
                      {{ (parseFloat(lt.annual_rate) * 100).toFixed(0) }}% p.a. diminishing
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Amount, Term & Schedule -->
            <div class="card">
              <div class="card-head">
                <div class="card-head-l">
                  <svg class="ico" style="color:var(--crs-red)" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                  <div>
                    <div class="card-title">Amount, Term &amp; Schedule</div>
                    <div class="card-sub">Everything on the right updates as you type</div>
                  </div>
                </div>
              </div>
              <div class="card-body">
                <div class="fgrid">
                  <div class="fg fspan2">
                    <div class="fgl">Loan Amount Requested <span class="req">*</span></div>
                    <div class="inp-currency">
                      <span class="prefix">₱</span>
                      <input type="number" v-model.number="form.amount" @input="recalc"
                        :min="selectedLoanType?.min_amount || 1000"
                        :max="selectedLoanType?.max_amount || 150000"
                        step="1000" />
                    </div>
                    <div class="fhint" v-if="selectedLoanType">
                      Range {{ pesoShort(selectedLoanType.min_amount) }} – {{ pesoShort(selectedLoanType.max_amount) }}
                      · {{ selectedLoanType.min_term }}–{{ selectedLoanType.max_term }} mo
                    </div>
                  </div>

                  <div class="fg">
                    <div class="fgl">Term (Months) <span class="req">*</span></div>
                    <select class="sel" v-model.number="form.term_months" @change="recalc">
                      <option v-for="t in termOptions" :key="t" :value="t">{{ t }} months</option>
                    </select>
                  </div>

                  <div class="fg">
                    <div class="fgl">Payment Frequency <span class="req">*</span></div>
                    <select class="sel" v-model="form.frequency" @change="recalc">
                      <option value="monthly">Monthly</option>
                      <option value="bimonthly">Bi-Monthly (15th &amp; 30th)</option>
                      <option value="weekly">Weekly</option>
                    </select>
                  </div>

                  <div class="fg">
                    <div class="fgl">Interest Rate</div>
                    <div class="inp-currency" style="background:var(--surface)">
                      <input type="text" :value="rateDisplay" readonly style="background:var(--surface);color:var(--ink-muted)" />
                      <span class="prefix" style="background:var(--surface-2);border-left:1.5px solid var(--border);border-right:0">🔒</span>
                    </div>
                  </div>

                  <div class="fg">
                    <div class="fgl">Release Date</div>
                    <input type="date" class="inp" v-model="form.release_date" @change="recalc" />
                  </div>

                  <div class="fg fspan2">
                    <div class="fgl">Purpose of Loan <span class="req">*</span></div>
                    <textarea class="tx" v-model="form.purpose"
                      placeholder="e.g. Home appliance purchase, child's tuition, working capital…"></textarea>
                  </div>
                </div>
              </div>
            </div>

            <!-- Fees & Deductions -->
            <div class="card">
              <div class="card-head">
                <div class="card-head-l">
                  <svg class="ico" style="color:var(--omni-orange)" viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                  <div>
                    <div class="card-title">Fees &amp; Upfront Deductions</div>
                    <div class="card-sub">Deducted from release amount — not added to balance</div>
                  </div>
                </div>
                <span class="badge b-amber">Total fees: {{ peso(totalFees) }}</span>
              </div>
              <div class="card-body">
                <label v-for="(f, i) in fees" :key="f.id"
                  :class="['fee-toggle', f.on && 'on']">
                  <input type="checkbox" v-model="fees[i].on" @change="recalc" />
                  <div style="flex:1;min-width:0">
                    <div class="fname">{{ f.label }}</div>
                    <div class="fhint">{{ f.hint }}</div>
                  </div>
                  <div class="famt">{{ peso(feeAmt(f)) }}</div>
                </label>
              </div>
            </div>

            <!-- Co-Makers -->
            <div class="card">
              <div class="card-head">
                <div class="card-head-l">
                  <svg class="ico" style="color:var(--blue)" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                  <div>
                    <div class="card-title">Co-Makers</div>
                    <div class="card-sub">2 regular members required · both must sign the printed form</div>
                  </div>
                </div>
              </div>
              <div class="card-body">
                <div v-for="(cm, i) in coMakers" :key="i" class="comaker-row">
                  <div class="comaker-av">{{ cm.first_name?.[0] }}{{ cm.last_name?.[0] }}</div>
                  <div class="comaker-info">
                    <div class="comaker-name">{{ cm.first_name }} {{ cm.last_name }}</div>
                    <div class="comaker-id">{{ cm.member_no }} · {{ cm.position || '—' }} · {{ cm.status || 'Regular' }}</div>
                  </div>
                  <span class="comaker-status">Eligible ✓</span>
                  <button class="btn btn-ghost btn-sm" @click="removeCoMaker(i)" style="padding:4px 6px">✕</button>
                </div>
                <div v-if="coMakers.length < 2" class="comaker-add" @click="showCoMakerSearch = true">
                  + Search &amp; add co-maker ({{ 2 - coMakers.length }} needed)
                </div>
              </div>
            </div>

          </div>

          <!-- ── RIGHT: COMPUTATION ── -->
          <div class="right-col">

            <div class="compute-card">
              <!-- Header -->
              <div class="compute-head">
                <div class="compute-eyebrow">Net Amount to Release</div>
                <div class="compute-total mono">{{ peso(netRelease) }}</div>
                <div class="compute-sub">after fees · released on {{ releaseDateDisplay }}</div>
              </div>

              <!-- Breakdown: release -->
              <div class="breakdown">
                <div class="bd-row">
                  <span class="bd-label">Loan Principal</span>
                  <span class="bd-amt mono">{{ peso(form.amount) }}</span>
                </div>
                <div v-for="f in activeFees" :key="f.id" class="bd-row">
                  <span class="bd-label" style="padding-left:12px;font-size:12px">– {{ f.label }}</span>
                  <span class="bd-amt minus mono">– {{ peso(feeAmt(f)) }}</span>
                </div>
                <div class="bd-row release">
                  <span class="bd-label" style="font-weight:700;color:var(--green)">
                    <svg class="ico" viewBox="0 0 24 24" style="color:var(--green)"><polyline points="20 6 9 17 4 12"/></svg>
                    Net released to member
                  </span>
                  <span class="bd-amt plus mono">{{ peso(netRelease) }}</span>
                </div>
              </div>

              <!-- Breakdown: total payable -->
              <div class="breakdown" style="background:var(--surface)">
                <div class="bd-row">
                  <span class="bd-label">Principal <span class="hint">(repaid)</span></span>
                  <span class="bd-amt mono">{{ peso(form.amount) }}</span>
                </div>
                <div class="bd-row">
                  <span class="bd-label">+ Total Interest <span class="hint">({{ rateDisplay }} diminishing)</span></span>
                  <span class="bd-amt mono">{{ calc ? peso(calc.totalInterest) : '₱0.00' }}</span>
                </div>
                <div class="bd-row total">
                  <span class="bd-label">Total Amount Payable</span>
                  <span class="bd-amt mono" style="font-size:15px;color:var(--crs-red)">{{ calc ? peso(calc.totalPayment) : '₱0.00' }}</span>
                </div>
              </div>

              <!-- Dates -->
              <div class="dates">
                <div class="date-card">
                  <div class="date-lbl">First Deduction</div>
                  <div class="date-val mono">{{ firstDate }}</div>
                  <div class="date-sub mono">{{ calc ? peso(calc.firstPayment) : '—' }}</div>
                </div>
                <div class="date-card">
                  <div class="date-lbl">End of Deduction</div>
                  <div class="date-val mono">{{ lastDate }}</div>
                  <div class="date-sub mono">{{ calc ? peso(calc.lastPayment) : '—' }}</div>
                </div>
              </div>

              <!-- Schedule -->
              <div class="schedule-head">
                <div>
                  <h4>Amortization Schedule</h4>
                  <div class="schedule-count">{{ calc ? calc.nPeriods : '—' }} periods · {{ freqLabel(form.frequency) }}</div>
                </div>
                <button class="btn btn-ghost btn-sm" style="padding:4px 8px;font-size:11px">
                  <svg class="ico" viewBox="0 0 24 24"><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                  CSV
                </button>
              </div>
              <div class="schedule-table">
                <table>
                  <thead>
                    <tr><th>#</th><th>Date</th><th>Principal</th><th>Interest</th><th>Amount Due</th></tr>
                  </thead>
                  <tbody>
                    <tr v-if="!calc || !scheduleWithDates.length">
                      <td colspan="5" style="text-align:center;color:var(--ink-muted);padding:16px">Fill in the form to generate</td>
                    </tr>
                    <tr v-for="row in scheduleWithDates" :key="row.period">
                      <td>{{ String(row.period).padStart(2, '0') }}</td>
                      <td>{{ row.dateStr }}</td>
                      <td>{{ row.principal.toFixed(2) }}</td>
                      <td>{{ row.interest.toFixed(2) }}</td>
                      <td class="payment">{{ row.payment.toFixed(2) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Eligibility checks -->
              <div class="elig-list">
                <div v-for="e in eligChecks" :key="e.label" :class="['elig-row', e.status]">
                  <span class="dot">{{ e.status === 'pass' ? '✓' : e.status === 'warn' ? '!' : '✗' }}</span>
                  <span>{{ e.label }}</span>
                </div>
              </div>

              <div class="preview-note">
                <svg class="ico" viewBox="0 0 24 24" style="color:var(--amber);flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <div>
                  <strong>Printable Packet</strong>
                  Generates a 5-page PDF: Loan Application, Authority to Deduct, and full amortization. Member, co-makers, HR &amp; COOP officer signatures required before re-upload.
                </div>
              </div>
            </div>

            <!-- PDF Preview -->
            <div class="pdf-preview-card">
              <div class="card-head">
                <div class="card-head-l">
                  <svg class="ico" style="color:var(--crs-red)" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                  <div>
                    <div class="card-title">PDF Preview · {{ pdfPageTitle }}</div>
                    <div class="card-sub">Exactly what will be printed &amp; signed</div>
                  </div>
                </div>
                <div style="display:flex;gap:3px">
                  <button v-for="p in 5" :key="p"
                    :class="['pdf-chip', pdfPage === p && 'pdf-chip-on']"
                    @click="pdfPage = p">{{ p }}</button>
                </div>
              </div>

              <!-- Page 1: Loan Application -->
              <div v-if="pdfPage === 1" class="pdf-page">
                <div class="pdf-stamp">DRAFT · UNSIGNED</div>
                <div class="pdf-letterhead">
                  <div class="pdf-co">CRS HOLDINGS CORPORATIONS · EMPLOYEES CREDIT COOPERATIVE</div>
                  <div class="pdf-addr">A.C. Cortes Ave., Alang-alang, Mandaue City, Cebu · CDA Reg. No. ___</div>
                  <div class="pdf-doc">{{ selectedLoanType?.label || 'Loan' }} · Application Form</div>
                </div>
                <div class="pdf-body">
                  <div class="pdf-field"><span class="k">Member ID</span><span class="v">{{ selectedMember.member_no }}</span></div>
                  <div class="pdf-field"><span class="k">Name</span><span class="v">{{ selectedMember.first_name }} {{ selectedMember.last_name }}</span></div>
                  <div class="pdf-field"><span class="k">Address</span><span class="v">{{ selectedMember.address || '—' }}</span></div>
                  <div class="pdf-field"><span class="k">Contact / Email</span><span class="v">{{ selectedMember.contact || '—' }} · {{ selectedMember.email || '—' }}</span></div>
                  <div class="pdf-field"><span class="k">Company</span><span class="v">{{ selectedMember.company || '—' }}</span></div>
                  <div class="pdf-field"><span class="k">Status / Position</span><span class="v">{{ selectedMember.status || 'Regular' }} · {{ selectedMember.position || '—' }}</span></div>
                  <div class="pdf-field"><span class="k">Loan Amount</span><span class="v">{{ peso(form.amount) }}</span></div>
                  <div class="pdf-field"><span class="k">Term</span><span class="v">{{ form.term_months }} months · {{ freqLabel(form.frequency) }}</span></div>
                  <div class="pdf-field"><span class="k">Interest Rate</span><span class="v">{{ rateDisplay }} diminishing</span></div>
                  <div class="pdf-field"><span class="k">Less: Total Fees</span><span class="v">{{ peso(totalFees) }}</span></div>
                  <div class="pdf-field"><span class="k">Net Released</span><span class="v">{{ peso(netRelease) }}</span></div>
                  <div class="pdf-field"><span class="k">Release Date</span><span class="v">{{ releaseDateDisplay }}</span></div>
                  <div class="pdf-field"><span class="k">Total Payable</span><span class="v">{{ calc ? peso(calc.totalPayment) : '—' }}</span></div>
                </div>
                <div class="pdf-sigs">
                  <div class="pdf-sig" style="padding-top:24px">Borrower Signature</div>
                  <div class="pdf-sig" style="padding-top:24px">Date</div>
                  <div class="pdf-sig" style="padding-top:24px">Co-Maker 1</div>
                  <div class="pdf-sig" style="padding-top:24px">Co-Maker 2</div>
                  <div class="pdf-sig" style="padding-top:24px">HR Manager (Approved)</div>
                  <div class="pdf-sig" style="padding-top:24px">COOP Manager / Loan Officer</div>
                </div>
              </div>

              <!-- Page 2: Authority to Deduct -->
              <div v-if="pdfPage === 2" class="pdf-page">
                <div class="pdf-stamp">DRAFT · UNSIGNED</div>
                <div class="pdf-letterhead">
                  <div class="pdf-co">CRS HOLDINGS CORPORATIONS · EMPLOYEES CREDIT COOPERATIVE</div>
                  <div class="pdf-addr">A.C. Cortes Ave., Alang-alang, Mandaue City, Cebu · CDA Reg. No. ___</div>
                  <div class="pdf-doc">Authority to Deduct</div>
                </div>
                <p class="pdf-para">
                  I, <b>{{ selectedMember.first_name }} {{ selectedMember.last_name }}</b>, a member/employee of
                  <b>{{ selectedMember.company || 'CRS Holdings' }}</b>, hereby voluntarily authorize the
                  deduction from my salary/payroll for the purpose of repaying my loan with the Cooperative.
                </p>
                <div class="pdf-body" style="margin-top:4px">
                  <div class="pdf-field"><span class="k">Total to Deduct</span><span class="v">{{ calc ? peso(calc.totalPayment) : '—' }}</span></div>
                  <div class="pdf-field"><span class="k">Deduction Schedule</span><span class="v">{{ freqLabel(form.frequency) }}</span></div>
                  <div class="pdf-field"><span class="k">First Deduction</span><span class="v">{{ firstDate }}</span></div>
                  <div class="pdf-field"><span class="k">Last Deduction</span><span class="v">{{ lastDate }}</span></div>
                  <div class="pdf-field"><span class="k">Number of Periods</span><span class="v">{{ calc ? calc.nPeriods + ' periods' : '—' }}</span></div>
                </div>
                <p class="pdf-para" style="font-size:10px;color:var(--ink-muted);margin-top:10px">
                  I fully understand and agree that said deductions shall continue until my loan, including interest and any applicable charges, is fully settled.
                </p>
                <div class="pdf-sigs" style="margin-top:26px">
                  <div class="pdf-sig" style="padding-top:28px">Employee / Member Signature</div>
                  <div class="pdf-sig" style="padding-top:28px">Date</div>
                  <div class="pdf-sig" style="padding-top:28px">Verified by (Coop Representative)</div>
                  <div class="pdf-sig" style="padding-top:28px">Date</div>
                </div>
              </div>

              <!-- Pages 3–5: Amortization -->
              <div v-if="pdfPage >= 3" class="pdf-page">
                <div class="pdf-stamp">DRAFT · UNSIGNED</div>
                <div class="pdf-letterhead">
                  <div class="pdf-co">CRS HOLDINGS CORPORATIONS · EMPLOYEES CREDIT COOPERATIVE</div>
                  <div class="pdf-addr">A.C. Cortes Ave., Alang-alang, Mandaue City, Cebu · CDA Reg. No. ___</div>
                  <div class="pdf-doc">Payment Schedule · Page {{ pdfPage - 2 }} of 3</div>
                </div>
                <div v-if="pdfSchedChunk.length" class="pdf-sched-table">
                  <table>
                    <thead><tr><th>#</th><th>Date</th><th>Principal</th><th>Interest</th><th>Amount Due</th><th>Balance</th></tr></thead>
                    <tbody>
                      <tr v-for="r in pdfSchedChunk" :key="r.period">
                        <td>{{ String(r.period).padStart(2,'0') }}</td>
                        <td>{{ r.dateStr }}</td>
                        <td>{{ r.principal.toFixed(2) }}</td>
                        <td>{{ r.interest.toFixed(2) }}</td>
                        <td>{{ r.payment.toFixed(2) }}</td>
                        <td>{{ r.balance.toFixed(2) }}</td>
                      </tr>
                    </tbody>
                  </table>
                  <div v-if="pdfPage === 5 && calc" class="pdf-totals">
                    <div><div class="pdf-totals-lbl">Principal</div><div class="pdf-totals-val">{{ peso(form.amount) }}</div></div>
                    <div><div class="pdf-totals-lbl">Interest</div><div class="pdf-totals-val">{{ peso(calc.totalInterest) }}</div></div>
                    <div><div class="pdf-totals-lbl">Total</div><div class="pdf-totals-val" style="color:var(--crs-red)">{{ peso(calc.totalPayment) }}</div></div>
                  </div>
                </div>
                <div v-else class="empty-state" style="padding:20px;font-size:11px">— no further periods —</div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- Sticky action bar -->
    <div class="action-bar" v-if="selectedMember">
      <div class="action-bar-info">
        <span class="saved">
          <svg class="ico" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
          Draft auto-saved
        </span>
        <span>·</span>
        <span>Generating PDF takes ~3s</span>
      </div>
      <div class="action-bar-actions">
        <button class="btn btn-secondary btn-sm" @click="selectedMember = null">Cancel</button>
        <button class="btn btn-secondary btn-sm" @click="saveLoan('DRAFT')" :disabled="saving">Save &amp; Continue Later</button>
        <button class="btn btn-primary" @click="saveLoan('PENDING')" :disabled="saving">
          <svg class="ico" viewBox="0 0 24 24"><rect x="6" y="3" width="12" height="18" rx="2"/><path d="M9 7h6M9 11h6M9 15h4"/></svg>
          {{ saving ? 'Saving…' : 'Generate &amp; Print 5-page Packet' }}
        </button>
      </div>
    </div>

    <!-- Find Member Modal -->
    <div v-if="showFindMember" class="modal-overlay" @click.self="showFindMember = false">
      <div class="modal" style="max-width:560px">
        <div class="modal-header">
          <div class="modal-title">Find Member</div>
          <button class="btn btn-ghost btn-sm" @click="showFindMember = false">✕</button>
        </div>
        <div class="modal-body">
          <div class="search-row">
            <svg class="search-ico" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m20 20-3-3"/></svg>
            <input v-model="memberSearch" @input="fetchMembers" class="form-input"
              placeholder="Search by name, member ID, or company…"
              style="padding-left:32px" />
          </div>
          <div v-if="loadingMembers" class="empty-state" style="padding:24px"><div class="spinner"></div></div>
          <div v-else class="member-pick-list">
            <div v-for="m in memberList" :key="m.id" class="member-pick-row" @click="pickMember(m)">
              <div class="mi-av" :style="{ background: avatarGrad(m.last_name) }">
                {{ m.first_name?.[0] }}{{ m.last_name?.[0] }}
              </div>
              <div style="flex:1;min-width:0">
                <div style="font-size:13px;font-weight:700;color:var(--ink)">{{ m.first_name }} {{ m.last_name }}</div>
                <div style="font-size:11px;color:var(--ink-muted)" class="mono">{{ m.member_no }} · {{ m.company }}</div>
              </div>
              <span :class="m.member_status === 'ACTIVE' ? 'badge badge-approved' : 'badge badge-closed'">{{ m.member_status || 'ACTIVE' }}</span>
            </div>
            <div v-if="!memberList.length" class="empty-state" style="padding:24px;font-size:13px">No members found</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Co-Maker Search Modal -->
    <div v-if="showCoMakerSearch" class="modal-overlay" @click.self="showCoMakerSearch = false">
      <div class="modal" style="max-width:480px">
        <div class="modal-header">
          <div class="modal-title">Add Co-Maker</div>
          <button class="btn btn-ghost btn-sm" @click="showCoMakerSearch = false">✕</button>
        </div>
        <div class="modal-body">
          <div class="search-row">
            <svg class="search-ico" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m20 20-3-3"/></svg>
            <input v-model="coMakerSearch" @input="fetchCoMakerCandidates"
              class="form-input" placeholder="Search member…" style="padding-left:32px" />
          </div>
          <div class="member-pick-list">
            <div v-for="m in coMakerCandidates" :key="m.id"
              class="member-pick-row" @click="addCoMaker(m)">
              <div class="mi-av" :style="{ background: avatarGrad(m.last_name) }">
                {{ m.first_name?.[0] }}{{ m.last_name?.[0] }}
              </div>
              <div style="flex:1;min-width:0">
                <div style="font-size:13px;font-weight:700;color:var(--ink)">{{ m.first_name }} {{ m.last_name }}</div>
                <div style="font-size:11px;color:var(--ink-muted)" class="mono">{{ m.member_no }}</div>
              </div>
            </div>
            <div v-if="!coMakerCandidates.length" class="empty-state" style="padding:24px;font-size:13px">No members found</div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '../composables/useApi'
import { computeSchedule, peso, freqLabel } from '../composables/useLoanCalc'
import { useToast } from '../composables/useToast'

const route = useRoute()
const { success, error } = useToast()

// ── State ──────────────────────────────────────────────
const memberList        = ref([])
const loanTypes         = ref([])
const selectedMember    = ref(null)
const selectedLoanType  = ref(null)
const memberSearch      = ref('')
const loadingMembers    = ref(false)
const saving            = ref(false)
const calc              = ref(null)
const pdfPage           = ref(1)
const showFindMember    = ref(false)
const showCoMakerSearch = ref(false)
const showPdfModal      = ref(false)
const coMakers          = ref([])
const coMakerSearch     = ref('')
const coMakerCandidates = ref([])

// ── Form ───────────────────────────────────────────────
const today = new Date().toISOString().slice(0, 10)
const defaultRelease = (() => {
  const d = new Date(); d.setDate(d.getDate() + 7)
  return d.toISOString().slice(0, 10)
})()

const form = ref({
  member_id: null, loan_type_id: null,
  amount: 28000, term_months: 9,
  frequency: 'bimonthly', purpose: '',
  release_date: defaultRelease,
  application_date: today,
})

// ── Fees ───────────────────────────────────────────────
const fees = ref([
  { id: 'service',    label: 'Service Fee',            pct: 0.02,  fixed: 0,   on: true,  hint: '2% of principal' },
  { id: 'cbu',        label: 'Capital Build-Up (CBU)', pct: 0.01,  fixed: 0,   on: true,  hint: '1% of principal · added to share capital' },
  { id: 'notarial',   label: 'Notarial Fee',           pct: 0,     fixed: 200, on: true,  hint: 'Fixed ₱200' },
  { id: 'insurance',  label: 'Loan Insurance (MRI)',   pct: 0.005, fixed: 0,   on: true,  hint: '0.5% of principal per year' },
  { id: 'processing', label: 'Processing Fee',         pct: 0,     fixed: 100, on: false, hint: 'Fixed ₱100' },
])

// ── Steps ──────────────────────────────────────────────
const steps = [
  { label: 'Member Selected' },
  { label: 'Loan Type' },
  { label: 'Amount, Fees & Terms' },
  { label: 'Co-Makers' },
  { label: 'Generate & Print' },
  { label: 'Upload Signed Copy' },
]
const currentStep = computed(() => {
  if (!selectedMember.value) return 0
  if (!form.value.loan_type_id) return 1
  if (!form.value.amount) return 2
  if (coMakers.value.length < 2) return 3
  return 4
})

// ── Helpers ────────────────────────────────────────────
const refNo = computed(() => {
  const y = new Date().getFullYear()
  const m = String(new Date().getMonth() + 1).padStart(2, '0')
  const d = String(new Date().getDate()).padStart(2, '0')
  return `LA-${y}-${m}${d}`
})

const rateDisplay = computed(() => {
  if (!selectedLoanType.value) return '12% p.a.'
  return `${(parseFloat(selectedLoanType.value.annual_rate) * 100).toFixed(0)}% p.a.`
})

const termOptions = computed(() => {
  if (!selectedLoanType.value) return [3,6,9,12,18,24,36]
  const opts = []
  for (let t = selectedLoanType.value.min_term; t <= selectedLoanType.value.max_term; t++) opts.push(t)
  return opts
})

const pesoShort = (n) => '₱' + Number(n).toLocaleString('en-PH', { maximumFractionDigits: 0 })

const fmtDate = (d) => d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' })

const releaseDateDisplay = computed(() =>
  form.value.release_date ? fmtDate(new Date(form.value.release_date)) : '—'
)

function getDateStep() {
  return form.value.frequency === 'bimonthly' ? 15 : form.value.frequency === 'weekly' ? 7 : 30
}

const scheduleWithDates = computed(() => {
  if (!calc.value || !form.value.release_date) return []
  const daysStep = getDateStep()
  const base = new Date(form.value.release_date)
  base.setDate(base.getDate() + daysStep)
  return calc.value.schedule.map((row, i) => {
    const d = new Date(base)
    d.setDate(base.getDate() + i * daysStep)
    return { ...row, dateStr: fmtDate(d) }
  })
})

const firstDate = computed(() => scheduleWithDates.value[0]?.dateStr || '—')
const lastDate = computed(() => scheduleWithDates.value[scheduleWithDates.value.length - 1]?.dateStr || '—')

const feeAmt = (f) => {
  if (!f.on) return 0
  if (f.id === 'insurance') return form.value.amount * f.pct * (form.value.term_months / 12)
  return form.value.amount * f.pct + f.fixed
}

const activeFees = computed(() => fees.value.filter(f => f.on && feeAmt(f) > 0))
const totalFees = computed(() => fees.value.reduce((s, f) => s + feeAmt(f), 0))
const netRelease = computed(() => Math.max(0, form.value.amount - totalFees.value))

const eligibleCeiling = computed(() => {
  const salary = parseFloat(selectedMember.value?.monthly_salary) || 0
  const cap = parseFloat(selectedMember.value?.share_capital) || 0
  return pesoShort(Math.min(salary * 6, 150000) + cap)
})

const eligChecks = computed(() => {
  if (!calc.value) return []
  const salary = parseFloat(selectedMember.value?.monthly_salary) || 1
  const dti = (calc.value.firstPayment / salary) * 100
  return [
    { label: `Loan amount within eligible limit`, status: form.value.amount <= 150000 ? 'pass' : 'fail' },
    { label: `DTI ratio ${dti.toFixed(0)}% — ${dti < 40 ? 'within 40% threshold' : 'exceeds 40% threshold'}`, status: dti < 40 ? 'pass' : dti < 50 ? 'warn' : 'fail' },
    { label: `${coMakers.value.length} of 2 co-makers attached`, status: coMakers.value.length >= 2 ? 'pass' : 'warn' },
  ]
})

const pdfPageTitle = computed(() => ({
  1: 'Page 1 — Loan Application',
  2: 'Page 2 — Authority to Deduct',
  3: 'Page 3 — Payment Schedule (1 of 3)',
  4: 'Page 4 — Payment Schedule (2 of 3)',
  5: 'Page 5 — Payment Schedule (3 of 3)',
}[pdfPage.value]))

const pdfSchedChunk = computed(() => {
  if (!scheduleWithDates.value.length) return []
  const n = scheduleWithDates.value.length
  const third = Math.ceil(n / 3)
  const idx = pdfPage.value - 3
  return scheduleWithDates.value.slice(idx * third, (idx + 1) * third)
})

const avatarGrads = [
  'linear-gradient(135deg,#E8591A,#8B1A1A)',
  'linear-gradient(135deg,#1A3A8B,#2980B9)',
  'linear-gradient(135deg,#1A6B3C,#27AE60)',
  'linear-gradient(135deg,#5B2AA7,#8E44AD)',
  'linear-gradient(135deg,#92620A,#D35400)',
]
const avatarGrad = (name) => avatarGrads[(name?.charCodeAt(0) || 0) % avatarGrads.length]

// ── Functions ──────────────────────────────────────────
function recalc() {
  if (!selectedLoanType.value) return
  calc.value = computeSchedule({
    principal: form.value.amount,
    termMonths: form.value.term_months,
    frequency: form.value.frequency,
    annualRate: parseFloat(selectedLoanType.value.annual_rate),
  })
}

function selectLoanType(lt) {
  selectedLoanType.value = lt
  form.value.loan_type_id = lt.id
  form.value.amount = Math.min(lt.max_amount, Math.max(lt.min_amount, form.value.amount))
  form.value.term_months = Math.min(lt.max_term, Math.max(lt.min_term, form.value.term_months))
  recalc()
}

function pickMember(m) {
  selectedMember.value = m
  form.value.member_id = m.id
  showFindMember.value = false
  memberSearch.value = ''
}

async function fetchMembers() {
  loadingMembers.value = true
  try {
    memberList.value = await api.getMembers(memberSearch.value ? { search: memberSearch.value } : {})
  } catch {}
  finally { loadingMembers.value = false }
}

async function fetchCoMakerCandidates() {
  try {
    const all = await api.getMembers(coMakerSearch.value ? { search: coMakerSearch.value } : {})
    const taken = new Set([selectedMember.value?.id, ...coMakers.value.map(c => c.id)])
    coMakerCandidates.value = all.filter(m => !taken.has(m.id))
  } catch {}
}

function addCoMaker(m) {
  if (coMakers.value.length < 2) {
    coMakers.value.push(m)
    form.value[`co_maker_${coMakers.value.length}_id`] = m.id
  }
  if (coMakers.value.length >= 2) showCoMakerSearch.value = false
}

function removeCoMaker(i) {
  coMakers.value.splice(i, 1)
  form.value.co_maker_1_id = coMakers.value[0]?.id || ''
  form.value.co_maker_2_id = coMakers.value[1]?.id || ''
}

async function saveLoan(status) {
  if (!selectedMember.value) return error('Select a member first')
  if (!form.value.loan_type_id) return error('Select a loan type')
  saving.value = true
  try {
    const payload = {
      ...form.value, status,
      annual_rate: selectedLoanType.value.annual_rate,
      co_maker_1_id: coMakers.value[0]?.id || null,
      co_maker_2_id: coMakers.value[1]?.id || null,
    }
    const result = await api.createLoan(payload)
    success(`Loan ${result.loan_no} saved as ${status}!`)
  } catch (e) { error(e.message) }
  finally { saving.value = false }
}

onMounted(async () => {
  await fetchMembers()
  try {
    const types = await api.getLoanTypes()
    loanTypes.value = types
    const def = types.find(t => t.code === 'commodity') || types[0]
    if (def) selectLoanType(def)
  } catch {}

  if (route.query.member_id) {
    const m = memberList.value.find(x => x.id == route.query.member_id)
    if (m) pickMember(m)
    else showFindMember.value = true
  }
})
</script>

<style scoped>
.view-wrap { display:flex; flex-direction:column; height:100%; overflow:hidden; background:var(--surface); }

/* Topbar */
.topbar {
  background:white; border-bottom:1px solid var(--border); height:58px;
  display:flex; align-items:center; padding:0 28px; gap:16px;
  flex-shrink:0; box-shadow:var(--shadow-xs); z-index:10;
}
.topbar-breadcrumb { display:flex; align-items:center; gap:8px; flex:1; }
.topbar-page { font-size:18px; font-weight:700; color:var(--ink); }
.topbar-sep { color:var(--border-dk); font-size:16px; }
.topbar-sub { font-size:13px; color:var(--ink-muted); }
.topbar-right { display:flex; align-items:center; gap:10px; }
.ico { width:14px; height:14px; stroke:currentColor; fill:none; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; display:inline-block; vertical-align:middle; flex-shrink:0; }

/* Content */
.content { flex:1; overflow-y:auto; padding:20px 28px 80px; }

/* Page head */
.page-head { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px; gap:20px; flex-wrap:wrap; }
.page-title-block h1 { font-size:22px; font-weight:800; color:var(--ink); letter-spacing:-0.01em; }
.page-sub { display:flex; gap:10px; align-items:center; flex-wrap:wrap; font-size:13px; color:var(--ink-muted); margin-top:3px; }
.ref-chip { background:var(--surface-2); border:1px solid var(--border); padding:3px 9px; border-radius:6px; font-size:11.5px; color:var(--ink-soft); }
.page-actions { display:flex; gap:8px; flex-wrap:wrap; }

/* Badges */
.badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:99px; font-size:11px; font-weight:700; }
.b-green  { background:var(--green-pale); color:var(--green); }
.b-amber  { background:var(--amber-pale); color:var(--amber); }
.b-blue   { background:var(--blue-pale);  color:var(--blue); }
.req { color:var(--crs-red); }

/* Stepper */
.step-card { margin-bottom:16px; }
.steps { display:flex; padding:14px 20px; background:var(--surface); overflow-x:auto; }
.step { flex:1; display:flex; flex-direction:column; align-items:center; position:relative; min-width:100px; }
.step:not(:last-child)::after { content:''; position:absolute; top:13px; left:55%; right:-45%; height:2px; background:var(--border); z-index:0; }
.step.done:not(:last-child)::after { background:var(--omni-orange); }
.step.active:not(:last-child)::after { background:linear-gradient(90deg,var(--omni-orange),var(--border)); }
.step-dot { width:26px; height:26px; border-radius:50%; background:white; border:2px solid var(--border); display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; color:var(--ink-muted); z-index:1; position:relative; }
.step.done .step-dot { background:var(--omni-orange); border-color:var(--omni-orange); color:white; }
.step.active .step-dot { background:white; border-color:var(--crs-red); color:var(--crs-red); box-shadow:0 0 0 4px rgba(139,26,26,0.1); }
.step-lbl { font-size:10.5px; color:var(--ink-muted); margin-top:7px; text-align:center; line-height:1.3; font-weight:500; }
.step.done .step-lbl { color:var(--omni-orange); font-weight:600; }
.step.active .step-lbl { color:var(--crs-red); font-weight:700; }

/* No member */
.no-member { display:flex; flex-direction:column; align-items:center; justify-content:center; padding:64px 32px; gap:10px; text-align:center; }
.no-member-icon { width:64px; height:64px; border-radius:50%; background:var(--crs-red-pale); display:flex; align-items:center; justify-content:center; }
.no-member-title { font-size:18px; font-weight:700; color:var(--ink); }
.no-member-sub { font-size:13px; color:var(--ink-muted); }

/* Member banner */
.member-banner { display:flex; align-items:center; gap:16px; padding:16px 20px; flex-wrap:wrap; }
.banner-av { width:56px; height:56px; border-radius:50%; background:linear-gradient(135deg,#E8591A,#8B1A1A); color:white; display:flex; align-items:center; justify-content:center; font-size:19px; font-weight:700; flex-shrink:0; }
.banner-info { flex:1; min-width:0; }
.banner-name { font-size:17px; font-weight:700; color:var(--ink); display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.banner-meta { display:flex; gap:14px; font-size:12px; color:var(--ink-muted); margin-top:3px; flex-wrap:wrap; }
.banner-meta span strong { color:var(--ink); font-weight:600; }
.banner-stats { display:flex; gap:22px; padding-left:16px; border-left:1px solid var(--border); flex-wrap:wrap; }
.bstat-lbl { font-size:9.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:var(--ink-muted); }
.bstat-val { font-size:15px; font-weight:700; color:var(--ink); margin-top:2px; font-family:var(--font-mono); }
.bstat-val.orange { color:var(--omni-orange); }
.bstat-val.green  { color:var(--green); }

/* 2-col layout */
.app-grid { display:grid; grid-template-columns:1fr 460px; gap:20px; align-items:start; margin-top:16px; }

/* Cards */
.card { background:white; border:1px solid var(--border); border-radius:12px; overflow:hidden; box-shadow:var(--shadow-xs); margin-bottom:16px; }
.card-head { padding:14px 20px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; gap:10px; }
.card-head-l { display:flex; align-items:center; gap:10px; }
.card-title { font-size:14px; font-weight:700; color:var(--ink); }
.card-sub { font-size:12px; color:var(--ink-muted); margin-top:2px; }
.card-body { padding:18px 20px; }

/* Loan types */
.ltype-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; }
.ltype { border:1.5px solid var(--border); border-radius:10px; padding:12px 14px; cursor:pointer; transition:all var(--tx); background:white; position:relative; }
.ltype:hover { border-color:var(--omni-orange); }
.ltype.on { border-color:var(--crs-red); background:var(--crs-red-pale); box-shadow:0 0 0 3px rgba(139,26,26,0.06); }
.ltype.on::after { content:'✓'; position:absolute; top:8px; right:10px; width:18px; height:18px; background:var(--crs-red); color:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; }
.ltype-name { font-size:13.5px; font-weight:700; color:var(--ink); margin-bottom:3px; }
.ltype-meta { font-size:10.5px; color:var(--ink-muted); font-family:var(--font-mono); line-height:1.5; }

/* Form inputs */
.fgrid { display:grid; grid-template-columns:1fr 1fr; gap:14px 16px; }
.fspan2 { grid-column:span 2; }
.fg { display:flex; flex-direction:column; gap:6px; }
.fgl { font-size:11px; font-weight:700; color:var(--ink-muted); letter-spacing:0.04em; text-transform:uppercase; }
.fhint { font-size:11px; color:var(--ink-muted); }
.inp, .sel, .tx {
  width:100%; padding:9px 12px; border:1.5px solid var(--border);
  border-radius:8px; font-size:13px; font-family:inherit; color:var(--ink);
  background:white; outline:none; transition:border-color var(--tx);
}
.inp:focus, .sel:focus, .tx:focus { border-color:var(--crs-red); box-shadow:0 0 0 3px rgba(139,26,26,0.08); }
.tx { min-height:64px; resize:vertical; }
.inp-currency { display:flex; align-items:stretch; border:1.5px solid var(--border); border-radius:8px; overflow:hidden; background:white; transition:border-color var(--tx); }
.inp-currency:focus-within { border-color:var(--crs-red); box-shadow:0 0 0 3px rgba(139,26,26,0.08); }
.prefix { padding:9px 12px; background:var(--surface); color:var(--ink-muted); font-weight:700; font-size:13px; border-right:1.5px solid var(--border); flex-shrink:0; }
.inp-currency input { flex:1; border:0; padding:9px 12px; font-size:14px; font-weight:600; font-family:var(--font-mono); outline:none; color:var(--ink); min-width:0; }

/* Fee toggles */
.fee-toggle { display:flex; align-items:center; gap:10px; padding:9px 12px; border:1.5px solid var(--border); border-radius:8px; cursor:pointer; background:white; margin-bottom:8px; transition:all var(--tx); }
.fee-toggle:hover { border-color:var(--omni-orange); }
.fee-toggle.on { background:var(--omni-orange-pale); border-color:var(--omni-orange); }
.fee-toggle input { accent-color:var(--crs-red); cursor:pointer; flex-shrink:0; }
.fname { font-size:12.5px; font-weight:600; color:var(--ink); }
.fhint { font-size:10.5px; color:var(--ink-muted); margin-top:1px; }
.famt { font-size:12px; font-weight:600; color:var(--ink); font-family:var(--font-mono); flex-shrink:0; }

/* Co-makers */
.comaker-row { display:flex; align-items:center; gap:10px; padding:10px 12px; border:1.5px solid var(--border); border-radius:10px; background:white; margin-bottom:8px; }
.comaker-av { width:32px; height:32px; border-radius:50%; background:var(--blue-pale); color:var(--blue); display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; flex-shrink:0; }
.comaker-info { flex:1; min-width:0; }
.comaker-name { font-size:13px; font-weight:600; color:var(--ink); }
.comaker-id { font-size:11px; color:var(--ink-muted); font-family:var(--font-mono); margin-top:1px; }
.comaker-status { font-size:10.5px; font-weight:700; color:var(--green); background:var(--green-pale); padding:2px 8px; border-radius:99px; flex-shrink:0; }
.comaker-add { border:1.5px dashed var(--border-dk); border-radius:10px; padding:14px; text-align:center; color:var(--ink-muted); cursor:pointer; font-size:13px; font-weight:500; transition:all var(--tx); }
.comaker-add:hover { border-color:var(--crs-red); color:var(--crs-red); background:var(--crs-red-pale); }

/* Right column: sticky */
.right-col { position:sticky; top:20px; }

/* Computation card */
.compute-card { background:white; border:1px solid var(--border); border-radius:12px; overflow:hidden; box-shadow:var(--shadow-sm); margin-bottom:16px; }
.compute-head { background:linear-gradient(135deg, var(--crs-red), #6B1212); color:white; padding:16px 20px; }
.compute-eyebrow { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.14em; opacity:0.8; }
.compute-total { font-family:var(--font-mono); font-size:30px; font-weight:700; margin-top:2px; }
.compute-sub { font-size:12px; opacity:0.8; margin-top:2px; }

.breakdown { padding:16px 20px; border-bottom:1px solid var(--border); }
.bd-row { display:flex; justify-content:space-between; align-items:center; padding:7px 0; font-size:13px; }
.bd-row.total { padding-top:11px; margin-top:4px; border-top:1.5px solid var(--border); font-weight:700; }
.bd-row.release { background:var(--green-pale); margin:6px -20px 0; padding:11px 20px; border-top:1.5px solid var(--green); border-bottom:1.5px solid var(--green); }
.bd-label { color:var(--ink-soft); display:flex; align-items:center; gap:7px; }
.hint { font-size:10.5px; color:var(--ink-faint); font-weight:400; }
.bd-amt { font-family:var(--font-mono); font-weight:600; color:var(--ink); }
.bd-amt.minus { color:var(--crs-red); }
.bd-amt.plus { color:var(--green); font-size:15px; }

.dates { padding:14px 20px; display:grid; grid-template-columns:1fr 1fr; gap:12px; border-bottom:1px solid var(--border); }
.date-card { background:var(--surface); border:1px solid var(--border); border-radius:8px; padding:10px 12px; }
.date-lbl { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:var(--ink-muted); }
.date-val { font-size:13.5px; font-weight:700; color:var(--ink); margin-top:3px; }
.date-sub { font-size:11px; color:var(--ink-muted); margin-top:2px; }

.schedule-head { display:flex; justify-content:space-between; align-items:center; padding:12px 20px; border-bottom:1px solid var(--border); }
.schedule-head h4 { font-size:13px; font-weight:700; color:var(--ink); }
.schedule-count { font-size:11px; color:var(--ink-muted); font-family:var(--font-mono); }
.schedule-table { max-height:240px; overflow-y:auto; }
.schedule-table table { width:100%; border-collapse:collapse; }
.schedule-table th { position:sticky; top:0; background:var(--surface); padding:8px 12px; font-size:9.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:var(--ink-muted); text-align:left; border-bottom:1px solid var(--border); }
.schedule-table th:nth-child(n+3) { text-align:right; }
.schedule-table td { padding:7px 12px; font-size:11.5px; border-bottom:1px solid var(--border); font-family:var(--font-mono); color:var(--ink-soft); }
.schedule-table td:nth-child(n+3) { text-align:right; }
.schedule-table td:first-child { color:var(--ink-muted); font-size:10.5px; }
.schedule-table tr:last-child td { border-bottom:none; }
.schedule-table tr:hover td { background:var(--surface); }
.schedule-table .payment { font-weight:600; color:var(--ink); }

/* Eligibility */
.elig-list { display:flex; flex-direction:column; gap:8px; padding:12px 20px; border-top:1px solid var(--border); }
.elig-row { display:flex; align-items:center; gap:10px; font-size:12px; color:var(--ink-soft); }
.elig-row .dot { width:18px; height:18px; border-radius:50%; background:var(--green-pale); color:var(--green); display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; flex-shrink:0; }
.elig-row.warn .dot { background:var(--amber-pale); color:var(--amber); }
.elig-row.fail .dot { background:var(--crs-red-pale); color:var(--crs-red); }

.preview-note { padding:12px 20px; background:var(--amber-pale); border-top:1px solid var(--border); display:flex; align-items:flex-start; gap:10px; font-size:12px; color:var(--amber); }
.preview-note strong { display:block; margin-bottom:2px; color:#6B460A; }

/* PDF Preview */
.pdf-preview-card { background:white; border:1px solid var(--border); border-radius:12px; overflow:hidden; box-shadow:var(--shadow-xs); }
.pdf-chip { padding:3px 9px; border-radius:6px; border:1.5px solid var(--border); background:white; font-size:11px; font-weight:600; color:var(--ink-soft); cursor:pointer; font-family:inherit; transition:all var(--tx); }
.pdf-chip:hover { border-color:var(--crs-red); color:var(--crs-red); }
.pdf-chip-on { background:var(--crs-red) !important; border-color:var(--crs-red) !important; color:white !important; }
.pdf-page { background:white; border:1px solid var(--border); border-radius:6px; padding:20px 22px; margin:16px; font-size:11px; color:var(--ink-soft); position:relative; }
.pdf-stamp { position:absolute; top:14px; right:14px; border:2px solid var(--crs-red-light); color:var(--crs-red-light); padding:4px 9px; font-size:9px; font-weight:700; letter-spacing:0.14em; transform:rotate(-5deg); border-radius:3px; opacity:0.7; }
.pdf-letterhead { text-align:center; padding-bottom:10px; border-bottom:1px solid var(--border); }
.pdf-co { font-weight:800; font-size:11px; color:var(--crs-red); letter-spacing:0.02em; }
.pdf-addr { font-size:9.5px; color:var(--ink-muted); margin-top:1px; }
.pdf-doc { font-family:Georgia, serif; font-size:14px; color:var(--ink); margin-top:6px; font-weight:600; }
.pdf-body { margin-top:10px; }
.pdf-field { display:grid; grid-template-columns:110px 1fr; gap:8px; padding:4px 0; border-bottom:1px dotted var(--border); font-size:10px; }
.pdf-field .k { color:var(--ink-muted); text-transform:uppercase; font-size:9px; letter-spacing:0.04em; font-weight:700; }
.pdf-field .v { color:var(--ink); font-weight:500; font-family:var(--font-mono); }
.pdf-para { font-size:10.5px; line-height:1.55; margin-top:10px; }
.pdf-sigs { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-top:14px; }
.pdf-sig { border-top:1px solid var(--ink); text-align:center; font-size:8.5px; color:var(--ink-muted); text-transform:uppercase; letter-spacing:0.04em; font-weight:700; }
.pdf-sched-table table { width:100%; border-collapse:collapse; }
.pdf-sched-table th { text-align:left; font-size:8.5px; font-weight:700; color:var(--ink-muted); text-transform:uppercase; letter-spacing:0.06em; padding:6px; border-bottom:1px solid var(--ink); }
.pdf-sched-table td { padding:3px 6px; border-bottom:1px dotted var(--border); font-size:9.5px; font-family:var(--font-mono); color:var(--ink-soft); }
.pdf-totals { display:grid; grid-template-columns:1fr 1fr 1fr; gap:8px; margin-top:14px; padding-top:10px; border-top:1px solid var(--border); }
.pdf-totals-lbl { font-size:8.5px; text-transform:uppercase; letter-spacing:0.06em; color:var(--ink-muted); font-weight:700; }
.pdf-totals-val { font-family:var(--font-mono); font-size:12px; font-weight:600; margin-top:2px; color:var(--ink); }

/* Sticky action bar */
.action-bar {
  position:sticky; bottom:0; background:white; border-top:1px solid var(--border);
  padding:12px 28px; display:flex; justify-content:space-between; align-items:center;
  z-index:20; box-shadow:0 -4px 14px rgba(0,0,0,0.04); flex-shrink:0;
}
.action-bar-info { font-size:12.5px; color:var(--ink-muted); display:flex; align-items:center; gap:8px; }
.saved { color:var(--green); display:inline-flex; align-items:center; gap:4px; }
.action-bar-actions { display:flex; gap:8px; }

/* Find member modal */
.search-row { position:relative; margin-bottom:14px; }
.search-ico { position:absolute; left:10px; top:50%; transform:translateY(-50%); width:14px; height:14px; stroke:var(--ink-muted); fill:none; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
.member-pick-list { max-height:340px; overflow-y:auto; display:flex; flex-direction:column; gap:2px; }
.member-pick-row { display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:8px; cursor:pointer; transition:background var(--tx); }
.member-pick-row:hover { background:var(--surface); }
.mi-av { width:34px; height:34px; border-radius:50%; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; color:white; }
</style>
