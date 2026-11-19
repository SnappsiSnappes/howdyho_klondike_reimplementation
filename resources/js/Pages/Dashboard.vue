<script setup>

const props = defineProps({
    authUser: Object, // Добавляем пропс для данных текущего пользователя
    IsAdmin: Boolean
})
// console.log(props, 'Dashboard.vue, props')
</script>

<template>

    <Head :title="`${$page.component}`" />

        <div class="mt-4 py-6" v-if="authUser">
            <a :href="route('post.create')" class="primary-btn w-1/5">Create New Post</a>
        </div>

</template>
