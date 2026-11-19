<!-- Components/ReactionSearch.vue -->
<script setup>
import { ref, watch, onUnmounted } from 'vue';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const props = defineProps({
  postId: {
    type: Number,
    required: true
  },
  placeholder: {
    type: String,
    default: 'Поиск реакций...'
  }
});

const inputRef = ref(null);

defineExpose({
  focusInput() {
    inputRef.value?.focus();
  }
});

const emit = defineEmits(['update-reactions']);


const searchQuery = ref('');
const timeoutId = ref(null);

// Очистка поиска
const clearSearch = () => {
  searchQuery.value = '';
  // emit сработает автоматически через watch
};

// Debounce + запрос
watch(searchQuery, (newQuery) => {
  clearTimeout(timeoutId.value);
  if (!newQuery.trim()) {
    emit('update-reactions', null);
    return;
  }

  timeoutId.value = setTimeout(async () => {
    try {
      const { data } = await axios.post('/SearchReaction', {
        query: newQuery.trim(),
      });

      const mappedElements = (data.Elements || []).map(el => ({
        id: el.id.toString(),
        label: el.name,
        path: `/storage/${el.file_name}`,
        sticker: el.type?.sticker || 0,
      }));

      emit('update-reactions', mappedElements);
    } catch (error) {
      console.error('Search error:', error);
      page.props.flash.message = { message: 'Не удалось выполнить поиск.', type: 'error' };
      emit('update-reactions', null);
    }
  }, 300);
});

onUnmounted(() => {
  clearTimeout(timeoutId.value);
});
</script>


<template>
  <div class="w-full lg:w-7/12">
    <div class="flex items-center ms-[0.2rem] pr-[0.5rem] w-full py-1.5  text-sm rounded-md bg-[--ReactionGlobalBg] border border-[--ReactionBorderColor] focus-within:ring-1 focus-within:ring-[--ReactionRingBg] focus-within:outline-none">
      <!-- Иконка поиска -->
      <div class="flex-shrink-0 text-[--ReactionTextColor]/60 mx-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </div>

      <!-- Настоящий input (без border, bg и т.д.) -->
      <input
        ref="inputRef"
        v-model="searchQuery"
        type="text"
        :placeholder="placeholder"
        class="w-full bg-transparent text-[--ReactionTextColor] placeholder-[--ReactionTextColor]/60 outline-none "
      />

      <!-- Кнопка очистки -->
      <button
        v-if="searchQuery"
        @click="clearSearch"
        class="flex-shrink-0  text-[--ReactionTextColor]/70 hover:text-[--ReactionTextColor] bg-transparent border-none cursor-pointer"
        aria-label="Очистить поиск"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
  </div>
</template>