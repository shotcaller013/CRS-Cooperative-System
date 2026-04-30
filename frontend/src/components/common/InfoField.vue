<template>
  <div :class="['info-field', span2 && 'span-2']">
    <div class="info-label">{{ label }}</div>
    <div :class="['info-value', mono && 'font-mono']">
      <slot>{{ value || '—' }}</slot>
    </div>
  </div>
</template>

<script setup>
defineProps({
  label: String,
  value: [String, Number],
  mono:  Boolean,
  span2: Boolean,
})
</script>

<style scoped>
.info-field  { display: flex; flex-direction: column; gap: 3px; }
.span-2      { grid-column: span 2; }
.info-label  { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-color-secondary); }
.info-value  { font-size: 14px; color: var(--text-color); }
</style>
