<script setup lang="ts">
import { ref, onMounted, watch } from "vue";
import { useApi } from "../composables/useApi";
import { useAuth } from "../composables/useAuth";
import { usePostModal } from "../composables/usePostModal";
import { useFormatDate } from "../composables/useFormatDate";
import RatingStars from "./RatingStars.vue";

interface Board {
  id: number;
  slug: string;
  name: string;
  description: string | null;
  sticky_body: string | null;
  is_archived: boolean;
  is_readonly: boolean;
  memes_count: number;
}

interface Thread {
  id: number;
  title: string;
  image_path: string | null;
  body?: string;
  is_anonymous: boolean;
  author_name: string | null;
  user?: { name: string };
  avg_rating: number | null;
  comments_count: number;
  views_count: number;
  created_at: string;
}

interface PaginationMeta {
  current_page: number;
  last_page: number;
  total: number;
  per_page: number;
}

const props = defineProps<{ slug: string }>();
const { get } = useApi();
const { user } = useAuth();
const { open: openPostModal } = usePostModal();
const { formatPostDate } = useFormatDate();

const board = ref<Board | null>(null);
const threads = ref<Thread[]>([]);
const meta = ref<PaginationMeta | null>(null);
const loading = ref(true);
const error = ref("");
const currentPage = ref(1);

const API_BASE = import.meta.env.VITE_API_URL ?? "";

function imageUrl(path: string) {
  return `${API_BASE}/storage/${path}`;
}

function displayName(thread: Thread): string {
  if (thread.is_anonymous) return thread.author_name || "Anonymous";
  return thread.user?.name ?? "Anonymous";
}

async function fetchBoard(page = 1) {
  loading.value = true;
  error.value = "";
  try {
    const res = await get<{
      data: Thread[];
      meta: PaginationMeta;
      board: Board;
    }>(`/boards/${props.slug}?page=${page}`);

    // BoardController returns MemeResource::collection(...)->additional(['board' => ...])
    // Laravel wraps paginated collection in { data: [], links: {}, meta: {}, board: {} }
    board.value = (res as any).board ?? null;
    threads.value = res.data;
    meta.value = (res as any).meta ?? null;
    currentPage.value = page;
  } catch (e: any) {
    error.value = e.message ?? "Failed to load board.";
  } finally {
    loading.value = false;
  }
}

// Re-fetch when slug changes (navigating between boards)
watch(
  () => props.slug,
  () => fetchBoard(1),
  { immediate: false },
);

onMounted(() => fetchBoard(1));

function handleNewThread() {
  if (!user.value) return; // App.vue's AuthModal handles unauthenticated users
  openPostModal(props.slug);
}

// Called by App.vue's @threadCreated if you wire it; or use the event bus pattern below.
// BoardView listens for a custom event on window so App.vue doesn't need prop drilling.
function onThreadCreated() {
  fetchBoard(currentPage.value);
}

// Listen for the PostModal's threadCreated event bubbled via usePostModal
// Since App.vue owns PostModal, we use a simple window event to refresh.
import { onBeforeUnmount } from "vue";
const REFRESH_EVENT = "boardview:refresh";

function requestRefresh(e: Event) {
  const detail = (e as CustomEvent).detail as { slug?: string } | undefined;
  if (!detail?.slug || detail.slug === props.slug) {
    fetchBoard(currentPage.value);
  }
}

onMounted(() => window.addEventListener(REFRESH_EVENT, requestRefresh));
onBeforeUnmount(() =>
  window.removeEventListener(REFRESH_EVENT, requestRefresh),
);
</script>

