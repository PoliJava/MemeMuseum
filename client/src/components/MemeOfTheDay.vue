<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useApi } from '../composables/useApi'

interface Tag  { id: number; name: string }
interface Meme {
  id: number
  title: string
  image_path: string | null
  avg_rating: number | null
  tags: Tag[]
}

const { get } = useApi()
const meme    = ref<Meme | null>(null)
const loading = ref(true)

const API_BASE = import.meta.env.VITE_API_URL ?? ''

function imageUrl(path: string) {
  return `${API_BASE}/storage/${path}`
}

function todayLabel() {
  return new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' })
}

onMounted(async () => {
  try {
    const res = await get<{ data: Meme }>('/memes/today')
    meme.value = res.data ?? null
  } catch {
    // silently hide the block if the endpoint fails
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div v-if="!loading && meme" class="sidebar-block motd-block">
    <div class="sidebar-block-title">Meme of the Day</div>

    <div class="motd-date">{{ todayLabel() }}</div>

    <router-link :to="`/thread/${meme.id}`" class="motd-image-link">
      <img
        v-if="meme.image_path"
        :src="imageUrl(meme.image_path)"
        :alt="meme.title"
        class="motd-img"
      />
      <div v-else class="motd-img-placeholder">[no image]</div>
    </router-link>

    <div class="motd-body">
      <router-link :to="`/thread/${meme.id}`" class="motd-title">
        {{ meme.title }}
      </router-link>

      <div v-if="meme.tags?.length" class="motd-tags">
        <router-link
          v-for="t in meme.tags"
          :key="t.id"
          :to="`/search?tag=${encodeURIComponent(t.name)}`"
          class="motd-tag"
        >{{ t.name }}</router-link>
      </div>

      <div v-if="meme.avg_rating != null" class="motd-rating">
        &#9733; {{ meme.avg_rating.toFixed(1) }}
      </div>

      <router-link :to="`/thread/${meme.id}`" class="motd-view-btn">
        View exhibit &rarr;
      </router-link>
    </div>
  </div>
</template>

<style scoped>
.motd-block {
  border-top: 3px solid var(--orange);
}

.motd-date {
  font-family: var(--font-mono);
  font-size: 10px;
  color: var(--grey);
  letter-spacing: 1px;
  padding: 6px 12px 0;
  text-transform: uppercase;
}

.motd-image-link {
  display: block;
  margin-top: 8px;
}

.motd-img {
  width: 100%;
  height: 160px;
  object-fit: cover;
  display: block;
  border-top: 1px solid var(--grey-lt);
  border-bottom: 1px solid var(--grey-lt);
  transition: opacity 0.15s;
}
.motd-img:hover { opacity: 0.88; }

.motd-img-placeholder {
  width: 100%;
  height: 120px;
  background: var(--parchment);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-mono);
  font-size: 11px;
  color: var(--grey);
}

.motd-body {
  padding: 10px 12px 12px;
  display: flex;
  flex-direction: column;
  gap: 7px;
}

.motd-title {
  font-family: var(--font-serif);
  font-size: 13px;
  font-weight: bold;
  color: var(--brown-dk);
  text-decoration: none;
  line-height: 1.35;
}
.motd-title:hover { color: var(--orange); text-decoration: underline; }

.motd-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
}
.motd-tag {
  font-family: var(--font-mono);
  font-size: 10px;
  color: var(--orange);
  background: var(--parchment);
  border: 1px solid var(--grey-lt);
  padding: 1px 5px;
  text-decoration: none;
}
.motd-tag:hover { background: var(--cream-dark); text-decoration: underline; }

.motd-rating {
  font-family: var(--font-mono);
  font-size: 11px;
  color: var(--grey-dk);
}

.motd-view-btn {
  display: inline-block;
  font-family: var(--font-sans);
  font-size: 11px;
  font-weight: bold;
  color: var(--orange);
  text-decoration: none;
  letter-spacing: 0.3px;
  margin-top: 2px;
}
.motd-view-btn:hover { text-decoration: underline; }
</style>
