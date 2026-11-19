<!-- PostCard.vue -->
<script setup>
import { Link } from "@inertiajs/vue3";
import EditorJsRenderer from "./EditorJsRenderer.vue";
import { useCategoryNavigation } from "../../Composables/CategoryHelper";

const props = defineProps({
    post: { type: Object, required: true },
});


const {isOpen, toggleItem, goToCategory} = useCategoryNavigation(props.post.category.id);


</script>

<template>
    <div class="w-full  post-card rounded-lg transition duration-300 relative">
        <!-- Grid-контейнер -->
        <div class="post-grid">


            <!-- Текстовая часть -->
            <div class="content-container">

                <div class="px-[0.3rem] sm:px-[1.5rem] ">
                    <div class="text-[--ui-text]">
                        <Link :href="route('post.show', post)">
                            <p class="cool-header ">{{ post.title }}</p>
                        </Link>

                        <div class=" text-[0.88rem] text-[--TextMuted] opacity-100 flex items-center flex-wrap gap-5 ">
                            <!-- Дата -->
                            <div>Опубликовано {{ post.humanReadableDate }}</div>

                            <!-- Категория -->
                            <div class="">
                                <a @click="(goToCategory(post.category.slug))" class="CategoryBlueFire cursor-pointer"
                                    :title="`Категория ${post.category.name}`">
                                    <img class="w-[1.4rem] h-[1.4rem] align-middle object-cover rounded inline-block"
                                        :src="`/storage/${post.category.preview_img}`" alt="Категория" />
                                    {{ post.category.name }}
                                </a>
                            </div>

                            <!-- Просмотры -->
                            <div class="flex items-center gap-2" title="Количество просмотров">

                                <img :src="`/storage/decoration/view.svg`" alt="Просмотры" class="w-6 h-6 invert " />
                                {{ post.views_count }}
                            </div>
                        </div>
                    </div>

                    <div class="!text-[#88919d] mb-5 mt-4">
                        <EditorJsRenderer :placement="'CARD'" :content="post.preview_text" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Grid layout */
.post-grid {
    display: grid;
    grid-template-columns: 1fr;
    min-height: 300px;
}



@media (min-width: 768px) {
    .post-grid {
        grid-template-columns: 1fr;
    }
}

/* Левая колонка — изображение */
.preview-image {
    background-color: #000;
    border-radius: 3px 0 0 3px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
    vertical-align: middle;
    object-position: center;
    /* <-- добавлено для лучшего центрирования */
}

.image-link {
    display: block;
    width: 100%;
    height: 100%;
}

.preview-img {
    width: 100%;
    height: 100%;
    max-height: 360px;
    min-height: 300px;
    object-fit: cover;
    display: block;
    object-position: bottom;

}

.image-placeholder {
    width: 100%;
    height: 100%;
    min-height: 250px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #111;
}

/* Правая колонка — контент */
.content-container {
    background-color: rgb(8, 12, 15);
    padding: 0rem 0.4rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    border-radius: 0 3px 3px 0;
}



.actions {
    color: white;
}

.meta {
    color: rgba(255, 255, 255, 0.7);
}
</style>