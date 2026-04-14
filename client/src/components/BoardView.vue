<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useApi } from '../composables/useApi'

interface Thread {
  id: number
  title: string
  image_path: string
  views_count: number
  avg_rating: number | null
  comments_count: number
  is_anonymous: boolean
  author_name: string | null
  user?: { name: string }
  created_at: string
}

interface BoardInfo {
  slug: string
  name: string
  description: string
  is_archived: boolean
}

const props = defineProps<{ slug: string }>()
const route = useRoute()
const { get } = useApi()

const board = ref<BoardInfo | null>(null)
const threads = ref<Thread[]>([])
const loading = ref(true)
const page = ref(1)

async function fetchBoard() {
  loading.value = true
  try {
    const res = await get<{ data: Thread[]; meta?: { board: BoardInfo } }>(`/boards/${props.slug}?page=${page.value}`)
    threads.value = res.data
    board.value = (res as any).board || (res as any).meta?.board
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
}

onMounted(fetchBoard)
watch(() => props.slug, fetchBoard)
watch(page, fetchBoard)

function openNewThread() {
  // Emettere evento verso App.vue per aprire PostModal con board slug
  window.dispatchEvent(new CustomEvent('openPostModal', { detail: { boardSlug: props.slug } }))
}
</script>

<template>
  <div class="board-view">
    <div class="board-header">
      <h1>/{{ board?.slug }}/ – {{ board?.name }}</h1>
      <p class="board-desc">{{ board?.description }}</p>
      <button class="btn-primary" @click="openNewThread">New Thread</button>
    </div>

    <div v-if="loading" class="loading">Loading threads...</div>
    <div v-else class="threads-list">
      <div v-for="thread in threads" :key="thread.id" class="thread-row">
        <router-link :to="`/thread/${thread.id}`" class="thread-link">
          <div class="thread-preview">
            <img
              v-if="thread.image_path"
              :src="`/storage/${thread.image_path}`"
              class="thread-thumb"
              loading="lazy"
            />
            <div class="thread-info">
              <h3>{{ thread.title }}</h3>
              <div class="thread-meta">
                <span>by {{ thread.is_anonymous ? (thread.author_name || 'Anonymous') : thread.user?.name }}</span>
                <span>⭐ {{ thread.avg_rating ?? '—' }}</span>
                <span>💬 {{ thread.comments_count ?? 0 }}</span>
                <span>👁️ {{ thread.views_count ?? 0 }}</span>
              </div>
            </div>
          </div>
        </router-link>
      </div>
    </div>

    <div v-if="threads.length" class="pagination">
      <button :disabled="page === 1" @click="page--">Prev</button>
      <span>Page {{ page }}</span>
      <button @click="page++">Next</button>
    </div>
  </div>
</template>

<style scoped>
.board-header { margin-bottom: 20px; }
.thread-row { border-bottom: 1px solid var(--grey-lt); padding: 12px 0; }
.thread-preview { display: flex; gap: 12px; align-items: flex-start; }
.thread-thumb { width: 60px; height: 60px; object-fit: cover; background: var(--parchment); }
.thread-info h3 { margin: 0 0 6px; font-size: 14px; }
.thread-meta { font-size: 11px; color: var(--grey-dk); display: flex; gap: 12px; }
.pagination { margin-top: 20px; display: flex; justify-content: center; gap: 12px; }
</style>