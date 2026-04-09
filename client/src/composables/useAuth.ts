import { ref } from 'vue'

export interface AuthUser {
  id: number
  name: string
  email: string
}

const user = ref<AuthUser | null>(null)
const loading = ref(false)

const API = import.meta.env.VITE_API_URL ?? ''

async function csrf(): Promise<void> {
  await fetch(`${API}/sanctum/csrf-cookie`, { credentials: 'include' })
}

function xsrfToken(): string {
  return decodeURIComponent(
    document.cookie.split('; ').find(r => r.startsWith('XSRF-TOKEN='))?.split('=')[1] ?? ''
  )
}

async function apiFetch<T>(path: string, body: Record<string, string>): Promise<T> {
  await csrf()
  const res = await fetch(`${API}${path}`, {
    method: 'POST',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-XSRF-TOKEN': xsrfToken(),
    },
    body: JSON.stringify(body),
  })
  const data = await res.json()
  if (!res.ok) {
    const errors = data.errors as Record<string, string[]> | undefined
    const msg = errors ? Object.values(errors)[0]?.[0] : (data.message as string | undefined)
    throw new Error(msg ?? 'Something went wrong.')
  }
  return data as T
}

async function register(name: string, email: string, password: string, password_confirmation: string): Promise<void> {
  loading.value = true
  try {
    const data = await apiFetch<{ user: AuthUser }>('/api/register', { name, email, password, password_confirmation })
    user.value = data.user
  } finally {
    loading.value = false
  }
}

async function login(email: string, password: string): Promise<void> {
  loading.value = true
  try {
    const data = await apiFetch<{ user: AuthUser }>('/api/login', { email, password })
    user.value = data.user
  } finally {
    loading.value = false
  }
}

async function logout(): Promise<void> {
  loading.value = true
  try {
    await apiFetch('/api/logout', {})
    user.value = null
  } finally {
    loading.value = false
  }
}

async function fetchUser(): Promise<void> {
  try {
    const res = await fetch(`${API}/api/user`, {
      credentials: 'include',
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
    if (res.ok) user.value = (await res.json()) as AuthUser
  } catch {
    // not authenticated — silently ignore
  }
}

export function useAuth() {
  return { user, loading, register, login, logout, fetchUser }
}