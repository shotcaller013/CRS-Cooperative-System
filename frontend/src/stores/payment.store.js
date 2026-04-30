// src/stores/payment.store.js
import { defineStore } from 'pinia'
import { ref } from 'vue'
import { paymentService } from '@/services/payment.service'

export const usePaymentStore = defineStore('payment', () => {
  const list         = ref([])
  const loanPayments = ref([])
  const loading      = ref(false)
  const saving       = ref(false)

  async function fetchList(params = {}) {
    loading.value = true
    try {
      const data = await paymentService.index(params)
      list.value = Array.isArray(data) ? data : (data.data ?? [])
    } finally { loading.value = false }
  }

  async function fetchByLoan(loanId) {
    loading.value = true
    try {
      const data = await paymentService.byLoan(loanId)
      loanPayments.value = Array.isArray(data) ? data : (data.data ?? [])
    } finally { loading.value = false }
  }

  async function record(data) {
    saving.value = true
    try {
      return await paymentService.store(data)
    } finally { saving.value = false }
  }

  return {
    list, loanPayments, loading, saving,
    fetchList, fetchByLoan, record,
  }
})
