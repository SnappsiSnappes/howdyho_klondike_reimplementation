<template>
  <div class="flex justify-center items-start w-full py-4">
    <div class="grid grid-cols-2 gap-y-4 w-full max-w-[22rem]">
      <!-- Элемент 1: Просмотры -->
      <div class="ps-3 flex items-center min-w-0">
        <div class="w-[2.4rem] h-[2.4rem] relative flex-shrink-0 mr-3">
          <div class="absolute inset-0 rounded-full bg-[--StatisticViews] opacity-50 animate-pulse-fast"></div>
          <div class="absolute inset-0 rounded-full bg-[--StatisticViews] opacity-40 animate-pulse-micro"></div>
          <div class="absolute inset-0 flex items-center justify-center">
            <div class="w-6 h-6 opacity-30 rounded-full bg-[--StatisticViews]"></div>
          </div>
        </div>
        <div class="text-left min-w-0">
          <div 
            class="font-bold text-[--StatisticViews] text-lg truncate transition-all duration-300"
            :class="{ 'animate-stat-bounce': animating.views }"
          >
            {{ formatNumber(viewsCount) }}
          </div>
          <div class="text-xs text-[--StatisticTextColor] truncate">просмотров</div>
        </div>
      </div>

      <!-- Элемент 2: Посты -->
      <div class="flex items-center min-w-0">
        <div class="w-[2.4rem] h-[2.4rem] relative flex-shrink-0 mr-3">
          <div class="absolute inset-0 rounded-full bg-[--StatisticPostsColor] opacity-60 animate-pulse-slow"></div>
          <div class="absolute inset-0 flex items-center justify-center">
            <div class="w-2 h-2 opacity-30 rounded-full bg-[--StatisticPostsColor]"></div>
          </div>
        </div>
        <div class="text-left min-w-0">
          <div 
            class="font-bold text-[--StatisticPostsColor] text-lg truncate transition-all duration-300"
            :class="{ 'animate-stat-bounce': animating.posts }"
          >
            {{ formatNumber(postsCount) }}
          </div>
          <div class="text-xs text-[--StatisticTextColor] truncate">постов</div>
        </div>
      </div>

      <!-- Элемент 3: Реакции -->
      <div class="ps-3 flex items-center min-w-0">
        <div class="w-[2.4rem] h-[2.4rem] relative flex-shrink-0 mr-3">
          <div class="absolute inset-0 rounded-full bg-[--StatisticReactions] opacity-50 animate-pulse-wide"></div>
          <div class="absolute inset-0 flex items-center justify-center">
            <div class="w-2 h-2 opacity-30 rounded-full bg-[--StatisticReactions]"></div>
          </div>
        </div>
        <div class="text-left min-w-0">
          <div 
            class="font-bold text-[--StatisticReactions] text-lg truncate transition-all duration-300"
            :class="{ 'animate-stat-bounce': animating.reactions }"
          >
            {{ formatNumber(reactionsCount) }}
          </div>
          <div class="text-xs text-[--StatisticTextColor] truncate">реакций</div>
        </div>
      </div>

      <!-- Элемент 4: Комментарии -->
      <div class="flex items-center min-w-0">
        <div class="w-[2.4rem] h-[2.4rem] relative flex-shrink-0 mr-3">
          <div class="absolute inset-0 rounded-full bg-[--StatisticComments] opacity-60 animate-pulse-slow"></div>
          <div class="absolute inset-0 flex items-center justify-center">
            <div class="w-2 h-2 opacity-30 rounded-full bg-[--StatisticComments]"></div>
          </div>
        </div>
        <div class="text-left min-w-0">
          <div 
            class="font-bold text-[--StatisticComments] text-lg truncate transition-all duration-300"
            :class="{ 'animate-stat-bounce': animating.comments }"
          >
            {{ formatNumber(commentsCount) }}
          </div>
          <div class="text-xs text-[--StatisticTextColor] truncate">комментариев</div>
        </div>
      </div>

      <!-- 🟢 Элемент 5: Онлайн (на всю ширину, красиво выделен) -->
      <div class="col-span-2 flex items-center justify-between min-w-0 px-4 py-3 mt-1 rounded-xl bg-gradient-to-r from-green-500/10 via-green-500/20 to-green-500/10 border border-green-500/30 shadow-[0_0_20px_rgba(34,197,94,0.15)] relative overflow-hidden">
        <!-- Фоновая анимированная полоса (shimmer) -->
        <div class="absolute inset-0 opacity-50 pointer-events-none">
          <div class="absolute inset-0"></div>
        </div>
        
        <div class="relative flex items-center gap-2.5">
          <!-- Пульсирующая точка "в сети" -->
          <div class="relative w-3 h-3 flex-shrink-0">
            <div class="absolute inset-0 rounded-full bg-green-500 animate-ping opacity-75"></div>
            <div class="absolute inset-0 rounded-full bg-green-500 shadow-[0_0_10px_rgba(34,197,94,0.9)]"></div>
          </div>
          <div class="flex flex-col">
            <span class="text-[11px] font-bold text-green-400 uppercase tracking-wider leading-tight">В сети</span>
            <span class="text-[9px] text-green-400/60 uppercase tracking-wider">прямо сейчас</span>
          </div>
        </div>
        
        <div 
          class="relative flex items-baseline gap-1.5 transition-all duration-300"
          :class="{ 'animate-stat-bounce': animating.online }"
        >
          <span class="text-3xl font-black text-green-400 tabular-nums ">
            {{ onlineCount }}
          </span>
          <span class="text-xs text-green-400/60 font-medium">чел.</span>
        </div>
      </div>
      
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'

