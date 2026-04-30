// src/services/report.service.js
import { request } from '@/composables/useApi'

const BASE = '/api/v1'

async function downloadReport(path, params, filename) {
  const token = localStorage.getItem('crs_token')
  const q = new URLSearchParams(params).toString()
  const res = await fetch(`${BASE}${path}${q ? '?' + q : ''}`, {
    headers: {
      Authorization: token ? `Bearer ${token}` : '',
      Accept: '*/*',
    },
  })
  if (!res.ok) throw new Error(`Export failed: HTTP ${res.status}`)
  const blob = await res.blob()
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}

function buildQuery(params) {
  const q = new URLSearchParams(
    Object.fromEntries(Object.entries(params).filter(([, v]) => v != null && v !== ''))
  ).toString()
  return q ? '?' + q : ''
}

export const reportService = {
  collection:  (p = {}) => request(`/reports/collection${buildQuery(p)}`),
  aging:       (p = {}) => request(`/reports/aging${buildQuery(p)}`),
  outstanding: (p = {}) => request(`/reports/outstanding${buildQuery(p)}`),
  download: downloadReport,
}
