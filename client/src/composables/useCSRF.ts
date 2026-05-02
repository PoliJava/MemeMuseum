const API_BASE = import.meta.env.VITE_API_URL ?? ''

let pending: Promise<void> | null = null

export function csrf(): Promise<void> {
  if (!pending) {
    pending = fetch(`${API_BASE}/sanctum/csrf-cookie`, { credentials: 'include' })
      .then(() => { pending = null })
      .catch(() => { pending = null })
  }
  return pending
}

export function xsrfToken(): string {
  const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/)
  return match ? decodeURIComponent(match[1]) : ''
}
