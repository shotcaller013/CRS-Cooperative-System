// src/composables/useDate.js
export function useDate() {
  function formatDate(date, opts = {}) {
    if (!date) return '—'
    return new Date(date).toLocaleDateString('en-PH', {
      year: 'numeric', month: 'short', day: 'numeric', ...opts,
    })
  }
  function formatDateTime(date) {
    if (!date) return '—'
    return new Date(date).toLocaleString('en-PH', {
      year: 'numeric', month: 'short', day: 'numeric',
      hour: '2-digit', minute: '2-digit',
    })
  }
  return { formatDate, formatDateTime }
}
