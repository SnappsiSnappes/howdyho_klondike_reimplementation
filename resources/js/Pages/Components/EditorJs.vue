<script setup>
import { onMounted, onUnmounted, ref, nextTick } from 'vue';
import EditorJS from '@editorjs/editorjs';
import Header from '@editorjs/header';
import List from '@editorjs/list';
import EditorjsList from '@editorjs/list';
import Quote from '@editorjs/quote';
import ImageTool from '@editorjs/image';

// import CodeTool from '@editorjs/code';
import CustomCodeTool from '../../EditorJsTools/CustomCodeTool';
import CustomReactionInlineTool from '../../EditorJsTools/CustomReactionInlineTool';

import Table from '@editorjs/table';
import Delimiter from '@editorjs/delimiter';
import Embed from '@editorjs/embed';
import axios from 'axios';

// import Paragraph from 'editorjs-paragraph-with-alignment';
// import Paragraph from '@editorjs/paragraph';
import Paragraph from '../../EditorJsTools/CustomParagraph';

import { usePage } from '@inertiajs/vue3';
import Underline from '@editorjs/underline';
import Warning from '@editorjs/warning';
import Marker from '@editorjs/marker';
import TextVariantTune from '@editorjs/text-variant-tune';

import Annotation from 'editorjs-annotation';

import AttachesTool from '@editorjs/attaches';
import Sortable from 'sortablejs';
import ImageGallery from '@kiberpro/editorjs-gallery';

import ColorPicker from 'editorjs-color-picker';
import CustomColorPicker from '../../EditorJsTools/CustomColorPicker';
import SuccessTool from '../../EditorJsTools/SuccessTool';
import Lottie from 'lottie-web';


// Доступ к данным страницы
const page = usePage();

const props = defineProps({
    initialData: {
        type: Object,
        // конвенция vue 3
        default: () => ({}),
    },
    DivId: {
        type: String,
        default: () => ({})
    }
});

console.log(props.DivId)

let editor = ref(null);

const container = document.getElementsByClassName('editor-container');


/**
 * 🎬 Рендерит Lottie — с повторными попытками если контент ещё не готов
 */
const renderInlineLotties = async (holderId, retries = 3) => {
    const holder = document.getElementById(holderId);
    if (!holder) {
        // console.warn('❌ Holder not found:', holderId);
        return;
    }

    const lottieSpans = holder.querySelectorAll('.InlineRender[data-reaction-type="lottie"]');

    // console.log(`🔍 Found ${lottieSpans.length} Lottie spans in ${holderId}`);

    for (const span of lottieSpans) {
        // Пропускаем если уже есть SVG
        if (span.querySelector('svg')) {
            // console.log('✅ Already rendered:', span.dataset.reactionPath);
            continue;
        }

        const path = span.dataset.reactionPath;
        if (!path) {
            // console.warn('⚠️ No path for span:', span);
            continue;
        }

        // 🔥 Важно: если span пустой — даём ему минимальный размер через стиль
        if (!span.textContent?.trim() && !span.children.length) {
            span.textContent = '🎬'; // fallback placeholder
            // console.log('🔧 Added placeholder to empty span');
        }

        try {
            // Ждём немного чтобы элемент точно был в DOM и имел размеры
            await new Promise(resolve => setTimeout(resolve, 100));

            // Проверяем что элемент видим и имеет размеры
            const rect = span.getBoundingClientRect();
            if (rect.width === 0 || rect.height === 0) {
                // console.warn('⚠️ Span has 0 size, skipping:', path);
                continue;
            }

            // Очищаем плейсхолдер только после успешной проверки
            span.textContent = '';

            const anim = Lottie.loadAnimation({
                container: span,
                renderer: 'svg',
                loop: true,
                autoplay: true,
                path: path,
                rendererSettings: {
                    preserveAspectRatio: 'xMidYMid meet',
                    progressiveLoad: false,
                    hideOnTransparent: true
                }
            });

            span._lottieAnim = anim;
            // console.log('✅ Lottie loaded:', path);

        } catch (e) {
            // console.warn('❌ Lottie error:', path, e);
            span.textContent = '🎬'; // fallback
        }
    }
};

/**
 * 🔥 Очистка анимаций
 */
