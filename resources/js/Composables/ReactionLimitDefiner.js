/**
  этот композибл существет чтобы посчитать ширину 
  пользовательского интерфейса и выдать то количество
  реакций которое сможет поместиться у пользователя в 
  1 строку
*/

// Composables/ReactionLimitDefiner.js

import { ref, onMounted, onUnmounted, computed } from 'vue';

/**
 * Вспомогательная функция для создания реактивного медиа-запроса
 */
function useMediaQuery(query) {
  const matches = ref(false);

  let mql;

  const update = (e) => {
    matches.value = e.matches;
  };

  onMounted(() => {
    mql = window.matchMedia(query);
    matches.value = mql.matches;
    mql.addEventListener('change', update);
  });

  onUnmounted(() => {
    if (mql) {
      mql.removeEventListener('change', update);
    }
  });

  return matches;
}

/**
 * Композибл: возвращает реактивный лимит количества отображаемых реакций
 */
export function useReactionLimit() {
  const isXl = useMediaQuery('(min-width: 1280px)');
  const isLg = useMediaQuery('(min-width: 1024px)');
  const isMd = useMediaQuery('(min-width: 768px)');
  const isSm = useMediaQuery('(min-width: 640px)');
  const is500 = useMediaQuery('(min-width: 500px)');
  const is400 = useMediaQuery('(min-width: 400px)');

  const reactionLimit = computed(() => {
    if (isXl.value) return 7;
    if (isLg.value) return 6;
    if (isMd.value) return 6;
    if (isSm.value) return 5;
    if (is500.value) return 3;
    if (is400.value) return 2;
    return 1;
  });

  return reactionLimit;
}