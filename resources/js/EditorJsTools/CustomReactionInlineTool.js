// resources/js/EditorJsTools/CustomReactionInlineTool.js
import {
    reactionPacksCache,
    loadAllPacksIfNeeded,
    loadPackIfNeeded,
} from "../Stores/ReactionCache";

export default class CustomReactionInlineTool {
    static get isInline() {
        return true;
    }

    static get title() {
        return "Вставить реакцию";
    }

    constructor({ api, config = {} }) {
        this.api = api;
        this.config = config;
        this.button = null;
        this.picker = null;
        this.lastRange = null;
        this.selectedReaction = null;

        // Состояние
        this.packs = [];
        this.currentPackId = null;
        this.currentElements = [];
        this.allElementsById = {};
    }

    /**
     * Рендер кнопки в тулбаре
     */
    render() {
        this.button = document.createElement("button");
        this.button.type = "button";
        this.button.classList.add(this.api.styles.inlineToolButton);
        this.button.innerHTML = `
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z" fill="currentColor"/>
            </svg>
        `;
        this.button.title = "Вставить реакцию";
        return this.button;
    }

    /**
     * Вызывается при клике на кнопку инструмента
     */
    surround(range) {
        if (!range) {
            // Если нет выделения — берём текущую позицию курсора
            const sel = window.getSelection();
            if (sel.rangeCount > 0) {
                range = sel.getRangeAt(0);
            }
        }

        this.lastRange = range;
        this.showReactionPicker();
    }

    /**
     * Показывает попап с выбором реакций
     */
    async showReactionPicker() {
        if (this.picker) this.hidePicker();

        // Загружаем паки, если нужно
        if (this.packs.length === 0) {
            try {
                this.packs = await loadAllPacksIfNeeded();
                // Загружаем первый пак
                if (this.packs[0]) {
                    await this.loadPack(this.packs[0].id);
                }
            } catch (e) {
                console.error("Ошибка загрузки паков реакций:", e);
                return;
            }
        }

        this.picker = this.createPicker();
        document.body.appendChild(this.picker);

        // Позиционируем рядом с выделением
        this.positionPicker();

        // Обработчик клика вне
        setTimeout(() => {
            document.addEventListener("click", this.handleOutsideClick);
            document.addEventListener("keydown", this.handleEscape);
        }, 0);
    }

    /**
     * Создаёт DOM попапа
     */
    createPicker() {
        const picker = document.createElement("div");
        picker.className = "reaction-inline-picker";

        // ✅ Все размеры в rem + CSS-переменные для темизации
        picker.style.cssText = `
        position: absolute;
        z-index: 10000;
        background: var(--ReactionDropdownGlobalBg, #1f2937);
        border: 0.06rem solid var(--ReactionBorderColor, #374151);
        border-radius: 0.5rem;
        box-shadow: 0 0.25rem 0.75rem rgba(0,0,0,0.15);
        display: flex;
        flex-direction: column;
        
        /* ✅ Фиксированные размеры в rem */
        width: 20rem;
        min-width: 17.5rem;
        max-width: 24rem;
        max-height: 18.75rem;
        
        font-family: system-ui, -apple-system, sans-serif;
        font-size: 0.875rem;
        overflow: hidden;
    `;

        // ── Хедер с паками ──
        const header = document.createElement("div");
        header.style.cssText = `
        display: flex;
        gap: 0.25rem;
        padding: 0.5rem;
        border-bottom: 0.06rem solid var(--ReactionBorderColor, #374151);
        background: var(--ReactionGlobalBg, #111827);
        overflow-x: auto;
        flex-shrink: 0;
    `;

        this.packs.forEach((pack) => {
            const btn = document.createElement("button");
            btn.type = "button";
            btn.style.cssText = `
            flex-shrink: 0;
            width: 2rem;
            height: 2rem;
            border-radius: 0.25rem;
            border: 0.125rem solid ${this.currentPackId === pack.id ? "var(--color-4, #3b82f6)" : "transparent"};
            background: var(--ReactionBtnBg, #374151);
            cursor: pointer;
            padding: 0.125rem;
            transition: border-color 0.15s, transform 0.1s;
            display: flex;
            align-items: center;
            justify-content: center;
        `;
            btn.innerHTML = `<img src="/storage/${pack.preview_img}" alt="${pack.name}" style="width:100%;height:100%;object-fit:cover;border-radius:0.125rem;">`;
            btn.title = pack.name;

            btn.onmouseenter = () => (btn.style.transform = "scale(1.05)");
            btn.onmouseleave = () => (btn.style.transform = "scale(1)");

            btn.onclick = (e) => {
                e.stopPropagation();
                this.loadPack(pack.id);
                Array.from(header.children).forEach(
                    (c) => (c.style.borderColor = "transparent"),
                );
                btn.style.borderColor = "var(--color-4, #3b82f6)";
            };
            header.appendChild(btn);
        });

        // ── Контейнер реакций с гридом ──
        const reactionsWrapper = document.createElement("div");
        reactionsWrapper.style.cssText = `
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
    `;

        const reactionsGrid = document.createElement("div");
        reactionsGrid.style.cssText = `
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.375rem;
        padding: 0.5rem;
        align-items: center;
        justify-items: center;
    `;

        this.renderReactions(reactionsGrid);

        reactionsWrapper.appendChild(reactionsGrid);
        picker.appendChild(header);
        picker.appendChild(reactionsWrapper);

        return picker;
    }

