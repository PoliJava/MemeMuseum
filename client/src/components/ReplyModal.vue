<script setup lang="ts">
import { ref, watch } from 'vue'
import { useApi } from '../composables/useApi'

const props = defineProps<{
  open: boolean
  threadId: number
  parentId?: number | null
  initialBody?: string
}>()

const emit = defineEmits<{
  close: []
  replyCreated: [comment: any]
}>()

const { postFile } = useApi()

const body = ref('')
const isAnonymous = ref(true)

// Pre-fill body (e.g. >>X quote) each time the modal opens
watch(() => props.open, (isOpen) => {
  if (isOpen) body.value = props.initialBody ?? ''
})
const authorName = ref('')
const imageFile = ref<File | null>(null)
const imagePreview = ref<string | null>(null)
const loading = ref(false)
const error = ref('')

function onFileChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0] ?? null
  imageFile.value = file
  if (imagePreview.value) URL.revokeObjectURL(imagePreview.value)
  imagePreview.value = file ? URL.createObjectURL(file) : null
}

function removeImage() {
  imageFile.value = null
  if (imagePreview.value) URL.revokeObjectURL(imagePreview.value)
  imagePreview.value = null
}

async function submit() {
  if (!body.value.trim() && !imageFile.value) {
    error.value = 'Reply must have text or an image.'
    return
  }
  loading.value = true
  error.value = ''
  try {
    const fd = new FormData()
    fd.append('meme_id', String(props.threadId))
    if (props.parentId) fd.append('parent_id', String(props.parentId))
    fd.append('is_anonymous', isAnonymous.value ? '1' : '0')
    if (body.value.trim()) fd.append('body', body.value)
    if (authorName.value.trim()) fd.append('author_name', authorName.value.trim())
    if (imageFile.value) fd.append('image', imageFile.value)

    const res = await postFile<any>('/comments', fd)
    emit('replyCreated', res.data)
    resetForm()
  } catch (e: any) {
    error.value = e.message ?? 'Could not post reply.'
  } finally {
    loading.value = false
  }
}

function resetForm() {
  body.value = ''
  authorName.value = ''
  error.value = ''
  removeImage()
}

function close() {
  resetForm()
  emit('close')
}
</script>

