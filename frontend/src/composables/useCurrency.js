// src/composables/useCurrency.js
export function useCurrency() {
  function formatCurrency(amount, currency = 'PHP', locale = 'en-PH') {
    if (amount === null || amount === undefined) return '—'
    return new Intl.NumberFormat(locale, {
      style: 'currency', currency,
      minimumFractionDigits: 2, maximumFractionDigits: 2,
    }).format(amount)
  }
  function formatNumber(n, decimals = 2) {
    return new Intl.NumberFormat('en-PH', { minimumFractionDigits: decimals }).format(n)
  }
  return { formatCurrency, formatNumber }
}
