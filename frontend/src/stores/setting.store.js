// src/stores/setting.store.js
import { defineStore } from 'pinia'
import { ref } from 'vue'
import { settingService } from '@/services/setting.service'

export const useSettingStore = defineStore('setting', () => {
  const profile     = ref(null)
  const loanTypes   = ref([])
  const preferences = ref({})
  const loading     = ref(false)
  const saving      = ref(false)

  async function fetchProfile() {
    loading.value = true
    try { profile.value = await settingService.getProfile() }
    finally { loading.value = false }
  }

  async function updateProfile(data) {
    saving.value = true
    try {
      profile.value = await settingService.updateProfile(data)
      return profile.value
    } finally { saving.value = false }
  }

  async function fetchLoanTypes() {
    loading.value = true
    try {
      const data = await settingService.getLoanTypes()
      loanTypes.value = Array.isArray(data) ? data : (data.data ?? [])
    } finally { loading.value = false }
  }

  async function createLoanType(data) {
    saving.value = true
    try {
      const result = await settingService.createLoanType(data)
      await fetchLoanTypes()
      return result
    } finally { saving.value = false }
  }

  async function updateLoanType(id, data) {
    saving.value = true
    try {
      const result = await settingService.updateLoanType(id, data)
      await fetchLoanTypes()
      return result
    } finally { saving.value = false }
  }

  async function deleteLoanType(id) {
    await settingService.deleteLoanType(id)
    loanTypes.value = loanTypes.value.filter(lt => lt.id !== id)
  }

  async function fetchPreferences() {
    preferences.value = await settingService.getPreferences()
  }

  async function updatePreferences(data) {
    saving.value = true
    try { preferences.value = await settingService.updatePreferences(data) }
    finally { saving.value = false }
  }

  return {
    profile, loanTypes, preferences, loading, saving,
    fetchProfile, updateProfile,
    fetchLoanTypes, createLoanType, updateLoanType, deleteLoanType,
    fetchPreferences, updatePreferences,
  }
})
