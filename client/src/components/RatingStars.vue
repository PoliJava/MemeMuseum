<script setup lang="ts">
import { ref } from 'vue'
import { useApi } from '../composables/useApi'
import { useAuth } from '../composables/useAuth'

const props = defineProps<{
  memeId: number
  initialRating: number | null
  myRating?: number | null
}>()

const { post, del } = useApi()
const { user } = useAuth()

const rating     = ref(props.initialRating ?? 0)
const hover      = ref(0)   // 0 = not hovering; 0.5–5 in half steps
const userRating = ref<number | null>(props.myRating ?? null)

// 0 / 50 / 100 percent fill for star n
function fillForStar(n: number): 0 | 50 | 100 {
  const active = hover.value !== 0 ? hover.value : rating.value
  if (active >= n)       return 100
  if (active >= n - 0.5) return 50
  return 0
}

// half-step from mouse x position
function valueFromEvent(e: MouseEvent, n: number): number {
  const rect = (e.currentTarget as HTMLElement).getBoundingClientRect()
  return e.clientX - rect.left < rect.width / 2 ? n - 0.5 : n
}

function onMouseMove(e: MouseEvent, n: number) {
  if (user.value) hover.value = valueFromEvent(e, n)
}

async function onClick(e: MouseEvent, n: number) {
  if (!user.value) return
  const value = valueFromEvent(e, n)
  try {
    const res = await post<{ avg_rating: number }>('/ratings', {
      meme_id: props.memeId,
      value,
    })
    rating.value    = res.avg_rating
    userRating.value = value
  } catch (err) {
    console.error(err)
  }
}

async function removeRating() {
  if (!user.value) return
  try {
    const res = await del<{ avg_rating: number | null }>(`/ratings/${props.memeId}`)
    rating.value     = res.avg_rating ?? 0
    userRating.value = null
    hover.value      = 0
  } catch (_) {}
}
</script>

<template>
  <div class="rating-stars" :class="{ 'is-interactive': !!user }">

    <span
      v-for="n in 5"
      :key="n"
      class="star-wrap"
      @mousemove="onMouseMove($event, n)"
      @mouseleave="hover = 0"
      @click="onClick($event, n)"
    >
      <!-- Gray ★ — defines the cell size -->
      <span class="star-bg">★</span>

      <!-- Orange ★ — clipped to fillForStar(n) % from the left.
           Uses the identical glyph so both layers are always the same size. -->
      <span
        v-if="fillForStar(n) > 0"
        class="star-fill"
        :style="{ width: fillForStar(n) + '%' }"
      >★</span>
    </span>

    <span class="rating-value">{{ rating ? rating.toFixed(1) : '—' }}</span>

    <button
      v-if="userRating !== null"
      class="remove-rating"
      title="Remove your rating"
      @click="removeRating"
    >✕</button>

  </div>
</template>

<style scoped>
.rating-stars {
  display: flex;
  align-items: center;
  gap: 1px;
}

.star-wrap {
  position: relative;
  display: inline-block;
  font-size: 18px;
  line-height: 1;
}
.is-interactive .star-wrap { cursor: pointer; }

.star-bg,
.star-fill {
  display: block;
  user-select: none;
  pointer-events: none;   /* events handled by .star-wrap */
}

.star-bg { color: var(--grey-lt); }

.star-fill {
  position: absolute;
  inset: 0;
  width: /* overridden inline */ 100%;
  overflow: hidden;
  white-space: nowrap;
  color: var(--orange);
}

.rating-value {
  margin-left: 7px;
  font-size: 12px;
  font-family: var(--font-mono);
  color: var(--grey-dk);
  min-width: 2.2ch;
}

.remove-rating {
  background: none;
  border: none;
  cursor: pointer;
  margin-left: 4px;
  font-size: 11px;
  color: var(--grey);
  padding: 0;
  line-height: 1;
  transition: color 0.15s;
}
.remove-rating:hover { color: var(--orange); }
</style>
