<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '../composables/useApi'

const API_BASE = import.meta.env.VITE_API_URL ?? ''

interface BoardStat { board_slug: string; board_name: string; count: number }
interface RecentMeme {
  id: number; title: string; image_path: string
  board_slug: string; avg_rating: number | null; created_at: string
}
interface FavoriteMeme {
  id: number; title: string; image_path: string; board_slug: string
}
interface Profile {
  name: string; email: string; avatar_path: string | null
  created_at: string; memes_count: number; avg_rating: number | null
  by_board: BoardStat[]; recent: RecentMeme[]; favorites: FavoriteMeme[]
}

const { get, postFile } = useApi()
const router = useRouter()

const profile  = ref<Profile | null>(null)
const loading  = ref(true)
const error    = ref('')

// Avatar upload state
const avatarPreview  = ref<string | null>(null)
const uploadLoading  = ref(false)
const uploadError    = ref('')

function storageUrl(path: string) {
  return `${API_BASE}/storage/${path}`
}

function memberSince(iso: string): string {
  const then = new Date(iso)
  const now  = new Date()
  const days = Math.floor((now.getTime() - then.getTime()) / 86_400_000)
  if (days < 1)   return 'Today'
  if (days < 7)   return `${days} day${days > 1 ? 's' : ''} ago`
  if (days < 31)  return `${Math.floor(days / 7)} week${Math.floor(days / 7) > 1 ? 's' : ''} ago`
  if (days < 365) return `${Math.floor(days / 30)} month${Math.floor(days / 30) > 1 ? 's' : ''} ago`
  const yrs = Math.floor(days / 365)
  return `${yrs} year${yrs > 1 ? 's' : ''} ago`
}

function maxBoardCount(boards: BoardStat[]): number {
  return Math.max(...boards.map(b => b.count), 1)
}

async function onAvatarChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file) return

  // Local preview
  if (avatarPreview.value) URL.revokeObjectURL(avatarPreview.value)
  avatarPreview.value = URL.createObjectURL(file)

  uploadLoading.value = true
  uploadError.value   = ''
  try {
    const fd = new FormData()
    fd.append('avatar', file)
    const res = await postFile<{ avatar_path: string }>('/profile/avatar', fd)
    if (profile.value) profile.value.avatar_path = res.avatar_path
  } catch (err: any) {
    uploadError.value = err.message ?? 'Upload failed.'
  } finally {
    uploadLoading.value = false
  }
}

