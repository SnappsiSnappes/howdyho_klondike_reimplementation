// resources/js/EditorJsTools/CustomParagraph.js
import Paragraph from 'editorjs-paragraph-with-alignment';

export default class CustomParagraph extends Paragraph {
    static get sanitize() {
        return {
            text: {
                br: true,
                class: true,
                span: {
                    class: true,
                    // Аннотации (твои старые)
                    "data-color": true,
                    "data-title": true,
                    "data-text": true,
                    // 🔥 Реакции (новые)
                    "data-reaction-id": true,
                    "data-reaction-type": true,
                    "data-reaction-path": true,
                    contenteditable: (val) => val === 'false',
                },
                // 🔥 Разрешаем img внутри span для картинок-реакций
                img: {
                    class: true,
                    src: true,
                    alt: true,
                    draggable: true,
                    loading: true,
                }
            },
        };
    }
}