    /**
     * Проверяет, является ли путь видеофайлом
     */
    isVideoPath(path) {
        if (!path) return false;
        const lower = path.toLowerCase();
        return lower.endsWith('.webm') || lower.endsWith('.mp4') || lower.endsWith('.mov');
    }

    /**
     * Рендерит сетку реакций + Lottie анимации + Видео
     */
    async renderReactions(container) {
        container.innerHTML = "";
        this.dropdownLotties = this.dropdownLotties || [];

        for (const el of this.currentElements) {
            const btn = document.createElement("button");
            btn.type = "button";

            // ✅ Фиксированный размер кнопки в rem + aspect-ratio
            btn.style.cssText = `
            width: 4.4rem;
            height: 4.4rem;
            aspect-ratio: 1.6 / 1;
            border: none;
            border-radius: 0.375rem;
            background: transparent;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.25rem;
            transition: background 0.15s, transform 0.1s;
            position: relative;
            overflow: hidden;
            flex-shrink: 0;
        `;

            btn.onmouseenter = () => {
                btn.style.background = "var(--ReactionGlobalBgHover, #374151)";
                btn.style.transform = "scale(1.02)";
            };
            btn.onmouseleave = () => {
                btn.style.background = "transparent";
                btn.style.transform = "scale(1)";
            };

            if (el.sticker === 1) {
                // 🔹 Lottie контейнер
                const lottieContainer = document.createElement("div");
                lottieContainer.className = "PickerLottie";
                lottieContainer.style.cssText = `
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
            `;
                btn.appendChild(lottieContainer);
                btn.title = `${el.label} (анимация)`;

                this.renderPickerLottie(lottieContainer, el.path);
            } else if (this.isVideoPath(el.path)) {
                // 🔹 Видео (webm, mp4 и т.д.)
                const video = document.createElement("video");
                video.className = "PickerReaction__media";
                video.src = el.path;
                video.autoplay = true;
                video.loop = true;
                video.muted = true;
                video.playsInline = true;
                video.style.cssText = `
                width: 100%;
                height: 100%;
                object-fit: contain;
                object-position: center;
                display: block;
                pointer-events: none;
            `;
                btn.appendChild(video);
                btn.title = el.label;
            } else {
                // 🔹 Картинка — с object-fit: contain
                const img = document.createElement("img");
                img.className = "PickerReaction__media";
                img.src = el.path;
                img.alt = el.label || "";
                img.style.cssText = `
                width: 100%;
                height: 100%;
                object-fit: contain;
                object-position: center;
                display: block;
                pointer-events: none;
            `;
                btn.appendChild(img);
                btn.title = el.label;
            }

            btn.onclick = (e) => {
                e.stopPropagation();
                this.selectReaction(el);
            };

            container.appendChild(btn);
        }
    }

    /**
     * Рендерит Lottie в кнопке выбора (без блокировки)
     */
    async renderPickerLottie(container, path) {
        try {
            await new Promise((resolve) => setTimeout(resolve, 30));

            const LottieLib = window.Lottie || window.lottie;
            if (!LottieLib || !container.isConnected) return;

            const anim = LottieLib.loadAnimation({
                container,
                renderer: "svg",
                loop: true,
                autoplay: true,
                path: path,
                rendererSettings: {
                    preserveAspectRatio: "xMidYMid meet",
                    progressiveLoad: false,
                    hideOnTransparent: true,
                },
            });

            container._pickerLottie = anim;
            this.dropdownLotties.push(anim);
        } catch (e) {
            console.warn("Picker Lottie error:", e);
            container.textContent = "🎬";
            container.style.display = "flex";
            container.style.alignItems = "center";
            container.style.justifyContent = "center";
            container.style.fontSize = "1.5rem";
        }
    }

