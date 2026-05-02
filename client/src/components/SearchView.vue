<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '../composables/useApi'
import { useFormatDate } from '../composables/useFormatDate'

interface Tag { id: number; name: string }
interface Meme {
  id: number
  title: string
  image_path: string | null
  avg_rating: number | null
  tags: Tag[]
  created_at: string
  views_count: number
  comments_count: number
}
interface Meta { current_page: number; last_page: number; total: number }

const route  = useRoute()
const router = useRouter()
const { get } = useApi()
const { formatPostDate } = useFormatDate()

const API_BASE = import.meta.env.VITE_API_URL ?? ''

const tag      = ref('')
const dateFrom = ref('')
const dateTo   = ref('')
const sort     = ref('newest')

const memes   = ref<Meme[]>([])
const meta    = ref<Meta | null>(null)
const loading = ref(false)
const error   = ref('')

function imageUrl(path: string) {
  return `${API_BASE}/storage/${path}`
}

async function fetchResults(page = 1) {
  loading.value = true
  error.value   = ''
  try {
    const params = new URLSearchParams({ sort: sort.value, page: String(page) })
    if (tag.value)      params.set('tag',       tag.value)
    if (dateFrom.value) params.set('date_from', dateFrom.value)
    if (dateTo.value)   params.set('date_to',   dateTo.value)

    const res = await get<{ data: Meme[]; meta: Meta }>(`/memes?${params}`)
    memes.value = res.data
    meta.value  = (res as any).meta ?? null
  } catch (e: any) {
    error.value = e?.message ?? 'Failed to load results.'
  } finally {
    loading.value = false
  }
}

function search() {
  const query: Record<string, string> = {}
  if (tag.value)      query.tag       = tag.value
  if (dateFrom.value) query.date_from = dateFrom.value
  if (dateTo.value)   query.date_to   = dateTo.value
  if (sort.value !== 'newest') query.sort = sort.value
  router.push({ path: '/search', query })
}

function goPage(page: number) {
  router.push({ path: '/search', query: { ...route.query, page: String(page) } })
}

// Sync from URL and fetch whenever query string changes (covers initial load + tag links)
watch(
  () => route.query,
  (q) => {
    tag.value      = (q.tag       as string) ?? ''
    dateFrom.value = (q.date_from as string) ?? ''
    dateTo.value   = (q.date_to   as string) ?? ''
    sort.value     = (q.sort      as string) ?? 'newest'
    fetchResults(Number(q.page) || 1)
  },
  { immediate: true },
)
</script>

<template>
  <div class="search-page">
    <div class="search-header">
      <div class="section-header">
        <h2 class="section-title">Search the Archive</h2>
        <span class="section-rule"></span>
      </div>
    </div>

    <!-- Filter form -->
    <form class="search-filters" @submit.prevent="search">
      <div class="filter-group">
        <label>Tag</label>
        <input
          v-model="tag"
          type="text"
          placeholder="e.g. Programming"
          @keydown.enter.prevent="search"
        />
      </div>
      <div class="filter-group">
        <label>From</label>
        <input v-model="dateFrom" type="date" />
      </div>
      <div class="filter-group">
        <label>To</label>
        <input v-model="dateTo" type="date" />
      </div>
      <div class="filter-group">
        <label>Sort</label>
        <select v-model="sort">
          <option value="newest">Newest first</option>
          <option value="oldest">Oldest first</option>
          <option value="top_rated">Top rated</option>
        </select>
      </div>
      <button type="submit" class="btn-search">Search</button>
    </form>

    <!-- States -->
    <div v-if="loading" class="sr-status">Searching the archive…</div>
    <div v-else-if="error" class="sr-error">{{ error }}</div>
    <div v-else-if="memes.length === 0" class="sr-status">No results found.</div>

    <!-- Results -->
    <div v-else class="sr-results">
      <div class="sr-count" v-if="meta">
        {{ meta.total }} result{{ meta.total !== 1 ? 's' : '' }}
      </div>

      <article v-for="meme in memes" :key="meme.id" class="sr-row">
        <div class="sr-thumb">
          <router-link :to="`/thread/${meme.id}`">
            <img
              v-if="meme.image_path"
              :src="imageUrl(meme.image_path)"
              :alt="meme.title"
              class="sr-img"
            />
            <div v-else class="sr-img-placeholder">[no image]</div>
          </router-link>
        </div>
        <div class="sr-info">
          <div class="sr-meta">
            <span class="sr-date">{{ formatPostDate(meme.created_at) }}</span>
            <span class="sr-id">No.{{ meme.id }}</span>
          </div>
          <router-link :to="`/thread/${meme.id}`" class="sr-title">
            {{ meme.title }}
          </router-link>
          <div class="sr-tags" v-if="meme.tags?.length">
            <router-link
              v-for="t in meme.tags"
              :key="t.id"
              :to="`/search?tag=${encodeURIComponent(t.name)}`"
              class="sr-tag"
            >
              {{ t.name }}
            </router-link>
          </div>
          <div class="sr-stats">
            <span v-if="meme.avg_rating != null">&#9733; {{ meme.avg_rating.toFixed(1) }}</span>
            <span>{{ meme.comments_count ?? 0 }} repl{{ (meme.comments_count ?? 0) === 1 ? 'y' : 'ies' }}</span>
            <span>{{ meme.views_count }} views</span>
          </div>
        </div>
      </article>
    </div>

    <!-- Pagination -->
    <div v-if="meta && meta.last_page > 1" class="pagination">
      <button
        class="page-btn"
        :disabled="meta.current_page === 1"
        @click="goPage(meta!.current_page - 1)"
      >« Prev</button>
      <button
        v-for="p in meta.last_page"
        :key="p"
        class="page-btn"
        :class="{ 'page-btn--active': p === meta.current_page }"
        @click="goPage(p)"
      >{{ p }}</button>
      <button
        class="page-btn"
        :disabled="meta.current_page === meta.last_page"
        @click="goPage(meta!.current_page + 1)"
      >Next »</button>
    </div>
  </div>
