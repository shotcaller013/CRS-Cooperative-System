<template>
  <Tag :value="status" :severity="severity" :class="`status-badge status-${status?.toLowerCase()}`" />
</template>

<script setup>
import Tag from 'primevue/tag'
import { computed } from 'vue'

const props = defineProps({ status: { type: String, default: '' } })

const severity = computed(() => {
  const map = {
    // Member statuses
    ACTIVE: 'success', INACTIVE: 'secondary', RESIGNED: 'danger',
    REGULAR: 'success', PROBI: 'warning', SUSPENDED: 'danger',
    // Loan statuses
    DRAFT: 'secondary', PENDING: 'warning', APPROVED: 'success',
    ACTIVE: 'info', CLOSED: 'secondary', REJECTED: 'danger',
    // Schedule statuses
    PAID: 'success', PARTIAL: 'warning', OVERDUE: 'danger',
  }
  return map[props.status] || 'secondary'
})
</script>

<style scoped>
.status-badge { font-size: 11px; font-weight: 600; letter-spacing: 0.3px; }
</style>
