// src/services/member.service.js
import api from '@/services/api'

export const memberService = {
  index:    (params = {})       => api.get('/members', { params }),
  show:     (id)                => api.get(`/members/${id}`),
  store:    (data)              => api.post('/members', data),
  update:   (id, data)          => api.put(`/members/${id}`, data),
  destroy:  (id)                => api.delete(`/members/${id}`),
  dropdown: ()                  => api.get('/members/dropdown'),
}
