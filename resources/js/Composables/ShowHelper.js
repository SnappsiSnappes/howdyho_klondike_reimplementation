import { computed } from "vue";

function extractPlainText(content, maxLength = 160) {
    if (!content) return "";

    // Парсим, если пришла строка
    let data = typeof content === "string" ? JSON.parse(content) : content;

    if (!data.blocks || !Array.isArray(data.blocks)) return "";

    // Собираем текст из поддерживаемых блоков
    const textParts = data.blocks
        .filter((block) =>
            ["paragraph", "header", "quote", "list"].includes(block.type),
        )
        .map((block) => {
            if (block.type === "list") {
                // Для списков берём текст элементов
                return block.data?.items?.join(" ") || "";
            }
            // Для остальных — текстовое поле
            return block.data?.text || "";
        })
        .filter((text) => text && text.trim())
        .join(" ");

    // Удаляем HTML-теги (если в тексте есть вложенная разметка)
    const plain = textParts.replace(/<[^>]*>/g, " ");

    // Нормализуем пробелы и обрезаем
    const cleaned = plain.replace(/\s+/g, " ").trim();

    // Обрезаем до maxLength + добавляем многоточие если нужно
    if (cleaned.length > maxLength) {
        return cleaned.substring(0, maxLength).trim() + "…";
    }

    return cleaned;
}

/**
 * Composable для генерации мета-тегов поста
 * @param {Object} props - props компонента (должен содержать post)
 * @returns {Object} computed-значения для meta-тегов
 */
export function SetupMetaForPost(props) {
    const fullImageUrl = computed(() => {
        if (!props.post?.preview_image) return null;

        // Если уже полный URL — возвращаем как есть
        if (props.post.preview_image.startsWith("https")) {
            return props.post.preview_image;
        }

        // Добавляем домен (безопасно для SSR)
        if (typeof window !== "undefined") {
            const origin = window.location.origin;
            return `${origin}${props.post.preview_image}`;
        }
        return null; // SSR: возвращаем null, если нет window
    });

    const fullPageUrl = computed(() => {
        if (typeof window !== "undefined") {
            return window.location.href;
        }
        return "";
    });

    const description = computed(() => {
        if (props.post?.preview_text) {
            return extractPlainText(props.post.preview_text);
        }
        return "";
    });

    return {
        fullImageUrl,
        fullPageUrl,
        description,
    };
}
