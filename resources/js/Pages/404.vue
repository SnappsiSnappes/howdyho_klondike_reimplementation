<script setup>
import { onMounted,onBeforeUnmount } from 'vue';
import lottie from 'lottie-web';

onMounted(() => {
    const anim = lottie.loadAnimation({
        container: document.getElementById('sticker'),
        renderer: 'svg',
        loop: true,
        autoplay: true,
        path: '/storage/decoration/404.json'
    });

    // Опционально: очистка при размонтировании
    onBeforeUnmount(() => {
      anim.destroy();
    });
});
</script>

<template>

    <Head :title="`404`" />

    <div class="px-4 Decoration">
        <div class="pt-[2rem]">
            <h1 class="header-1 UnderlineDecoration">страница не найдена</h1>
            <div id="sticker" class="w-full h-full"></div>
        </div>
    </div>
</template>