</template>

<style scoped>
.search-page { max-width: 900px; margin: 0 auto; padding-bottom: 40px; }

/* ── Filter form ── */
.search-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: flex-end;
  background: #fff;
  border: 1px solid var(--grey-lt);
  padding: 14px;
  margin-bottom: 18px;
  box-shadow: var(--shadow-sm);
}
.filter-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
  flex: 1;
  min-width: 120px;
}
.filter-group label {
  font-size: 10px;
  font-weight: bold;
  text-transform: uppercase;
  letter-spacing: 1.5px;
  color: var(--grey-dk);
  font-family: var(--font-sans);
}
.filter-group input,
.filter-group select {
  height: 32px;
  padding: 0 8px;
  border: 1px solid var(--grey-lt);
  background: var(--cream);
  font-family: var(--font-mono);
  font-size: 12px;
  color: var(--espresso);
  outline: none;
}
.filter-group input:focus,
.filter-group select:focus {
  border-color: var(--orange);
  box-shadow: 0 0 0 2px rgba(212,98,26,0.12);
}
.btn-search {
  height: 32px;
  padding: 0 18px;
  background: var(--orange);
  color: #fff;
  border: 1px solid var(--orange-lt);
  font-family: var(--font-sans);
  font-size: 11px;
  font-weight: bold;
  letter-spacing: 0.5px;
  cursor: pointer;
  white-space: nowrap;
  align-self: flex-end;
  transition: background 0.15s;
}
.btn-search:hover { background: var(--orange-lt); }

/* ── Status / count ── */
.sr-status {
  padding: 40px;
  text-align: center;
  font-family: var(--font-mono);
  font-size: 12px;
  color: var(--grey);
  letter-spacing: 1px;
}
.sr-error {
  padding: 16px;
  color: var(--orange);
  font-family: var(--font-mono);
  font-size: 12px;
  border-left: 3px solid var(--orange);
  margin-bottom: 12px;
}
.sr-count {
  font-family: var(--font-mono);
  font-size: 11px;
  color: var(--grey);
  padding: 6px 14px;
  border-bottom: 1px solid var(--grey-lt);
  background: var(--cream-dark);
}

/* ── Result rows ── */
.sr-results {
  border: 1px solid var(--grey-lt);
  background: #fff;
  box-shadow: var(--shadow-sm);
}
.sr-row {
  display: flex;
  gap: 14px;
  padding: 12px 14px;
  border-bottom: 1px solid var(--grey-lt);
}
.sr-row:last-child { border-bottom: none; }
.sr-row:nth-child(even) { background: var(--cream); }

.sr-thumb { flex-shrink: 0; width: 100px; }
.sr-img { width: 100px; height: 80px; object-fit: cover; border: 1px solid var(--grey-lt); display: block; }
.sr-img-placeholder {
  width: 100px; height: 80px;
  background: var(--parchment);
  border: 1px solid var(--grey-lt);
  display: flex; align-items: center; justify-content: center;
  font-family: var(--font-mono); font-size: 10px; color: var(--grey);
}

.sr-info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 5px; }
.sr-meta { display: flex; gap: 10px; font-size: 11px; flex-wrap: wrap; }
.sr-date { font-family: var(--font-mono); color: var(--grey-dk); font-size: 10px; }
.sr-id   { font-family: var(--font-mono); color: var(--grey);    font-size: 10px; }

.sr-title {
  font-family: var(--font-serif);
  font-size: 14px;
  font-weight: bold;
  color: var(--brown-dk);
  text-decoration: none;
}
.sr-title:hover { text-decoration: underline; color: var(--orange); }

.sr-tags { display: flex; flex-wrap: wrap; gap: 6px; }
.sr-tag {
  font-family: var(--font-mono);
  font-size: 10px;
  color: var(--orange);
  background: var(--parchment);
  border: 1px solid var(--grey-lt);
  padding: 1px 6px;
  text-decoration: none;
}
.sr-tag:hover { background: var(--cream-dark); text-decoration: underline; }

.sr-stats {
  display: flex;
  gap: 14px;
  font-family: var(--font-mono);
  font-size: 11px;
  color: var(--grey);
  flex-wrap: wrap;
}

/* ── Pagination ── */
.pagination { display: flex; gap: 4px; margin-top: 18px; flex-wrap: wrap; }
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

@media (max-width: 600px) {
  .filter-group { min-width: 100%; }
  .btn-search   { width: 100%; }
  .sr-thumb     { width: 70px; }
  .sr-img       { width: 70px; height: 56px; }
  .sr-img-placeholder { width: 70px; height: 56px; }
}
</style>
