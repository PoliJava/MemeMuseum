<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useApi } from "../composables/useApi";
import TagCloud from "./TagCloud.vue";
import MemeOfTheDay from "./MemeOfTheDay.vue";

interface Board {
  id: number;
  slug: string;
  name: string;
  description: string;
  is_archived: boolean;
  is_readonly: boolean;
  memes_count?: number;
}

const { get } = useApi();
const boards = ref<Board[]>([]);
const loading = ref(true);

onMounted(async () => {
  try {
    const res = await get<{ data: Board[] }>("/boards");
    boards.value = res.data;
  } catch (err) {
    console.error("Error fetching boards:", err);
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div class="content-grid">
    <section class="boards-section" id="boards">
      <div class="section-header">
        <h2 class="section-title">Galleries &amp; Boards</h2>
        <span class="section-rule"></span>
      </div>
      <div v-if="loading" class="loading">Loading boards…</div>
      <div v-else class="boards-table">
        <div class="boards-table-head">
          <span>Board</span>
          <span>Description</span>
          <span class="col-right">Threads</span>
          <span class="col-right">Status</span>
        </div>
        <router-link
          v-for="board in boards"
          :key="board.id"
          :to="`/board/${board.slug}`"
          class="board-row"
          :class="{ 'board-row--archived': board.is_archived }"
        >
          <span class="board-slug">/{{ board.slug }}/</span>
          <span class="board-info">
            <span class="board-name">{{ board.name }}</span>
            <span class="board-desc">{{ board.description }}</span>
          </span>
          <span class="board-stat col-right">{{ board.memes_count ?? 0 }}</span>
          <span class="board-stat col-right">
            <span v-if="!board.is_archived" class="active-dot"></span>
            <span v-else class="archived-tag">archived</span>
          </span>
        </router-link>
      </div>
    </section>

    <aside class="sidebar">
      <MemeOfTheDay />
      <TagCloud />
      <div class="sidebar-block sidebar-block--rules">
        <div class="sidebar-block-title">Museum Rules</div>
        <ol class="rules-list">
          <li>All submissions must be in good faith.</li>
          <li>No reposts within 30 days.</li>
          <li>Source your classics.</li>
          <li>OC boards require original work only.</li>
          <li>The curators' decisions are final.</li>
        </ol>
      </div>
      <!-- No submit CTA here: posting requires a board context.
           The "+ New Thread" button lives in BoardView. -->
      <div class="sidebar-block sidebar-block--hint">
        <div class="sidebar-block-title">Contribute</div>
        <p>
          Enter a board and use the <strong>+ New Thread</strong> button to
          submit a work.
        </p>
      </div>
    </aside>
  </div>
</template>

<style scoped>
.loading {
  padding: 20px;
  text-align: center;
  color: var(--grey);
}
</style>
