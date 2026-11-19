<script setup>
import { computed } from 'vue';
import { PaperClipIcon } from '@heroicons/vue/24/outline';
import { DocumentIcon } from '@heroicons/vue/24/outline';
import { PhotoIcon } from '@heroicons/vue/24/outline';
import { GifIcon } from '@heroicons/vue/16/solid';

const props = defineProps({
    file: {
        type: Object,
        required: true,
    },
});

// Форматирование размера файла
const fileSize = computed(() => {
    const sizeInBytes = props.file.size;
    if (sizeInBytes < 1024) return `${sizeInBytes} B`;
    if (sizeInBytes < 1024 * 1024) return `${(sizeInBytes / 1024).toFixed(1)} KB`;
    return `${(sizeInBytes / (1024 * 1024)).toFixed(1)} MB`;
});

// Определение иконки на основе расширения файла
const fileIcon = computed(() => {
    const extension = props.file.extension.toLowerCase();
    switch (extension) {
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
        case 'bmp':
        case 'webp':
            return PhotoIcon;
        case 'torrent':
            return DocumentIcon;
        case 'pdf':
            return DocumentIcon;
        default:
            return PaperClipIcon;
    }
});
</script>

<template>
    <div class="file-container">
        <!-- Иконка файла -->
        <div class="file-icon mr-4">
            <component :is="fileIcon" class="icon" />
        </div>

        <!-- Информация о файле -->
        <div class="file-info flex-grow">
            <!-- Название файла -->
            <div class="file-name">
                {{ file.name }}
            </div>

            <!-- Размер файла -->
            <div class="file-size">
                {{ fileSize }}
            </div>
        </div>

        <!-- Кнопка скачивания -->
        <a :href="file.url" target="_blank" download class="file-download-btn">
            Скачать
        </a>
    </div>
</template>

