import api from './api'

// 🔑 LOGIN
export async function login(data) {
  // Required for Sanctum
  await api.get('/sanctum/csrf-cookie')

  const res = await api.post('/login', data)

  // if using token response
  if (res.data.token) {
    localStorage.setItem('auth_token', res.data.token)
  }

  return res.data
}

// 🚪 LOGOUT
export function logout() {
  localStorage.removeItem('auth_token')
  return api.post('/logout')
}