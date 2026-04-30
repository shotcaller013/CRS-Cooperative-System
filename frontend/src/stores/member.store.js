// src/stores/member.store.js
import { defineStore } from 'pinia'
import { ref } from 'vue'
import { memberService } from '@/services/member.service'

export const useMemberStore = defineStore('member', () => {
  const list       = ref([])
  const selected   = ref(null)
  const loading    = ref(false)
  const saving     = ref(false)
  const pagination = ref({ total: 0, per_page: 15, current_page: 1, last_page: 1 })
  const dropdown   = ref([])

  async function fetchList(params = {}) {
    loading.value = true
    try {
      const res = await memberService.index(params)
      list.value       = res.data.data
      pagination.value = res.data.meta.pagination
    } finally {
      loading.value = false
    }
  }

  async function fetchOne(id) {
    loading.value = true
    try {
      const res = await memberService.show(id)
      selected.value = res.data.data
      return selected.value
    } finally {
      loading.value = false
    }
  }

  async function fetchDropdown() {
    const res = await memberService.dropdown()
    dropdown.value = res.data.data
  }

  async function create(payload) {
    saving.value = true
    try {
      const res = await memberService.store(payload)
      return res.data.data
    } finally {
      saving.value = false
    }
  }

  async function update(id, payload) {
    saving.value = true
    try {
      const res = await memberService.update(id, payload)
      return res.data.data
    } finally {
      saving.value = false
    }
  }

  async function remove(id) {
    await memberService.destroy(id)
    list.value = list.value.filter(m => m.id !== id)
  }

  function clearSelected() {
    selected.value = null
  }

  return {
    list, selected, loading, saving, pagination, dropdown,
    fetchList, fetchOne, fetchDropdown, create, update, remove, clearSelected,
  }
})