const cleanupLotties = (holderId) => {
    const holder = document.getElementById(holderId);
    if (!holder) return;

    holder.querySelectorAll('.InlineRender[data-reaction-type="lottie"]').forEach(span => {
        if (span._lottieAnim) {
            span._lottieAnim.destroy();
            delete span._lottieAnim;
        }
    });
};




onMounted(() => {
    editor.value = new EditorJS({
        holder: props.DivId,
        tools: {
            gallery: {
                class: ImageGallery,
                config: {
                    sortableJs: Sortable,
                    endpoints: {
                        byFile: '/upload-image',
                    },
                    additionalRequestHeaders: {
                        'X-CSRF-TOKEN': page.props.csrf_token,
                    },
                },
            },

            textVariant: TextVariantTune,

            Marker: {
                class: Marker,
            },

            warning: Warning,

            success: SuccessTool,

            code: CustomCodeTool,

            underline: Underline,

            annotation: Annotation,

            paragraph: {
                class: Paragraph,
                inlineToolbar: ['annotation', 'ColorPicker', 'underline', 'Marker', 'bold', 'link', 'ReactionInline'],
                config: {
                    sanitizer: false,
                }
            },

            // 👇 Регистрируем сам инструмент
            ReactionInline: {
                class: CustomReactionInlineTool,
                // Можно передать конфиг, если нужно
                config: {}
            },

            ColorPicker: {
                class: CustomColorPicker,
                config: {
                    colors: ['color-1', 'color-2', 'color-3', 'color-4'],
                    columns: 4,
                },
            },

            header: {
                class: Header,
                inlineToolbar: ['annotation', 'ColorPicker', 'underline', 'Marker', 'bold', 'link', 'ReactionInline'],
            },
            
            List: {
                class: EditorjsList,
                inlineToolbar: ['annotation', 'ColorPicker', 'underline', 'Marker', 'bold', 'link', 'ReactionInline'],

                config: {
                    defaultStyle: 'unordered'
                },
            },



            quote: Quote,
            image: {
                class: ImageTool,
                config: {
                    features: {
                        border: false,
                        caption: 'optional',
                        stretch: false,
                        withBackground: false
                    },
                    endpoints: {
                        byFile: '/upload-image',
                    },
                    additionalRequestHeaders: {
                        'X-CSRF-TOKEN': page.props.csrf_token,
                    },
                },
            },
            table: Table,
            delimiter: Delimiter,
            embed: Embed,
            attaches: {
                class: AttachesTool,
                config: {
                    endpoint: '/upload-file',
                    additionalRequestHeaders: {
                        'X-CSRF-TOKEN': page.props.csrf_token,
                    },
                }
            },
        },
        tunes: ['textVariant'],

        sanitizer: false,

        data: props.initialData,
        onReady: async () => {
            await nextTick(); // ✅ Исправленная опечатка
            await renderInlineLotties(props.DivId);
        }


    });

    // 🔄 Дополнительная страховка: если onReady не сработал, пробуем через таймаут
    setTimeout(() => {
        renderInlineLotties(props.DivId);
    }, 500);


});

onUnmounted(() => {
    cleanupLotties(props.DivId);

    if (editor.value) {
        editor.value.destroy();
    }
});



// Функция нормализации данных (заменяем &nbsp; на пробел)
const normalizeNbsp = (obj) => {
    if (typeof obj === 'string') {
        return obj.replace(/&nbsp;/g, ' ');
    }
    if (Array.isArray(obj)) {
        return obj.map(item => normalizeNbsp(item));
    }
    if (typeof obj === 'object' && obj !== null) {
        const normalized = {};
        for (const key in obj) {
            normalized[key] = normalizeNbsp(obj[key]);
        }
        return normalized;
    }
    return obj;
};

// Метод для сохранения данных из редактора
const save = async () => {
    try {
        const editorData = await editor.value.save();
        // Нормализуем все строки в данных
        const normalizedData = normalizeNbsp(editorData);
        console.log(normalizedData);
        return normalizedData; // ✅ ВОЗВРАЩАЕМ очищенные данные!
    } catch (error) {
        console.error('Error saving editor data:', error);
        throw error; // чтобы родитель знал об ошибке
    }
};

// используется родителем
defineExpose({ save });

console.log(props.initialData)
</script>

<template>
    <div :id="props.DivId" class="editor-container mt-4"></div>
</template>