onMounted(async () => {
  try {
    profile.value = await get<Profile>('/profile')
  } catch {
    // 401 or any error → not logged in, go home
    router.replace('/')
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div v-if="loading" class="pv-loading">Loading profile…</div>

  <div v-else-if="profile" class="profile-page">

    <!-- Identity card-->
    <div class="identity-card">

      <!-- Avatar -->
      <div class="avatar-wrap">
        <img
          v-if="avatarPreview || profile.avatar_path"
          :src="avatarPreview ?? storageUrl(profile.avatar_path!)"
          class="avatar-img"
          alt="avatar"
        />
        <div v-else class="avatar-placeholder">
          {{ profile.name.charAt(0).toUpperCase() }}
        </div>

        <label class="avatar-upload-btn" :class="{ 'is-loading': uploadLoading }" title="Change avatar">
          <input type="file" accept="image/*" class="avatar-file-input" @change="onAvatarChange" />
          {{ uploadLoading ? '…' : '✎' }}
        </label>
      </div>

      <!-- Info -->
      <div class="identity-info">
        <h1 class="identity-name">{{ profile.name }}</h1>
        <span class="identity-since">Member since {{ memberSince(profile.created_at) }}</span>
        <span class="identity-email">{{ profile.email }}</span>
        <p v-if="uploadError" class="upload-error">{{ uploadError }}</p>
      </div>
    </div>

    <!-- Stats row-->
    <div class="stats-row">
      <div class="stat-box">
        <span class="stat-value">{{ profile.memes_count }}</span>
        <span class="stat-label">Posts</span>
      </div>
      <div class="stat-box">
        <span class="stat-value">
          {{ profile.avg_rating !== null ? profile.avg_rating.toFixed(1) + ' ★' : '—' }}
        </span>
        <span class="stat-label">Avg Rating</span>
      </div>
      <div class="stat-box">
        <span class="stat-value">{{ profile.by_board.length }}</span>
        <span class="stat-label">Boards Active</span>
      </div>
    </div>

    <!-- Posts by board-->
    <section v-if="profile.by_board.length" class="profile-section">
      <h2 class="section-heading">Posts by Board</h2>
      <div class="board-bars">
        <div v-for="b in profile.by_board" :key="b.board_slug" class="board-bar-row">
          <router-link :to="`/board/${b.board_slug}`" class="board-bar-slug">
            /{{ b.board_slug }}/
          </router-link>
          <div class="board-bar-track">
            <div
              class="board-bar-fill"
              :style="{ width: (b.count / maxBoardCount(profile.by_board) * 100) + '%' }"
            ></div>
          </div>
          <span class="board-bar-count">{{ b.count }}</span>
        </div>
      </div>
    </section>

    <!-- Recent posts-->
    <section v-if="profile.recent.length" class="profile-section">
      <h2 class="section-heading">Recent Posts</h2>
      <div class="recent-grid">
        <router-link
          v-for="m in profile.recent"
          :key="m.id"
          :to="`/thread/${m.id}`"
          class="recent-thumb"
        >
          <img :src="storageUrl(m.image_path)" :alt="m.title" class="recent-img" />
          <div class="recent-overlay">
            <span class="recent-title">{{ m.title }}</span>
            <span class="recent-meta">/{{ m.board_slug }}/ · {{ m.avg_rating !== null ? m.avg_rating.toFixed(1) + '★' : 'unrated' }}</span>
          </div>
        </router-link>
      </div>
    </section>

    <!-- Favorites -->
    <section v-if="profile.favorites.length" class="profile-section">
      <h2 class="section-heading">Saved Memes</h2>
      <div class="recent-grid">
        <router-link
          v-for="m in profile.favorites"
          :key="m.id"
          :to="`/thread/${m.id}`"
          class="recent-thumb"
        >
          <img :src="storageUrl(m.image_path)" :alt="m.title" class="recent-img" />
          <div class="recent-overlay">
            <span class="recent-title">{{ m.title }}</span>
            <span class="recent-meta">/{{ m.board_slug }}/</span>
          </div>
        </router-link>
      </div>
    </section>

    <!-- Empty state -->
    <div v-if="!profile.memes_count" class="pv-empty">
      You haven't posted anything yet.
      <router-link to="/">Browse the boards</router-link> to get started.
    </div>

  </div>
</template>

<style scoped>
.pv-loading, .pv-empty {
  padding: 60px 20px;
  text-align: center;
  font-family: var(--font-mono);
  font-size: 12px;
  color: var(--grey);
  letter-spacing: 1px;
}
.pv-empty a { color: var(--orange); text-decoration: none; }
.pv-empty a:hover { text-decoration: underline; }

.profile-page {
  max-width: 780px;
  margin: 0 auto;
  padding: 0 0 60px;
}

/* Identity card*/
.identity-card {
  display: flex;
  align-items: flex-start;
  gap: 24px;
  padding: 28px 20px 24px;
  border-bottom: 3px solid var(--orange);
  margin-bottom: 28px;
}

/* Avatar */
.avatar-wrap {
  position: relative;
  flex-shrink: 0;
}
.avatar-img,
.avatar-placeholder {
  width: 84px;
  height: 84px;
  border-radius: 50%;
  border: 3px solid var(--orange);
  object-fit: cover;
  display: flex;
  align-items: center;
  justify-content: center;
}
.avatar-placeholder {
  background: var(--espresso);
  font-family: var(--font-serif);
  font-size: 36px;
  font-weight: bold;
  color: var(--orange-lt);
}

.avatar-upload-btn {
  position: absolute;
  bottom: 0; right: 0;
  width: 26px; height: 26px;
  border-radius: 50%;
  background: var(--orange);
  color: #fff;
  font-size: 13px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  border: 2px solid var(--cream);
  transition: background 0.15s;
  user-select: none;
}
.avatar-upload-btn:hover:not(.is-loading) { background: var(--orange-lt); }
.avatar-upload-btn.is-loading { opacity: 0.6; cursor: default; }
.avatar-file-input {
  position: absolute;
  inset: 0; opacity: 0;
  cursor: pointer;
  border-radius: 50%;
}

/* Identity text */
.identity-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding-top: 6px;
}
.identity-name {
  font-family: var(--font-serif);
  font-size: 22px;
  font-weight: bold;
  color: var(--espresso);
  margin: 0;
}
.identity-since {
  font-family: var(--font-mono);
  font-size: 11px;
  color: var(--orange);
  letter-spacing: 0.5px;
}
.identity-email {
  font-family: var(--font-mono);
  font-size: 11px;
  color: var(--grey);
}
.upload-error {
  font-size: 11px;
  color: var(--orange);
  font-family: var(--font-mono);
  margin: 2px 0 0;
}

/* Stats row*/
.stats-row {
  display: flex;
  gap: 12px;
  margin-bottom: 28px;
  padding: 0 4px;
  flex-wrap: wrap;
}
.stat-box {
  flex: 1;
  min-width: 100px;
  background: var(--cream-dark);
  border: 1px solid var(--grey-lt);
  padding: 14px 16px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}
.stat-value {
  font-family: var(--font-serif);
  font-size: 24px;
  font-weight: bold;
  color: var(--espresso);
  line-height: 1;
}
.stat-label {
  font-family: var(--font-mono);
  font-size: 9px;
  text-transform: uppercase;
  letter-spacing: 1.5px;
  color: var(--grey);
}

/* Sections*/
.profile-section {
  margin-bottom: 32px;
  padding: 0 4px;
}
.section-heading {
  font-family: var(--font-serif);
  font-size: 13px;
  font-weight: bold;
  color: var(--brown-dk);
  text-transform: uppercase;
  letter-spacing: 1px;
  border-bottom: 1px solid var(--grey-lt);
  padding-bottom: 6px;
  margin: 0 0 14px;
}

/* Board bars*/
.board-bars { display: flex; flex-direction: column; gap: 8px; }
.board-bar-row {
  display: flex;
  align-items: center;
  gap: 10px;
}
.board-bar-slug {
  font-family: var(--font-mono);
  font-size: 11px;
  font-weight: bold;
  color: var(--orange);
  text-decoration: none;
  min-width: 60px;
}
.board-bar-slug:hover { text-decoration: underline; }
.board-bar-track {
  flex: 1;
  height: 12px;
  background: var(--parchment);
  border: 1px solid var(--grey-lt);
  overflow: hidden;
}
.board-bar-fill {
  height: 100%;
  background: var(--orange);
  transition: width 0.4s ease;
}
.board-bar-count {
  font-family: var(--font-mono);
  font-size: 11px;
  color: var(--grey-dk);
  min-width: 16px;
  text-align: right;
}

/* Recent grid*/
.recent-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
  gap: 8px;
}
.recent-thumb {
  position: relative;
  display: block;
  aspect-ratio: 1;
  overflow: hidden;
  border: 1px solid var(--grey-lt);
  text-decoration: none;
}
.recent-thumb:hover .recent-overlay { opacity: 1; }
.recent-img {
  width: 100%; height: 100%;
  object-fit: cover;
  display: block;
}
.recent-overlay {
  position: absolute;
  inset: 0;
  background: rgba(46, 31, 20, 0.82);
  opacity: 0;
  transition: opacity 0.18s;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 6px;
  gap: 4px;
}
.recent-title {
  font-family: var(--font-sans);
  font-size: 10px;
  font-weight: bold;
  color: var(--cream);
  text-align: center;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.recent-meta {
  font-family: var(--font-mono);
  font-size: 9px;
  color: var(--orange-lt);
}

@media (max-width: 500px) {
  .identity-card { flex-direction: column; align-items: center; text-align: center; }
  .stats-row { gap: 8px; }
  .recent-grid { grid-template-columns: repeat(3, 1fr); }
}
</style>
