// Composables/useRandomCategory.js
import { ref, onMounted } from "vue";
import { usePage } from "@inertiajs/vue3";
import { router } from '@inertiajs/vue3';


export function useRandomCategory() {
    const page = usePage();
    const categories = ref(page.props.categories || []);
    const selectedRandomCategory = ref(null);

    // Выбираем сразу, если категории есть
    if (categories.value.length > 0) {
        const randomIndex = Math.floor(Math.random() * categories.value.length);
        selectedRandomCategory.value = categories.value[randomIndex];
    }

    const goToCategory = (slug) => {
        router.visit(route("post.index"), {
            data: { category: slug },
        });
    };

    return {
        selectedRandomCategory,
        goToCategory,
    };
}
