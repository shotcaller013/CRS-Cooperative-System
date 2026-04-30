const BASE = '/api/v1'

function getToken() {
  return localStorage.getItem('crs_token')
}

export async function request(path, options = {}) {
  const token = getToken()
  const res = await fetch(`${BASE}${path}`, {
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...options.headers,
    },
    ...options,
    body: options.body ? JSON.stringify(options.body) : undefined,
  })

  if (!res.ok) {
    const json = await res.json().catch(() => ({}))
    throw new Error(json.message || `HTTP ${res.status}`)
  }

  const json = await res.json()
  return json.data !== undefined ? json.data : json
}

function normalizeLoan(l) {
  if (!l) return l
  return {
    ...l,
    first_name:      l.member?.first_name  ?? l.first_name,
    last_name:       l.member?.last_name   ?? l.last_name,
    member_no:       l.member?.member_no   ?? l.member_no,
    loan_type_label: l.loan_type?.label    ?? l.loan_type_label,
    schedule:        l.amortization_schedules ?? l.schedule ?? [],
  }
}

function normalizeMember(m) {
  if (!m) return m
  return {
    ...m,
    active_loans: m.active_loans_count ?? m.active_loans ?? 0,
  }
}

export const api = {
  // ── Members ───────────────────────────────────────────────────
  getMembers: async (params = {}) => {
    const q = new URLSearchParams(params).toString()
    const data = await request(`/members${q ? '?' + q : ''}`)
    return (Array.isArray(data) ? data : []).map(normalizeMember)
  },

  getMember: async (id) => {
    const data = await request(`/members/${id}`)
    const m = normalizeMember(data)
    if (m.loans) m.loans = m.loans.map(normalizeLoan)
    return m
  },

  createMember: (data) => request('/members', { method: 'POST', body: data }),
  updateMember: (id, data) => request(`/members/${id}`, { method: 'PUT', body: data }),
  deleteMember: (id) => request(`/members/${id}`, { method: 'DELETE' }),

  // ── Loans ─────────────────────────────────────────────────────
  getLoans: async (params = {}) => {
    const q = new URLSearchParams(params).toString()
    const data = await request(`/loans${q ? '?' + q : ''}`)
    return (Array.isArray(data) ? data : []).map(normalizeLoan)
  },

  getLoan: async (id) => normalizeLoan(await request(`/loans/${id}`)),

  getPipeline: async () => {
    const data = await request('/loans/pipeline')
    const result = {}
    for (const [status, loans] of Object.entries(data)) {
      result[status] = Array.isArray(loans) ? loans.map(normalizeLoan) : []
    }
    return result
  },

  createLoan: async (data) => normalizeLoan(await request('/loans', { method: 'POST', body: data })),
  updateLoan:  async (id, data) => normalizeLoan(await request(`/loans/${id}`, { method: 'PUT', body: data })),
  calcLoan:    (data) => request('/loans/calculate', { method: 'POST', body: data }),

  // ── Loan types ────────────────────────────────────────────────
  getLoanTypes: () => request('/loan-types'),

  // ── Auth ──────────────────────────────────────────────────────
  login:  (data) => request('/login',  { method: 'POST', body: data }),
  logout: ()     => request('/logout', { method: 'POST' }),
}
