<script setup>
import { ref, onMounted } from "vue";

const memes = ref([]);

onMounted(async () => {
  try {
    const res = await fetch("http://127.0.0.1:8000/api/memes");
    memes.value = await res.json();
  } catch (err) {
    console.error(err);
  }
});
</script>

<template>
  <div>
    <h1>MemeMuseum 🖼️</h1>

    <ul>
      <li v-for="meme in memes" :key="meme.id">
        {{ meme.title }}
      </li>
    </ul>
  </div>
</template>
