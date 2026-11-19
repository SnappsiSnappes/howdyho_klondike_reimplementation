<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import PostCardSmall from '../Components/PostCardSmall.vue';
import PostCardNoImage from '../Components/PostCardNoImage.vue';
import { ViewColumnsIcon, } from '@heroicons/vue/24/solid';

const props = defineProps({
    post: Object,
    authUser: Object,
    IsAdmin: Boolean,
    relatedPosts: Array,
});

// Состояние слайдера
const currentIndex = ref(0);
const autoSlideInterval = ref(null);
const SLIDE_DURATION = 15000; 

// Автоматическая прокрутка
const startAutoSlide = () => {
    autoSlideInterval.value = setInterval(() => {
        nextSlide();
    }, SLIDE_DURATION);
};

const stopAutoSlide = () => {
    if (autoSlideInterval.value) {
        clearInterval(autoSlideInterval.value);
        autoSlideInterval.value = null;
    }
};

// Управление слайдами
const nextSlide = () => {
    if (props.relatedPosts.length <= 1) return;
    currentIndex.value = (currentIndex.value + 1) % props.relatedPosts.length;
};

const prevSlide = () => {
    if (props.relatedPosts.length <= 1) return;
    currentIndex.value =
        (currentIndex.value - 1 + props.relatedPosts.length) % props.relatedPosts.length;
};

// При монтировании — запускаем авто-слайд
onMounted(() => {
    if (props.relatedPosts.length > 1) {
        startAutoSlide();
    }
});

// Очищаем интервал при уничтожении компонента
onBeforeUnmount(() => {
    stopAutoSlide();
});

// Если relatedPosts изменились — сбросить индекс и перезапустить
watch(() => props.relatedPosts, () => {
    currentIndex.value = 0;
    stopAutoSlide();
    if (props.relatedPosts.length > 1) {
        startAutoSlide();
    }
});
</script>

<template>
    <div id="SimilarPosts" v-if="relatedPosts.length > 0" class="pt-10">
        <div class="text-2xl font-roboto-condensed  pt-5 items-center flex gap-1">
            <component :is=ViewColumnsIcon class="h-[1.6rem] w-[1.6rem] inline " />
            Похожие материалы:
        </div>

        <div class="relative overflow-hidden mt-4">
            <!-- Контейнер слайдов -->
            <div class="flex transition-transform duration-500 ease-in-out"
                :style="{ transform: `translateX(-${currentIndex * 100}%)` }">
                <div v-for="post in relatedPosts" :key="post.id" class="w-full flex-shrink-0 ">
                    <PostCardSmall v-if="post.preview_image" :post="post" :IsAdmin="IsAdmin" :authUser="authUser" />
                    <PostCardNoImage v-else :post="post" :IsAdmin="IsAdmin" :authUser="authUser" />
                </div>
            </div>
        </div>

        <!-- Кнопки навигации -->
        <div v-if="relatedPosts.length > 1" class="flex justify-between items-center mt-4 ">
            <button @click="prevSlide" @mouseenter="stopAutoSlide" @mouseleave="startAutoSlide"
                class="p-2 rounded-full shadow-[--ui-shadow]  shadow-md bg-[--ui-btn-bg] hover:bg-[--ui-btn-hover-bg] text-[--ui-btn-text] transition"
                aria-label="Предыдущий пост">
                ◀
            </button>

            <div class="flex space-x-2">
                <!-- Индикаторы (точки) -->
                <button v-for="(_, index) in relatedPosts" :key="index" @click="currentIndex = index"
                    @mouseenter="stopAutoSlide" @mouseleave="startAutoSlide" class="w-3 h-3 rounded-full transition"
                    :class="index === currentIndex ? 'bg-[--ui-btn-active-bg]' : 'bg-[--StatisticTextColor]   '"
                    :aria-label="`Перейти к слайду ${index + 1}`"></button>
            </div>

            <button @click="nextSlide" @mouseenter="stopAutoSlide" @mouseleave="startAutoSlide"
                class="p-2 rounded-full shadow-[--ui-shadow]  shadow-md bg-[--ui-btn-bg] hover:bg-[--ui-btn-hover-bg] text-[--ui-btn-text] transition"
                aria-label="Следующий пост">
                ▶
            </button>
        </div>
    </div>
</template>

<style scoped>
/* Убедимся, что карточки не сжимаются */
#SimilarPosts .w-full {
    min-width: 0;
}
</style>