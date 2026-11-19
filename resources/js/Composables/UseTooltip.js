// composables/useTooltip.js
import { reactive } from "vue";

export function useTooltip() {
    const tooltip = reactive({
        visible: false,
        title: "",
        x: 0,
        y: 0,
    });

    // Предполагаемая высота тултипа (в px) — настройте под ваш дизайн
    const TOOLTIP_HEIGHT = 32; // 1rem = 16px → 32px = 2rem
    const TOOLTIP_WIDTH = 200; // Ширина по умолчанию

    let hideTimeout = null;
    const AUTO_HIDE_DELAY = 1100; // Авто-скрытие через ? секунд

    const showTooltip = (e, title) => {
        // Сбрасываем предыдущий таймер, чтобы не было гонки
        if (hideTimeout) {
            clearTimeout(hideTimeout);
            hideTimeout = null;
        }

        const targetRect = e.target.getBoundingClientRect();

        let x = targetRect.right + 10; // Отступ 10px справа от элемента
        let y = targetRect.top + targetRect.height / 2 - TOOLTIP_HEIGHT / 2;

        // Защита от выхода за границы экрана по Y
        const maxY = window.innerHeight - TOOLTIP_HEIGHT - 5;
        const minY = 5;
        const safeY = Math.max(minY, Math.min(y, maxY));

        // Защита от выхода за границы экрана по X
        const maxX = window.innerWidth - TOOLTIP_WIDTH - 5;
        const minX = 5;
        const safeX = Math.max(minX, Math.min(x, maxX));

        tooltip.visible = true;
        tooltip.title = title;
        tooltip.x = safeX;
        tooltip.y = safeY;

        // Автоматическое исчезновение через AUTO_HIDE_DELAY мс
        hideTimeout = setTimeout(() => {
            hideTooltip();
        }, AUTO_HIDE_DELAY);
    };

    const hideTooltip = () => {
        if (hideTimeout) {
            clearTimeout(hideTimeout);
            hideTimeout = null;
        }
        tooltip.visible = false;
    };

    return {
        tooltip,
        showTooltip,
        hideTooltip,
    };
}