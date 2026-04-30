import { ref } from 'vue'

const TOKEN_KEY = 'crs_token'
const USER_KEY  = 'crs_user'

const token = ref(localStorage.getItem(TOKEN_KEY) || null)
const user  = ref(JSON.parse(localStorage.getItem(USER_KEY) || 'null'))

export function useAuth() {
  function setSession(data) {
    token.value = data.token
    user.value  = data.user
    localStorage.setItem(TOKEN_KEY, data.token)
    localStorage.setItem(USER_KEY, JSON.stringify(data.user))
  }

  function clearSession() {
    token.value = null
    user.value  = null
    localStorage.removeItem(TOKEN_KEY)
    localStorage.removeItem(USER_KEY)
  }

  const isAuthenticated = () => !!token.value

  return { token, user, setSession, clearSession, isAuthenticated }
}
