<template>
  <div class="page-wrap">
    <header class="topbar">
      <span class="topbar-page">Reports</span>
      <span class="topbar-sep">/</span>
      <span class="topbar-sub">Financial reporting and collections monitoring</span>
    </header>

    <div class="reports-grid">
      <div v-for="r in reports" :key="r.route"
        class="report-card" @click="$router.push(r.route)">
        <div class="report-icon">{{ r.icon }}</div>
        <div class="report-name">{{ r.name }}</div>
        <div class="report-desc">{{ r.desc }}</div>
        <div class="report-exports">
          <span class="export-pill">Excel</span>
          <span class="export-pill">PDF</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const reports = [
  {
    route: '/reports/collection',
    icon: '◈',
    name: 'Collection summary',
    desc: 'Expected vs collected per period, broken down by loan type. Track collection rate month by month.',
  },
  {
    route: '/reports/aging',
    icon: '⊘',
    name: 'Aging report',
    desc: 'Overdue periods grouped into 0–30, 31–60, 61–90, and 90+ day buckets. Identify critical accounts.',
  },
  {
    route: '/reports/outstanding',
    icon: '◉',
    name: 'Outstanding balance',
    desc: 'Remaining loan balance per member across all active loans. Includes overdue and penalty amounts.',
  },
]
</script>

<style scoped>
.page-wrap    { display: flex; flex-direction: column; height: 100%; overflow: hidden; }
.topbar {
  display: flex; align-items: center; gap: 8px;
  padding: 14px 24px; border-bottom: 1px solid var(--border);
  background: white; flex-shrink: 0;
}
.topbar-page { font-weight: 600; font-size: 14px; }
.topbar-sep  { color: var(--ink-muted); }
.topbar-sub  { font-size: 12px; color: var(--ink-muted); }
.reports-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; padding: 24px; }
.report-card {
  background: white; border: 1px solid var(--border);
  border-radius: 12px; padding: 24px; cursor: pointer;
  transition: all .15s ease; display: flex; flex-direction: column; gap: 10px;
}
.report-card:hover { border-color: var(--crs-red); transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,.08); }
.report-icon { font-size: 24px; color: var(--crs-red); }
.report-name { font-size: 17px; font-weight: 500; color: var(--ink); }
.report-desc { font-size: 13px; color: var(--ink-muted); line-height: 1.5; flex: 1; }
.report-exports { display: flex; gap: 6px; }
.export-pill {
  font-size: 11px; padding: 2px 8px;
  background: var(--surface); border: 1px solid var(--border);
  border-radius: 4px; color: var(--ink-muted);
}
</style>
