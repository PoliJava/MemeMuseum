<script setup lang="ts">
import { ref, watch } from "vue";
import { useApi } from "../composables/useApi";
import { useAuth } from "../composables/useAuth";

const props = defineProps<{ open: boolean; boardSlug?: string | null }>();
const emit = defineEmits<{ close: []; threadCreated: [meme: any] }>();

const { get, postFile } = useApi();
const { user } = useAuth();

// Resolved numeric board ID (the API requires exists:boards,id)
const resolvedBoardId = ref<number | null>(null);
const boardName = ref<string | null>(null);
const resolvingBoard = ref(false);

const form = ref({
  title: "",
  body: "",
  age: "Modern (2017-2020)",
  is_anonymous: true,
  author_name: "",
  image: null as File | null,
});

const tagInput = ref("");

const loading = ref(false);
const error = ref("");
const imagePreview = ref<string | null>(null);

// Resolve slug → numeric board id whenever the modal opens with a slug
watch(
  () => [props.open, props.boardSlug] as const,
  async ([open, slug]) => {
    if (!open) return;
    error.value = "";
    if (slug) {
      resolvingBoard.value = true;
      try {
        // GET /boards returns all boards; find the one matching the slug
        const res = await get<{
          data: Array<{ id: number; slug: string; name: string }>;
        }>("/boards");
        const board = res.data.find((b) => b.slug === slug);
        if (board) {
          resolvedBoardId.value = board.id;
          boardName.value = board.name;
        } else {
          error.value = `Board "/${slug}/" not found.`;
          resolvedBoardId.value = null;
        }
      } catch {
        error.value = "Could not load board list.";
        resolvedBoardId.value = null;
      } finally {
        resolvingBoard.value = false;
      }
    } else {
      resolvedBoardId.value = null;
      boardName.value = null;
    }
  },
  { immediate: true },
);

function onFileChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0] ?? null;
  form.value.image = file;
  if (imagePreview.value) URL.revokeObjectURL(imagePreview.value);
  imagePreview.value = file ? URL.createObjectURL(file) : null;
}

function resetForm() {
  if (imagePreview.value) URL.revokeObjectURL(imagePreview.value);
  imagePreview.value = null;
  form.value = {
    title: "",
    body: "",
    age: "modern",
    is_anonymous: true,
    author_name: "",
    image: null,
  };
  tagInput.value = "";
  error.value = "";
}

function close() {
  resetForm();
  emit("close");
}

