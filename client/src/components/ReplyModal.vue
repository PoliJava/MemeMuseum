<script setup lang="ts">
import { ref } from 'vue'
import { useApi } from '../composables/useApi'

const props = defineProps<{ open: boolean; threadId: number; parentId?: number }>()
const emit = defineEmits(['close', 'replyCreated'])

const { post } = useApi()

const body = ref('')
const isAnonymous = ref(true)
const authorName = ref('')
const loading = ref(false)
const error = ref('')

async function submit() {
  if (!body.value.trim()) {
    error.value = 'Reply cannot be empty.'
    return
  }
  loading.value = true
  try {
    const payload = {
      body: body.value,
      meme_id: props.threadId,
      parent_id: props.parentId || null,
      is_anonymous: isAnonymous.value,
      author_name: authorName.value || null,
    }
    const res = await post<any>('/comments', payload)
    emit('replyCreated', res.data)
    emit('close')
    body.value = ''
    authorName.value = ''
  } catch (e: any) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="open" class="mm-overlay" @click.self="emit('close')">
      <div class="mm-modal">
        <div class="mm-modal-header">
          <span>Reply to thread</span>
          <button class="mm-close" @click="emit('close')">✕</button>
        </div>
        <div class="mm-body">
          <div class="mm-field">
            <textarea v-model="body" rows="4" placeholder="Write your reply..."></textarea>
          </div>
          <div class="mm-field">
            <label><input type="checkbox" v-model="isAnonymous" /> Anonymous</label>
          </div>
          <div class="mm-field" v-if="isAnonymous">
            <input v-model="authorName" placeholder="Name (optional)" />
          </div>
          <div v-if="error" class="mm-error">{{ error }}</div>
          <button class="mm-btn-primary" :disabled="loading" @click="submit">
            {{ loading ? 'Posting...' : 'Post Reply' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>