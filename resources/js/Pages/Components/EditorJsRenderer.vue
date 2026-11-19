<script setup>
import { computed, onMounted, nextTick, ref, onUnmounted, watch } from "vue";
// Импортируйте свои компоненты по необходимости
import Gallery from "./ArticleBlocks/Gallery.vue";
import Image from "./ArticleBlocks/Image.vue";
import List from "./ArticleBlocks/List.vue";
import File from "./ArticleBlocks/File.vue";
import Code from "./ArticleBlocks/Code.vue";

const props = defineProps({
  // Принимает либо строку JSON, либо уже распарсенный объект с blocks
  content: {
    type: [String, Object],
    required: true,
  },
  placement: {
    type: String,
    required: false,
    default: 'DEFAULT', // значение по умолчанию
    validator(value) {
      // Допустимые значения
      const allowedPlacements = ['DEFAULT', 'CARD', 'INSIDE_POST'];
      return allowedPlacements.includes(value);
    }
  }
});

// console.log(props.content)


const renderInlineLotties = async (container) => {
    if (!container) return;
    
    const lottieSpans = container.querySelectorAll('.InlineRender[data-reaction-type="lottie"]');
    
    for (const span of lottieSpans) {
        // Пропускаем если уже есть SVG (уже зарендерено)
        if (span.querySelector('svg')) continue;
        
        const path = span.dataset.reactionPath;
        if (!path) continue;
        
        try {
            // Очищаем плейсхолдер (эмодзи)
            span.textContent = '';
            
            // Рендерим Lottie
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
            
            // Сохраняем ссылку для очистки
            span._lottieAnim = anim;
        } catch (e) {
            console.warn('Lottie render error:', e);
            // Fallback: оставляем эмодзи
            span.textContent = '🎬';
        }
    }
};

// 🎬 Рендерим видео реакции
const renderInlineVideos = async (container) => {
    if (!container) return;
    
    const videoSpans = container.querySelectorAll('.InlineRender[data-reaction-type="video"]');
    
    for (const span of videoSpans) {
        const video = span.querySelector('video');
        if (!video) continue;
        
        // Убеждаемся что видео muted (иначе autoplay заблокируется)
        video.muted = true;
        
        // Ждём когда видео будет готово
        const tryPlay = () => {
            if (video.readyState >= 2) { // HAVE_CURRENT_DATA
                video.play().catch(e => {
                    console.warn('Video play failed:', e);
                });
            } else {
                video.addEventListener('loadeddata', () => {
                    video.play().catch(e => {
                        console.warn('Video play failed:', e);
                    });
                }, { once: true });
            }
        };
        
        // Небольшая задержка чтобы DOM полностью обновился
        setTimeout(tryPlay, 50);
    }
};

// 🔥 Очистка анимаций при размонтировании
const cleanupLotties = (container) => {
    if (!container) return;
    container.querySelectorAll('.InlineRender[data-reaction-type="lottie"]').forEach(span => {
        if (span._lottieAnim) {
            span._lottieAnim.destroy();
            delete span._lottieAnim;
        }
    });
};

// 🔥 Очистка видео при размонтировании
const cleanupVideos = (container) => {
    if (!container) return;
    container.querySelectorAll('.InlineRender[data-reaction-type="video"] video').forEach(video => {
        video.pause();
        video.removeAttribute('src');
        video.load();
    });
};


const paragraphColorClass = computed(() => {
  switch (props.placement) {
    case 'CARD':
      return 'text-[--PostCardColor] paragraph-card';
    case 'INSIDE_POST':
      return 'text-[--PostTextColor] paragraph-inside-post';
    default:
      return 'text-gray-200 dark:text-gray-300 paragraph-default break-words';
  }
});

// Парсим контент безопасно
const parsedContent = computed(() => {
  if (typeof props.content === "string") {
    try {
      return JSON.parse(props.content);
    } catch (error) {
      console.error("Invalid JSON in EditorJsRenderer:", error);
      return { blocks: [] };
    }
  } else if (typeof props.content === "object" && props.content !== null) {
    return props.content;
  } else {
    return { blocks: [] };
  }
});

const alignmentClass = (block) => {
  switch (block.data?.alignment) {
    case "center": return "text-center";
    case "right": return "text-right";
    default: return "text-left";
  }
};

// Popover state
const popover = ref({
  visible: false,
  title: "",
  description: "",
  x: 0,
  y: 0,
});

