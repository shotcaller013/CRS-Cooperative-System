<template>
  <form @submit.prevent="emit('submit', form)" class="crs-form">
    <div class="form-grid">
      <div class="field">
        <label class="field-label">Member # <span class="required">*</span></label>
        <InputText v-model="form.member_no" placeholder="CRS-00XXX"
          :disabled="isEdit" :class="{ 'p-invalid': errors.member_no }" class="w-full" />
        <small class="p-error">{{ errors.member_no }}</small>
      </div>

      <div class="field">
        <label class="field-label">Employment Status <span class="required">*</span></label>
        <Dropdown v-model="form.status" :options="empStatuses"
          placeholder="Select status" class="w-full"
          :class="{ 'p-invalid': errors.status }" />
        <small class="p-error">{{ errors.status }}</small>
      </div>

      <div class="field">
        <label class="field-label">First Name <span class="required">*</span></label>
        <InputText v-model="form.first_name" class="w-full"
          :class="{ 'p-invalid': errors.first_name }" />
        <small class="p-error">{{ errors.first_name }}</small>
      </div>

      <div class="field">
        <label class="field-label">Last Name <span class="required">*</span></label>
        <InputText v-model="form.last_name" class="w-full"
          :class="{ 'p-invalid': errors.last_name }" />
        <small class="p-error">{{ errors.last_name }}</small>
      </div>

      <div class="field">
        <label class="field-label">Middle Name</label>
        <InputText v-model="form.middle_name" class="w-full" />
      </div>

      <div class="field">
        <label class="field-label">Contact #</label>
        <InputText v-model="form.contact" class="w-full" />
      </div>

      <div class="field col-span-2">
        <label class="field-label">Address</label>
        <InputText v-model="form.address" class="w-full" />
      </div>

      <div class="field">
        <label class="field-label">Email</label>
        <InputText v-model="form.email" type="email" class="w-full"
          :class="{ 'p-invalid': errors.email }" />
        <small class="p-error">{{ errors.email }}</small>
      </div>

      <div class="field">
        <label class="field-label">Company</label>
        <InputText v-model="form.company" class="w-full" />
      </div>

      <div class="field">
        <label class="field-label">Branch</label>
        <InputText v-model="form.branch" class="w-full" />
      </div>

      <div class="field">
        <label class="field-label">Department</label>
        <InputText v-model="form.department" class="w-full" />
      </div>

      <div class="field">
        <label class="field-label">Position</label>
        <InputText v-model="form.position" class="w-full" />
      </div>

      <div class="field">
        <label class="field-label">Direct Supervisor</label>
        <InputText v-model="form.supervisor" class="w-full" />
      </div>

      <div class="field">
        <label class="field-label">Date Hired</label>
        <Calendar v-model="form.date_hired" dateFormat="yy-mm-dd" class="w-full"
          :showIcon="true" />
      </div>

      <div class="field">
        <label class="field-label">Monthly Salary (₱)</label>
        <InputNumber v-model="form.monthly_salary" mode="currency" currency="PHP"
          locale="en-PH" class="w-full" :minFractionDigits="2" />
      </div>

      <div class="field">
        <label class="field-label">Share Capital (₱)</label>
        <InputNumber v-model="form.share_capital" mode="currency" currency="PHP"
          locale="en-PH" class="w-full" :minFractionDigits="2" />
      </div>

      <div class="field">
        <label class="field-label">Member Status <span class="required">*</span></label>
        <Dropdown v-model="form.member_status" :options="memberStatuses"
          placeholder="Select" class="w-full" />
      </div>
    </div>

    <div class="form-actions">
      <Button type="button" label="Cancel" severity="secondary"
        outlined @click="emit('cancel')" />
      <Button type="submit" :label="isEdit ? 'Update Member' : 'Save Member'"
        :loading="loading" icon="pi pi-save" />
    </div>
  </form>
</template>

<script setup>
import { reactive, watch } from 'vue'
import InputText   from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Dropdown    from 'primevue/dropdown'
import Calendar    from 'primevue/calendar'
import Button      from 'primevue/button'

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
  errors:     { type: Object, default: () => ({}) },
  loading:    { type: Boolean, default: false },
  isEdit:     { type: Boolean, default: false },
})
const emit = defineEmits(['submit', 'cancel'])

const empStatuses    = ['REGULAR', 'PROBI', 'SUSPENDED', 'INACTIVE']
const memberStatuses = ['ACTIVE', 'INACTIVE', 'RESIGNED']

const form = reactive({
  member_no:      '',
  last_name:      '',
  first_name:     '',
  middle_name:    '',
  address:        '',
  contact:        '',
  email:          '',
  company:        '',
  branch:         '',
  department:     '',
  status:         'PROBI',
  position:       '',
  supervisor:     '',
  date_hired:     null,
  monthly_salary: 0,
  share_capital:  0,
  member_status:  'ACTIVE',
})

watch(() => props.modelValue, (val) => {
  if (val && Object.keys(val).length) Object.assign(form, val)
}, { immediate: true, deep: true })
</script>

<style scoped>
.crs-form { display: flex; flex-direction: column; gap: 0; }
.form-grid {
  display: grid; grid-template-columns: 1fr 1fr; gap: 16px 20px;
  padding: 4px 0;
}
.col-span-2 { grid-column: span 2; }
.field { display: flex; flex-direction: column; gap: 5px; }
.field-label { font-size: 12px; font-weight: 600; color: var(--text-color-secondary); }
.required { color: var(--red-500); }
.form-actions {
  display: flex; justify-content: flex-end; gap: 10px;
  padding-top: 20px; border-top: 1px solid var(--surface-border); margin-top: 20px;
}
</style>
