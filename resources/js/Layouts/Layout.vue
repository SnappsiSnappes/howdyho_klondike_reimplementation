<script setup>
import { provide, ref, watch } from 'vue';

import FlashMessage from '../Pages/Components/FlashMessage.vue';
import { usePage } from '@inertiajs/vue3'; // Импортируем usePage

import DesktopSideBar from './DesktopSideBar.vue';
import MobileSideBar from './MobileSideBar.vue';
import { Link } from '@inertiajs/vue3';
import { SearchHelper } from '../Composables/SearchHelper';

const page = usePage();
const props = defineProps({
    auth: Object,
});


const isSidebarOpen = ref(false);
const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
};


watch(isSidebarOpen, (isOpen) => {
    if (isOpen) {
        document.body.classList.add('overflow-hidden');
    } else {
        document.body.classList.remove('overflow-hidden');
    }
});


// Метод для очистки флеш-сообщения
function clearFlashMessage() {
    page.props.flash.message = null;
    page.props.flash.type = null;
};

const {
    searchQuery,
    isSearchFocused,
    performSearch,
    handleSearchKeydown,
} = SearchHelper();


provide('searchQuery', searchQuery);

</script>

<template>

    <div class="flex flex-col min-h-screen ">

        <header class="HeaderNAVBAR flex lg:block">

            <nav class="flex  items-center justify-between  HeaderNav space-x-5 sm:space-x-6">

                <div class="LogoSection">
                    <div class="Logo"></div>
                    <Link :href="route('post.index')">Snappsi<span class="text-yellow-400">Snappes</span></Link>
                </div>


                <div class="space-x-6 relative">



                    <!-- <Link class=" HeaderText" :href="route('post.index')"
                        :class="{ 'HeaderActive': page.component === 'Post/Index' }">Посты</Link> -->
                </div>

                <!-- --- Добавленный блок поиска --- -->
                <div class="flex-1 max-w-md mx-4 hidden sm:block">
                    <div class="relative">
                        <input v-model="searchQuery" @keydown="handleSearchKeydown" @focus="isSearchFocused = true"
                            @blur="isSearchFocused = false" type="text" placeholder="Что ищем?" class="SearchInput"
                            autocomplete="off" minlength="3" maxlength="15" />

                        <button @click="performSearch" class="SearchBtn">
                            <!-- Показываем "ENTER" только при фокусе -->
                            <transition name="fade">
                                <small v-if="isSearchFocused" class="SearchEnterText">ENTER</small>
                            </transition>
                        </button>
                    </div>
                </div>
            </nav>
        </header>

        <!-- Всплывающее уведомление
        <FlashMessage v-if="page.props.flash.message" :message="page.props.flash.message" :type="page.props.flash.type"
            @close="clearFlashMessage" /> -->

        <!-- Основной контент с сайдбаром -->
        <div id="Main" class="flex flex-1 w-full max-w-screen-xl mx-auto gap-6">

            <!-- Основной контент -->
            <main  class="flex-1 min-w-0 w-9/12">
                <slot />
            </main>

            <!-- Мобильный сайдбар (overlay) -->
            <div class="fixed inset-0 z-40 lg:hidden transition-opacity duration-300" :class="{
                'opacity-100 visible pointer-events-auto': isSidebarOpen,
                'opacity-0 invisible pointer-events-none': !isSidebarOpen
            }">

                <!-- Оверлей для затемнения фона -->
                <div class="fixed inset-0 bg-black bg-opacity-50" @click="isSidebarOpen = false"></div>

                <!-- Сайдбар мобильный -->
                <div v-if="page.component != 'Post/Edit' && page.component != 'Post/Create'"
                    class="MobileSideBar absolute  left-0 top-0 h-full 
                w-[18.4rem] 
                sm:w-[20rem]

                bg-[--CategoryItemBg] text-white overflow-y-auto transform transition-transform duration-300 ease-in-out "
                    :class="{ 'translate-x-0': isSidebarOpen, '-translate-x-full': !isSidebarOpen }">
                    <MobileSideBar />
                </div>
            </div>

            <!-- Сайдбар десктопный  -->
            <div v-if="page.component != 'Post/Edit' && page.component != 'Post/Create'"
                class="hidden lg:block w-3/12 pt-6 mt-[-0.7rem] mb-[-25.9rem] SideBar">
                <!-- 20px-->
                <DesktopSideBar />
            </div>
        </div>


    </div>

    <!-- Кнопка для мобилок -->
    <button @click="toggleSidebar" class=" SideBarSvg">
    </button>

    <div class="relative -z-10">
        <div class="absolute  left-0 w-full bg-[#0b0d10] h-[22rem]">


            <!-- Адаптивный блок текста -->
            <div class="
    text-[--TextMuted]
    xl:ps-[15rem] xl:w-[35rem] xl:text-[0.86rem] 
    lg:ps-[10rem] lg:w-[30rem] lg:text-[0.86rem]  
    md:ps-[10rem] md:w-[30rem] md:text-[0.86rem] md:pt-[5rem] 
    sm:ps-[5rem] sm:w-[25rem] sm:text-[0.86rem] sm:pt 
    ps-[3rem] w-[18rem] pt-[5rem] text-[0.86rem] 
    text-balance
    ">
                <div class="LogoSectionFooter mb-2  ">
                    <div class="Logo"></div>
                    <Link :href="route('post.index')">Snappsi<span class="text-yellow-400">Snappes</span></Link>
                </div>
                <p>
                    Это ресурс не преследует цель незаконного распространения материалов, поэтому если вы являетесь
                    правообладателем, сообщите мне о нарушении по указанным ниже контактам.
                </p>
            </div>

            <div class="absolute top-[20.5rem] left-0 w-full bg-[#040506] h-[5.3rem]">
                <div class="
      xl:ps-[15rem] xl:text-[0.9rem] 
      lg:ps-[10rem] lg:text-[1rem] 
      md:ps-[10rem] md:text-[1rem] 
      sm:ps-[5rem] sm:text-[1rem] 
      ps-[3rem]  text-[0.8rem]
        text-balance pt-[1rem] w-full ">
                    <p>
                        <span class="text-[--TextMuted]">© 2026 </span> <span>NAME!!##</span> <span
                            class="text-[--secondary] text-[0.76rem]">email@email</span>
                    </p>
                </div>
            </div>

        </div>
    </div>


</template>