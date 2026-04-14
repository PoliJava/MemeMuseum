<script setup lang="ts">
import { ref } from 'vue'
import { useApi } from '../composables/useApi'
import { useAuth } from '../composables/useAuth'

const props = defineProps<{ open: boolean; boardSlug?: string | null }>()
const emit = defineEmits(['close', 'threadCreated'])

const { postFile } = useApi()
const { user } = useAuth()

const form = ref({
  title: '',
  body: '',
  age: 'modern',
  is_anonymous: true,
  author_name: '',
  image: null as File | null,
})
const loading = ref(false)
const error = ref('')

async function submit() {
  if (!form.value.image) {
    error.value = 'Please select an image or video.'
    return
  }
  if (!form.value.title.trim()) {
    error.value = 'Title is required.'
    return
  }
  loading.value = true
  error.value = ''
  try {
    const fd = new FormData()
    fd.append('title', form.value.title)
    fd.append('body', form.value.body || '')
    fd.append('age', form.value.age)
    // board_id: idealmente il backend accetta slug, altrimenti devi passare l'id numerico
    // Per semplicità assumiamo che il backend converta lo slug in id. Se non lo fa,
    // dovresti fare una fetch per ottenere l'id della board. Qui passo slug, da implementare nel backend.
    fd.append('board_id', props.boardSlug ?? '')
    fd.append('is_anonymous', String(form.value.is_anonymous))
    if (form.value.author_name) fd.append('author_name', form.value.author_name)
    fd.append('image', form.value.image)

    const res = await postFile<any>('/memes', fd)
    emit('threadCreated', res.data)
    emit('close')
    // reset form
    form.value = { title: '', body: '', age: 'modern', is_anonymous: true, author_name: '', image: null }
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
      <div class="mm-modal mm-modal-large">
        <div class="mm-modal-header">
          <span>Submit new work</span>
          <button class="mm-close" @click="emit('close')">✕</button>
        </div>
        <div class="mm-body">
          <div class="mm-field">
            <label>Title *</label>
            <input v-model="form.title" required />
          </div>
          <div class="mm-field">
            <label>Description (optional)</label>
            <textarea v-model="form.body" rows="3"></textarea>
          </div>
          <div class="mm-field">
            <label>Era</label>
            <select v-model="form.age">
              <option value="classic">Classic (2010–2015)</option>
              <option value="modern">Modern (2016–2020)</option>
              <option value="ancient">Ancient (pre-2010)</option>
              <option value="modern_era">Post-2020</option>
            </select>
          </div>
          <div class="mm-field">
            <label>File (image/video)</label>
            <input type="file" @change="e => form.image = (e.target as HTMLInputElement).files?.[0] || null" accept="image/*,video/*" />
          </div>
          <div class="mm-field">
            <label>
              <input type="checkbox" v-model="form.is_anonymous" />
              Post anonymously
            </label>
          </div>
          <div class="mm-field" v-if="form.is_anonymous">
            <label>Name (optional)</label>
            <input v-model="form.author_name" placeholder="Anonymous" />
          </div>
          <div v-if="error" class="mm-error">{{ error }}</div>
          <button class="mm-btn-primary" :disabled="loading" @click="submit">
            {{ loading ? 'Uploading...' : 'Submit to Museum' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.mm-modal-large { max-width: 500px; width: 90%; }
textarea { width: 100%; padding: 8px; font-family: monospace; }
</style>