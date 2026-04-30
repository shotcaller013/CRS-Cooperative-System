// src/services/loan.service.js
import api from '@/services/api'

export const loanService = {
  index:      (params = {}) => api.get('/loans', { params }),
  show:       (id)          => api.get(`/loans/${id}`),
  store:      (data)        => api.post('/loans', data),
  update:     (id, data)    => api.put(`/loans/${id}`, data),
  destroy:    (id)          => api.delete(`/loans/${id}`),
  approve:    (id, data)    => api.post(`/loans/${id}/approve`, data),
  pipeline:   ()            => api.get('/loans/pipeline'),
  loanTypes:  ()            => api.get('/loan-types'),
  calculate:  (data)        => api.post('/loans/calculate', data),
}
