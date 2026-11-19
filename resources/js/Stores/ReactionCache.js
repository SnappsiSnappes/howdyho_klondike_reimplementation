// /resources/js/Stores/ReactionCache.js
import { shallowRef } from 'vue';
import axios from 'axios';

// Глобальный кэш: { packId => { elements: [...], loadedAt: timestamp } }
export const reactionPacksCache = shallowRef({});

// Флаг загрузки паков (чтобы не делать 2 запроса одновременно)
const loadingPromises = new Map();

/**
 * Загружает пак, если он ещё не в кэше.
 * Возвращает промис с данными пака.
 */
export async function loadPackIfNeeded(packId) {
  // Если уже в кэше — возвращаем кэш
  if (reactionPacksCache.value[packId]) {
    return reactionPacksCache.value[packId];
  }

  // Если уже идёт загрузка — дожидаемся её
  if (loadingPromises.has(packId)) {
    return await loadingPromises.get(packId);
  }

  // Начинаем загрузку
  const promise = axios.get(`/GetCertainPack/${packId}`)
    .then(response => {
      const mappedElements = response.data.Elements.map(el => ({
        id: el.id.toString(),
        label: el.name,
        path: `/storage/${el.file_name}`,
        sticker: el.type.sticker,
      }));

      const packData = { id: packId, elements: mappedElements };

      // Обновляем реактивный кэш
      reactionPacksCache.value = {
        ...reactionPacksCache.value,
        [packId]: packData
      };

      return packData;
    })
    .finally(() => {
      loadingPromises.delete(packId);
    });

  loadingPromises.set(packId, promise);
  return await promise;
}

// Также можно кэшировать список всех паков (если он одинаковый для всех)
export const allPacksCache = shallowRef(null);
export async function loadAllPacksIfNeeded() {
  if (allPacksCache.value) return allPacksCache.value;

  const { data } = await axios.get('/GetReactions');
  allPacksCache.value = data.all_packs;
  return data.all_packs;
}