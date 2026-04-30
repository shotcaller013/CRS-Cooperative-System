<template>
  <div class="rate-cell">
    <div class="rate-track">
      <div class="rate-fill" :style="{ width: clampedRate + '%', background: color }" />
    </div>
    <span class="mono rate-pct">{{ rate }}%</span>
  </div>
</template>

<script setup>
import { computed } from 'vue'
const props = defineProps({ rate: { type: Number, default: 0 } })
const clampedRate = computed(() => Math.min(100, Math.max(0, props.rate)))
const color = computed(() =>
  props.rate >= 90 ? '#16a34a' : props.rate >= 75 ? '#d97706' : 'var(--crs-red)'
)
</script>

<style scoped>
.rate-cell  { display: flex; align-items: center; gap: 8px; }
.rate-track { flex: 1; height: 6px; background: var(--surface-2); border-radius: 3px; overflow: hidden; }
.rate-fill  { height: 100%; border-radius: 3px; transition: width .3s; }
.rate-pct   { font-size: 12px; min-width: 44px; text-align: right; }
</style>
