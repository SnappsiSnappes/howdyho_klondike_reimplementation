<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import lottie from 'lottie-web';

import { computed, ref, inject, onMounted, onBeforeUnmount } from 'vue'
import PostCard from '../Components/PostCard.vue';
import PostCardNoImage from '../Components/PostCardNoImage.vue';
import ToggleReaction from '../Components/ToggleReaction.vue';
import ReactionSvgCustom from '../../svg/ReactionSvgCustom.vue';
import ViewsSvg from '../../svg/ViewsSvg.vue';
import DateSvg from '../../svg/DateSvg.vue';
import { useRandomCategory } from '../../Composables/RandomCategoryPicker';
import { usePaginationWithFilters } from '../../Composables/PaginationHelper';


const props = defineProps({
    post: Object,
    SelectedCategory: Object,
    sort: String, // ← добавь
    search: String,
});


const searchQuery = inject('searchQuery')

// Инициализация композабла
const { buildPageUrl } = usePaginationWithFilters();


// Храним текущую сортировку и направление
const currentSort = ref(props.sort?.split(':')?.[0] || 'date');
const currentDirection = ref(props.sort?.split(':')?.[1] || 'desc');

const FilterByReactions = (sortBy) => {
    let newDirection = 'desc';

    // Переключаем направление, если тот же тип
    if (currentSort.value === sortBy) {
        newDirection = currentDirection.value === 'desc' ? 'asc' : 'desc';
    }

    currentSort.value = sortBy;
    currentDirection.value = newDirection;

    router.visit(route('post.index'), {
        data: {
            search: searchQuery.value,
            sort: `${sortBy}:${newDirection}`,
            category: props.SelectedCategory.slug,
        }
    });
};

const visiblePages = computed(() => {
    const { links } = props.post
    // Убираем первую и последнюю стрелки (« и »), если они есть
    const pages = links.slice(1, -1)

    const totalPages = props.post.last_page
    const currentPage = props.post.current_page

    // Показываем максимум 5 номеров страниц вокруг текущей
    const delta = 2
    const range = []
    const rangeWithDots = []

    for (let i = Math.max(2, currentPage - delta); i <= Math.min(totalPages - 1, currentPage + delta); i++) {
        range.push(i)
    }

    if (currentPage - delta > 2) {
        rangeWithDots.push({ ...pages[0], label: '1' }) // первая
        rangeWithDots.push({ ...pages[0], label: '…', url: null, active: false })
    }

    range.forEach(i => {
        // Найдём соответствующий элемент в links по label (осторожно — label может быть строкой!)
        const pageItem = pages.find(p => parseInt(p.label) === i)
        if (pageItem) rangeWithDots.push(pageItem)
    })

    if (currentPage + delta < totalPages - 1) {
        rangeWithDots.push({ ...pages[0], label: '…', url: null, active: false })
        rangeWithDots.push({ ...pages[pages.length - 1], label: String(totalPages) })
    }

    // Если всего 1–2 страницы — просто вернём все
    if (totalPages <= 5) {
        return pages
    }

    return rangeWithDots
})


// const buildPageUrl = (baseUrl) => {
//     if (!baseUrl) return '#';
    
//     // Создаем объект URL из базовой ссылки пагинации
//     const url = new URL(baseUrl);
    
//     // Получаем текущие параметры из query string (если они там вдруг есть)
//     const params = new URLSearchParams(url.search);
    
//     // Принудительно устанавливаем наши актуальные параметры сортировки и категории
//     // Важно: используем текущие значения из ref/current state
//     params.set('sort', `${currentSort.value}:${currentDirection.value}`);
    
//     if (props.SelectedCategory?.slug) {
//         params.set('category', props.SelectedCategory.slug);
//     }
    
//     // Если есть поиск, тоже добавляем его
//     if (searchQuery.value) {
//         params.set('search', searchQuery.value);
//     }

//     // Собираем итоговый URL
//     return `${url.pathname}?${params.toString()}`;
// };


const { selectedRandomCategory, goToCategory } = useRandomCategory();


// ✅ Проверка: есть ли посты
const hasPosts = computed(() => {
    return props.post.data && props.post.data.length > 0;
});


let anim = null;

onMounted(() => {
    if (!hasPosts.value) {
        anim = lottie.loadAnimation({
            container: document.getElementById('NoData'),
            renderer: 'svg',
            loop: true,
            autoplay: true,
            path: '/storage/decoration/no-data.json'
        });
    }
});

onBeforeUnmount(() => {
    if (anim) {
        anim.destroy();
    }
});

</script>

