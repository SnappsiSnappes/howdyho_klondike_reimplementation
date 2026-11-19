<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import TextInput from '../Components/TextInput.vue';
import Editor from '../Components/EditorJs.vue';

// Получаем категории из props
const props = defineProps({
    categories: {
        required: true,
    },
});

const bodyEditor = ref(null);
const previewEditor = ref(null);

const form = useForm({
    title: null,
    body: null,
    preview_text: null,
    preview_image: null,
    preview_image_url: null,
    category_id: null, // ← добавили
});

const handleSave = async () => {
    try {
        const bodyData = await bodyEditor.value.save();
        const previewData = await previewEditor.value.save();

        form.body = JSON.stringify(bodyData);
        form.preview_text = JSON.stringify(previewData);

        form.post(route('post.store'), {
            preserveScroll: true,
            onError: (errors) => {
                console.error('Errors:', errors);
            },
        });
    } catch (error) {
        console.error('Error saving editors:', error);
    }
};

const AddFile = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.preview_image = file;
        // Создаём временный URL для предпросмотра
        form.preview_image_url = URL.createObjectURL(file);
    }
};

</script>

<template>

    <Head :title="`${$page.component}`" />
    <div class="w-10/12 mx-auto">
        <h1 class="text-2xl font-bold mb-2 mt-5">Создать новый пост</h1>

        <form @submit.prevent="handleSave">
            <TextInput name="Название поста макс 255 символов" v-model="form.title" :message="form.errors.title" />

            <!-- Выбор категории -->
            <label class="block mt-4 font-medium">Категория</label>
            <select v-model="form.category_id" class="MainThemeInput"
                :class="{ 'border-red-500': form.errors.category_id }">
                <option value="">— Select a category —</option>
                <option v-for="(key,value) in categories" :key="key" :value="value">
                    {{ key }}
                </option>
            </select>
            <small v-if="form.errors.category_id" class="text-red-500 block mt-1">
                {{ form.errors.category_id }}
            </small>

            <label class="block mt-4 font-medium">Превью текст, отображается только на общих карточках 761 символ</label>
            <Editor ref="previewEditor" :DivId="'PreviewTextEditorJs'" />

            <label class="block mt-4 font-medium">Тест статьи, без лимита</label>
            <Editor ref="bodyEditor" :DivId="'BodyEditorJs'" />

            <label class="block mt-4 font-medium">Превью картинка, максимум 99000 кб</label>
            <img v-if="form.preview_image_url" :src="form.preview_image_url"
                class="object-cover w-28 h-28 rounded mt-2" />
            <input type="file" @input="AddFile" name="preview_image" class="mt-2 FileThemeInput">
            <small v-if="form.errors.preview_image" class="text-red-500 block mt-1">
                {{ form.errors.preview_image }}
            </small>

            <div v-if="form.errors.body" class="text-red-500 mt-2">{{ form.errors.body }}</div>
            <div v-if="form.errors.preview_text" class="text-red-500 mt-2">{{ form.errors.preview_text }}</div>

            <div class="mt-6 mb-8">
                <button class="primary-btn" :disabled="form.processing">
                    {{ form.processing ? 'Создаю пост...' : 'Создать пост' }}
                </button>
            </div>
        </form>
    </div>
</template>