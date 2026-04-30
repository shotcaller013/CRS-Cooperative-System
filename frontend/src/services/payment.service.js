// src/services/payment.service.js
import { request } from '@/composables/useApi'

export const paymentService = {
  index:  (params = {}) => {
    const q = new URLSearchParams(params).toString()
    return request(`/payments${q ? '?' + q : ''}`)
  },
  store:  (data)   => request('/payments', { method: 'POST', body: data }),
  show:   (id)     => request(`/payments/${id}`),
  byLoan: (loanId) => request(`/loans/${loanId}/payments`),
}