async function submit() {
  error.value = "";

  if (!form.value.image) {
    error.value = "Please select an image.";
    return;
  }
  if (!form.value.title.trim()) {
    error.value = "Title is required.";
    return;
  }
  if (!resolvedBoardId.value) {
    error.value = "No board selected. Please open this form from a board page.";
    return;
  }

  loading.value = true;
  try {
    const fd = new FormData();
    fd.append("title", form.value.title.trim());
    fd.append("body", form.value.body);
    fd.append("age", form.value.age);
    fd.append("board_id", String(resolvedBoardId.value)); // numeric ID — StoreRequest: exists:boards,id
    fd.append("is_anonymous", form.value.is_anonymous ? "1" : "0");
    if (form.value.is_anonymous && form.value.author_name.trim()) {
      fd.append("author_name", form.value.author_name.trim());
    }
    fd.append("image", form.value.image);
    const tags = tagInput.value
      .split(",")
      .map((t) => t.trim())
      .filter((t) => t.length > 0);
    tags.forEach((t) => fd.append("tags[]", t));

    const res = await postFile<{ data: any }>("/memes", fd);
    emit("threadCreated", res.data);
    resetForm();
    emit("close");
  } catch (e: any) {
    // Surface Laravel validation errors if present
    error.value =
      e?.response?.data?.message ?? e?.message ?? "Submission failed.";
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="open" class="mm-overlay" @click.self="close">
        <Transition name="slide-up">
          <div
            v-if="open"
            class="mm-modal mm-modal-large"
            role="dialog"
            aria-modal="true"
          >
            <div class="mm-modal-header">
              <span class="mm-modal-logo">𝕄</span>
              <span class="mm-modal-title">
                Submit to Collection
                <span v-if="boardName" class="mm-modal-board"
                  >/{{ props.boardSlug }}/</span
                >
              </span>
              <button class="mm-close" @click="close" aria-label="Close">
                ✕
              </button>
            </div>

            <div class="mm-body">
              <!-- Board resolving state -->
              <div v-if="resolvingBoard" class="mm-resolving">
                Locating board…
              </div>

              <template v-else>
                <!-- Image upload + preview -->
                <div class="mm-field">
                  <label>Image *</label>
                  <div
                    class="mm-upload-area"
                    :class="{ 'mm-upload-area--filled': imagePreview }"
                  >
                    <input
                      type="file"
                      accept="image/*"
                      class="mm-file-input"
                      @change="onFileChange"
                    />
                    <div v-if="!imagePreview" class="mm-upload-placeholder">
                      <span class="mm-upload-icon">⬆</span>
                      <span>Click or drag an image here</span>
                      <span class="mm-upload-hint"
                        >Max 2 MB · jpg / png / gif / webp</span
                      >
                    </div>
                    <img
                      v-else
                      :src="imagePreview"
                      class="mm-preview-img"
                      alt="preview"
                    />
                  </div>
                </div>

                <!-- Title -->
                <div class="mm-field">
                  <label>Title *</label>
                  <input
                    v-model="form.title"
                    type="text"
                    placeholder="Give this artifact a name"
                    maxlength="255"
                    @input="error = ''"
                  />
                </div>

                <!-- Description -->
                <div class="mm-field">
                  <label
                    >Description
                    <span class="mm-optional">(optional)</span></label
                  >
                  <textarea
                    v-model="form.body"
                    rows="3"
                    placeholder="Provenance, context, lore…"
                  ></textarea>
                </div>

                <!-- Era -->
                <div class="mm-field">
                  <label>Era</label>
                  <select v-model="form.age">
                    <option value="Ancient (Pre-2004)">
                      Ancient (Pre-2004)
                    </option>
                    <option value="Medieval (2004-2008)">
                      Medieval (2004–2008)
                    </option>
                    <option value="Classic (2009-2013)">
                      Classic (2009–2013)
                    </option>
                    <option value="Golden (2014 - 2016)">
                      Golden (2014–2016)
                    </option>
                    <option value="Modern (2017 - 2020)">
                      Modern (2017–2020)
                    </option>
                    <option value="Postmodern (2021 - Present)">
                      Postmodern (2021–Present)
                    </option>
                  </select>
                </div>

                <!-- Tags -->
                <div class="mm-field">
                  <label>Tags <span class="mm-optional">(optional, comma-separated)</span></label>
                  <input
                    v-model="tagInput"
                    type="text"
                    placeholder="e.g. Programming, Cats, Reaction"
                    maxlength="200"
                  />
                  <div v-if="tagInput.trim()" class="mm-tag-preview">
                    <span
                      v-for="tag in tagInput.split(',').map(t => t.trim()).filter(t => t)"
                      :key="tag"
                      class="mm-tag-chip"
                    >{{ tag }}</span>
                  </div>
                </div>

                <!-- Anonymous toggle -->
                <div class="mm-field mm-field--inline">
                  <label class="mm-checkbox-label">
                    <input type="checkbox" v-model="form.is_anonymous" />
                    <span>Post anonymously</span>
                  </label>
                </div>

                <!-- Author name — shown when anonymous (server: required_if:is_anonymous,true but nullable) -->
                <div v-if="form.is_anonymous" class="mm-field">
                  <label
                    >Display name
                    <span class="mm-optional">(optional)</span></label
                  >
                  <input
                    v-model="form.author_name"
                    type="text"
                    placeholder="Anonymous"
                    maxlength="64"
                  />
                </div>

                <div v-if="error" class="mm-error">{{ error }}</div>

                <button
                  class="mm-btn-primary"
                  :disabled="loading || resolvingBoard"
                  @click="submit"
                >
                  <span v-if="loading" class="mm-spinner" />
                  {{ loading ? "Uploading…" : "Submit to Museum" }}
                </button>
              </template>
            </div>

            <div class="mm-modal-foot">
              All submissions are subject to curation review · Max 2 MB per
              image
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
  max-width: 380px;
  background: var(--cream);
  border: 1px solid var(--brown-lt);
  box-shadow: 0 8px 40px rgba(46, 31, 20, 0.45);
  overflow: hidden;
}
.mm-modal-large {
  max-width: 480px;
}

.mm-modal-header {
  background: var(--espresso);
  border-bottom: 3px solid var(--orange);
  padding: 10px 16px;
  display: flex;
  align-items: center;
  gap: 10px;
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
  display: flex;
  align-items: center;
  gap: 8px;
}
.mm-modal-board {
  font-family: var(--font-mono);
  font-size: 11px;
  color: var(--orange-lt);
  background: rgba(255, 255, 255, 0.08);
  padding: 2px 6px;
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
.mm-close:hover {
  color: var(--cream);
}

.mm-body {
  padding: 20px 20px 16px;
}

.mm-field {
  margin-bottom: 14px;
}
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
.mm-field input[type="text"],
.mm-field input[type="email"],
.mm-field select,
.mm-field textarea {
  width: 100%;
  padding: 7px 10px;
  background: #fff;
  border: 1px solid var(--grey-lt);
  font-family: var(--font-mono);
  font-size: 12px;
  color: var(--espresso);
  outline: none;
  transition:
    border-color 0.15s,
    box-shadow 0.15s;
  resize: vertical;
}
.mm-field input:focus,
.mm-field select:focus,
.mm-field textarea:focus {
  border-color: var(--orange);
  box-shadow: 0 0 0 3px rgba(212, 98, 26, 0.12);
}

/* Upload area */
.mm-upload-area {
  position: relative;
  border: 2px dashed var(--grey-lt);
  background: var(--parchment);
  min-height: 100px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: border-color 0.15s;
}
.mm-upload-area:hover {
  border-color: var(--orange);
}
.mm-upload-area--filled {
  border-style: solid;
  border-color: var(--orange);
  padding: 0;
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
  gap: 4px;
  font-family: var(--font-mono);
  font-size: 11px;
  color: var(--grey);
  pointer-events: none;
}
.mm-upload-icon {
  font-size: 20px;
  color: var(--grey-lt);
}
.mm-upload-hint {
  font-size: 10px;
  color: var(--grey);
}
.mm-preview-img {
  width: 100%;
  max-height: 200px;
  object-fit: contain;
  display: block;
}

/* Inline checkbox */
.mm-field--inline {
  margin-bottom: 10px;
}
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
.mm-checkbox-label input {
  margin: 0;
  accent-color: var(--orange);
}

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
.mm-btn-primary:hover:not(:disabled) {
  background: var(--orange-lt);
}
.mm-btn-primary:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.mm-spinner {
  display: inline-block;
  width: 12px;
  height: 12px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: mm-spin 0.6s linear infinite;
}
@keyframes mm-spin {
  to {
    transform: rotate(360deg);
  }
}

.mm-tag-preview {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
  margin-top: 6px;
}
.mm-tag-chip {
  font-family: var(--font-mono);
  font-size: 10px;
  color: var(--orange);
  background: var(--parchment);
  border: 1px solid var(--grey-lt);
  padding: 2px 7px;
}

.mm-resolving {
  text-align: center;
  font-family: var(--font-mono);
  font-size: 11px;
  color: var(--grey);
  padding: 24px 0;
  letter-spacing: 1px;
}

.mm-modal-foot {
  background: var(--parchment);
  border-top: 1px solid var(--grey-lt);
  padding: 7px 16px;
  font-size: 10px;
  color: var(--grey);
  font-style: italic;
  text-align: center;
  letter-spacing: 0.3px;
}

/* Transitions — matching AuthModal */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
.slide-up-enter-active {
  transition:
    transform 0.22s ease,
    opacity 0.22s ease;
}
.slide-up-leave-active {
  transition:
    transform 0.18s ease,
    opacity 0.18s ease;
}
.slide-up-enter-from {
  transform: translateY(16px);
  opacity: 0;
}
.slide-up-leave-to {
  transform: translateY(8px);
  opacity: 0;
}
</style>