<template>

    <Head :title="`Посты`" />
    <div class="px-4 Decoration">

        <!-- <div v-if="selectedRandomCategory && selectedRandomCategory.collection_img !== null"
            @click.stop="goToCategory(selectedRandomCategory.slug)"
            class="pt-[3rem] cursor-pointer opacity-90 hover:opacity-100 md:min-h-[190px] px-[2rem] md:px-0">
            <img :src="'/storage/' + selectedRandomCategory.collection_img" :alt="selectedRandomCategory.name" />
        </div> -->


        <div class="h-full pt-[3rem] h-min-[5rem] flex items-end justify-center   md:justify-start">
            <div
                class="flex flex-nowrap  md:flex-row md:justify-between w-full  md:gap-0  flex-col gap-[2rem]    items-center">

                <div class="flex flex-nowrap md:flex-row flex-col gap-5 md:gap-0 justify-center items-center">
                    <div
                        class="ps-[2rem]   text-[2.21rem]   font-roboto-condensed PostIndexCategoryUnderlineDecoration font-semibold  relative group">

                        <span class="IconForCategoryPostIndex"
                            :style="{ backgroundImage: `url(/storage/${props.SelectedCategory.preview_img})` }">
                            <span class="IconForCategoryPostIndexBg"
                                :style="{ backgroundImage: `url(/storage/${props.SelectedCategory.preview_img})` }"></span>
                        </span>

                        <p class="text-nowrap ">{{ props.SelectedCategory.name }}</p>
                    </div>
                    <div v-if="props.SelectedCategory.description"
                        class="  md:ps-[1rem] text-[1.21rem] font-roboto-condensed ">
                        <p class="PostIndexP">{{ props.SelectedCategory.description }}</p>

                    </div>
                </div>

                <div class="flex items-center gap-4 justify-end">
                    <!-- В нужном месте (рядом с иконками) -->
                    <div class="flex items-center cursor-pointer md:ps-[1rem] text-[1.21rem] font-roboto-condensed pb-5 md:pb-0"
                        title="Сортировка по количеству реакций" @click="FilterByReactions('reactions')">
                        <ReactionSvgCustom :color="currentSort === 'reactions' ? 'var(--secondary)' : '#e5e7eb'" />
                        <span v-if="currentSort === 'reactions'"
                            class="ms-1 text-[2rem] text-[--secondary] select-none">
                            {{ currentDirection === 'asc' ? '↑' : '↓' }}
                        </span>
                    </div>

                    <div class="flex items-center cursor-pointer md:ps-[1rem] text-[1.21rem] font-roboto-condensed pb-5 md:pb-0"
                        title="Сортировка по количеству просмотров" @click="FilterByReactions('views')">
                        <ViewsSvg :color="currentSort === 'views' ? 'var(--secondary)' : '#e5e7eb'" />
                        <span v-if="currentSort === 'views'" class="ms-1 text-[2rem] text-[--secondary] select-none">
                            {{ currentDirection === 'asc' ? '↑' : '↓' }}
                        </span>
                    </div>

                    <div class="flex items-center cursor-pointer md:ps-[1rem] text-[1.21rem] font-roboto-condensed pb-5 md:pb-0"
                        title="Сортировка по дате создания" @click="FilterByReactions('date')">
                        <DateSvg :color="currentSort === 'date' ? 'var(--secondary)' : '#e5e7eb'" />
                        <span v-if="currentSort === 'date'" class="ms-1 text-[2rem] text-[--secondary] select-none">
                            {{ currentDirection === 'asc' ? '↑' : '↓' }}
                        </span>
                    </div>
                </div>
            </div>


        </div>


        <div v-for="post in post.data" :key="post.id" class="mb-4  py-4 ">

            <PostCard v-if="post.preview_image" :post="post" />
            <PostCardNoImage v-if="!post.preview_image" :post="post" />

            <!-- Реакции -->
            <div class="pt-1 flex justify-center ">
                <ToggleReaction :PostId="post.id" />
            </div>
        </div>

        <div v-if="post.data.length == 0">
            <div class="pt-[2rem]">
                <h1 class="header-1 UnderlineDecoration">Ничего не найдено</h1>
                <div id="NoData" class="w-full h-[15rem]"></div>
            </div>
        </div>

        <!-- Pagination -->
        <!-- Pagination -->
        <div v-if="post.last_page > 1" class="mt-8 mb-4 flex items-center justify-center space-x-2">
            
            <!-- Первая страница -->
            <Link 
                v-if="post.current_page > 1" 
                :href="buildPageUrl(post.first_page_url, {
                    currentSort,
                    currentDirection,
                    categorySlug: props.SelectedCategory?.slug,
                    searchQuery: searchQuery?.value
                })" 
                preserve-scroll 
                class="PaginationButton"
            >
                «
            </Link>

            <!-- Предыдущая -->
            <Link 
                v-if="post.prev_page_url" 
                :href="buildPageUrl(post.prev_page_url, {
                    currentSort,
                    currentDirection,
                    categorySlug: props.SelectedCategory?.slug,
                    searchQuery: searchQuery?.value
                })" 
                preserve-scroll 
                class="PaginationButton"
            >
                ‹
            </Link>

            <!-- Центральные страницы -->
            <template v-for="(page, index) in visiblePages" :key="index">
                <Link 
                    v-if="page.url" 
                    :href="buildPageUrl(page.url, {
                        currentSort,
                        currentDirection,
                        categorySlug: props.SelectedCategory?.slug,
                        searchQuery: searchQuery?.value
                    })" 
                    preserve-scroll 
                    class="PaginationButton"
                    :class="{ 'PaginationButton--active': page.active }" 
                    v-html="page.label" 
                />
                <span v-else class="PaginationButton PaginationButton--disabled" v-html="page.label" />
            </template>

            <!-- Следующая -->
            <Link 
                v-if="post.next_page_url" 
                :href="buildPageUrl(post.next_page_url, {
                    currentSort,
                    currentDirection,
                    categorySlug: props.SelectedCategory?.slug,
                    searchQuery: searchQuery?.value
                })" 
                preserve-scroll 
                class="PaginationButton"
            >
                ›
            </Link>

            <!-- Последняя -->
            <Link 
                v-if="post.current_page < post.last_page" 
                :href="buildPageUrl(post.last_page_url, {
                    currentSort,
                    currentDirection,
                    categorySlug: props.SelectedCategory?.slug,
                    searchQuery: searchQuery?.value
                })" 
                preserve-scroll 
                class="PaginationButton"
            >
                »
            </Link>
        </div>
    </div>
</template>
