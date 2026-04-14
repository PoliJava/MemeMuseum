<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useApi } from '../composables/useApi'

interface Tag {
  id: number
  name: string
  count: number
}

const { get } = useApi()
const tags = ref<Tag[]>([])

onMounted(async () => {
  try {
    const res = await get<{ data: Tag[] }>('/tags/popular')
    tags.value = res.data
  } catch (err) {
    console.error(err)
  }
})
</script>

<template>
  <div class="sidebar-block">
    <div class="sidebar-block-title">Tag Cloud</div>
    <div class="tag-cloud">
      <router-link
        v-for="tag in tags"
        :key="tag.id"
        :to="`/tag/${tag.name}`"
        class="tag"
        :style="{ fontSize: Math.min(24, 12 + (tag.count || 1) * 1.5) + 'px' }"
      >
        {{ tag.name }}
      </router-link>
    </div>
  </div>
</template>

<style scoped>
.tag-cloud { padding: 12px; display: flex; flex-wrap: wrap; gap: 8px; }
.tag { color: var(--brown-dk); text-decoration: none; }
.tag:hover { text-decoration: underline; }
</style>