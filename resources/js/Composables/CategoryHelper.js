// composables/useCategoryNavigation.js
import { ref,computed } from 'vue';
import { router } from '@inertiajs/vue3';

export function useCategoryNavigation(CategoryId) {
  const openItems = ref(new Set());
  const isOpen = computed(() => {
    return openItems.value.has(CategoryId);
  });
  const toggleItem = (id) => {
    openItems.value.has(id)
      ? openItems.value.delete(id)
      : openItems.value.add(id);
  };

  const goToCategory = (slug) => {
        router.visit(route('post.index'), {
        data: {
            category:slug,
        }
    });
  };

  return {
    isOpen,
    toggleItem,
    goToCategory,
  };
}