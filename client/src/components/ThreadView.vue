<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useApi } from "../composables/useApi";
import { useAuth } from "../composables/useAuth";
import { useFormatDate } from "../composables/useFormatDate";
import RatingStars from "./RatingStars.vue";
import ReplyModal from "./ReplyModal.vue";

interface CommentAuthor {
  id: number;
  name: string;
}

interface Comment {
  id: number;
  body: string | null;
  image_path: string | null;
  display_name: string;
  is_anonymous: boolean;
  parent_id: number | null;
  created_at: string;
  user?: CommentAuthor;
}

interface Thread {
  id: number;
  title: string;
  image_path: string;
  body?: string;
  is_anonymous: boolean;
  author_name: string | null;
  user?: { id: number; name: string };
  avg_rating: number | null;
  my_rating?: number | null;
  views_count: number;
  created_at: string;
  comments?: Comment[];
}

const props = defineProps<{ id: string }>();
const { get, del, put } = useApi();
const { user } = useAuth();
const { formatPostDate } = useFormatDate();
const router = useRouter();

const thread = ref<Thread | null>(null);
const replies = ref<Comment[]>([]);
const loading = ref(true);

// ── Reply modal ──────────────────────────────
const showReplyModal = ref(false);
const replyParentId = ref<number | null>(null);
const replyInitialBody = ref("");

// parentId = DB parent for threading; initialBody = >>X pre-fill in textarea
function openReply(parentId: number | null = null, initialBody = "") {
  replyParentId.value = parentId;
  replyInitialBody.value = initialBody;
  showReplyModal.value = true;
}

function handleReplyCreated(newReply: Comment) {
  replies.value.push(newReply);
  showReplyModal.value = false;
}

// ── Body rendering — turns >>X tokens into anchor links ──
interface BodyPart {
  text: string;
  href: string | null;
}
function parseBodyLine(line: string): BodyPart[] {
  const parts = line.split(/(>>\d+)/)
  return parts.map((p) => {
    const m = p.match(/^>>(\d+)$/)
    return m ? { text: p, href: `#post-${m[1]}` } : { text: p, href: null }
  })
}

// ── Inline comment edit ──────────────────────
const editingId = ref<number | null>(null);
const editBody = ref("");
const editLoading = ref(false);
const editError = ref("");

function startEdit(comment: Comment) {
  editingId.value = comment.id;
  editBody.value = comment.body ?? "";
  editError.value = "";
}

function cancelEdit() {
  editingId.value = null;
  editBody.value = "";
  editError.value = "";
}

async function saveEdit(id: number) {
  if (!editBody.value.trim()) {
    editError.value = "Reply cannot be empty.";
    return;
  }
  editLoading.value = true;
  editError.value = "";
  try {
    const res = await put<{ data: Comment }>(`/comments/${id}`, { body: editBody.value });
    const idx = replies.value.findIndex((r) => r.id === id);
    if (idx !== -1) replies.value[idx] = { ...replies.value[idx], body: res.data.body };
    editingId.value = null;
  } catch (e: any) {
    editError.value = e.message ?? "Could not save edit.";
  } finally {
    editLoading.value = false;
  }
}

// ── Delete comment ───────────────────────────
const deletingIds = ref<number[]>([]);

async function deleteComment(id: number) {
  if (!confirm("Delete this reply?")) return;
  deletingIds.value = [...deletingIds.value, id];
  try {
    await del(`/comments/${id}`);
    replies.value = replies.value.filter((r) => r.id !== id);
  } catch (e: any) {
    alert(e.message ?? "Could not delete comment.");
  } finally {
    deletingIds.value = deletingIds.value.filter((x) => x !== id);
  }
}

// ── Delete meme ──────────────────────────────
const deletingMeme = ref(false);

async function deleteMeme() {
  if (!confirm("Delete this post? This cannot be undone.")) return;
  deletingMeme.value = true;
  try {
    await del(`/memes/${props.id}`);
    router.push("/");
  } catch (e: any) {
    alert(e.message ?? "Could not delete post.");
    deletingMeme.value = false;
  }
}

// ── Ownership ────────────────────────────────
function isOwnComment(c: Comment) {
  return !!user.value && !!c.user && c.user.id === user.value.id;
}
function isOwnThread() {
  return !!user.value && !!thread.value?.user && thread.value.user.id === user.value.id;
}

