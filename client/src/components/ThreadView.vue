<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useApi } from '../composables/useApi'
import RatingStars from './RatingStars.vue'
import ReplyModal from './ReplyModal.vue'

interface Comment {
  id: number
  body: string
  display_name: string
  is_anonymous: boolean
  parent_id: number | null
  created_at: string
}

interface Thread {
  id: number
  title: string
  image_path: string
  body?: string
  is_anonymous: boolean
  author_name: string | null
  user?: { name: string }
  avg_rating: number | null
  views_count: number
  created_at: string
  comments?: Comment[]
}

const props = defineProps<{ id: string }>()
const route = useRoute()
const { get } = useApi()

const thread = ref<Thread | null>(null)
const replies = ref<Comment[]>([])
const loading = ref(true)
const showReplyModal = ref(false)

async function fetchThread() {
  loading.value = true
  try {
    const res = await get<{ data: Thread }>(`/memes/${props.id}`)
    thread.value = res.data
    replies.value = res.data.comments || []
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
}

onMounted(fetchThread)

function handleReplyCreated(newReply: Comment) {
  replies.value.push(newReply)
  showReplyModal.value = false
}
</script>

<template>
  <div v-if="loading" class="loading">Loading thread...</div>
  <div v-else class="thread-view">
    <div class="op-post">
      <div class="post-header">
        <span class="post-number">#{{ thread?.id }}</span>
        <span class="post-name">
          {{ thread?.is_anonymous ? (thread?.author_name || 'Anonymous') : thread?.user?.name }}
        </span>
        <span class="post-date">{{ new Date(thread!.created_at).toLocaleString() }}</span>
      </div>
      <h2>{{ thread?.title }}</h2>
      <div class="post-media">
        <img
          v-if="thread?.image_path"
          :src="`/storage/${thread.image_path}`"
          class="post-image"
        />
      </div>
      <div class="post-body" v-if="thread?.body">
        <p>{{ thread.body }}</p>
      </div>
      <div class="post-meta">
        <RatingStars :meme-id="Number(props.id)" :initial-rating="thread?.avg_rating ?? null" />
        <button class="reply-btn" @click="showReplyModal = true">Reply</button>
      </div>
    </div>

    <div class="replies-section" v-if="replies.length">
      <h3>Replies</h3>
      <div
        v-for="reply in replies"
        :key="reply.id"
        class="reply-post"
        :style="{ marginLeft: reply.parent_id ? '30px' : '0' }"
      >
        <div class="post-header">
          <span class="post-number">#{{ reply.id }}</span>
          <span class="post-name">{{ reply.display_name }}</span>
          <span class="post-date">{{ new Date(reply.created_at).toLocaleString() }}</span>
          <a v-if="reply.parent_id" :href="`#post-${reply.parent_id}`" class="reply-link">>>{{ reply.parent_id }}</a>
        </div>
        <div class="post-body">
          <p>{{ reply.body }}</p>
        </div>
      </div>
    </div>

    <ReplyModal
      :open="showReplyModal"
      :thread-id="Number(props.id)"
      @close="showReplyModal = false"
      @reply-created="handleReplyCreated"
    />
  </div>
</template>

<style scoped>
.op-post { background: var(--cream-dark); padding: 16px; margin-bottom: 24px; border: 1px solid var(--grey-lt); }
.post-image { max-width: 100%; max-height: 300px; margin: 12px 0; }
.reply-post { background: white; padding: 12px; margin-bottom: 8px; border: 1px solid var(--grey-lt); }
.reply-link { color: var(--orange); font-family: monospace; font-size: 11px; margin-left: 8px; }
.post-meta { display: flex; gap: 16px; align-items: center; margin-top: 12px; }
.reply-btn { background: none; border: 1px solid var(--orange); padding: 4px 12px; cursor: pointer; }
</style>