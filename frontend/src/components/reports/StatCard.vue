<template>
  <div class="stat-card">
    <div class="stat-label">{{ label }}</div>
    <div :class="['stat-value', mono && 'mono', accent && `accent-${accent}`]">{{ value }}</div>
    <div v-if="sub" class="stat-sub">{{ sub }}</div>
  </div>
</template>

<script setup>
defineProps({
  label:  String,
  value:  [String, Number],
  sub:    String,
  mono:   Boolean,
  accent: { type: String, default: null }, // success | warning | danger
})
</script>

<style scoped>
.stat-card   { background: var(--surface); border: 1px solid var(--border); border-radius: 8px; padding: 14px 16px; }
.stat-label  { font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: var(--ink-muted); margin-bottom: 5px; }
.stat-value  { font-size: 20px; font-weight: 500; color: var(--ink); }
.stat-sub    { font-size: 11px; color: var(--ink-muted); margin-top: 3px; }
.accent-success { color: #16a34a !important; }
.accent-warning { color: #d97706 !important; }
.accent-danger  { color: var(--crs-red) !important; }
</style>
