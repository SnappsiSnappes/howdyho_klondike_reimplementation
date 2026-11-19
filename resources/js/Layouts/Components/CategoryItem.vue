<script setup>
import { ChevronLeftIcon, ArrowRightIcon } from '@heroicons/vue/24/solid';
import { computed } from 'vue';
import { useCategoryNavigation } from '../../Composables/CategoryHelper';

const props = defineProps({
  category: {
    type: Object,
    required: true
  }
});

const {isOpen, toggleItem, goToCategory} = useCategoryNavigation(props.category.id);


const hasChildren = computed(() => {
  return props.category.children && Object.keys(props.category.children).length > 0;
});


</script>

<template>
  <div class="bg-[--CategoryItemBg] hover:bg-[--CategoryHoverBg] pt-[0.45rem]">
    <div class="flex items-center w-full group">
      <button
        v-if="hasChildren"
        type="button"
        class=" flex-1 p-5 flex justify-between items-center text-left text-[#d1d1d1] font-medium "
        @click="toggleItem(category.id)"
        :aria-expanded="isOpen"
      >
        <div class="flex items-center gap-3 ">
          <div class="pe-3 relative ">
            <span
              class="IconForCategoryDesktop"
              :style="{ backgroundImage: `url(/storage/${category.preview_img})` }"
            >
              <span
                class="IconForCategoryDesktopBg"
                :style="{ backgroundImage: `url(/storage/${category.preview_img})` }"
              ></span>
            </span>
          </div>
          <span>{{ category.name }}</span>
        </div>
        <ChevronLeftIcon
          class="h-5 w-5 opacity-70 transition-transform duration-200"
          :class="{ '-rotate-90': isOpen }"
        />
      </button>

      <div
        v-else
        class="flex-1 p-5 flex items-center gap-3 text-[#d1d1d1]"
      >
        <div class="pe-3 relative group">
          <span
            class="IconForCategoryDesktop"
            :style="{ backgroundImage: `url(/storage/${category.preview_img})` }"
          >
            <span
              class="IconForCategoryDesktopBg"
              :style="{ backgroundImage: `url(/storage/${category.preview_img})` }"
            ></span>
          </span>
        </div>
        <span>{{ category.name }}</span>
      </div>

      <button
        type="button"
        class="p-3 text-[#888] hover:text-white flex-shrink-0"
        @click.stop="goToCategory(category.slug)"
        title="Перейти к категории"
      >
        <ArrowRightIcon class="h-4 w-4" />
      </button>
    </div>

    <div
      v-if="hasChildren"
      class="overflow-hidden transition-all duration-300 ease-in-out ml-4 border-l-2 border-[#444] "
      :class="isOpen ? 'max-h-[1000px] opacity-100' : 'max-h-0 opacity-0 group'"
    >
      <div class="bg-[#2a2e31] ">
        <CategoryItem
          v-for="(child, key) in category.children"
          :key="key"
          :category="child"
        />
      </div>
    </div>
  </div>
</template>
