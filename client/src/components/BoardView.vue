<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from "vue";
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

interface PreviewComment {
  id: number;
  body: string | null;
  image_path: string | null;
  display_name: string;
  parent_id: number | null;
  created_at: string;
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
  preview_comments?: PreviewComment[];
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

function displayName(t: Thread): string {
  if (t.is_anonymous) return t.author_name || "Anonymous";
  return t.user?.name ?? "Anonymous";
}

function hiddenCount(thread: Thread): number {
  const shown = thread.preview_comments?.length ?? 0;
  return Math.max(0, thread.comments_count - shown);
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

watch(() => props.slug, () => fetchBoard(1), { immediate: false });
onMounted(() => fetchBoard(1));

function handleNewThread() {
  if (!user.value) return;
  openPostModal(props.slug);
}

const REFRESH_EVENT = "boardview:refresh";
function requestRefresh(e: Event) {
  const detail = (e as CustomEvent).detail as { slug?: string } | undefined;
  if (!detail?.slug || detail.slug === props.slug) fetchBoard(currentPage.value);
}
onMounted(() => window.addEventListener(REFRESH_EVENT, requestRefresh));
onBeforeUnmount(() => window.removeEventListener(REFRESH_EVENT, requestRefresh));
</script>

<template>
  <div class="board-page">

    <!-- Board header bar -->
    <div class="board-header">
      <div class="board-header-left">
        <span class="board-header-slug">/{{ slug }}/</span>
        <span v-if="board" class="board-header-name">{{ board.name }}</span>
        <span v-if="board?.description" class="board-header-desc">— {{ board.description }}</span>
      </div>
      <div class="board-header-right">
        <span v-if="board?.is_archived" class="board-badge--archived">archived</span>
        <button
          v-else-if="!board?.is_readonly"
          class="btn-new-thread"
          :disabled="!user"
          :title="!user ? 'Sign in to post' : ''"
          @click="handleNewThread"
        >+ New Thread</button>
      </div>
    </div>

    <!-- Loading / error -->
    <div v-if="loading" class="bv-loading">Loading /{{ slug }}/…</div>
    <div v-else-if="error" class="bv-error">{{ error }}</div>

    <template v-else>
      <!-- Sticky notice -->
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
        >Be the first to post</button>
      </div>

      <!-- Thread list — chan style -->
      <div v-else class="thread-list">
        <div v-for="thread in threads" :key="thread.id" class="thread-block">

          <!-- OP post -->
          <div class="op-post">
            <div class="op-header">
              <span class="post-subject">{{ thread.title }}</span>
              <span class="post-name">{{ displayName(thread) }}</span>
              <span class="post-date">{{ formatPostDate(thread.created_at) }}</span>
              <span class="post-no">No.{{ thread.id }}</span>
              <router-link :to="`/thread/${thread.id}`" class="post-reply-link">[Reply]</router-link>
            </div>

            <div class="op-content" :class="{ 'op-content--has-image': thread.image_path }">
              <!-- Image -->
              <div v-if="thread.image_path" class="op-image-wrap">
                <router-link :to="`/thread/${thread.id}`">
                  <img
                    :src="imageUrl(thread.image_path)"
                    :alt="thread.title"
                    class="op-image"
                  />
                </router-link>
              </div>

              <!-- Body text -->
              <div v-if="thread.body" class="op-body">
                <p
                  v-for="(line, i) in thread.body.split('\n')"
                  :key="i"
                  :class="{ greentext: line.startsWith('>') }"
                >{{ line }}</p>
              </div>
            </div>

            <!-- OP meta footer -->
            <div class="op-meta">
              <RatingStars :meme-id="thread.id" :initial-rating="thread.avg_rating ?? null" />
              <span class="op-stat">{{ thread.comments_count }} {{ thread.comments_count === 1 ? 'reply' : 'replies' }}</span>
              <span class="op-stat">{{ thread.views_count }} views</span>
              <router-link :to="`/thread/${thread.id}`" class="view-thread-btn">[View Thread]</router-link>
            </div>
          </div>

          <!-- Preview replies -->
          <div v-if="thread.preview_comments?.length" class="preview-replies">
            <div v-if="hiddenCount(thread) > 0" class="replies-omitted">
              {{ hiddenCount(thread) }} {{ hiddenCount(thread) === 1 ? 'reply' : 'replies' }} omitted.
              <router-link :to="`/thread/${thread.id}`">View full thread.</router-link>
            </div>

            <div
              v-for="reply in thread.preview_comments"
              :key="reply.id"
              :id="`preview-${reply.id}`"
              class="preview-reply"
            >
              <div class="reply-header">
                <span class="post-name">{{ reply.display_name }}</span>
                <span class="post-date">{{ formatPostDate(reply.created_at) }}</span>
                <span class="post-no">No.{{ reply.id }}</span>
                <a v-if="reply.parent_id" :href="`/thread/${thread.id}#post-${reply.parent_id}`" class="reply-backlink">
                  &gt;&gt;{{ reply.parent_id }}
                </a>
              </div>

              <div v-if="reply.image_path" class="reply-image-wrap">
                <a :href="imageUrl(reply.image_path)" target="_blank">
                  <img :src="imageUrl(reply.image_path)" class="reply-image" alt="" />
                </a>
              </div>

              <div v-if="reply.body" class="reply-body">
                <p
                  v-for="(line, i) in reply.body.split('\n')"
                  :key="i"
                  :class="{ greentext: line.startsWith('>') }"
                >{{ line }}</p>
              </div>
            </div>
          </div>

        </div><!-- /thread-block -->
      </div><!-- /thread-list -->

      <!-- Pagination -->
      <div v-if="meta && meta.last_page > 1" class="pagination">
        <button class="page-btn" :disabled="currentPage === 1" @click="fetchBoard(currentPage - 1)">« Prev</button>
        <button
          v-for="p in meta.last_page"
          :key="p"
          class="page-btn"
          :class="{ 'page-btn--active': p === currentPage }"
          @click="fetchBoard(p)"
        >{{ p }}</button>
        <button class="page-btn" :disabled="currentPage === meta.last_page" @click="fetchBoard(currentPage + 1)">Next »</button>
      </div>

    </template>
  </div>
</template>

<style scoped>
/* ── Page wrapper ── */
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
.btn-new-thread:hover:not(:disabled) { background: var(--orange-lt); }
.btn-new-thread:disabled { opacity: 0.45; cursor: not-allowed; }

/* ── Status messages ── */
.bv-loading, .bv-empty {
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

/* ── Thread list ── */
.thread-list {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.thread-block {
  border-bottom: 2px solid var(--parchment);
  padding: 12px 14px 0;
}
.thread-block:last-child { border-bottom: none; }

/* ── OP post ── */
.op-post {
  background: var(--cream-dark);
  border: 1px solid var(--grey-lt);
  padding: 10px 12px 8px;
  margin-bottom: 0;
}

.op-header {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: 8px;
  margin-bottom: 8px;
  font-size: 12px;
}
.post-subject {
  font-family: var(--font-serif);
  font-size: 14px;
  font-weight: bold;
  color: var(--brown-dk);
}
.post-name {
  color: #117743;
  font-weight: bold;
  font-family: var(--font-sans);
  font-size: 12px;
}
.post-date {
  font-family: var(--font-mono);
  font-size: 11px;
  color: var(--grey-dk);
}
.post-no {
  font-family: var(--font-mono);
  font-size: 11px;
  color: var(--grey);
}
.post-reply-link {
  font-family: var(--font-mono);
  font-size: 11px;
  color: var(--orange);
  text-decoration: none;
}
.post-reply-link:hover { text-decoration: underline; }

/* Image + body side-by-side */
.op-content { overflow: hidden; }
.op-content::after { content: ''; display: table; clear: both; }

.op-image-wrap {
  float: left;
  margin: 0 14px 8px 0;
}
.op-image {
  max-width: 240px;
  max-height: 320px;
  object-fit: contain;
  border: 1px solid var(--grey-lt);
  display: block;
  cursor: zoom-in;
}

.op-body p {
  margin: 2px 0;
  font-size: 13px;
  line-height: 1.55;
  white-space: pre-wrap;
  font-family: var(--font-mono);
}
.greentext { color: #789922; }

/* OP meta bar */
.op-meta {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 14px;
  padding-top: 8px;
  margin-top: 8px;
  border-top: 1px solid var(--grey-lt);
  font-size: 11px;
  clear: both;
}
.op-stat {
  font-family: var(--font-mono);
  color: var(--grey);
}
.view-thread-btn {
  font-family: var(--font-mono);
  font-size: 11px;
  color: var(--orange);
  text-decoration: none;
  font-weight: bold;
  margin-left: auto;
}
.view-thread-btn:hover { text-decoration: underline; }

/* ── Preview replies — flat, same depth as OP ── */
.preview-replies {
  padding-bottom: 12px;
}

.replies-omitted {
  font-family: var(--font-mono);
  font-size: 11px;
  color: var(--grey);
  padding: 6px 0 4px;
  font-style: italic;
}
.replies-omitted a {
  color: var(--orange);
  text-decoration: none;
}
.replies-omitted a:hover { text-decoration: underline; }

.preview-reply {
  background: #fff;
  border: 1px solid var(--grey-lt);
  border-top: none;
  padding: 7px 12px;
  margin-bottom: 0;
}

.reply-header {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: 7px;
  margin-bottom: 5px;
  font-size: 11px;
}
.reply-backlink {
  color: var(--orange);
  font-family: var(--font-mono);
  font-size: 11px;
  text-decoration: none;
}
.reply-backlink:hover { text-decoration: underline; }

.reply-image-wrap { margin: 4px 0 6px; }
.reply-image {
  max-width: 160px;
  max-height: 160px;
  object-fit: contain;
  border: 1px solid var(--grey-lt);
  display: block;
  cursor: zoom-in;
}

.reply-body p {
  margin: 1px 0;
  font-size: 12px;
  line-height: 1.5;
  white-space: pre-wrap;
  font-family: var(--font-mono);
}

/* ── Sticky ── */
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
.sticky-body { padding: 10px 14px; }
.sticky-body p {
  font-size: 13px;
  line-height: 1.55;
  margin: 2px 0;
  color: var(--espresso);
  white-space: pre-wrap;
}
.sticky-body .greentext { color: #789922; }

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
.page-btn:hover:not(:disabled) { background: var(--cream-dark); }
.page-btn--active { background: var(--espresso); color: var(--cream); border-color: var(--espresso); }
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }

/* ── Responsive ── */
@media (max-width: 600px) {
  .op-image-wrap { float: none; margin: 0 0 8px 0; }
  .op-image { max-width: 100%; }
  .thread-block { padding: 8px 8px 0; }
}
@media (max-width: 400px) {
  .board-header { padding: 8px 10px; }
  .pagination { padding: 0 8px; }
}
</style>