const page = usePage();
const stats = ref(page.props.stats || {});

// Инициализируем все значения из share
const postsCount = ref(stats.value.posts || 0);
const commentsCount = ref(stats.value.comments || 0);
const reactionsCount = ref(stats.value.reactions || 0);
const viewsCount = ref(stats.value.views || 0);
const onlineCount = ref(stats.value.online || 0);

// Отслеживаем, какие поля "прыгают"
const animating = ref({
    posts: false,
    comments: false,
    reactions: false,
    views: false,
    online: false
});

// Функция для запуска анимации
const triggerAnimation = (key) => {
    animating.value[key] = true;
    setTimeout(() => {
        animating.value[key] = false;
    }, 600); // Длительность анимации
};

// Следим за изменениями каждого значения
watch(postsCount, (newVal, oldVal) => {
    if (newVal !== oldVal && oldVal !== 0) triggerAnimation('posts');
});
watch(commentsCount, (newVal, oldVal) => {
    if (newVal !== oldVal && oldVal !== 0) triggerAnimation('comments');
});
watch(reactionsCount, (newVal, oldVal) => {
    if (newVal !== oldVal && oldVal !== 0) triggerAnimation('reactions');
});
watch(viewsCount, (newVal, oldVal) => {
    if (newVal !== oldVal && oldVal !== 0) triggerAnimation('views');
});
watch(onlineCount, (newVal, oldVal) => {
    if (newVal !== oldVal && oldVal !== 0) triggerAnimation('online');
});

let timeoutId = null;

const fetchStats = async () => {
    try {
        const response = await axios.get('/get-stats');
        postsCount.value = response.data.posts;
        commentsCount.value = response.data.comments;
        reactionsCount.value = response.data.reactions;
        viewsCount.value = response.data.views;
        onlineCount.value = response.data.online;
        
    } catch (error) {
        console.error('Ошибка получения статистики:', error);
    } finally {
        scheduleNextFetch();
    }
};

const scheduleNextFetch = () => {
    let delay = 5000 + (Math.random() * 3000);
    if (document.hidden) {
        delay = 60000 * 25;
    }
    timeoutId = setTimeout(fetchStats, delay);
};

const handleVisibilityChange = () => {
    clearTimeout(timeoutId);
    if (!document.hidden) {
        fetchStats();
    } else {
        scheduleNextFetch();
    }
};

const formatNumber = (num) => {
    return new Intl.NumberFormat('ru-RU').format(num);
};

onMounted(() => {
    document.addEventListener('visibilitychange', handleVisibilityChange);
    fetchStats();
});

onUnmounted(() => {
    document.removeEventListener('visibilitychange', handleVisibilityChange);
    clearTimeout(timeoutId);
});
</script>

<style scoped>
/* Анимация "прыжка" при изменении числа */
@keyframes stat-bounce {
    0% { transform: scale(1); }
    25% { transform: scale(1.25); }
    50% { transform: scale(0.9); }
    75% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.animate-stat-bounce {
    animation: stat-bounce 0.6s ease-in-out;
}

/* Анимация световой полосы для блока "Онлайн" */
@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.animate-shimmer {
    animation: shimmer 23s infinite linear;
}
</style>