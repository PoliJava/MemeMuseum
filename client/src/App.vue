<script setup>
import { ref, onMounted } from "vue";
import "./style.css";

const boards = ref([
  {
    id: "classic",
    slug: "/cls/",
    name: "Classics",
    description: "The canon. Pre-2012 artifacts and foundational works.",
    posts: 1842,
    active: 312,
  },
  {
    id: "reaction",
    slug: "/rx/",
    name: "Reaction",
    description: "Expressions, faces, and the full spectrum of human emotion.",
    posts: 5610,
    active: 891,
  },
  {
    id: "dank",
    slug: "/dank/",
    name: "Dank",
    description: "Contemporary works of ironic and post-ironic significance.",
    posts: 9204,
    active: 1430,
  },
  {
    id: "oc",
    slug: "/oc/",
    name: "Original Content",
    description: "New acquisitions. All works must be original.",
    posts: 743,
    active: 98,
  },
  {
    id: "meta",
    slug: "/meta/",
    name: "Meta",
    description: "Discussion of the museum itself. Format theory. Taxonomy.",
    posts: 220,
    active: 44,
  },
  {
    id: "archive",
    slug: "/arc/",
    name: "Archive",
    description: "Preserved specimens. Read-only. Do not disturb.",
    posts: 18392,
    active: 0,
  },
]);

const recentPosts = ref([
  { id: 1, board: "/dank/", title: "The One With The Dog", replies: 47, images: 12, time: "2 min ago" },
  { id: 2, board: "/cls/", title: "Distracted Boyfriend: A Reappraisal", replies: 83, images: 5, time: "11 min ago" },
  { id: 3, board: "/rx/", title: "New Wojak variants — taxonomy thread", replies: 201, images: 88, time: "23 min ago" },
  { id: 4, board: "/oc/", title: "My submission for the Q2 exhibition", replies: 14, images: 3, time: "34 min ago" },
  { id: 5, board: "/dank/", title: "Irony levels are off the chart", replies: 66, images: 21, time: "51 min ago" },
  { id: 6, board: "/meta/", title: "Should shitposts have their own wing?", replies: 39, images: 0, time: "1 hr ago" },
]);

const stats = ref({ visitors: "4,821", posts: "36,011", memes: "12,493" });
</script>

<template>
  <div id="museum-root">

    <!-- NOISE TEXTURE OVERLAY -->
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
          <a href="#">Collection</a>
          <a href="#">Exhibitions</a>
          <a href="#">Submit</a>
          <a href="#">About</a>
          <a href="#" class="nav-login">Sign In</a>
        </nav>
      </div>
    </header>

    <!-- HERO BANNER -->
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
          <a href="#" class="btn-ghost">Submit a Work</a>
        </div>
      </div>
      <div class="hero-deco" aria-hidden="true">
        <div class="deco-frame deco-frame--1">[ EXHIBIT A ]</div>
        <div class="deco-frame deco-frame--2">[ EXHIBIT B ]</div>
        <div class="deco-frame deco-frame--3">[ EXHIBIT C ]</div>
      </div>
    </section>

    <!-- STATS TICKER -->
    <div class="ticker">
      <div class="ticker-inner">
        <span>Visitors online: <strong>{{ stats.visitors }}</strong></span>
        <span class="sep">·</span>
        <span>Total posts: <strong>{{ stats.posts }}</strong></span>
        <span class="sep">·</span>
        <span>Works catalogued: <strong>{{ stats.memes }}</strong></span>
        <span class="sep">·</span>
        <span class="ticker-notice">This is an anonymous imageboard. Curate responsibly.</span>
      </div>
    </div>

    <!-- MAIN CONTENT -->
    <main>
      <div class="content-grid">

        <!-- BOARDS -->
        <section class="boards-section" id="boards">
          <div class="section-header">
            <h2 class="section-title">Galleries &amp; Boards</h2>
            <span class="section-rule"></span>
          </div>

          <div class="boards-table">
            <div class="boards-table-head">
              <span>Board</span>
              <span>Description</span>
              <span class="col-right">Posts</span>
              <span class="col-right">Active</span>
            </div>
            <a
              v-for="board in boards"
              :key="board.id"
              href="#"
              class="board-row"
              :class="{ 'board-row--archived': board.active === 0 }"
            >
              <span class="board-slug">{{ board.slug }}</span>
              <span class="board-info">
                <span class="board-name">{{ board.name }}</span>
                <span class="board-desc">{{ board.description }}</span>
              </span>
              <span class="board-stat col-right">{{ board.posts.toLocaleString() }}</span>
              <span class="board-stat col-right">
                <span v-if="board.active > 0" class="active-dot"></span>
                <span v-else class="archived-tag">archived</span>
                {{ board.active > 0 ? board.active : '' }}
              </span>
            </a>
          </div>
        </section>

        <!-- SIDEBAR -->
        <aside class="sidebar">

          <!-- Recent Posts -->
          <div class="sidebar-block">
            <div class="sidebar-block-title">Recent Posts</div>
            <ul class="recent-list">
              <li v-for="post in recentPosts" :key="post.id" class="recent-item">
                <a href="#" class="recent-link">
                  <span class="recent-board">{{ post.board }}</span>
                  <span class="recent-title">{{ post.title }}</span>
                </a>
                <div class="recent-meta">
                  <span>R:{{ post.replies }}</span>
                  <span>I:{{ post.images }}</span>
                  <span class="recent-time">{{ post.time }}</span>
                </div>
              </li>
            </ul>
          </div>

          <!-- Rules -->
          <div class="sidebar-block sidebar-block--rules">
            <div class="sidebar-block-title">Museum Rules</div>
            <ol class="rules-list">
              <li>All submissions must be in good faith.</li>
              <li>No reposts within 30 days.</li>
              <li>Source your classics.</li>
              <li>OC boards require original work only.</li>
              <li>The curators' decisions are final.</li>
            </ol>
          </div>

          <!-- Post CTA -->
          <div class="sidebar-block sidebar-block--cta">
            <div class="sidebar-block-title">Contribute</div>
            <p>Have an original work or a significant cultural artifact?</p>
            <a href="#" class="btn-primary btn-full">Submit to Collection →</a>
          </div>

        </aside>
      </div>
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

  </div>
</template>