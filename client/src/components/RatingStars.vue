<script setup lang="ts">
import { ref, watch } from 'vue'
import { useApi } from '../composables/useApi'
import { useAuth } from '../composables/useAuth'

const props = defineProps<{ memeId: number; initialRating: number | null }>()
const { post, del } = useApi()
const { user } = useAuth()

const rating = ref(props.initialRating || 0)
const hover = ref(0)
const userRating = ref<number | null>(null)

async function setRating(value: number) {
  if (!user.value) return
  try {
    const res = await post<{ avg_rating: number }>('/ratings', { meme_id: props.memeId, value })
    rating.value = res.avg_rating
    userRating.value = value
  } catch (e) {
    console.error(e)
  }
}

async function removeRating() {
  if (!user.value) return
  try {
    await del(`/ratings/${props.memeId}`)
    rating.value = props.initialRating || 0
    userRating.value = null
  } catch (e) {}
}
</script>

<template>
  <div class="rating-stars">
    <span
      v-for="star in 5"
      :key="star"
      class="star"
      @click="setRating(star)"
      @mouseenter="hover = star"
      @mouseleave="hover = 0"
    >
      <span v-if="(hover || rating) >= star" class="filled">★</span>
      <span v-else>☆</span>
    </span>
    <span class="rating-value">{{ rating ? rating.toFixed(1) : '—' }}</span>
    <button v-if="userRating" @click="removeRating" class="remove-rating">✕</button>
  </div>
</template>

<style scoped>
.star { cursor: pointer; font-size: 18px; margin-right: 2px; color: var(--orange); }
.filled { color: var(--orange); }
.rating-value { margin-left: 6px; font-size: 12px; }
.remove-rating { background: none; border: none; cursor: pointer; margin-left: 8px; }
</style>