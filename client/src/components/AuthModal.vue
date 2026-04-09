<script setup lang="ts">
import { ref, watch } from 'vue'
import { useAuth } from '../composables/useAuth'

const props = defineProps<{ open: boolean }>()
const emit = defineEmits<{ close: [] }>()

const { user, loading, login, register, logout } = useAuth()

type Tab = 'signin' | 'signup'
const tab = ref<Tab>('signin')
const error = ref('')

const si = ref({ email: '', password: '' })
const su = ref({ name: '', email: '', password: '', confirm: '' })

watch(() => props.open, (v) => { if (v) error.value = '' })

function switchTab(t: Tab) {
  tab.value = t
  error.value = ''
}

async function doSignIn() {
  error.value = ''
  if (!si.value.email || !si.value.password) { error.value = 'Please fill in all fields.'; return }
  try {
    await login(si.value.email, si.value.password)
    si.value = { email: '', password: '' }
    emit('close')
  } catch (e) {
    error.value = (e as Error).message
  }
}

async function doSignUp() {
  error.value = ''
  const { name, email, password, confirm } = su.value
  if (!name || !email || !password || !confirm) { error.value = 'Please fill in all fields.'; return }
  if (password !== confirm) { error.value = 'Passwords do not match.'; return }
  if (password.length < 6) { error.value = 'Password must be at least 6 characters.'; return }
  try {
    await register(name, email, password, confirm)
    su.value = { name: '', email: '', password: '', confirm: '' }
    emit('close')
  } catch (e) {
    error.value = (e as Error).message
  }
}

async function doLogout() {
  await logout()
  emit('close')
}

function initials(name: string): string {
  return name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()
}
</script>

<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="open" class="mm-overlay" @click.self="emit('close')">
        <Transition name="slide-up">
          <div v-if="open" class="mm-modal" role="dialog" aria-modal="true">

            <div class="mm-modal-header">
              <span class="mm-modal-logo">𝕄</span>
              <span class="mm-modal-title">MemeMuseum</span>
              <button class="mm-close" @click="emit('close')" aria-label="Close">✕</button>
            </div>

            <div v-if="user" class="mm-body">
              <div class="mm-user-block">
                <div class="mm-avatar">{{ initials(user.name) }}</div>
                <div class="mm-user-info">
                  <div class="mm-user-name">{{ user.name }}</div>
                  <div class="mm-user-email">{{ user.email }}</div>
                </div>
              </div>
              <div class="mm-logged-msg">— Curator access granted —</div>
              <button class="mm-btn-ghost" :disabled="loading" @click="doLogout">
                {{ loading ? 'Signing out…' : 'Sign out' }}
              </button>
            </div>

            <div v-else>
              <div class="mm-tabs">
                <button class="mm-tab" :class="{ active: tab === 'signin' }" @click="switchTab('signin')">Sign In</button>
                <button class="mm-tab" :class="{ active: tab === 'signup' }" @click="switchTab('signup')">Register</button>
              </div>

              <div class="mm-body">
                <template v-if="tab === 'signin'">
                  <div class="mm-field">
                    <label>Email address</label>
                    <input v-model="si.email" type="email" placeholder="curator@example.com" @keydown.enter="doSignIn" @input="error = ''" />
                  </div>
                  <div class="mm-field">
                    <label>Password</label>
                    <input v-model="si.password" type="password" placeholder="••••••••" @keydown.enter="doSignIn" @input="error = ''" />
                  </div>
                  <div v-if="error" class="mm-error">{{ error }}</div>
                  <button class="mm-btn-primary" :disabled="loading" @click="doSignIn">
                    <span v-if="loading" class="mm-spinner" />
                    {{ loading ? 'Signing in…' : 'Enter the Museum' }}
                  </button>
                  <div class="mm-switch">
                    No account? <a href="#" @click.prevent="switchTab('signup')">Register here</a>
                  </div>
                </template>

                <template v-else>
                  <div class="mm-field">
                    <label>Display name</label>
                    <input v-model="su.name" type="text" placeholder="Anonymous Curator" @input="error = ''" />
                  </div>
                  <div class="mm-field">
                    <label>Email address</label>
                    <input v-model="su.email" type="email" placeholder="curator@example.com" @input="error = ''" />
                  </div>
                  <div class="mm-field">
                    <label>Password</label>
                    <input v-model="su.password" type="password" placeholder="Min. 6 characters" @input="error = ''" />
                  </div>
                  <div class="mm-field">
                    <label>Confirm password</label>
                    <input v-model="su.confirm" type="password" placeholder="••••••••" @keydown.enter="doSignUp" @input="error = ''" />
                  </div>
                  <div v-if="error" class="mm-error">{{ error }}</div>
                  <button class="mm-btn-primary" :disabled="loading" @click="doSignUp">
                    <span v-if="loading" class="mm-spinner" />
                    {{ loading ? 'Registering…' : 'Join the Collection' }}
                  </button>
                  <div class="mm-switch">
                    Already a curator? <a href="#" @click.prevent="switchTab('signin')">Sign in</a>
                  </div>
                </template>
              </div>
            </div>

            <div class="mm-modal-foot">
              Anonymous imageboard · All works property of their creators
            </div>

          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.mm-overlay { position: fixed; inset: 0; z-index: 1000; background: rgba(46,31,20,0.72); display: flex; align-items: center; justify-content: center; padding: 1rem; }