<template>
  <div class="board-page">
    <!-- Board header bar -->
    <div class="board-header">
      <div class="board-header-left">
        <span class="board-header-slug">/{{ slug }}/</span>
        <span v-if="board" class="board-header-name">{{ board.name }}</span>
        <span v-if="board?.description" class="board-header-desc">
          — {{ board.description }}
        </span>
      </div>
      <div class="board-header-right">
        <span
          v-if="board?.is_archived"
          class="board-badge board-badge--archived"
        >
          archived
        </span>
        <button
          v-else-if="!board?.is_readonly"
          class="btn-new-thread"
          :disabled="!user"
          :title="!user ? 'Sign in to post' : ''"
          @click="handleNewThread"
        >
          + New Thread
        </button>
      </div>
    </div>

    <!-- Loading / error states -->
    <div v-if="loading" class="bv-loading">Loading /{{ slug }}/…</div>
    <div v-else-if="error" class="bv-error">{{ error }}</div>

    <template v-else>
      <!-- Sticky post -->
      <div v-if="board?.sticky_body" class="sticky-post">
        <div class="sticky-header">
          <span class="sticky-pin">[STICKY]</span>
          <span class="sticky-name">Board Notice</span>
        </div>
        <div class="sticky-body">
          <p
            v-for="(line, i) in board.sticky_body.split('\n')"
            :key="i"
            :class="{ greentext: line.startsWith('>') }"
          >{{ line }}</p>
        </div>
      </div>

      <!-- Empty state -->
      <div v-if="threads.length === 0" class="bv-empty">
        <p>No threads yet in this board.</p>
        <button
          v-if="!board?.is_archived && !board?.is_readonly && user"
          class="btn-new-thread"
          @click="handleNewThread"
        >
          Be the first to post
        </button>
      </div>

      <!-- Thread catalog -->
      <div v-else class="thread-catalog">
        <article v-for="thread in threads" :key="thread.id" class="catalog-row">
          <!-- Thumbnail -->
          <div class="catalog-thumb">
            <router-link :to="`/thread/${thread.id}`">
              <img
                v-if="thread.image_path"
                :src="imageUrl(thread.image_path)"
                :alt="thread.title"
                class="catalog-img"
              />
              <div v-else class="catalog-img-placeholder">[no image]</div>
            </router-link>
          </div>

          <!-- Thread info -->
          <div class="catalog-info">
            <div class="catalog-meta">
              <span class="catalog-name">{{ displayName(thread) }}</span>
              <span class="catalog-date">{{
                formatPostDate(thread.created_at)
              }}</span>
              <span class="catalog-id">No.{{ thread.id }}</span>
            </div>

            <router-link :to="`/thread/${thread.id}`" class="catalog-title">
              {{ thread.title }}
            </router-link>

            <p v-if="thread.body" class="catalog-excerpt">
              {{ thread.body.slice(0, 160)
              }}{{ thread.body.length > 160 ? "…" : "" }}
            </p>

            <div class="catalog-stats">
              <RatingStars
                :meme-id="thread.id"
                :initial-rating="thread.avg_rating ?? null"
              />
              <span class="catalog-replies">
                <router-link :to="`/thread/${thread.id}`">
                  {{ thread.comments_count }}
                  {{ thread.comments_count === 1 ? "reply" : "replies" }}
                </router-link>
              </span>
              <span class="catalog-views">{{ thread.views_count }} views</span>
            </div>
          </div>
        </article>
      </div>

      <!-- Pagination -->
      <div v-if="meta && meta.last_page > 1" class="pagination">
        <button
          class="page-btn"
          :disabled="currentPage === 1"
          @click="fetchBoard(currentPage - 1)"
        >
          « Prev
        </button>
        <button
          v-for="p in meta.last_page"
          :key="p"
          class="page-btn"
          :class="{ 'page-btn--active': p === currentPage }"
          @click="fetchBoard(p)"
        >
          {{ p }}
        </button>
        <button
          class="page-btn"
          :disabled="currentPage === meta.last_page"
          @click="fetchBoard(currentPage + 1)"
        >
          Next »
        </button>
      </div>
    </template>
  </div>
</template>

<style scoped>
/* ── Layout ── */
.board-page {
  max-width: 900px;
  margin: 0 auto;
  padding: 0 0 40px;
}

/* ── Board header bar ── */
.board-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 14px;
  background: var(--espresso);
  border-bottom: 3px solid var(--orange);
  margin-bottom: 18px;
  flex-wrap: wrap;
  gap: 8px;
}
.board-header-left {
  display: flex;
  align-items: baseline;
  gap: 8px;
  flex-wrap: wrap;
}
.board-header-slug {
  font-family: var(--font-mono);
  font-size: 16px;
  font-weight: bold;
  color: var(--orange-lt);
  letter-spacing: 1px;
}
.board-header-name {
  font-family: var(--font-serif);
  font-size: 14px;
  color: var(--cream);
}
.board-header-desc {
  font-family: var(--font-mono);
  font-size: 11px;
  color: var(--grey);
}
.board-badge--archived {
  font-family: var(--font-mono);
  font-size: 10px;
  letter-spacing: 1px;
  text-transform: uppercase;
  color: var(--grey);
  border: 1px solid var(--grey);
  padding: 2px 7px;
}

/* ── New thread button ── */
.btn-new-thread {
  background: var(--orange);
  color: #fff;
  border: 1px solid var(--orange-lt);
  padding: 6px 14px;
  font-family: var(--font-sans);
  font-size: 11px;
  font-weight: bold;
  letter-spacing: 0.5px;
  cursor: pointer;
  transition: background 0.15s;
}
.btn-new-thread:hover:not(:disabled) {
  background: var(--orange-lt);
}
.btn-new-thread:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

