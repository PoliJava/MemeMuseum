const API_BASE = import.meta.env.VITE_API_URL ?? 'http://localhost:8000'

async function csrf() {
  await fetch(`${API_BASE}/sanctum/csrf-cookie`, { credentials: 'include' })
}

function xsrfToken(): string {
  const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/)
  return match ? decodeURIComponent(match[1]) : ''
}

async function request<T>(
  method: string,
  path: string,
  data?: any,
  isFile = false
): Promise<T> {
  await csrf()
  const headers: HeadersInit = {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'X-XSRF-TOKEN': xsrfToken(),
  }
  const options: RequestInit = {
    method,
    credentials: 'include',
    headers,
  }
  if (isFile && data instanceof FormData) {
    options.body = data
    // Non impostare Content-Type, il browser lo imposta con il boundary
  } else if (data) {
    headers['Content-Type'] = 'application/json'
    options.body = JSON.stringify(data)
  }
  // Assicura il prefisso /api (tranne per sanctum che è già escluso)
  const fullPath = path.startsWith('/api') ? path : `/api${path}`
  const res = await fetch(`${API_BASE}${fullPath}`, options)
  const json = await res.json()
  if (!res.ok) {
    const message = json.message || (json.errors ? Object.values(json.errors).flat()[0] : 'API error')
    throw new Error(message)
  }
  return json as T
}

export function useApi() {
  return {
    get: <T>(path: string) => request<T>('GET', path),
    post: <T>(path: string, data?: any) => request<T>('POST', path, data),
    put: <T>(path: string, data?: any) => request<T>('PUT', path, data),
    del: <T>(path: string) => request<T>('DELETE', path),
    postFile: <T>(path: string, fd: FormData) => request<T>('POST', path, fd, true),
  }
}