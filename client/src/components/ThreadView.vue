<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useApi } from "../composables/useApi";
import { useFormatDate } from "../composables/useFormatDate";
import RatingStars from "./RatingStars.vue";
import ReplyModal from "./ReplyModal.vue";

interface Comment {
  id: number;
  body: string;
  display_name: string;
  is_anonymous: boolean;
  parent_id: number | null;
  created_at: string;
}

interface Thread {
  id: number;
  title: string;
  image_path: string;
  body?: string;
  is_anonymous: boolean;
  author_name: string | null;
  user?: { name: string };
  avg_rating: number | null;
  views_count: number;
  created_at: string;
  comments?: Comment[];
}

const props = defineProps<{ id: string }>();
const { get } = useApi();
const { formatPostDate } = useFormatDate();

const thread = ref<Thread | null>(null);
const replies = ref<Comment[]>([]);
const loading = ref(true);
const showReplyModal = ref(false);

async function fetchThread() {
  loading.value = true;
  try {
    const res = await get<{ data: Thread }>(`/memes/${props.id}`);
    thread.value = res.data;
    replies.value = res.data.comments || [];
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
}

onMounted(fetchThread);

function handleReplyCreated(newReply: Comment) {
  replies.value.push(newReply);
  showReplyModal.value = false;
}
</script>

<template>
  <div v-if="loading" class="loading">Loading thread…</div>
  <div v-else class="thread-view">
    <div class="op-post" :id="`post-${thread?.id}`">
      <div class="post-header">
        <span class="post-subject">{{ thread?.title }}</span>
        <span class="post-name">
          {{
            thread?.is_anonymous
              ? thread?.author_name || "Anonymous"
              : thread?.user?.name
          }}
        </span>
        <span class="post-date">{{ formatPostDate(thread!.created_at) }}</span>
        <span class="post-number">No.{{ thread?.id }}</span>
      </div>

      <div class="post-media" v-if="thread?.image_path">
        <a :href="`/storage/${thread.image_path}`" target="_blank">
          <img
            :src="`/storage/${thread.image_path}`"
            class="post-image"
            alt=""
          />
        </a>
      </div>

      <div class="post-body" v-if="thread?.body">
        <p
          v-for="(line, i) in thread.body.split('\n')"
          :key="i"
          :class="{ greentext: line.startsWith('>') }"
        >
          {{ line }}
        </p>
      </div>

      <div class="post-meta">
        <RatingStars
          :meme-id="Number(props.id)"
          :initial-rating="thread?.avg_rating ?? null"
        />
        <button class="reply-btn" @click="showReplyModal = true">
          [Reply]
        </button>
      </div>
    </div>

    <div class="replies-section" v-if="replies.length">
      <div
        v-for="reply in replies"
        :key="reply.id"
        :id="`post-${reply.id}`"
        class="reply-post"
        :class="{ 'reply-post--nested': reply.parent_id }"
      >
        <div class="post-header">
          <span class="post-name">{{ reply.display_name }}</span>
          <span class="post-date">{{ formatPostDate(reply.created_at) }}</span>
          <span class="post-number">No.{{ reply.id }}</span>
          <a
            v-if="reply.parent_id"
            :href="`#post-${reply.parent_id}`"
            class="reply-link"
            >&gt;&gt;{{ reply.parent_id }}</a
          >
        </div>
        <div class="post-body">
          <p
            v-for="(line, i) in reply.body.split('\n')"
            :key="i"
            :class="{ greentext: line.startsWith('>') }"
          >
            {{ line }}
          </p>
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
.loading {
  padding: 40px 0;
  text-align: center;
  font-family: var(--font-mono);
  font-size: 12px;
  color: var(--grey);
  letter-spacing: 1px;
}

.op-post {
  background: var(--cream-dark);
  padding: 12px 16px;
  margin-bottom: 16px;
  border: 1px solid var(--grey-lt);
}
.reply-post {
  background: #fff;
  padding: 10px 14px;
  margin-bottom: 6px;
  border: 1px solid var(--grey-lt);
}
.reply-post--nested {
  margin-left: 30px;
}

/* Post header — imageboard style: name · date · No.id */
.post-header {
  display: flex;
  align-items: baseline;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 8px;
  font-size: 12px;
}
.post-subject {
  font-family: var(--font-serif);
  font-weight: bold;
  color: var(--brown-dk);
  font-size: 13px;
}
.post-name {
  color: #117743;
  font-weight: bold;
  font-family: var(--font-sans);
}
.post-date {
  color: var(--grey-dk);
  font-family: var(--font-mono);
  font-size: 11px;
}
.post-number {
  color: var(--grey);
  font-family: var(--font-mono);
  font-size: 11px;
}

.post-media {
  margin: 8px 0;
}
.post-image {
  max-width: 100%;
  max-height: 400px;
  border: 1px solid var(--grey-lt);
  display: block;
  cursor: zoom-in;
}

.post-body p {
  margin: 2px 0;
  font-size: 13px;
  line-height: 1.5;
  white-space: pre-wrap;
}
.greentext {
  color: #789922;
}

.post-meta {
  display: flex;
  gap: 16px;
  align-items: center;
  margin-top: 10px;
  padding-top: 8px;
  border-top: 1px solid var(--grey-lt);
}
.reply-btn {
  background: none;
  border: none;
  padding: 0;
  font-family: var(--font-mono);
  font-size: 12px;
  color: var(--orange);
  cursor: pointer;
}
.reply-btn:hover {
  text-decoration: underline;
}

.reply-link {
  color: var(--orange);
  font-family: var(--font-mono);
  font-size: 11px;
  text-decoration: none;
}
.reply-link:hover {
  text-decoration: underline;
}
</style>