/* ── Status messages ── */
.bv-loading,
.bv-empty {
  padding: 40px;
  text-align: center;
  font-family: var(--font-mono);
  font-size: 12px;
  color: var(--grey);
  letter-spacing: 1px;
}
.bv-error {
  padding: 20px;
  color: var(--orange);
  font-family: var(--font-mono);
  font-size: 12px;
  border-left: 3px solid var(--orange);
}

/* ── Thread catalog rows ── */
.thread-catalog {
  display: flex;
  flex-direction: column;
  gap: 0;
}
.catalog-row {
  display: flex;
  gap: 14px;
  padding: 12px 14px;
  border-bottom: 1px solid var(--grey-lt);
  background: #fff;
  transition: background 0.1s;
}
.catalog-row:nth-child(even) {
  background: var(--cream);
}
.catalog-row:hover {
  background: var(--cream-dark);
}

/* Thumbnail */
.catalog-thumb {
  flex-shrink: 0;
  width: 100px;
}
.catalog-img {
  width: 100px;
  height: 80px;
  object-fit: cover;
  border: 1px solid var(--grey-lt);
  display: block;
}
.catalog-img-placeholder {
  width: 100px;
  height: 80px;
  background: var(--parchment);
  border: 1px solid var(--grey-lt);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-mono);
  font-size: 10px;
  color: var(--grey);
}

/* Info column */
.catalog-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 5px;
}
.catalog-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  font-size: 11px;
  align-items: baseline;
}
.catalog-name {
  color: #117743;
  font-weight: bold;
  font-family: var(--font-sans);
}
.catalog-date {
  font-family: var(--font-mono);
  color: var(--grey-dk);
  font-size: 10px;
}
.catalog-id {
  font-family: var(--font-mono);
  color: var(--grey);
  font-size: 10px;
}
.catalog-title {
  font-family: var(--font-serif);
  font-size: 14px;
  font-weight: bold;
  color: var(--brown-dk);
  text-decoration: none;
}
.catalog-title:hover {
  text-decoration: underline;
  color: var(--orange);
}
.catalog-excerpt {
  font-size: 12px;
  color: var(--grey-dk);
  font-family: var(--font-mono);
  margin: 0;
  line-height: 1.5;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.catalog-stats {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-top: 2px;
}
.catalog-replies a,
.catalog-views {
  font-family: var(--font-mono);
  font-size: 11px;
  color: var(--grey);
  text-decoration: none;
}
.catalog-replies a:hover {
  color: var(--orange);
  text-decoration: underline;
}

/* ── Pagination ── */
.pagination {
  display: flex;
  gap: 4px;
  margin-top: 18px;
  padding: 0 14px;
  flex-wrap: wrap;
}
.page-btn {
  padding: 4px 10px;
  font-family: var(--font-mono);
  font-size: 11px;
  background: var(--parchment);
  border: 1px solid var(--grey-lt);
  color: var(--espresso);
  cursor: pointer;
  transition: background 0.12s;
}
.page-btn:hover:not(:disabled) {
  background: var(--cream-dark);
}
.page-btn--active {
  background: var(--espresso);
  color: var(--cream);
  border-color: var(--espresso);
}
.page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

/* ── Sticky post ── */
.sticky-post {
  background: #fffbe6;
  border: 1px solid #e6d97a;
  border-left: 4px solid #c8a800;
  margin-bottom: 14px;
}
.sticky-header {
  display: flex;
  align-items: baseline;
  gap: 8px;
  padding: 6px 14px;
  background: #fdf5c0;
  border-bottom: 1px solid #e6d97a;
  font-size: 11px;
}
.sticky-pin {
  font-family: var(--font-mono);
  font-weight: bold;
  color: #8a7000;
  letter-spacing: 1px;
}
.sticky-name {
  font-family: var(--font-sans);
  font-weight: bold;
  color: #5c4a00;
  font-size: 12px;
}
.sticky-body {
  padding: 10px 14px;
}
.sticky-body p {
  font-size: 13px;
  line-height: 1.55;
  margin: 2px 0;
  color: var(--espresso);
  white-space: pre-wrap;
}
.sticky-body .greentext { color: #789922; }

@media (max-width: 600px) {
  .catalog-row { padding: 10px; gap: 10px; }
  .catalog-thumb { width: 70px; }
  .catalog-img { width: 70px; height: 56px; }
  .catalog-img-placeholder { width: 70px; height: 56px; }
  .catalog-stats { flex-wrap: wrap; gap: 8px; }
}

@media (max-width: 400px) {
  .board-header { padding: 8px 10px; }
  .pagination { padding: 0 10px; }
}
</style>
