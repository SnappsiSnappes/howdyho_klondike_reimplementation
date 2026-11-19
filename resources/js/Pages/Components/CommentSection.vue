<script setup>
import { ref, onMounted, nextTick } from 'vue';
import axios from 'axios';
import { ChatBubbleBottomCenterIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    post: Object,
});

const comments = ref([]);
const form = ref({
    username: '',
    text: '',
});
const errors = ref({});
const loading = ref(false);
const offset = ref(0);
const OffsetStep = 3;
const hasMoreComments = ref(true);

// Для отслеживания состояния комментариев
const commentStates = ref({}); // { id: { expanded: false, needsExpandButton: false } }

const loadComments = async (append = false) => {
    const response = await axios.get(`/post/${props.post.slug}/comments`, {
        params: {
            offset: append ? offset.value : 0
        }
    });
    const commentsArray = Array.isArray(response.data.comments)
        ? response.data.comments
        : Object.values(response.data.comments);

    if (append) {
        // Append new comments to existing list
        comments.value = [...comments.value, ...commentsArray];
    } else {
        // Replace all comments
        comments.value = response.data.comments || [];
    }

    // Проверяем: если текущее количество комментариев меньше общего количества, то есть еще что загружать
    hasMoreComments.value = comments.value.length < response.data.total_amount;

    // ✅ Дожидаемся, пока DOM обновится, и только потом проверяем overflow
    await nextTick();

    commentsArray.forEach(comment => {
        checkOverflow(comment.id);
    });
};

const loadMoreComments = async () => {
    offset.value += OffsetStep; // Increment offset by 3 (or 5 if you change the backend limit)
    await loadComments(true); // Append new comments
};

const submitComment = async () => {
    loading.value = true;
    errors.value = {};

    try {
        const response = await axios.post('/comment', {
            ...form.value,
            post_id: props.post.id,
        });

        alert(response.data.message || 'Комментарий отправлен на модерацию.');
        form.value.username = '';
        form.value.text = '';

        // Reset pagination and reload all comments
        offset.value = 0;
        loadComments();
    } catch (err) {
        if (err.response?.data?.errors) {
            errors.value = err.response.data.errors;
        } else {
            alert('Ошибка: ' + (err.response?.data?.message || 'Неизвестная ошибка'));
        }
    } finally {
        loading.value = false;
    }
};

// Проверяем, переполняется ли текст
const checkOverflow = (id) => {
    const element = document.getElementById(`comment-text-${id}`);
    if (!element) return;

    // Временно убираем ограничение высоты
    const wasExpanded = commentStates.value[id]?.expanded;
    if (!wasExpanded) {
        const fullHeight = element.scrollHeight;
        const currentHeight = element.clientHeight;

        // Если полный текст выше, чем видимая область (например, 3 строки)
        const needsButton = fullHeight > currentHeight * 1.5; // Коэффициент запаса

        commentStates.value[id] = {
            expanded: false,
            needsExpandButton: needsButton,
        };
    } else {
        commentStates.value[id] = {
            expanded: true,
            needsExpandButton: true,
        };
    }
};

// Переключаем состояние
const toggleExpand = (id) => {
    const state = commentStates.value[id];
    if (!state) return;

    state.expanded = !state.expanded;
};

onMounted(() => {
    loadComments();
});

</script>

<template>
    <div class="Comment-label ">
        <component :is=ChatBubbleBottomCenterIcon class="h-[2rem] w-[2rem] inline" /> Комментарии:
    </div>
    <div class="comment-section">

        <!-- Список комментариев -->
        <div class="comments-list">
            <div v-for="comment in comments" :key="comment.id" class="comment">
                <div class="username-div">
                    <p class="username-name">{{ comment.username }}</p>
                    <small class="username-date">{{ comment.humanReadableDate }}</small>
                </div>

                <!-- Контейнер текста -->
                <div class="comment-text-container">
                    <p :id="`comment-text-${comment.id}`" :class="[
                        'comment-text',
                        commentStates[comment.id]?.expanded ? 'expanded' : ''
                    ]">
                        {{ comment.text }}
                    </p>

                </div>
                
                <!-- Кнопка "Показать полностью" -->
                <button v-if="commentStates[comment.id]?.needsExpandButton" @click="toggleExpand(comment.id)"
                    class="expand-btn">
                    {{ commentStates[comment.id]?.expanded ? 'Свернуть' : 'Показать полностью' }}
                </button>

            </div>


        </div>

        <!-- Кнопка "Загрузить еще комментарии" -->
        <button v-if="hasMoreComments && comments.length > 0" @click="loadMoreComments" class="Comment-load-more-btn">
            Загрузить еще комментарии
        </button>

        <!-- Форма создания комментария -->
        <form @submit.prevent="submitComment" class="comment-form">
            <div class="pb-4">
                <label class="LabelBright">Ваше имя:</label>
                <input v-model="form.username" type="text" class="MainThemeInput CommentInput" maxlength="30"
                    required />
                <div class="char-count">{{ form.username.length }}/30</div>
                <div v-if="errors.username" class="error">{{ errors.username }}</div>
            </div>

            <div>
                <label class="LabelBright">Комментарий:</label>
                <textarea v-model="form.text" maxlength="250" class="MainThemeInput CommentInput resize-none"
                    rows="4"></textarea>
                <div class="char-count">{{ form.text.length }}/250</div>
                <div v-if="errors.text" class="error">{{ errors.text }}</div>
            </div>

            <button type="submit" :disabled="loading" class="Comment-Submit">
                {{ loading ? 'Отправка...' : 'Отправить комментарий' }}
            </button>
        </form>
    </div>
</template>
