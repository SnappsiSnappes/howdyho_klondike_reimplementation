<script setup>
import { ref, onMounted, nextTick } from 'vue';
import axios from 'axios';

const props = defineProps({
    postId: Number,
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
    const response = await axios.get(`/post/${props.postId}/comments`, {
        params: {
            offset: append ? offset.value : 0
        }
    });

    if (append) {
        // Append new comments to existing list
        comments.value = [...comments.value, ...response.data.comments];
    } else {
        // Replace all comments
        comments.value = response.data.comments || [];
    }

    // Проверяем: если текущее количество комментариев меньше общего количества, то есть еще что загружать
    hasMoreComments.value = comments.value.length < response.data.total_amount;

    // ✅ Дожидаемся, пока DOM обновится, и только потом проверяем overflow
    await nextTick();

    response.data.comments.forEach(comment => {
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
            post_id: props.postId,
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
    <div class="comment-section-small">
        <div class="">
            <!-- Список комментариев -->
            <div class="comments-list">
                <div v-for="comment in comments.slice(0, 3)" :key="comment.id" class="comment-small">
                    <div class="username-div-small">
                        <p class="username-name-small">{{ comment.username }}</p>
                        <small class="username-date-small">{{ new Date(comment.created_at).toLocaleString() }}</small>
                    </div>

                    <!-- Контейнер текста -->
                    <div class="comment-text-container-small">
                        <p :id="`comment-text-${comment.id}`" class="comment-text-small">
                            {{ comment.text }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Кнопка "Загрузить еще комментарии" -->
            <button v-if="hasMoreComments && comments.length > 0" @click="loadMoreComments" class="load-more-btn">
                Загрузить еще комментарии
            </button>
        </div>

        <!-- Форма создания комментария -->
        <form @submit.prevent="submitComment" class="comment-form">
            <div class="pb-4">
                <label class="LabelBright">Ваше имя:</label>
                <input v-model="form.username" type="text" class="MainThemeInput input-full" maxlength="30" required />
                <div class="char-count">{{ form.username.length }}/30</div>
                <div v-if="errors.username" class="error">{{ errors.username }}</div>
            </div>

            <div>
                <label class="LabelBright">Комментарий:</label>
                <textarea v-model="form.text" maxlength="250" class="MainThemeInput input-full resize-none"
                    rows="4"></textarea>
                <div class="char-count">{{ form.text.length }}/250</div>
                <div v-if="errors.text" class="error">{{ errors.text }}</div>
            </div>

            <button type="submit" :disabled="loading" class="submit-btn">
                {{ loading ? 'Отправка...' : 'Отправить комментарий' }}
            </button>
        </form>
    </div>
</template>

<style scoped>
.comment-section-small {
    @apply mt-7 grid grid-cols-2 gap-[3rem];
}

.comment-small {
    @apply border border-[--secondary-fade] rounded-lg mt-5 p-3;
}

.username-div-small {
    @apply mb-2 leading-4 m-2;
}

.username-name-small {
    @apply truncate max-w-xs text-[--ui-link] text-sm;
}

.username-date-small {
    color: var(--label-muted);
    font-weight: 400;
    font-size: 0.75rem;
    margin-bottom: 0.25rem;
    display: block;
    transition: color 0.2s ease;
}

.comment-text-container-small {
    @apply mt-1;
}

.comment-text-small {
    @apply break-words text-xs leading-tight;
    max-height: 3em; /* ≈ 2 строки */
    overflow: hidden;
    line-height: 1.2;
}

.load-more-btn {
    @apply mt-4 px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded hover:bg-gray-300 dark:hover:bg-gray-600 transition;
}

.comment-form {
    @apply mt-6 ;
}

.input-full {
    @apply w-full;
}

.submit-btn {
    @apply mt-2 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50 transition;
}

.error {
    @apply text-red-500 text-sm mt-1;
}

.char-count {
    @apply text-xs text-gray-500 mt-1;
}
</style>