    /**
     * Очищает все Lottie в дропдауне
     */
    cleanupPickerLotties() {
        if (this.dropdownLotties) {
            this.dropdownLotties.forEach((anim) => anim?.destroy());
            this.dropdownLotties = [];
        }
    }

    /**
     * Загружает элементы пака
     */
    async loadPack(packId) {
        if (this.currentPackId === packId) return;

        try {
            const packData = await loadPackIfNeeded(packId);
            this.currentPackId = packId;
            this.currentElements = packData.elements;

            // Обновляем кэш всех элементов
            packData.elements.forEach((el) => {
                this.allElementsById[el.id] = el;
            });

            // Перерисовываем грид
            const grid = this.picker?.querySelector(
                'div[style*="grid-template-columns"]',
            );
            if (grid) this.renderReactions(grid);
        } catch (e) {
            console.error("Ошибка загрузки пака:", e);
        }
    }

    /**
     * Позиционирует попап рядом с выделением
     */
    positionPicker() {
        if (!this.lastRange || !this.picker) return;

        const rect = this.lastRange.getBoundingClientRect();
        const pickerRect = this.picker.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;

        // По умолчанию — снизу от выделения
        let top = rect.bottom + window.scrollY + 65;
        let left = rect.left + window.scrollX;

        // Если не влезает снизу — показываем сверху
        if (top + pickerRect.height > viewportHeight + window.scrollY) {
            top = rect.top + window.scrollY - pickerRect.height - 8;
        }

        // Если вылезает справа — прижимаем к правому краю
        if (left + pickerRect.width > viewportWidth + window.scrollX - 16) {
            left = viewportWidth + window.scrollX - pickerRect.width - 16;
        }

        // Не даём уйти за левый край
        left = Math.max(8, left);

        this.picker.style.top = `${top}px`;
        this.picker.style.left = `${left}px`;
    }

    /**
     * Обработчик клика вне попапа
     */
    handleOutsideClick = (e) => {
        if (
            this.picker &&
            !this.picker.contains(e.target) &&
            e.target !== this.button
        ) {
            this.hidePicker();
        }
    };

    /**
     * Закрытие по Escape
     */
    handleEscape = (e) => {
        if (e.key === "Escape") {
            this.hidePicker();
        }
    };

    /**
     * Скрывает попап
     */
    hidePicker() {
        if (this.picker) {
            document.removeEventListener("click", this.handleOutsideClick);
            document.removeEventListener("keydown", this.handleEscape);

            // 🔥 Очищаем Lottie в дропдауне
            this.cleanupPickerLotties();

            // 🔥 Останавливаем видео в дропдауне
            this.picker.querySelectorAll('video').forEach(v => {
                v.pause();
                v.removeAttribute('src');
                v.load();
            });

            this.picker.remove();
            this.picker = null;
        }
    }

    /**
     * Выбирает реакцию и вставляет в редактор
     */
    selectReaction(reaction) {
        if (!this.lastRange) return;

        const sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(this.lastRange);
        this.lastRange.deleteContents();

        const reactionSpan = this.createReactionSpan(reaction);
        this.lastRange.insertNode(reactionSpan);

        // ✅ Курсор после реакции
        this.lastRange.setStartAfter(reactionSpan);
        this.lastRange.setEndAfter(reactionSpan);
        sel.removeAllRanges();
        sel.addRange(this.lastRange);

        // ✅ Lottie инициализируем ТОЛЬКО после вставки в DOM
        if (reaction.sticker === 1) {
            // Используем microtask чтобы гарантировать, что элемент в DOM
            Promise.resolve().then(() => {
                this.initLottieInSpan(reactionSpan, reaction);
            });
        }

        this.hidePicker();
        this.lastRange = null;
    }

    /**
     * Определяет тип реакции по данным
     */
    getReactionType(reaction) {
        if (reaction.sticker === 1) return "lottie";
        if (this.isVideoPath(reaction.path)) return "video";
        return "image";
    }