// ── Fetch ────────────────────────────────────
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
</script>

<template>
  <div v-if="loading" class="loading">Loading thread…</div>
  <div v-else class="thread-view">

    <!-- ═══ OP POST ═══ -->
    <div class="op-post" :id="`post-${thread?.id}`">
      <div class="post-header">
        <span class="post-subject">{{ thread?.title }}</span>
        <span class="post-name">
          {{ thread?.is_anonymous ? (thread?.author_name || "Anonymous") : thread?.user?.name }}
        </span>
        <span class="post-date">{{ formatPostDate(thread!.created_at) }}</span>
        <!-- Clickable No.X — quotes this post -->
        <span
          class="post-no post-no--link"
          :title="`Click to quote No.${thread?.id}`"
          @click="user && openReply(null, `>>${thread!.id}\n`)"
        >No.{{ thread?.id }}</span>
        <button v-if="user" class="inline-btn" @click="openReply(null)">[Reply]</button>
        <button
          v-if="isOwnThread()"
          class="inline-btn inline-btn--danger"
          :disabled="deletingMeme"
          @click="deleteMeme"
        >[{{ deletingMeme ? '…' : 'Delete' }}]</button>
      </div>

      <div class="post-media" v-if="thread?.image_path">
        <a :href="`/storage/${thread.image_path}`" target="_blank">
          <img :src="`/storage/${thread.image_path}`" class="post-image" alt="" />
        </a>
      </div>

      <div class="post-body" v-if="thread?.body">
        <p v-for="(line, i) in thread.body.split('\n')" :key="i"
           :class="{ greentext: line.startsWith('>') && !line.startsWith('>>') }">
          <template v-for="(part, j) in parseBodyLine(line)" :key="j">
            <a v-if="part.href" :href="part.href" class="quote-link">{{ part.text }}</a>
            <template v-else>{{ part.text }}</template>
          </template>
        </p>
      </div>

      <div class="post-meta">
        <RatingStars
          :meme-id="Number(props.id)"
          :initial-rating="thread?.avg_rating ?? null"
          :my-rating="thread?.my_rating ?? null"
        />
        <span class="post-views">{{ thread?.views_count }} views</span>
      </div>
    </div>

    <!-- ═══ REPLIES — all flat, no nesting ═══ -->
    <div class="replies-section" v-if="replies.length">
      <div
        v-for="reply in replies"
        :key="reply.id"
        :id="`post-${reply.id}`"
        class="reply-post"
      >
        <div class="post-header">
          <span class="post-name">{{ reply.display_name }}</span>
          <span class="post-date">{{ formatPostDate(reply.created_at) }}</span>
          <!-- Clickable No.X quotes this reply -->
          <span
            class="post-no post-no--link"
            :title="`Click to quote No.${reply.id}`"
            @click="user && openReply(reply.id, `>>${reply.id}\n`)"
          >No.{{ reply.id }}</span>
          <!-- Backlink to the post this is replying to -->
          <a v-if="reply.parent_id" :href="`#post-${reply.parent_id}`" class="quote-link">
            &gt;&gt;{{ reply.parent_id }}
          </a>
          <!-- Own-post actions inline in header -->
          <span v-if="isOwnComment(reply) && editingId !== reply.id" class="post-own-actions">
            <button class="inline-btn" @click="startEdit(reply)">[Edit]</button>
            <button
              class="inline-btn inline-btn--danger"
              :disabled="deletingIds.includes(reply.id)"
              @click="deleteComment(reply.id)"
            >[{{ deletingIds.includes(reply.id) ? '…' : 'Delete' }}]</button>
          </span>
        </div>

        <!-- Image -->
        <div class="post-media" v-if="reply.image_path">
          <a :href="`/storage/${reply.image_path}`" target="_blank">
            <img :src="`/storage/${reply.image_path}`" class="post-image post-image--reply" alt="" />
          </a>
        </div>

        <!-- Edit mode -->
        <div v-if="editingId === reply.id" class="edit-area">
          <textarea v-model="editBody" rows="4" class="edit-textarea"></textarea>
          <div v-if="editError" class="edit-error">{{ editError }}</div>
          <div class="edit-actions">
            <button class="inline-btn" :disabled="editLoading" @click="saveEdit(reply.id)">
              [{{ editLoading ? '…' : 'Save' }}]
            </button>
            <button class="inline-btn" @click="cancelEdit">[Cancel]</button>
          </div>
        </div>

        <!-- Body -->
        <div v-else-if="reply.body" class="post-body">
          <p v-for="(line, i) in reply.body.split('\n')" :key="i"
             :class="{ greentext: line.startsWith('>') && !line.startsWith('>>') }">
            <template v-for="(part, j) in parseBodyLine(line)" :key="j">
              <a v-if="part.href" :href="part.href" class="quote-link">{{ part.text }}</a>
              <template v-else>{{ part.text }}</template>
            </template>
          </p>
        </div>
      </div>
    </div>

    <ReplyModal
      :open="showReplyModal"
      :thread-id="Number(props.id)"
      :parent-id="replyParentId"
      :initial-body="replyInitialBody"
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