const showPopover = (e, title, description) => {
  const x = e.clientX + 10;
  const y = e.clientY - 90;

  // Защита от выхода за границы экрана
  const popoverWidth = 300;
  const popoverHeight = 100;
  const maxX = window.innerWidth - popoverWidth - 10;
  const maxY = window.innerHeight - popoverHeight - 10;

  const safeX = Math.max(5, Math.min(x, maxX));
  const safeY = Math.max(5, Math.min(y, maxY));

  popover.value = {
    visible: true,
    title: title || "Аннотация",
    description: description || "",
    x: safeX,
    y: safeY,
  };
};

const hidePopover = () => {
  popover.value.visible = false;
};

// 🧠 Умная обработка аннотаций
let annotationHandlers = [];

const attachAnnotationListeners = () => {
  const annotations = document.body.querySelectorAll(".cdx-annotation");
  annotationHandlers = []; // сброс при повторном вызове


  annotations.forEach(el => {
    const title = el.getAttribute("data-title") || "";
    const desc = el.getAttribute("data-text") || "";

    const enter = (e) => showPopover(e, title, desc);
    const leave = () => hidePopover();

    el.addEventListener("mouseenter", enter);
    el.addEventListener("mouseleave", leave);

    annotationHandlers.push({ el, enter, leave });
  });
};


const cleanupAnnotationListeners = () => {
  annotationHandlers.forEach(({ el, enter, leave }) => {
    el.removeEventListener("mouseenter", enter);
    el.removeEventListener("mouseleave", leave);
  });
  annotationHandlers = [];
};

onMounted(async () => {
    await nextTick();
    
    const container = document.getElementById('editorjs-renderer');
    
    // Рендерим аннотации (твой код)
    attachAnnotationListeners();
    
    // 🎬 Рендерим Lottie реакции
    renderInlineLotties(container);
    
    // 🎥 Рендерим видео реакции
    renderInlineVideos(container);
});

onUnmounted(() => {
    cleanupAnnotationListeners();
    
    const container = document.getElementById('editorjs-renderer');
    
    // 🔥 Очищаем Lottie анимации
    cleanupLotties(container);
    
    // 🔥 Очищаем видео
    cleanupVideos(container);
});

// 🔁 Если контент динамический — перерендер при изменении
watch(() => props.content, async () => {
    await nextTick();
    const container = document.getElementById('editorjs-renderer');
    
    // Сначала очищаем старые
    cleanupLotties(container);
    cleanupVideos(container);
    
    // Потом рендерим новые
    renderInlineLotties(container);
    renderInlineVideos(container);
    
    attachAnnotationListeners();
}, { deep: true });
</script>

