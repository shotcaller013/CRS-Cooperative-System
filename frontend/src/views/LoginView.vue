<template>
  <div class="login-page">
    <div class="login-card">
      <div class="login-brand">
        <div class="brand-logo"><span class="brand-crs">CRS</span></div>
        <div>
          <div class="brand-name">Credit Cooperative</div>
          <div class="brand-sub">Loan Management System</div>
        </div>
      </div>

      <form class="login-form" @submit.prevent="submit">
        <div class="field">
          <label>Email</label>
          <input
            v-model="form.email"
            type="email"
            placeholder="you@crs.com"
            autocomplete="email"
            required
          />
        </div>
        <div class="field">
          <label>Password</label>
          <input
            v-model="form.password"
            type="password"
            placeholder="••••••••"
            autocomplete="current-password"
            required
          />
        </div>

        <p v-if="error" class="login-error">{{ error }}</p>

        <button type="submit" class="login-btn" :disabled="loading">
          {{ loading ? 'Signing in…' : 'Sign in' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const router = useRouter()

const form = reactive({
  email: '',
  password: '',
})

const loading = ref(false)
const error = ref('')

async function submit() {
  loading.value = true
  error.value = ''

  try {
    // ✅ DIRECT login (NO csrf needed)
    const res = await api.post('/login', form)

    // store token
    if (res.data.token) {
      localStorage.setItem('auth_token', res.data.token)
    }

    router.replace('/')
  } catch (e) {
    error.value =
      e.response?.data?.message ||
      e.message ||
      'Login failed'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  background: var(--coop-dark);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
}

.login-card {
  width: 100%;
  max-width: 380px;
  background: var(--coop-surface);
  border: 1px solid var(--coop-border);
  border-radius: 12px;
  padding: 36px 32px;
}

.login-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 32px;
}
.brand-logo {
  width: 42px; height: 42px; border-radius: 9px;
  background: var(--coop-red);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.brand-crs  { font-family: var(--font-serif); font-size: 15px; color: #fff; }
.brand-name { font-size: 13px; font-weight: 600; color: var(--coop-cream); }
.brand-sub  { font-size: 11px; color: var(--coop-muted); margin-top: 2px; }

.login-form { display: flex; flex-direction: column; gap: 18px; }

.field { display: flex; flex-direction: column; gap: 6px; }
.field label { font-size: 12px; font-weight: 600; color: var(--coop-muted); text-transform: uppercase; letter-spacing: 0.5px; }
.field input {
  background: var(--coop-mid);
  border: 1px solid var(--coop-border);
  border-radius: 7px;
  padding: 10px 12px;
  color: var(--coop-cream);
  font-size: 14px;
  outline: none;
  transition: border-color 0.15s;
}
.field input:focus { border-color: var(--coop-red-soft); }
.field input::placeholder { color: var(--coop-muted); }

.login-error {
  font-size: 13px;
  color: #f87171;
  background: rgba(248,113,113,.08);
  border: 1px solid rgba(248,113,113,.2);
  border-radius: 6px;
  padding: 8px 12px;
  margin: 0;
}

.login-btn {
  margin-top: 4px;
  background: var(--coop-red);
  color: #fff;
  border: none;
  border-radius: 7px;
  padding: 11px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.15s;
}
.login-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.login-btn:not(:disabled):hover { opacity: 0.88; }
</style>