    /**
     * Создаёт span с реакцией — УПРОЩЁННАЯ СТРУКТУРА
     */
    createReactionSpan(reaction) {
        const span = document.createElement("span");
        const type = this.getReactionType(reaction);

        // ✅ Используем classList.add вместо className =
        span.classList.add("InlineRender");
        span.contentEditable = "false";
        span.dataset.reactionId = reaction.id;
        span.dataset.reactionType = type;
        span.dataset.reactionPath = reaction.path;

        if (type === "lottie") {
            // 🔹 Lottie: плейсхолдер + атрибуты
            span.textContent = "🎬";
            span.title = reaction.label || "Анимация";
        } else if (type === "video") {
            // 🔹 Видео
            const video = document.createElement("video");
            video.classList.add("InlineRender__media");
            video.src = reaction.path;
            video.autoplay = true;
            video.loop = true;
            video.muted = true;
            video.playsInline = true;
            video.draggable = false;
            span.appendChild(video);
            span.title = reaction.label || "Видео";
        } else {
            // 🔹 Картинка
            const img = document.createElement("img");
            img.classList.add("InlineRender__media");
            img.src = reaction.path;
            img.alt = reaction.label || "";
            img.draggable = false;
            img.loading = "lazy";
            span.appendChild(img);
        }

        return span;
    }

    /**
     * Инициализация Lottie — теперь на самом span
     */
    async initLottieInSpan(span, reaction) {
        await new Promise((resolve) => setTimeout(resolve, 50));

        try {
            const LottieLib = this.lottieLib || window.Lottie || window.lottie;
            if (!LottieLib) return;

            // ✅ Уничтожаем старую анимацию
            if (span._lottieAnim) {
                span._lottieAnim.destroy();
                delete span._lottieAnim;
            }

            // Очищаем плейсхолдер
            span.textContent = "";

            const anim = LottieLib.loadAnimation({
                container: span, // ✅ Контейнер — сам span
                renderer: "svg",
                loop: true,
                autoplay: true,
                path: reaction.path,
                rendererSettings: {
                    preserveAspectRatio: "xMidYMid meet",
                    progressiveLoad: false,
                    hideOnTransparent: true,
                },
            });

            span._lottieAnim = anim;
        } catch (e) {
            console.warn("Lottie init error:", e);
            // Fallback: показываем эмодзи если не вышло
            span.textContent = "🎬";
        }
    }

    /**
     * Проверяет, находится ли выделение внутри реакции
     */
    checkState(selection) {
        if (!selection || selection.rangeCount === 0) return false;

        const range = selection.getRangeAt(0);
        let node = range.commonAncestorContainer;
        if (node.nodeType === Node.TEXT_NODE) node = node.parentElement;

        while (node && node !== document) {
            if (node.dataset?.reactionId) {
                return true;
            }
            node = node.parentElement;
        }
        return false;
    }

    /**
     * ✅ Sanitize — разрешаем атрибуты правильно
     */
    static get sanitize() {
        return {
            span: {
                // Разрешаем class, но проверяем что есть наш класс
                class: (value) => value && value.includes("InlineRender"),
                contenteditable: (value) => value === "false",
                // data-* атрибуты — просто true, Editor.js пропустит
                "data-reaction-id": true,
                "data-reaction-type": true,
                "data-reaction-path": true,
            },
            img: {
                class: (value) =>
                    value && value.includes("InlineRender__media"),
                src: true,
                alt: true,
                draggable: (value) => value === "false",
                loading: (value) => value === "lazy",
            },
            video: {
                class: (value) =>
                    value && value.includes("InlineRender__media"),
                src: true,
                autoplay: (value) => value === "true" || value === "",
                loop: (value) => value === "true" || value === "",
                muted: (value) => value === "true" || value === "",
                playsinline: (value) => value === "true" || value === "",
                draggable: (value) => value === "false",
            },
        };
    }

    /**
     * Очистка
     */
    destroy() {
        this.hidePicker();
        // 🔥 Очищаем анимации в редакторе (если остались)
        document
            .querySelectorAll(
                '[class*="InlineRender"][data-reaction-type="lottie"]',
            )
            .forEach((span) => {
                if (span._lottieAnim) {
                    span._lottieAnim.destroy();
                    delete span._lottieAnim;
                }
            });
        
        // 🔥 Останавливаем видео в редакторе
        document
            .querySelectorAll(
                '[class*="InlineRender"][data-reaction-type="video"] video',
            )
            .forEach((video) => {
                video.pause();
                video.removeAttribute('src');
                video.load();
            });
    }
}