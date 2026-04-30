// src/stores/report.store.js
import { defineStore } from 'pinia'
import { ref } from 'vue'
import { reportService } from '@/services/report.service'

export const useReportStore = defineStore('report', () => {
  const collection  = ref(null)
  const aging       = ref(null)
  const outstanding = ref(null)
  const loading     = ref(false)
  const exporting   = ref(false)

  async function fetchCollection(params = {}) {
    loading.value = true
    try {
      const data = await reportService.collection(params)
      collection.value = data
      return data
    } finally { loading.value = false }
  }

  async function fetchAging(params = {}) {
    loading.value = true
    try {
      const data = await reportService.aging(params)
      aging.value = data
      return data
    } finally { loading.value = false }
  }

  async function fetchOutstanding(params = {}) {
    loading.value = true
    try {
      const data = await reportService.outstanding(params)
      outstanding.value = data
      return data
    } finally { loading.value = false }
  }

  async function exportReport(type, params, format) {
    exporting.value = true
    try {
      const ext = format === 'excel' ? 'xlsx' : 'pdf'
      const filename = `${type}_report_${new Date().toISOString().slice(0, 10)}.${ext}`
      await reportService.download(`/reports/${type}`, { ...params, export: format }, filename)
    } finally { exporting.value = false }
  }

  return {
    collection, aging, outstanding, loading, exporting,
    fetchCollection, fetchAging, fetchOutstanding, exportReport,
  }
})
