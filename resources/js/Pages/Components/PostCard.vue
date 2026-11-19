<!-- PostCard.vue -->
<script setup>
import { Link } from "@inertiajs/vue3";
import { onMounted, ref, nextTick } from "vue";
import PhotoSwipeLightbox from "photoswipe/lightbox";
import ImageHelper from "../../Objects/ImageHelper";
import EditorJsRenderer from "./EditorJsRenderer.vue";
import { useCategoryNavigation } from "../../Composables/CategoryHelper";

const props = defineProps({
  post: { type: Object, required: true },
});

const ConvertedFile = ref(null);

onMounted(async () => {
  if (props.post.preview_image) {
    try {
      ConvertedFile.value = await ImageHelper.GetImageWithSizes(props.post.preview_image);
    } catch (e) {
      console.error("Не удалось прочитать размеры превью:", e);
      // фолбэк, чтобы lightbox всё равно работал
      ConvertedFile.value = {
        url: props.post.preview_image,
        width: 1600,
        height: 900,
      };
    }

    // ВАЖНО: ждём реального рендера <a> в DOM, иначе PhotoSwipe не найдёт gallery
    await nextTick();

    const lightbox = new PhotoSwipeLightbox({
      gallery: "#image",
      children: "a",
      pswpModule: () => import("photoswipe"),
      showHideAnimationType: "zoom",
      showAnimationDuration: 350,
      hideAnimationDuration: 350,
      closeOnScroll: false,
      wheelToZoom: true,
    });
    lightbox.init();
  }
});

const { isOpen, toggleItem, goToCategory } = useCategoryNavigation(props.post.category.id);
</script>

<template>
  <div class="w-full post-card rounded-lg transition duration-300 relative">
    <!-- Grid-контейнер -->
    <div class="post-grid">
      <!-- Изображение превью -->
      <div id="image" class="preview-image">
        <template v-if="ConvertedFile">
          <a
            :data-pswp-width="ConvertedFile.width"
            :data-pswp-height="ConvertedFile.height"
            :href="ConvertedFile.url"
            class="image-link"
          >
            <img
              :src="ConvertedFile.url"
              :alt="ConvertedFile.caption || 'Image'"
              class="preview-img"
              loading="lazy"
              decoding="async"
            />
          </a>
        </template>
        <template v-else>
          <div class="image-placeholder">
            <span class="text-gray-500">No image</span>
          </div>
        </template>
      </div>

      <!-- Текстовая часть -->
      <div class="content-container">
        <div class="px-[1.5rem]">
          <div class="text-[--ui-text]">
            <Link :href="route('post.show', post)">
              <p class="cool-header">{{ post.title }}</p>
            </Link>

            <div class="text-[0.88rem] text-[--TextMuted] opacity-100 flex items-center flex-wrap gap-5">
              <!-- Дата -->
              <div>Опубликовано {{ post.humanReadableDate }}</div>

              <!-- Категория -->
              <div class="min-w-0">
                <div
                  @click="goToCategory(post.category.slug)"
                  class="CategoryBlueFire cursor-pointer flex items-center gap-1 min-w-0"
                  :title="`Категория ${post.category.name}`"
                >
                  <img
                    class="w-[1.4rem] h-[1.4rem] shrink-0 object-cover rounded inline-block"
                    :src="`/storage/${post.category.preview_img}`"
                    alt="Категория"
                  />
                  <span class="truncate">{{ post.category.name }}</span>
                </div>
              </div>

              <!-- Просмотры -->
              <div class="flex items-center gap-2" title="Количество просмотров">
                <img :src="`/storage/decoration/view.svg`" alt="Просмотры" class="w-6 h-6 invert shrink-0" />
                {{ post.views_count }}
              </div>
            </div>
          </div>

          <div class="!text-[#88919d] mb-5 mt-4">
            <EditorJsRenderer :placement="'CARD'" :content="post.preview_text" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ===== Корневая карточка: жёстко не даём ничего вылезти ===== */
.post-card {
  width: 100%;
  max-width: 100%;
  min-width: 0;
  overflow: hidden;      /* всё, что больше — обрезается по скруглению */
  border-radius: 3px;
  contain: paint;        /* изоляция: дети не влияют на внешнюю раскладку */
}

/* ===== Grid layout ===== */
.post-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr);   /* mobile */
  width: 100%;
  min-height: 300px;
  overflow: hidden;
  border-radius: 3px;
}

@media (min-width: 768px) {
  .post-grid {
    /* minmax(0, …) — ключ к фиксу: трек не может растянуться под картинку */
    grid-template-columns: minmax(0, 23rem) minmax(0, 1fr);
  }
}

/* ===== Левая колонка — изображение ===== */
.preview-image {
  position: relative;        /* якорь для абсолютной картинки */
  overflow: hidden;
  width: 100%;
  min-width: 0;
  align-self: stretch;
  background-color: #000;
  border-radius: 3px 0 0 3px;

  /* mobile: высота задаётся контейнером, а не картинкой */
  aspect-ratio: 16 / 10;
  min-height: 200px;
  max-height: 60vh;
}

@media (min-width: 768px) {
  .preview-image {
    aspect-ratio: auto;      /* тянемся на всю высоту текстовой колонки */
    min-height: 300px;
  }
}

.image-link {
  position: absolute;
  inset: 0;
  display: block;
  width: 100%;
  height: 100%;
}

/* Картинка вырвана из потока => её реальные размеры НИКАК не влияют на карточку */
.preview-img {
  position: absolute;
  inset: 0;
  display: block;
  width: 100%;
  height: 100%;
  max-width: 100%;
  max-height: 100%;
  object-fit: cover;
  object-position: center;
}

.image-placeholder {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #111;
}

/* ===== Правая колонка — контент ===== */
.content-container {
  min-width: 0;              /* иначе длинные слова/ссылки раздвинут колонку */
  overflow: hidden;
  background-color: rgb(8, 12, 15);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  border-radius: 0 3px 3px 0;
}

/* Длинные заголовки и любой текст — переносим, а не растягиваем */
.cool-header {
  max-width: 100%;
  overflow-wrap: anywhere;
  word-break: break-word;
}

/* Контент из EditorJS (превью-текст) — картинки/видео/код не вылезают */
.content-container :deep(img),
.content-container :deep(video),
.content-container :deep(iframe),
.content-container :deep(embed),
.content-container :deep(svg) {
  max-width: 100%;
  height: auto;
}

.content-container :deep(pre) {
  max-width: 100%;
  overflow-x: auto;
  white-space: pre-wrap;
  word-break: break-word;
}

.content-container :deep(a) {
  overflow-wrap: anywhere;
  word-break: break-word;
}

.actions { color: white; }
.meta    { color: rgba(255, 255, 255, 0.7); }
</style>