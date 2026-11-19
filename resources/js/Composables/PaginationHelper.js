import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";

export function usePaginationWithFilters() {
    const page = usePage();

    /**
     * Формирует URL для пагинации, сохраняя текущие параметры фильтрации и сортировки.
     * @param {string|null} baseUrl - Базовый URL страницы от Laravel (например, ?page=2)
     * @param {Object} options - Дополнительные опции
     * @param {string} options.currentSort - Текущий тип сортировки (напр. 'reactions')
     * @param {string} options.currentDirection - Направление сортировки (напр. 'desc')
     * @param {string} options.categorySlug - Слаг категории
     * @param {string} options.searchQuery - Поисковый запрос
     * @returns {string} Итоговый URL
     */
    const buildPageUrl = (
        baseUrl,
        { currentSort, currentDirection, categorySlug, searchQuery },
    ) => {
        if (!baseUrl) return "#";

        try {
            // Если baseUrl относительный (начинается с /), добавляем протокол и хост для корректного парсинга
            // Или используем window.location.origin, если baseUrl просто путь
            let fullUrl = baseUrl;
            if (baseUrl.startsWith("/")) {
                fullUrl = window.location.origin + baseUrl;
            } else if (!baseUrl.startsWith("http")) {
                // На случай если пришло что-то странное
                fullUrl = window.location.origin + "/" + baseUrl;
            }

            const url = new URL(fullUrl);
            const params = new URLSearchParams(url.search);

            // 1. Сортировка
            if (currentSort && currentDirection) {
                params.set("sort", `${currentSort}:${currentDirection}`);
            }

            // 2. Категория
            if (categorySlug) {
                params.set("category", categorySlug);
            }

            // 3. Поиск
            // Важно: если поиск пустой, лучше удалить параметр, чтобы не было ?search=
            if (searchQuery) {
                params.set("search", searchQuery);
            } else {
                params.delete("search");
            }

            // Собираем обратно.
            // replace() нужен, чтобы убрать лишний слэш или протокол, если мы их добавляли искусственно,
            // но Inertia Link обычно нормально съедает абсолютные URL того же домена.
            // Для чистоты вернем путь + поисковую строку
            return `${url.pathname}?${params.toString()}`;
        } catch (e) {
            console.error("Ошибка построения URL пагинации:", e);
            return baseUrl; // Фолбэк на оригинальный URL, если что-то пошло не так
        }
    };

    return {
        buildPageUrl,
    };
}
