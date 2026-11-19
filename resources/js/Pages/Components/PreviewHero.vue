<script setup>
import { onMounted, ref, watch, onBeforeUnmount } from "vue";
import ImageHelper from "../../Objects/ImageHelper";
import PhotoSwipeLightbox from "photoswipe/lightbox";
import EditorJsRenderer from "./EditorJsRenderer.vue";
import { ChatBubbleBottomCenterIcon, ViewColumnsIcon, DocumentTextIcon } from '@heroicons/vue/24/outline';
import ReactionSvg from "../../svg/ReactionSvg.vue";
import { useCategoryNavigation } from "../../Composables/CategoryHelper";

const props = defineProps({
    post: {
        type: Object,
        required: true,
    },
});
const {isOpen, toggleItem, goToCategory} = useCategoryNavigation(props.post.category.id);

// Реактивная высота контейнера
const containerHeight = ref('17rem'); // fallback: если изображения нет — 17rem

// Функция расчёта высоты
const CalculateContainerHeight = () => {
    if (!props.post.preview_image) {
        containerHeight.value = '15rem';
        return;
    }

    // Есть изображение — считаем 55% от высоты окна, но не менее 500px
    const heightInPx = window.innerHeight * 0.55;
    containerHeight.value = Math.max(500, heightInPx) + 'px';
};



// Реактивная переменная для хранения данных изображения
const ConvertedFile = ref({});

const scrollTo = (selector) => {
    const element = document.querySelector(selector);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

onMounted(async () => {
    CalculateContainerHeight(); // сразу применяем высоту

    // Обработка изображения, если есть
    if (props.post.preview_image) {
        ConvertedFile.value = await ImageHelper.GetImageWithSizes(props.post.preview_image);

        // Инициализация PhotoSwipeLightbox
        const lightbox = new PhotoSwipeLightbox({
            gallery: "#HeroImage",
            children: "a",
            pswpModule: () => import("photoswipe"),
            showHideAnimationType: "zoom",
            showAnimationDuration: 350,
            hideAnimationDuration: 350,
            closeOnScroll: false,
            wheelToZoom: true,
        });
        lightbox.init();
    }
});


</script>

<template>
    <div class="  overflow-hidden transition duration-300 ">
        <!-- Основной контент -->
        <div class="relative h-min-[500px]   bg-gray-800  overflow-hidden " :style="{ height: containerHeight }">
            <!-- Изображение превью (фон) -->
            <div class="absolute top-0 left-0 w-full h-full preview-img"
                :style=" { backgroundImage: `url(${post.preview_image})` }">
                <!-- Затемнение снизу вверх -->
                <div class="Darker">
                </div>
            </div>

            <!-- Кликабельное изображение для PhotoSwipeLightbox -->
            <div id="HeroImage" class="absolute inset-0 flex items-center justify-center">
                <a :href="ConvertedFile.url" :data-pswp-width="ConvertedFile.width"
                    :data-pswp-height="ConvertedFile.height" class="w-full h-full"
                    :class="{ 'cursor-pointer': ConvertedFile.url }">
                    <!-- хак -->
                    <img :src="ConvertedFile.url" :alt="ConvertedFile.caption || 'Image'"
                        class="opacity-0 w-full h-full" />

                </a>
            </div>

            <!-- Текстовая часть поверх изображения -->
            <div class="absolute bottom-0 left-0 w-full">

                <div class="sm:px-[1.5rem] sm:py-[1.1rem] px-[1rem] py-[0.7rem]">
                    <!-- Заголовок -->
                    <p class="gradient-header  mb-2  ">{{ post.title }}</p>

                    <div
                        class="text-[0.94rem] sm:text-[1.04rem] text-[--TextMuted] opacity-100 pt-1 flex items-start sm:items-center  flex-nowrap flex-col sm:flex-row  overflow-hidden 
                        gap-2
                        sm:gap-5
                        ">
                        <!-- Дата -->
                        <div class="whitespace-nowrap">Опубликовано {{ post.humanReadableDate }}</div>

                        <!-- Категория -->
                        <div class="whitespace-nowrap flex-shrink-0 cursor-pointer">
                            <a @click="(goToCategory(post.category.slug))"
                                class="CategoryBlueFire flex items-center gap-1"
                                :title="`Категория ${post.category.name}`">
                                <img class="w-[1.4rem] h-[1.4rem] align-middle object-cover rounded inline-block"
                                    :src="`/storage/${post.category.preview_img}`" alt="Категория" />
                                <span class="whitespace-nowrap">{{ post.category.name }}</span>
                            </a>
                        </div>

                        <!-- Просмотры -->
                        <div class="flex items-center gap-2 whitespace-nowrap flex-shrink-0"
                            title="Количество просмотров">
                            <img :src="`/storage/decoration/view.svg`" alt="Просмотры" class="w-6 h-6 invert" />
                            {{ post.views_count }}
                        </div>
                    </div>
                </div>

                <div class="preview-tabs">
                    <div class="preview-tab" @click="scrollTo('#editorjs-renderer')">
                        <component :is=DocumentTextIcon class="h-[1.6rem] w-[1.6rem] inline  align-middle" />
                        ТЕКСТ СТАТЬИ
                    </div>
                    <div class="preview-tab tab-reactions "
                        @click="scrollTo('.reaction-btn-bg'); scrollTo('.reaction-btn-bg-active')">
                        <ReactionSvg />
                        РЕАКЦИИ
                    </div>
                    <div class="preview-tab preview-tab-comments" @click="scrollTo('.Comment-label')">
                        <component :is=ChatBubbleBottomCenterIcon class="h-[1.6rem] w-[1.6rem] inline align-middle" />
                        КОММЕНТАРИИ
                    </div>
                    <div class="preview-tab preview-tab-similar" @click="scrollTo('#SimilarPosts')">
                        <component :is=ViewColumnsIcon class="h-[1.6rem] w-[1.6rem] inline  align-middle" />
                        ПОХОЖИЕ МАТЕРИАЛЫ
                    </div>



                </div>


            </div>
        </div>
    </div>
</template>

<style scoped>
.bg-cover {
    background-size: cover;
    background-position: center top;
    /* ← КЛЮЧЕВОЕ */
    background-repeat: no-repeat;
}

.preview-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    object-position: bottom;
    background-repeat: no-repeat;
    background-size: cover;
    background-position: center top;
}
</style>
