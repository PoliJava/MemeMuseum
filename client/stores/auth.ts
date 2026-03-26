import { defineStore } from 'pinia'
import axios from '../plugins/axios' 

interface User {
  id: number
  name: string
  email: string
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null as User | null,
    token: localStorage.getItem('token') || null,
  }),
  actions: {
    async login(email: string, password: string) {
      const response = await axios.post('/api/login', { email, password })
      this.token = response.data.token
      this.user = response.data.user
      if (this.token) localStorage.setItem('token', this.token)
      // ❌ NO manual header assignment here – the interceptor handles it
    },
    logout() {
      axios.post('/api/logout')
      this.token = null
      this.user = null
      localStorage.removeItem('token')    
    }, 
  },
})