<template>

  <div id="editorjs-renderer" class="w-full px-5 sm:px-2">
    <div v-for="(block, index) in parsedContent.blocks" :key="index" :class="['editor-block', alignmentClass(block)]">
      <!-- Paragraph -->
      <div v-if="block.type === 'paragraph'" :class="paragraphColorClass" v-html="block.data.text"></div>

      <!-- Header-->
      <div v-else-if="block.type === 'header'" :class="`header-${block.data.level} text-[var(--ui-heading)]`" v-html="block.data.text"></div>

      <!-- Code -->
      <div v-else-if="block.type === 'code'">
        <Code :blockData="block.data" />
      </div>

      <!-- List -->
      <div v-else-if="block.type === 'List'">
        <List :BlockData="block.data" />
      </div>

      <!-- Image -->
      <div v-else-if="block.type === 'image'">
        <Image :ImageUrl="block.data.file.url" :caption="block.data.caption" :Width="block.data.file.width"
          :Height="block.data.file.height" />
      </div>

      <!-- Gallery -->
      <div v-else-if="block.type === 'gallery'">
        <Gallery :files="block.data.files" :caption="block.data.caption" />
      </div>

      <!-- File (attaches) -->
      <div v-else-if="block.type === 'attaches'">
        <File :file="block.data.file" />
      </div>

      <!-- Delimiter -->
      <div v-else-if="block.type === 'delimiter'" class=" my-8 flex items-center justify-center">
        <div
          class="flex-grow border-t border-transparent bg-gradient-to-r from-[var(--primary-from)] to-[var(--primary-to)] h-0.5">
        </div>
        <div
          class="flex-grow border-t border-transparent bg-gradient-to-l from-[var(--primary-from)] to-[var(--primary-to)] h-0.5">
        </div>
      </div>

      <!-- Warning -->
      <div v-else-if="block.type === 'warning'"
        class="border-l-[1rem]  p-4 my-4 rounded-lg bg-[var(--ui-bg-base)] border-[var(--tertiary)]">
        <svg xmlns="http://www.w3.org/2000/svg" class="absolute w-[2.4rem] h-[2.4rem] text-[var(--tertiary)]"
          fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <strong v-if="block.data.title" class=" font-bold mb-2 pl-16 block text-[var(--tertiary)] ">{{ block.data.title
          }}</strong>
        <p class="break-words pl-16 mt-1 text-[var(--TextColor)]">{{ block.data.message }}</p>
      </div>

      <!-- Success -->
      <div v-else-if="block.type === 'success'"
        class="border-l-[1rem] p-4 my-4 rounded-lg bg-[var(--ui-bg-base)] border-[var(--secondary)] relative">
        <!-- Классы стилей -->
        <svg xmlns="http://www.w3.org/2000/svg" class="absolute w-[3rem] h-[3rem] text-[var(--secondary)] " fill="none"
          viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <strong v-if="block.data.title" class="font-bold mb-2 pl-16 block  text-[var(--secondary)]">{{ block.data.title
        }}</strong>
        <p class="break-words pl-16 mt-1 text-[var(--TextColor)]">{{ block.data.message }}</p>
      </div>

      <!-- Quote -->
      <div v-else-if="block.type === 'quote'"
        class=" relative bg-[var(--ui-bg-base)] border-l-[1rem] border-[var(--info-light)] p-6 rounded-lg my-4">
        <svg xmlns="http://www.w3.org/2000/svg"
          class="absolute top-4 left-4 w-[2rem] h-[2rem] text-[var(--info-light)] " fill="none" viewBox="0 0 24 24"
          stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
        <p class="break-words text-[var(--TextColor)] pl-16">{{ block.data.text }}</p>
        <footer v-if="block.data.caption" class="text-sm text-[var(--info)] mt-2 pl-16">
          — {{ block.data.caption }}
        </footer>
      </div>

      <!-- Embed -->
      <div v-else-if="block.type === 'embed'" class="embed-block my-6">
        <iframe :src="block.data.embed" frameborder="0" allowfullscreen
          class="w-full max-w-2xl h-auto aspect-video rounded-lg shadow-lg mx-auto bg-[var(--ui-bg-base)]"></iframe>
        <p v-if="block.data.caption" class="text-sm text-[var(--TextMuted)] mt-2 text-center">
          {{ block.data.caption }}
        </p>
      </div>

      <!-- Table -->
      <div v-else-if="block.type === 'table'" class=" my-4 overflow-x-auto">
        <table class="w-full border-collapse border border-[var(--ui-border)] ">
          <tbody>
            <tr v-for="(row, rowIndex) in block.data.content" :key="rowIndex"
              class="border border-[var(--ui-border)] hover:bg-[var(--ui-bg-hover)] transition-colors">
              <td v-for="(cell, cellIndex) in row" :key="cellIndex"
                class="p-2 border border-[var(--ui-border)] text-[var(--TextColor)]">
                {{ cell }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Unsupported block -->
      <div v-else class="text-[var(--TextMuted)] italic py-2">
        Unsupported block type: {{ block.type }}
        {{ block.data.text }}
      </div>
    </div>
  </div>

  <!-- Popover for annotations -->
  <Teleport to="body">
    <Transition enter-active-class="transition-opacity duration-200"
      leave-active-class="transition-opacity duration-150" enter-from-class="opacity-0" leave-to-class="opacity-0">
      <div v-if="popover.visible"
        class="fixed z-[9999] bg-[var(--ui-bg-base)] p-2 rounded-lg shadow-lg border border-[var(--ui-border)] max-w-xs text-md text-[var(--TextColor)]"
        :style="{ left: popover.x + 'px', top: popover.y + 'px' }">
        <p class="font-roboto-condensed text-[var(--ui-heading)]">{{ popover.title }}</p>
        <p class="font-roboto-condensed mt-1 text-[var(--TextMuted)]">{{ popover.description }}</p>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
/* Добавляем стили, если нужно переопределить что-то специфичное */
.warning-div {
  border-left-color: var(--tertiary);
}
</style>