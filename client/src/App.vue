<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import AuthModal from './components/AuthModal.vue'
import PostModal from './components/PostModal.vue'
import { useAuth } from './composables/useAuth'

const { user, logout, fetchUser } = useAuth()
const showAuth = ref(false)
const showPostModal = ref(false)
const currentBoardSlug = ref<string | null>(null)

onMounted(() => fetchUser())

function openPostModal(boardSlug?: string) {
  currentBoardSlug.value = boardSlug || null
  if (!user.value) {
    showAuth.value = true
  } else {
    showPostModal.value = true
  }
}

// Listener per l'evento custom emesso da BoardView
function handleCustomEvent(e: CustomEvent) {
  if (e.type === 'openPostModal') {
    openPostModal((e as CustomEvent).detail?.boardSlug)
  }
}

onMounted(() => {
  window.addEventListener('openPostModal', handleCustomEvent as EventListener)
})
onUnmounted(() => {
  window.removeEventListener('openPostModal', handleCustomEvent as EventListener)
})
</script>

<template>
  <div id="museum-root">

    <div class="noise" aria-hidden="true"></div>

    <!-- HEADER -->
    <header>
      <div class="header-inner">
        <div class="header-left">
          <div class="logotype">
            <span class="logo-mark">𝕄</span>
            <div class="logo-text">
              <span class="logo-name">MemeMuseum</span>
              <span class="logo-sub">Est. MMXXV · Open Collection</span>
            </div>
          </div>
        </div>
        <nav class="header-nav">
          <router-link to="/">Collection</router-link>
          <a href="#">Exhibitions</a>
          <a href="#" @click.prevent="openPostModal()">Submit</a>
          <a href="#">About</a>

          <!-- Logged out: Sign In button -->
          <a v-if="!user" href="#" class="nav-login" @click.prevent="showAuth = true">
            Sign In
          </a>

          <!-- Logged in: curator name + sign out -->
          <template v-else>
            <span class="nav-curator">
              <span class="nav-curator-dot"></span>
              {{ user.name }}
            </span>
            <a href="#" class="nav-login" @click.prevent="logout">Sign Out</a>
          </template>
        </nav>
      </div>
    </header>

    <!-- HERO -->
    <section class="hero-banner">
      <div class="hero-inner">
        <div class="hero-label">Welcome to the Collection</div>
        <h1 class="hero-title">The World's Premier<br><em>Meme Repository</em></h1>
        <p class="hero-desc">
          A living archive of internet culture. Browse the boards, contribute original works,
          and participate in the ongoing curatorial discourse.
        </p>
        <div class="hero-actions">
          <a href="#boards" class="btn-primary">Enter the Museum</a>
          <a href="#" class="btn-ghost" @click.prevent="openPostModal()">
            {{ user ? 'Submit a Work' : 'Sign In to Submit' }}
          </a>
        </div>
      </div>
      <div class="hero-deco" aria-hidden="true">
        <div class="deco-frame deco-frame--1">[ EXHIBIT A ]</div>
        <div class="deco-frame deco-frame--2">[ EXHIBIT B ]</div>
        <div class="deco-frame deco-frame--3">[ EXHIBIT C ]</div>
      </div>
    </section>

    <!-- TICKER -->
    <div class="ticker">
      <div class="ticker-inner">
        <span>Visitors online: <strong>4,821</strong></span>
        <span class="sep">·</span>
        <span>Total posts: <strong>36,011</strong></span>
        <span class="sep">·</span>
        <span>Works catalogued: <strong>12,493</strong></span>
        <span class="sep">·</span>
        <span class="ticker-notice">This is an anonymous imageboard. Curate responsibly.</span>
      </div>
    </div>

    <!-- MAIN: router view -->
    <main>
      <router-view @openPostModal="openPostModal" />
    </main>

    <!-- FOOTER -->
    <footer>
      <div class="footer-inner">
        <span class="footer-logo">𝕄 MemeMuseum</span>
        <span>Anonymous imageboard. All works property of their creators.</span>
        <nav class="footer-nav">
          <a href="#">Rules</a>
          <a href="#">Privacy</a>
          <a href="#">Contact</a>
        </nav>
      </div>
    </footer>

    <!-- MODALI GLOBALI -->
    <AuthModal :open="showAuth" @close="showAuth = false" />
    <PostModal
      :open="showPostModal"
      :board-slug="currentBoardSlug"
      @close="showPostModal = false"
      @threadCreated="() => { /* puoi eventualmente ricaricare la board corrente */ }"
    />

  </div>
</template>

<style>
/* Logged-in curator indicator in nav */
.nav-curator {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: var(--brown-lt);
  padding: 5px 10px;
}

.nav-curator-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #4caf78;
  flex-shrink: 0;
}
</style>