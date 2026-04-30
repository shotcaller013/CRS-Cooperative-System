<template>
  <div class="page-wrap">
    <div class="page-header">
      <Button icon="pi pi-arrow-left" text @click="$router.push('/loans')" />
      <div>
        <h1 class="page-title">New Loan Application</h1>
        <p class="page-sub">Generate loan packet for a cooperative member</p>
      </div>
    </div>
    <div class="page-content">
      <div class="form-card">
        <LoanForm
          :modelValue="initialForm"
          :errors="errors"
          :loading="loanStore.saving"
          :members="memberStore.dropdown"
          :loanTypes="loanStore.loanTypes"
          @submit="handleSubmit"
          @cancel="$router.push('/loans')"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import Button   from 'primevue/button'
import LoanForm from '@/components/loan/LoanForm.vue'
import { useLoanStore }   from '@/stores/loan.store'
import { useMemberStore } from '@/stores/member.store'
import { useToast } from '@/composables/useToast'

const router      = useRouter()
const route       = useRoute()
const loanStore   = useLoanStore()
const memberStore = useMemberStore()
const { success, error } = useToast()
const errors = ref({})

// Pre-fill member_id from query param (when navigated from member detail)
const initialForm = computed(() => ({
  member_id: route.query.member_id ? Number(route.query.member_id) : null,
}))

async function handleSubmit(form) {
  errors.value = {}
  try {
    const loan = await loanStore.create(form)
    success(`Loan ${loan.loan_no} created successfully!`)
    router.push(`/loans/${loan.id}`)
  } catch (e) {
    if (e?.response?.status === 422) errors.value = e.response.data.errors || {}
    else error(e?.response?.data?.message || 'Failed to create loan.')
  }
}

onMounted(async () => {
  await Promise.all([memberStore.fetchDropdown(), loanStore.fetchLoanTypes()])
})
</script>

<style scoped>
.page-wrap   { display: flex; flex-direction: column; height: 100%; overflow: hidden; }
.page-header { display: flex; align-items: center; gap: 12px; padding: 18px 24px; border-bottom: 1px solid var(--surface-border); flex-shrink: 0; }
.page-title  { font-size: 20px; font-weight: 700; margin: 0; }
.page-sub    { font-size: 12px; color: var(--text-color-secondary); margin: 2px 0 0; }
.page-content { flex: 1; overflow-y: auto; padding: 24px; }
.form-card   { max-width: 860px; margin: 0 auto; background: var(--surface-card); border: 1px solid var(--surface-border); border-radius: 10px; padding: 28px; }
</style>
