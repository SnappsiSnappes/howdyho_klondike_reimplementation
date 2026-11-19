// resources/js/EditorJsTools/CustomColorPicker.js

export default class CustomColorPicker {
    static get isInline() {
        return true;
    }

    static get title() {
        return "Text Color";
    }

    constructor({ api, config = {} }) {
        this.api = api;
        this.colors = config.colors || [
            "color-1",
            "color-2",
            "color-3",
            "color-4",
        ];
        this.columns = config.columns || 4;

        this.button = null;
        this.picker = null;
        this.lastRange = null;
    }

    render() {
        this.button = document.createElement("button");
        this.button.type = "button";
        this.button.innerHTML = `
      <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M13.75 1.75H0.25V12.25H13.75V1.75Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M3.75 7.875L7 11.375L10.25 7.875" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M7 11.375V2.625" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    `;
        this.button.classList.add(this.api.styles.inlineToolButton);
        return this.button;
    }

    surround(range) {
        if (!range || range.toString().trim() === "") {
            return;
        }

        this.lastRange = range;
        this.showPicker();
    }

    showPicker() {
        if (this.picker) {
            this.hidePicker();
        }

        this.picker = this.createPicker();
        document.body.appendChild(this.picker);

        const rect = this.lastRange.getBoundingClientRect();
        this.picker.style.position = "absolute";
        this.picker.style.left = `${rect.left + window.scrollX}px`;
        this.picker.style.top = `${rect.bottom + window.scrollY + 5}px`;
        this.picker.style.zIndex = "10000";

        setTimeout(() => {
            document.addEventListener("click", this.handleOutsideClick);
        }, 0);
    }



    createPicker() {
        const picker = document.createElement("div");
        picker.classList.add('CustomColorPicker')
        picker.style.cssText = `
      background-color: var(--ui-bg-base);
      border: 0.06rem solid var(--ui-border);
      border-radius: 0.4rem;
      padding: 1rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.15);
      display: grid;
      gap: 0.6rem;
      grid-template-columns: repeat(${this.columns}, 1fr);

    `;

        this.colors.forEach((colorName) => {
            const colorBtn = document.createElement("button");
            colorBtn.type = "button";
            colorBtn.style.cssText = `
        width: 2.4rem;
        height: 2.4rem;
        border: none;
        border-radius: 1rem;
        cursor: pointer;
        background: var(--${colorName}, #999);
      `;
            colorBtn.title = colorName;

            colorBtn.addEventListener("click", (e) => {
                e.stopPropagation();
                this.applyColor(colorName);
                this.hidePicker();
            });

            picker.appendChild(colorBtn);
        });

        return picker;
    }

    /**
     * Находит ближайший родительский элемент с data-color в пределах текущего блока
     * @param {Range} range
     * @returns {HTMLElement|null}
     */
    findClosestColorSpan(range) {
        let el = range.commonAncestorContainer;

        // Перейти к элементу, если выделение — текст
        if (el.nodeType === Node.TEXT_NODE) {
            el = el.parentElement;
        }

        // Идти вверх, пока не найдём span[data-color] или не выйдем из contenteditable
        while (el && el.nodeType === Node.ELEMENT_NODE) {
            if (el.contentEditable === "false") break; // защита от выхода из редактора
            if (el.tagName === "SPAN" && el.dataset.color !== undefined) {
                return el;
            }
            el = el.parentElement;
        }

        return null;
    }

applyColor(colorName) {
    if (!this.lastRange) return;

    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(this.lastRange);

    const selectedText = selection.toString().trim();
    if (!selectedText) return;

    // Попробуем найти существующий data-color спан
    const existingColorSpan = this.findClosestColorSpan(this.lastRange);

    if (existingColorSpan) {
        // Просто меняем цвет
        existingColorSpan.dataset.color = colorName;
    } else {
        // Создаём новый спан
        const span = document.createElement("span");
        span.dataset.color = colorName;
        span.textContent = selectedText;

        const range = this.lastRange.cloneRange();
        range.deleteContents();
        range.insertNode(span);
    }

    selection.removeAllRanges();
    this.lastRange = null;

    // 🔥 Удаляем все пустые span[data-color] в текущем блоке
    this.cleanupEmptyColorSpans();
}

/**
 * Удаляет все пустые <span data-color> в пределах текущего блока (contenteditable)
 */
cleanupEmptyColorSpans() {
    // Найдём текущий блок (родительский contenteditable)
    const currentBlock = document.activeElement?.closest?.('[contenteditable="true"]');
    if (!currentBlock) return;

    // Находим все span с data-color внутри блока
    const colorSpans = currentBlock.querySelectorAll('span[data-color]');

    colorSpans.forEach(span => {
        // Проверяем, пустой ли спан: нет текста и нет дочерних непустых элементов
        if (span.textContent.trim() === '' && !Array.from(span.children).some(child => child.textContent.trim() !== '')) {
            // Заменяем спан на его содержимое (ничего, если пустой)
            const parent = span.parentNode;
            while (span.firstChild) {
                parent.insertBefore(span.firstChild, span);
            }
            parent.removeChild(span);
        }
    });
}

    handleOutsideClick = (e) => {
        if (
            this.picker &&
            !this.picker.contains(e.target) &&
            e.target !== this.button
        ) {
            this.hidePicker();
        }
    };

    hidePicker() {
        if (this.picker) {
            document.removeEventListener("click", this.handleOutsideClick);
            this.picker.remove();
            this.picker = null;
        }
    }

    checkState(range) {
        try {
            if (!range || !range.commonAncestorContainer) {
                return false;
            }

            const container = range.commonAncestorContainer;
            let parent =
                container.nodeType === Node.TEXT_NODE
                    ? container.parentElement
                    : container;

            if (!parent) return false;

            while (parent && parent.nodeType === Node.ELEMENT_NODE) {
                if (
                    parent instanceof HTMLElement &&
                    parent.tagName === "SPAN" &&
                    parent.dataset.color
                ) {
                    return this.colors.includes(parent.dataset.color);
                }
                parent = parent.parentElement;
            }
        } catch (e) {
            console.warn("ColorPicker checkState error:", e);
        }
        return false;
    }

    static get sanitize() {
        return {
            span: {
                "data-color": (value) => {
                    if (typeof value !== "string") return false;
                    return /^color-\d+$/.test(value); // или просто this.colors.includes(value), но статически
                },
            },
        };
    }

    destroy() {
        this.hidePicker();
    }
}