/* ── OP ── */
.op-post {
  background: var(--cream-dark);
  padding: 12px 16px;
  margin-bottom: 10px;
  border: 1px solid var(--grey-lt);
}

/* ── Replies — all at the same level ── */
.reply-post {
  background: #fff;
  padding: 8px 14px;
  margin-bottom: 4px;
  border: 1px solid var(--grey-lt);
}

/* ── Shared header ── */
.post-header {
  display: flex;
  align-items: baseline;
  flex-wrap: wrap;
  gap: 7px;
  margin-bottom: 6px;
  font-size: 12px;
}
.post-subject {
  font-family: var(--font-serif);
  font-weight: bold;
  color: var(--brown-dk);
  font-size: 14px;
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

/* Post number — plain + clickable variant */
.post-no {
  font-family: var(--font-mono);
  font-size: 11px;
  color: var(--grey);
}
.post-no--link {
  cursor: pointer;
}
.post-no--link:hover {
  color: var(--orange);
  text-decoration: underline;
}

/* Inline text buttons (Reply, Edit, Delete, Save, Cancel) */
.inline-btn {
  background: none;
  border: none;
  padding: 0;
  font-family: var(--font-mono);
  font-size: 11px;
  color: var(--orange);
  cursor: pointer;
  line-height: 1;
}
.inline-btn:hover { text-decoration: underline; }
.inline-btn:disabled { opacity: 0.5; cursor: default; text-decoration: none; }
.inline-btn--danger { color: #a33; }
.inline-btn--danger:hover { color: #c00; }

.post-own-actions {
  display: flex;
  gap: 5px;
  margin-left: 2px;
}

/* ── Media ── */
.post-media { margin: 6px 0; }
.post-image {
  max-width: 100%;
  max-height: 400px;
  border: 1px solid var(--grey-lt);
  display: block;
  cursor: zoom-in;
}
.post-image--reply { max-height: 280px; }

/* ── Body text ── */
.post-body p {
  margin: 2px 0;
  font-size: 13px;
  line-height: 1.5;
  white-space: pre-wrap;
}
.greentext { color: #789922; }
.quote-link {
  color: var(--orange);
  font-family: var(--font-mono);
  font-size: inherit;
  text-decoration: none;
}
.quote-link:hover { text-decoration: underline; }

/* ── OP meta bar ── */
.post-meta {
  display: flex;
  gap: 14px;
  align-items: center;
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px solid var(--grey-lt);
  flex-wrap: wrap;
}
.post-views {
  font-family: var(--font-mono);
  font-size: 11px;
  color: var(--grey);
}

/* ── Inline edit ── */
.edit-area { margin: 6px 0; }
.edit-textarea {
  width: 100%;
  padding: 7px 10px;
  background: #fff;
  border: 1px solid var(--orange);
  font-family: var(--font-mono);
  font-size: 12px;
  color: var(--espresso);
  outline: none;
  resize: vertical;
  box-shadow: 0 0 0 3px rgba(212, 98, 26, 0.1);
}
.edit-error {
  font-size: 11px;
  color: var(--orange);
  font-family: var(--font-mono);
  margin: 4px 0;
}
.edit-actions { display: flex; gap: 8px; margin-top: 6px; }

@media (max-width: 480px) {
  .thread-view { padding: 0 2px; }
}
</style>
