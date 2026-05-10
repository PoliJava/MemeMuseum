<script setup lang="ts">
import { ref } from 'vue'
import { useApi } from '../composables/useApi'

const props = defineProps<{
  memeId: number
  initial: boolean
}>()

const { post, del } = useApi()

const saved   = ref(props.initial)
const loading = ref(false)

async function toggle() {
  if (loading.value) return
  loading.value = true
  try {
    if (saved.value) {
      await del(`/favorites/${props.memeId}`)
      saved.value = false
    } else {
      await post('/favorites', { meme_id: props.memeId })
      saved.value = true
    }
  } catch {}
  finally { loading.value = false }
}
</script>

<template>
  <button
    class="fav-btn"
    :class="{ 'fav-btn--saved': saved }"
    :disabled="loading"
    :title="saved ? 'Remove from favorites' : 'Save to favorites'"
    @click="toggle"
  >
    {{ loading ? '[…]' : saved ? '[♥ Saved]' : '[♡ Save]' }}
  </button>
</template>

<style scoped>
.fav-btn {
  background: none;
  border: none;
  padding: 0;
  font-family: var(--font-mono);
  font-size: 11px;
  color: var(--orange);
  cursor: pointer;
  line-height: 1;
}
.fav-btn:hover { text-decoration: underline; }
.fav-btn:disabled { opacity: 0.5; cursor: default; text-decoration: none; }
.fav-btn--saved { color: #a33; }
.fav-btn--saved:hover { color: #c00; }
</style>
