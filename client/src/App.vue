<script setup lang="ts">
import { ref, onMounted } from "vue";
import AuthModal from "./components/AuthModal.vue";
import PostModal from "./components/PostModal.vue";
import { useAuth } from "./composables/useAuth";
import { usePostModal } from "./composables/usePostModal";

const { user, logout, fetchUser } = useAuth();
const {
  isOpen: showPostModal,
  boardSlug: currentBoardSlug,
  open: openPost,
  close: closePost,
} = usePostModal();
const showAuth = ref(false);

onMounted(() => fetchUser());

function handleThreadCreated(meme: any) {
  closePost();
  // Tell the active BoardView to re-fetch
  window.dispatchEvent(
    new CustomEvent("boardview:refresh", {
      detail: { slug: currentBoardSlug.value },
    }),
  );
}

function requireAuthThenPost(slug?: string) {
  if (!user.value) {
    showAuth.value = true;
  } else {
    openPost(slug);
  }
}
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
          <a href="#">About</a>

          <a
            v-if="!user"
            href="#"
            class="nav-login"
            @click.prevent="showAuth = true"
          >
            Sign In
          </a>
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
        <h1 class="hero-title">
          The World's Premier<br /><em>Meme Repository</em>
        </h1>
        <p class="hero-desc">
          A living archive of internet culture. Browse the boards, contribute
          original works, and participate in the ongoing curatorial discourse.
        </p>
        <div class="hero-actions">
          <router-link to="/" class="btn-primary">Enter the Museum</router-link>
          <a
            v-if="!user"
            href="#"
            class="btn-ghost"
            @click.prevent="showAuth = true"
          >
            Sign In to Contribute
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
        <span class="ticker-notice"
          >This is an anonymous imageboard. Curate responsibly.</span
        >
      </div>
    </div>

    <!-- MAIN -->
    <main>
      <router-view />
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

    <!-- GLOBAL MODALS -->
    <AuthModal :open="showAuth" @close="showAuth = false" />
    <PostModal
      :open="showPostModal"
      :board-slug="currentBoardSlug"
      @close="closePost"
      @thread-created="handleThreadCreated"
    />
  </div>
</template>

<style>
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
