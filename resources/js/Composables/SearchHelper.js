import { ref } from "vue";
import { router } from "@inertiajs/vue3";

export const SearchHelper = () => {
    // --- Добавленный код для поиска ---
    const searchQuery = ref("");
    const isSearchFocused = ref(false);

    const performSearch = () => {
        if (searchQuery.value.trim()) {
            // Перенаправляем на страницу постов с параметром поиска
            router.visit("/post", {
                data: {
                    search: searchQuery.value.trim(),
                },
            });
        } else {
            router.visit("/post");
        }
    };

    const handleSearchKeydown = (e) => {
        if (e.key === "Enter") {
            e.preventDefault();
            performSearch();
        }
    };

    return {
        searchQuery,
        isSearchFocused,
        performSearch,
        handleSearchKeydown,
    };
};
