<template>
  <div class="page-wrap">
    <div class="page-header">
      <Button icon="pi pi-arrow-left" text @click="$router.push('/members')" />
      <div>
        <h1 class="page-title">Add New Member</h1>
        <p class="page-sub">Create a new cooperative member record</p>
      </div>
    </div>
    <div class="page-content">
      <div class="form-card">
        <MemberForm
          :errors="errors"
          :loading="memberStore.saving"
          @submit="handleSubmit"
          @cancel="$router.push('/members')"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import MemberForm from '@/components/member/MemberForm.vue'
import { useMemberStore } from '@/stores/member.store'
import { useToast } from '@/composables/useToast'

const router      = useRouter()
const memberStore = useMemberStore()
const { success, error } = useToast()
const errors = ref({})

async function handleSubmit(form) {
  errors.value = {}
  try {
    await memberStore.create(form)
    success('Member created successfully!')
    router.push('/members')
  } catch (e) {
    if (e?.response?.status === 422) {
      errors.value = e.response.data.errors || {}
    } else {
      error(e?.response?.data?.message || 'Failed to create member.')
    }
  }
}
</script>

<style scoped>
.page-wrap    { display: flex; flex-direction: column; height: 100%; overflow: hidden; }
.page-header  { display: flex; align-items: center; gap: 12px; padding: 18px 24px; border-bottom: 1px solid var(--surface-border); flex-shrink: 0; }
.page-title   { font-size: 20px; font-weight: 700; margin: 0; }
.page-sub     { font-size: 12px; color: var(--text-color-secondary); margin: 2px 0 0; }
.page-content { flex: 1; overflow-y: auto; padding: 24px; }
.form-card    { max-width: 820px; margin: 0 auto; background: var(--surface-card); border: 1px solid var(--surface-border); border-radius: 10px; padding: 28px; }
</style>
