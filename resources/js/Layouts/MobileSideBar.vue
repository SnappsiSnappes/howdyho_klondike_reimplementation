<!-- resources/js/Components/SideBar/DesktopSideBar.vue -->
<script setup>
import { ref, } from 'vue';
import { router, usePage } from '@inertiajs/vue3'

import Statistics from '../Pages/Components/SideBar/Statistics.vue';
import CategoryItem from './Components/CategoryItem.vue';
import { SearchHelper } from '../Composables/SearchHelper';
import ArrowRight from '../svg/ArrowRight.vue';

const page = usePage();

const props = defineProps({
  auth: Object,
});
const categories = ref(page.props.categories || []);

const {
  searchQuery,
  isSearchFocused,
  performSearch,
  handleSearchKeydown,
} = SearchHelper();


</script>

<template>

  <Statistics class="px-4 mt-4" />


  <div class="px-[1.2rem] ">
    <nav class="flex flex-col w-full gap-y-2">

      <div class="py-5">
        <div class="relative">
          <input v-model="searchQuery" @keydown="handleSearchKeydown" @focus="isSearchFocused = true"
            @blur="isSearchFocused = false" type="text" placeholder="Что ищем?" class="SearchInput-mobile"
            autocomplete="off" minlength="3" maxlength="30" />

          <button @click="performSearch" class="SearchBtn-mobile">
            <!-- Показываем "ENTER" только при фокусе -->
            <transition name="fade">
              <small v-if="isSearchFocused" class="SearchEnterText-mobile"><ArrowRight :color="'var(--secondary)'" /></small>
            </transition>
          </button>
        </div>
      </div>


      <!-- Все пункты — одинаковая структура: Link > div.Text -->


      <Link :href="route('home')" :class="page.component === 'home' ? 'HeaderSelectedDiv' : 'HeaderUnselectedDiv'">
      <div class="HeaderText" :class="{ 'HeaderActive': page.component === 'home' }">
        Обо мне
      </div>
      </Link>

      <Link :href="route('post.index')"
        :class="page.component === 'Post/Index' ? 'HeaderSelectedDiv' : 'HeaderUnselectedDiv'">
      <div class="HeaderText" :class="{ 'HeaderActive': page.component === 'Post/Index' }">
        Посты
      </div>
      </Link>

    </nav>
  </div>


  <div class="px-4 ">
    <div class="SideBarH1">КАТЕГОРИИ</div>
  </div>

  <div class="px-4 space-y-1 ">
    <CategoryItem v-for="category in categories" :key="category.id" :category="category" />
  </div>
</template>