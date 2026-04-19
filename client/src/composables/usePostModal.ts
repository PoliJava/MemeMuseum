/**
 * usePostModal.ts
 *
 * Shared composable that replaces the window.dispatchEvent('openPostModal') hack.
 *
 * Usage
 * -----
 * In App.vue:
 *   import { usePostModal } from './composables/usePostModal'
 *   const { isOpen, boardSlug, open, close } = usePostModal()
 *
 * In any child (BoardView, BoardList, header button, etc.):
 *   import { usePostModal } from '../composables/usePostModal'
 *   const { open } = usePostModal()
 *   // then: open('funny')  or  open()  to open without a pre-selected board
 */

import { ref } from "vue";

const isOpen = ref(false);
const boardSlug = ref<string | null>(null);

export function usePostModal() {
  function open(slug?: string) {
    boardSlug.value = slug ?? null;
    isOpen.value = true;
  }

  function close() {
    isOpen.value = false;
    // Keep boardSlug alive until the modal's leave-transition finishes
    setTimeout(() => {
      boardSlug.value = null;
    }, 300);
  }

  return { isOpen, boardSlug, open, close };
}
