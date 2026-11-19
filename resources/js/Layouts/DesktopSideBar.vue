<!-- resources/js/Components/SideBar/DesktopSideBar.vue -->
<script setup>
import {  ref, } from 'vue';
import { usePage } from '@inertiajs/vue3'

import Statistics from '../Pages/Components/SideBar/Statistics.vue';
import CategoryItem from './Components/CategoryItem.vue';

const page = usePage();

const categories = ref(page.props.categories || []);
// console.log(page.props)
</script>

<template>
  <Statistics />
  <div>
    <div class="SideBarH1">КАТЕГОРИИ</div>
  </div>

  <CategoryItem
    v-for="category in categories"
    :key="category.id"
    :category="category"
  />
</template>