<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="open" class="mm-overlay" @click.self="close">
        <Transition name="slide-up">
          <div v-if="open" class="mm-modal" role="dialog" aria-modal="true">

            <div class="mm-modal-header">
              <span class="mm-modal-logo">𝕄</span>
              <span class="mm-modal-title">
                {{ parentId ? `Reply to No.${parentId}` : 'Reply to Thread' }}
              </span>
              <button class="mm-close" @click="close" aria-label="Close">✕</button>
            </div>

            <div class="mm-body">
              <div v-if="parentId" class="mm-quote-ref">&gt;&gt;{{ parentId }}</div>

              <!-- Image upload -->
              <div class="mm-field">
                <label>Image <span class="mm-optional">(optional)</span></label>
                <div
                  class="mm-upload-area"
                  :class="{ 'mm-upload-area--filled': imagePreview }"
                >
                  <input
                    v-if="!imagePreview"
                    type="file"
                    accept="image/*"
                    class="mm-file-input"
                    @change="onFileChange"
                  />
                  <div v-if="!imagePreview" class="mm-upload-placeholder">
                    <span class="mm-upload-icon">⬆</span>
                    <span>Click to attach an image</span>
                    <span class="mm-upload-hint">Max 2 MB · jpg / png / gif / webp</span>
                  </div>
                  <template v-else>
                    <img :src="imagePreview" class="mm-preview-img" alt="preview" />
                    <button class="mm-remove-img" @click="removeImage" title="Remove image">✕</button>
                  </template>
                </div>
              </div>

              <!-- Body text -->
              <div class="mm-field">
                <label>
                  Reply text
                  <span class="mm-optional" v-if="imageFile">(optional when image attached)</span>
                </label>
                <textarea
                  v-model="body"
                  rows="4"
                  placeholder="Write your reply… (> for greentext)"
                ></textarea>
              </div>

              <!-- Anonymous toggle -->
              <div class="mm-field mm-field--inline">
                <label class="mm-checkbox-label">
                  <input type="checkbox" v-model="isAnonymous" />
                  <span>Post anonymously</span>
                </label>
              </div>

              <div v-if="isAnonymous" class="mm-field">
                <label>Display name <span class="mm-optional">(optional)</span></label>
                <input v-model="authorName" type="text" placeholder="Anonymous" maxlength="64" />
              </div>

              <div v-if="error" class="mm-error">{{ error }}</div>

              <button class="mm-btn-primary" :disabled="loading" @click="submit">
                <span v-if="loading" class="mm-spinner" />
                {{ loading ? 'Posting…' : 'Post Reply' }}
              </button>
            </div>

          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.mm-overlay {
  position: fixed;
  inset: 0;
  z-index: 1000;
  background: rgba(46, 31, 20, 0.72);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.mm-modal {
  width: 100%;
  max-width: 460px;
  background: var(--cream);
  border: 1px solid var(--brown-lt);
  box-shadow: 0 8px 40px rgba(46, 31, 20, 0.45);
  overflow: hidden;
  max-height: 90vh;
  overflow-y: auto;
}

.mm-modal-header {
  background: var(--espresso);
  border-bottom: 3px solid var(--orange);
  padding: 10px 16px;
  display: flex;
  align-items: center;
  gap: 10px;
  position: sticky;
  top: 0;
  z-index: 1;
}
.mm-modal-logo {
  font-family: var(--font-serif);
  font-size: 22px;
  color: var(--orange-lt);
  line-height: 1;
}
.mm-modal-title {
  font-family: var(--font-serif);
  font-size: 14px;
  color: var(--cream);
  font-weight: bold;
  letter-spacing: 0.5px;
  flex: 1;
}
.mm-close {
  background: none;
  border: none;
  color: var(--grey);
  font-size: 13px;
  cursor: pointer;
  padding: 2px 4px;
  line-height: 1;
  transition: color 0.15s;
}
.mm-close:hover { color: var(--cream); }

.mm-body { padding: 16px 20px 20px; }

.mm-quote-ref {
  font-family: var(--font-mono);
  font-size: 12px;
  color: var(--orange);
  font-weight: bold;
  margin-bottom: 12px;
}

.mm-field { margin-bottom: 14px; }
.mm-field label {
  display: block;
  font-size: 10px;
  font-weight: bold;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  color: var(--grey-dk);
  margin-bottom: 5px;
  font-family: var(--font-sans);
}
.mm-optional {
  font-weight: normal;
  letter-spacing: 0;
  text-transform: none;
  color: var(--grey);
}
.mm-field textarea,
.mm-field input[type="text"] {
  width: 100%;
  padding: 7px 10px;
  background: #fff;
  border: 1px solid var(--grey-lt);
  font-family: var(--font-mono);
  font-size: 12px;
  color: var(--espresso);
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
  resize: vertical;
}
.mm-field textarea:focus,
.mm-field input:focus {
  border-color: var(--orange);
  box-shadow: 0 0 0 3px rgba(212, 98, 26, 0.12);
}

/* Upload area */
.mm-upload-area {
  position: relative;
  border: 2px dashed var(--grey-lt);
  background: var(--parchment);
  min-height: 80px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: border-color 0.15s;
  overflow: hidden;
}
.mm-upload-area:hover {
  border-color: var(--orange);
}
.mm-upload-area--filled {
  border-style: solid;
  border-color: var(--orange);
  min-height: unset;
  cursor: default;
}
.mm-file-input {
  position: absolute;
  inset: 0;
  opacity: 0;
  cursor: pointer;
  width: 100%;
  height: 100%;
}
.mm-upload-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 3px;
  font-family: var(--font-mono);
  font-size: 11px;
  color: var(--grey);
  pointer-events: none;
  padding: 12px;
  text-align: center;
}
.mm-upload-icon { font-size: 18px; color: var(--grey-lt); }
.mm-upload-hint { font-size: 10px; }
.mm-preview-img {
  width: 100%;
  max-height: 200px;
  object-fit: contain;
  display: block;
}
.mm-remove-img {
  position: absolute;
  top: 6px;
  right: 6px;
  background: rgba(46, 31, 20, 0.65);
  border: none;
  color: #fff;
  font-size: 11px;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
  transition: background 0.15s;
}
.mm-remove-img:hover { background: rgba(46, 31, 20, 0.9); }

.mm-field--inline { margin-bottom: 10px; }
.mm-checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-family: var(--font-sans);
  font-size: 11px;
  font-weight: bold;
  letter-spacing: 0.5px;
  color: var(--grey-dk);
  text-transform: none;
}
.mm-checkbox-label input { margin: 0; accent-color: var(--orange); }

.mm-error {
  font-size: 11px;
  color: var(--orange);
  border-left: 2px solid var(--orange);
  padding-left: 8px;
  margin-bottom: 12px;
  font-family: var(--font-mono);
}

.mm-btn-primary {
  width: 100%;
  padding: 9px 0;
  background: var(--orange);
  color: #fff;
  border: 1px solid var(--orange-lt);
  font-family: var(--font-sans);
  font-size: 12px;
  font-weight: bold;
  letter-spacing: 0.5px;
  cursor: pointer;
  transition: background 0.15s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}
.mm-btn-primary:hover:not(:disabled) { background: var(--orange-lt); }
.mm-btn-primary:disabled { opacity: 0.55; cursor: not-allowed; }

.mm-spinner {
  display: inline-block;
  width: 12px;
  height: 12px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: mm-spin 0.6s linear infinite;
}
@keyframes mm-spin { to { transform: rotate(360deg); } }

.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.slide-up-enter-active { transition: transform 0.22s ease, opacity 0.22s ease; }
.slide-up-leave-active { transition: transform 0.18s ease, opacity 0.18s ease; }
.slide-up-enter-from { transform: translateY(16px); opacity: 0; }
.slide-up-leave-to { transform: translateY(8px); opacity: 0; }
</style>