.mm-modal { width: 100%; max-width: 380px; background: var(--cream); border: 1px solid var(--brown-lt); box-shadow: 0 8px 40px rgba(46,31,20,0.45); overflow: hidden; }
.mm-modal-header { background: var(--espresso); border-bottom: 3px solid var(--orange); padding: 10px 16px; display: flex; align-items: center; gap: 10px; }
.mm-modal-logo { font-family: var(--font-serif); font-size: 22px; color: var(--orange-lt); line-height: 1; }
.mm-modal-title { font-family: var(--font-serif); font-size: 14px; color: var(--cream); font-weight: bold; letter-spacing: 0.5px; flex: 1; }
.mm-close { background: none; border: none; color: var(--grey); font-size: 13px; cursor: pointer; padding: 2px 4px; line-height: 1; transition: color 0.15s; }
.mm-close:hover { color: var(--cream); }
.mm-tabs { display: flex; background: var(--cream-dark); border-bottom: 1px solid var(--grey-lt); }
.mm-tab { flex: 1; padding: 9px 0; background: none; border: none; border-bottom: 2px solid transparent; font-family: var(--font-sans); font-size: 11px; font-weight: bold; letter-spacing: 1.5px; text-transform: uppercase; color: var(--grey-dk); cursor: pointer; transition: color 0.15s, border-color 0.15s, background 0.15s; }
.mm-tab:hover { color: var(--brown-dk); background: rgba(0,0,0,0.03); }
.mm-tab.active { color: var(--orange); border-bottom-color: var(--orange); background: var(--cream); }
.mm-body { padding: 20px 20px 16px; }
.mm-field { margin-bottom: 13px; }
.mm-field label { display: block; font-size: 10px; font-weight: bold; letter-spacing: 1.5px; text-transform: uppercase; color: var(--grey-dk); margin-bottom: 5px; font-family: var(--font-sans); }
.mm-field input { width: 100%; height: 34px; padding: 0 10px; background: #fff; border: 1px solid var(--grey-lt); font-family: var(--font-mono); font-size: 12px; color: var(--espresso); outline: none; transition: border-color 0.15s, box-shadow 0.15s; }
.mm-field input:focus { border-color: var(--orange); box-shadow: 0 0 0 3px rgba(212,98,26,0.12); }
.mm-field input::placeholder { color: var(--grey); font-family: var(--font-mono); }
.mm-error { font-size: 11px; color: var(--orange); border-left: 2px solid var(--orange); padding-left: 8px; margin-bottom: 12px; font-family: var(--font-mono); }
.mm-btn-primary { width: 100%; padding: 9px 0; background: var(--orange); color: #fff; border: 1px solid var(--orange-lt); font-family: var(--font-sans); font-size: 12px; font-weight: bold; letter-spacing: 0.5px; cursor: pointer; transition: background 0.15s; display: flex; align-items: center; justify-content: center; gap: 8px; }
.mm-btn-primary:hover:not(:disabled) { background: var(--orange-lt); }
.mm-btn-primary:disabled { opacity: 0.55; cursor: not-allowed; }
.mm-btn-ghost { width: 100%; padding: 9px 0; background: transparent; color: var(--brown-dk); border: 1px solid var(--grey-lt); font-family: var(--font-sans); font-size: 12px; font-weight: bold; letter-spacing: 0.5px; cursor: pointer; transition: border-color 0.15s, background 0.15s; }
.mm-btn-ghost:hover:not(:disabled) { border-color: var(--brown); background: var(--cream-dark); }
.mm-btn-ghost:disabled { opacity: 0.55; cursor: not-allowed; }
.mm-spinner { display: inline-block; width: 12px; height: 12px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: mm-spin 0.6s linear infinite; }
@keyframes mm-spin { to { transform: rotate(360deg); } }
.mm-switch { text-align: center; margin-top: 12px; font-size: 11px; color: var(--grey-dk); }
.mm-switch a { color: var(--orange); text-decoration: none; font-weight: bold; }
.mm-switch a:hover { text-decoration: underline; }
.mm-user-block { display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--cream-dark); border: 1px solid var(--grey-lt); margin-bottom: 14px; }
.mm-avatar { width: 38px; height: 38px; background: var(--brown-dk); border: 2px solid var(--orange); color: var(--orange-lt); font-family: var(--font-serif); font-size: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.mm-user-name { font-family: var(--font-serif); font-size: 14px; font-weight: bold; color: var(--brown-dk); }
.mm-user-email { font-family: var(--font-mono); font-size: 11px; color: var(--grey-dk); margin-top: 2px; }
.mm-logged-msg { text-align: center; font-family: var(--font-mono); font-size: 10px; letter-spacing: 2px; color: var(--grey); margin-bottom: 14px; }
.mm-modal-foot { background: var(--parchment); border-top: 1px solid var(--grey-lt); padding: 7px 16px; font-size: 10px; color: var(--grey); font-style: italic; text-align: center; letter-spacing: 0.3px; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.slide-up-enter-active { transition: transform 0.22s ease, opacity 0.22s ease; }
.slide-up-leave-active { transition: transform 0.18s ease, opacity 0.18s ease; }
.slide-up-enter-from { transform: translateY(16px); opacity: 0; }
.slide-up-leave-to { transform: translateY(8px); opacity: 0; }
</style>