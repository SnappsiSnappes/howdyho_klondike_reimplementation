<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

import TextInput from '../Components/TextInput.vue';
import Editor from '../Components/EditorJs.vue';

// Ссылки на редакторы
const bodyEditor = ref(null);
const previewEditor = ref(null);

const props = defineProps({
    post: Object,
    categories: {
        required: true,
    },
});


const deletePreviewImage = ref(false);

// Парсим данные из JSON (если они есть)
const initialBody = props.post.body ? JSON.parse(props.post.body) : {};
const initialPreviewText = props.post.preview_text ? JSON.parse(props.post.preview_text) : {};

const form = useForm({
    title: props.post.title || '',
    body: props.post.body || '', // оставим строку, но заменим перед отправкой
    preview_text: props.post.preview_text || '',
    preview_image: null,
    preview_image_url: props.post.preview_image || null,
    delete_preview_image: false,
    category_id: props.post.category_id, // ← добавили

});

// НОВЫЙ метод сохранения — без аргументов, собираем данные сами
const handleSave = async () => {

    try {
        const bodyData = await bodyEditor.value.save();
        const previewData = await previewEditor.value.save();

        console.log({
            bodyData: bodyData,
            previewData: previewData
        })

        form.body = JSON.stringify(bodyData);
        form.preview_text = JSON.stringify(previewData);
        form.delete_preview_image = deletePreviewImage.value;

        form.post(route('post.update', props.post), {
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
        form.preview_image_url = URL.createObjectURL(file);
        deletePreviewImage.value = false;
    }
};

const removePreviewImage = () => {
    deletePreviewImage.value = true;
    form.preview_image = null;
    form.preview_image_url = null;
};

function deletePost(post) {
    if (confirm('Are you sure you want to delete this post?')) {
        router.delete(route('post.destroy', post), {
            onSuccess: () => {
                console.log('Post deleted successfully');
            },
            onError: () => {
                console.error('Error deleting post');
            },
        });
    }
}
</script>

<template>

    <Head :title="`${$page.component}`" />
    <div class="w-10/12 mx-auto">

        <h1>Редактировать пост
        </h1>

        <form @submit.prevent="deletePost(post)">
            <button type="submit" class="text-red-500">УДАЛИТЬ ПОСТ</button>
        </form>

        <form @submit.prevent="handleSave" class="]">

            <!-- Выбор категории -->
            <label class="block mt-4 font-medium">Категория</label>
            <select v-model="form.category_id" class="MainThemeInput my-2"
                :class="{ 'border-red-500': form.errors.category_id }">
                <option value="">— Select a category —</option>
                <option v-for="(key, value) in categories" :key="key" :value="value">
                    {{ key }}
                </option>

            </select>
            <small v-if="form.errors.category_id" class="text-red-500 block mt-1">
                {{ form.errors.category_id }}
            </small>

            <!-- Заголовок -->
            <TextInput name="Название поста макс 255 символов" v-model="form.title" :message="form.errors.title" />



            <!-- Preview Text Editor -->
            <label class="block mt-4">Превью текст, отображается только на общих карточках</label>
            <Editor ref="previewEditor" :DivId="'PreviewTextEditorJs'" :initialData="initialPreviewText" />

            <!-- Main Body Editor -->
            <label class="block mt-4">Тест статьи, без лимита</label>
            <Editor ref="bodyEditor" :DivId="'BodyEditorJs'" :initialData="initialBody" />


            <!-- Preview Image -->
            <label class="block mt-4">Превью картинка, максимум 99000 кб</label>
            <div class="flex items-center space-x-2">
                <img v-if="form.preview_image_url" :src="form.preview_image_url" class="object-cover w-28 h-28" />
                <input type="file" @input="AddFile" name="preview_image" class="mt-2 FileThemeInput" />
                <button v-if="form.preview_image_url" type="button" @click="removePreviewImage"
                    class="text-red-500 hover:text-red-700">
                    Remove
                </button>
            </div>


            <small v-if="form.errors.preview_image" class="error">{{ form.errors.preview_image }}</small>

            <!-- Ошибки -->
            <div v-if="form.errors.body" class="text-red-500 mt-2">{{ form.errors.body }}</div>
            <div v-if="form.errors.preview_text" class="text-red-500 mt-2">{{ form.errors.preview_text }}</div>

            <!-- Кнопка -->
            <div class="mt-6 mb-8">
                <button class="primary-btn" :disabled="form.processing">
                    Обновить пост
                </button>
            </div>
        </form>


    </div>
</template>