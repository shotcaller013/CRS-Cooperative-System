// src/stores/loan.store.js
import { defineStore } from 'pinia'
import { ref } from 'vue'
import { loanService } from '@/services/loan.service'

export const useLoanStore = defineStore('loan', () => {
  const list       = ref([])
  const selected   = ref(null)
  const pipeline   = ref({})
  const loanTypes  = ref([])
  const loading    = ref(false)
  const saving     = ref(false)
  const pagination = ref({ total: 0, per_page: 15, current_page: 1, last_page: 1 })

  async function fetchList(params = {}) {
    loading.value = true
    try {
      const res = await loanService.index(params)
      list.value       = res.data.data
      pagination.value = res.data.meta.pagination
    } finally {
      loading.value = false
    }
  }

  async function fetchOne(id) {
    loading.value = true
    try {
      const res = await loanService.show(id)
      selected.value = res.data.data
      return selected.value
    } finally {
      loading.value = false
    }
  }

  async function fetchPipeline() {
    loading.value = true
    try {
      const res = await loanService.pipeline()
      pipeline.value = res.data.data
    } finally {
      loading.value = false
    }
  }

  async function fetchLoanTypes() {
    const res = await loanService.loanTypes()
    loanTypes.value = res.data.data
  }

  async function calculate(payload) {
    const res = await loanService.calculate(payload)
    return res.data.data
  }

  async function create(payload) {
    saving.value = true
    try {
      const res = await loanService.store(payload)
      return res.data.data
    } finally {
      saving.value = false
    }
  }

  async function update(id, payload) {
    saving.value = true
    try {
      const res = await loanService.update(id, payload)
      return res.data.data
    } finally {
      saving.value = false
    }
  }

  async function approve(id, payload = {}) {
    saving.value = true
    try {
      const res = await loanService.approve(id, payload)
      return res.data.data
    } finally {
      saving.value = false
    }
  }

  async function remove(id) {
    await loanService.destroy(id)
    list.value = list.value.filter(l => l.id !== id)
  }

  function clearSelected() {
    selected.value = null
  }

  return {
    list, selected, pipeline, loanTypes, loading, saving, pagination,
    fetchList, fetchOne, fetchPipeline, fetchLoanTypes,
    calculate, create, update, approve, remove, clearSelected,
  }
})
