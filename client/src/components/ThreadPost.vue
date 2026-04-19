<template>
  <div :class="['post', isOp ? 'op-post' : 'reply-post']">
    <div class="post-header">
      <span v-if="post.subject" class="post-subject">{{ post.subject }}</span>
      <span class="post-author">{{ post.author }}</span>
      <span class="post-date">{{ post.timestamp }}</span>
      <span class="post-id">No. {{ post.id }}</span>
      <span v-if="isOp" class="thread-action" @click="$emit('open-thread')"
        >[Reply]</span
      >
    </div>

    <div class="post-container">
      <div v-if="post.image" class="post-image-container">
        <a :href="post.image" target="_blank">
          <img :src="post.image" class="post-thumb" alt="thumbnail" />
        </a>
      </div>
      <div class="post-body">
        <p
          v-for="(line, index) in formattedBody"
          :key="index"
          :class="{ greentext: line.startsWith('>') }"
        >
          {{ line }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  post: Object,
  isOp: Boolean,
});

defineEmits(["open-thread", "quote"]);

const formattedBody = computed(() => {
  return props.post.body.split("\n");
});
</script>
