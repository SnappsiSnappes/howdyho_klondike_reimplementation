<script setup>
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue';
import CommentSection from '../Components/CommentSection.vue';
import RelatedPosts from '../Components/RelatedPosts.vue';
import ToggleReaction from '../Components/ToggleReaction.vue';
import PreviewHero from '../Components/PreviewHero.vue';
import EditorJsRenderer from '../Components/EditorJsRenderer.vue';
import { useRandomCategory } from '../../Composables/RandomCategoryPicker';
import { SetupMetaForPost } from '../../Composables/ShowHelper';

const props = defineProps({
    post: Object,
    relatedPosts: Array,

});

const { selectedRandomCategory, goToCategory } = useRandomCategory();

const { fullImageUrl, fullPageUrl, description } = SetupMetaForPost(props);

</script>

<template>

    <Head :title="post.title">
        <!-- Basic Meta -->
        <meta name="description" :content="description" />

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="article" />
        <meta property="og:url" :content="fullPageUrl" />
        <meta property="og:title" :content="post.title" />
        <meta property="og:description" :content="description" />

        <!-- OG Image (только если есть превью) -->
        <template v-if="fullImageUrl">
            <meta property="og:image" :content="fullImageUrl" />
            <meta property="og:image:alt" :content="post.title" />
        </template>

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="post.title" />
        <meta name="twitter:description" :content="description" />
    </Head>

    <div class="px-4 ">

        <div id="post-content" class="w-full">

            <div class="pb-5">
                <PreviewHero :post="props.post" />

            </div>

            <EditorJsRenderer :content="props.post.body" />

        </div>

        <RelatedPosts :post="post" :related-posts="relatedPosts" />

        <!-- разграничительная линия -->
        <div class=" my-[3rem] flex items-center justify-center">
            <div
                class="flex-grow border-t border-transparent bg-gradient-to-r from-[var(--primary-from)] to-[var(--primary-to)] h-[0.2rem]">
            </div>
        </div>
        <ToggleReaction :PostId="props.post.id" />


        <div v-if="selectedRandomCategory && selectedRandomCategory.collection_img !== null"
            @click.stop="goToCategory(selectedRandomCategory.slug)"
            class="pt-[3rem] cursor-pointer opacity-90 hover:opacity-100 md:min-h-[190px] px-[2rem] md:px-0">
            <img :src="'/storage/' + selectedRandomCategory.collection_img" :alt="selectedRandomCategory.name" />
        </div>

        <div class="py-[3.5rem]">
            <CommentSection :post="post" />
        </div>
    </div>
</template>
