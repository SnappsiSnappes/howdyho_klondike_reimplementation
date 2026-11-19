<script setup>
import { ref, onMounted, onUnmounted, computed, nextTick, watch, reactive } from 'vue';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';
import FlashMessage from '../Components/FlashMessage.vue';
import Lottie from 'lottie-web';
import SearchReaction from '../Components/SearchReaction.vue';
import ReactionSvg from '../../svg/ReactionSvg.vue';
import { useTooltip } from '../../Composables/UseTooltip.js';
import { useReactionLimit } from '../../Composables/ReactionLimitDefiner.js';
import { reactionPacksCache, loadAllPacksIfNeeded, loadPackIfNeeded } from '../../Stores/ReactionCache.js';
// Используем Composable
const { tooltip, showTooltip, hideTooltip } = useTooltip();


const page = usePage();

const props = defineProps({
  PostId: {
    type: Number,
    required: true
  }
});

// Получаем реактивный лимит
const reactionLimit = useReactionLimit();

// Показывать все реакции или только топ-7?
const ShowAllReactions = ref(false);

// Есть ли реакции сверх первых 7? (нужна ли кнопка "Показать ещё"?)
const HasMoreReactions = computed(() => {
  return ActiveReactions.value.length > reactionLimit.value;
});

// Какие реакции сейчас видны пользователю?
const VisibleReactions = computed(() => {
  //(ПОКА ЧТО НЕ СОРТИРУЕМ) Сортируем реакции по популярности: от самых частых к редким
  const ReactionsByPopularity = [...ActiveReactions.value]; //.sort((a, b) => b.count - a.count)

  // Если пользователь нажал "Показать все" — возвращаем всё
  if (ShowAllReactions.value) {
    return ReactionsByPopularity;
  }
  // Иначе — только топ-reactionLimit.value
  return ReactionsByPopularity.slice(0, reactionLimit.value);
});

/* #!! */
const reactionPlaceholder = ref('Поиск реакций...');
const handleReactionHover = (reaction) => {
  reactionPlaceholder.value = reaction.label;
};

function ClearPostAnimsHashTable() {
  Object.keys(PostAnimsHashTable.value)
    .filter(key => key.startsWith(`${props.PostId}-`))
    .forEach(key => {
      PostAnimsHashTable.value[key]?.destroy();
      delete PostAnimsHashTable.value[key];
    });
}


function ApplySearchResults(reactions) {
  if (reactions === null) {
    LoadCertainPack(SelectedPackId.value)
    return;
  } else {
    // Обновляем кэш AllLoadedElementsById
    reactions.forEach(el => {
      AllLoadedElementsById.value[el.id] = el;
    });
    SelectedPackArray.value = reactions;

    ClearPostAnimsHashTable()
    // Перерисовываем анимации
    HasRenderedDropdownReactionsBoolean.value = false;
    nextTick(RenderDropdownAnimations);
    nextTick(RenderActiveLotties);
  }

}
const clearFlash = () => {
  // Принудительно удаляем сообщение из props (Inertia позволяет это делать вручную)
  if (page.props.flash) {
    page.props.flash.message = null;
    page.props.flash.type = null;
  }
};

const searchReactionRef = ref(null);



/* #!! */



// <!-- Правая колонка: реакции -->
const SelectedPackArray = ref([]);
const SelectedPackId = ref(null);
const IsDropdownOpenBoolean = ref(false);
const ReactionPacksList = ref([]);
const ReactionPacksById = ref({});
const ReactionCountsHashTable = reactive({});
const UserReactionsSet = ref(new Set());

// Хэши анимаций с составными ключами: `${PostId}-${id}`
const DropdownAnimsHashTable = ref({});
const PostAnimsHashTable = ref({});

const HasRenderedDropdownReactionsBoolean = ref(false);
const AllLoadedElementsById = ref({});

// Утилита для генерации уникального ключа
const getAnimKey = (id) => `${props.PostId}-${id}`;

// === Загрузка данных ===
const LoadStickerPacksAndFirstPackElements = async () => {
  try {
    // Загружаем список паков (кэшируется глобально)
    const packs = await loadAllPacksIfNeeded();

    ReactionPacksList.value = packs;
    ReactionPacksById.value = packs.reduce((acc, pack) => {
      acc[pack.id] = pack;
      return acc;
    }, {});

    // Загружаем первый пак через кэш
    const firstPack = packs[0];
    if (firstPack) {
      await LoadCertainPack(firstPack.id);
    }
  } catch (error) {
    console.error('Ошибка загрузки стикеров:', error);
    page.props.flash.message = { message: 'Не удалось загрузить стикеры.', type: 'error' };
  }
};

const LoadPostReactions = async () => {
  try {
    const { data } = await axios.get(`/post/${props.PostId}/reactions`);

    // Очистка
    for (const key in ReactionCountsHashTable) {
      delete ReactionCountsHashTable[key];
    }

    // Заполняем хэштаблицы
    Object.entries(data.reactions || {}).forEach(([id, info]) => {
      const el = info.element;
      if (!el) return;

      const mapped = {
        id: el.id.toString(),
        label: el.name,
        path: `/storage/${el.file_name}`,
        sticker: el.type?.sticker || 0,
      };

      AllLoadedElementsById.value[mapped.id] = mapped;
      ReactionCountsHashTable[mapped.id] = { count: info.count };
    });

    UserReactionsSet.value = new Set(data.user_reacted_to || []);

    nextTick(RenderActiveLotties);
  } catch (error) {
    console.error('Ошибка при загрузке реакций:', error);
  }
};

// === Загрузка конкретного пака ===
const LoadCertainPack = async (packId) => {
  try {
    // Используем глобальный кэш!
    const packData = await loadPackIfNeeded(packId);

    SelectedPackId.value = packId;
    SelectedPackArray.value = packData.elements;

    // Обновляем локальный AllLoadedElementsById, чтобы не ломать остальной код
    packData.elements.forEach(el => {
      AllLoadedElementsById.value[el.id] = el;
    });

    ClearPostAnimsHashTable();
    HasRenderedDropdownReactionsBoolean.value = false;
    RenderDropdownAnimations();
  } catch (error) {
    console.error('Ошибка загрузки пака:', error);
    page.props.flash.message = { message: 'Не удалось загрузить стикерпак.', type: 'error' };
  }
};

// === Рендер анимаций ===
const RenderActiveLotties = () => {
  // Уничтожаем старые анимации текущего поста
  Object.keys(DropdownAnimsHashTable.value)
    .filter(key => key.startsWith(`${props.PostId}-`))
    .forEach(key => {
      DropdownAnimsHashTable.value[key]?.destroy();
      delete DropdownAnimsHashTable.value[key];
    });

  ActiveReactions.value.forEach(reaction => {
    const animKey = getAnimKey(reaction.id);
    const container = document.getElementById(`post-reactions-lottie-sticker-${animKey}`);

    if (!container || reaction.sticker !== 1) return;

    container.innerHTML = '';
    const anim = Lottie.loadAnimation({
      container,
      renderer: 'svg',
      loop: true,
      autoplay: true,
      path: reaction.path
    });
    DropdownAnimsHashTable.value[animKey] = anim;
  });
};

const RenderDropdownAnimations = () => {
  if (HasRenderedDropdownReactionsBoolean.value) return;

  nextTick(() => {
    SelectedPackArray.value.forEach(sticker => {
      const animKey = getAnimKey(sticker.id);
      const container = document.getElementById(`dropdown-lottie-sticker-${animKey}`);

      if (!container || PostAnimsHashTable.value[animKey]) return;
      if (sticker.sticker !== 1) return;

      const anim = Lottie.loadAnimation({
        container,
        renderer: 'svg',
        loop: true,
        autoplay: true,
        path: sticker.path
      });
      PostAnimsHashTable.value[animKey] = anim;
    });
    HasRenderedDropdownReactionsBoolean.value = true;
  });
};

// === Обработчики ===
const handleClickOutside = (event) => {
  const dropdown = document.getElementById(`reactionDropdown${props.PostId}`);
  const button = document.getElementById(`reactionDropdownButton${props.PostId}`);
  if (dropdown && !dropdown.contains(event.target) && button && !button.contains(event.target)) {
    IsDropdownOpenBoolean.value = false;
  }
};

const toggleReaction = async (reactionId) => {
  try {
    const { data } = await axios.post(
      `/post/${props.PostId}/react`,
      { reactionId },
    );

    const { removed } = data;
    // page.props.flash.message = data.message;

    if (removed) {
      const current = ReactionCountsHashTable[reactionId];
      if (current) {
        current.count -= 1;
        if (current.count <= 0) delete ReactionCountsHashTable[reactionId];
      }
      UserReactionsSet.value.delete(reactionId);
    } else {
      if (!ReactionCountsHashTable[reactionId]) {
        ReactionCountsHashTable[reactionId] = { count: 0 };
      }
      ReactionCountsHashTable[reactionId].count += 1;
      UserReactionsSet.value.add(reactionId);
    }

    nextTick(RenderActiveLotties);
  } catch (error) {
    console.error(error);
    page.props.flash.message = { message: error.response.data.message, type: 'error' };
  }
};

// === Вычисляемые свойства ===
const ActiveReactions = computed(() => {
  return Object.entries(ReactionCountsHashTable)
    .filter(([id, data]) => data?.count > 0)
    .map(([id, data]) => {
      const element = AllLoadedElementsById.value[id];
      if (!element) return null;
      return {
        ...element,
        count: data.count,
        isUser: UserReactionsSet.value.has(id)
      };
    })
    .filter(Boolean);
});

// === Watchers ===

/**
 * этот ватчер смотрит нажал ли пользователь на кнопку открытия меню реакций
 */
watch(IsDropdownOpenBoolean, (isOpen) => {
  if (isOpen) {
    // Запускаем анимации (если ещё не запущены)
    if (!HasRenderedDropdownReactionsBoolean.value) {
      LoadStickerPacksAndFirstPackElements();
      RenderDropdownAnimations();
    }

    // Фокусируем input поиска — асинхронно, после рендера
    // nextTick(() => {
    //   searchReactionRef.value?.focusInput();
    // });
  }


});
/**
 * этот ватчер смотрит чтобы после разворачивания всех реакций, lottie стикеры прогрузились снова
 */
watch(ShowAllReactions, (newVal) => {
  if (newVal) {
    nextTick(() => {
      RenderActiveLotties();
    });
  }
});

/**
 * подгружает реакции в КЕШ
 */
watch(
  () => reactionPacksCache.value,
  (newCache) => {
    // Обновляем AllLoadedElementsById всеми элементами из кэша
    const updated = { ...AllLoadedElementsById.value };
    Object.values(newCache).forEach(pack => {
      pack.elements.forEach(el => {
        updated[el.id] = el;
      });
    });
    AllLoadedElementsById.value = updated;
  },
  { immediate: true, deep: false }
);

// === Lifecycle ===
onMounted(() => {
  LoadPostReactions();
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);

  // Уничтожаем ТОЛЬКО анимации текущего поста
  Object.keys(DropdownAnimsHashTable.value)
    .filter(key => key.startsWith(`${props.PostId}-`))
    .forEach(key => DropdownAnimsHashTable.value[key]?.destroy());

  Object.keys(PostAnimsHashTable.value)
    .filter(key => key.startsWith(`${props.PostId}-`))
    .forEach(key => PostAnimsHashTable.value[key]?.destroy());
});


</script>


<template>
  <div class="flex flex-col gap-y-[0.5rem] relative items-center  pt-[0.5rem]">
    <!-- Кнопка открытия dropdown -->
    <button :id="`reactionDropdownButton${PostId}`" @click="IsDropdownOpenBoolean = !IsDropdownOpenBoolean"
      class=" flex items-center justify-center w-10 h-10  rounded-full bg-[--ReactionBtnBg] hover:bg-[--ReactionBtnHover]  hover:text-[--ReactionTextColorOnHover] transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-[--ReactionRingBg]"
      type="button" aria-haspopup="true" :aria-expanded="IsDropdownOpenBoolean" title="Добавить реакцию" :class="{
        'reaction-btn-bg-active cdx-marker transition-all duration-200 ring-[--color-4] hover:bg-[--color-4] ring-2 focus:ring-[--color-4] ': IsDropdownOpenBoolean == true,
        'reaction-btn-bg': IsDropdownOpenBoolean == false
      }">
      <ReactionSvg />
    </button>

    <div class="">
      <Transition enter-active-class="transition-all duration-200 ease-out"
        leave-active-class="transition-all duration-150 ease-in" enter-from-class="opacity-0 scale-90 translate-y-2"
        enter-to-class="opacity-100 scale-100 translate-y-0" leave-from-class="opacity-100 scale-100 translate-y-0"
        leave-to-class="opacity-0 scale-95 translate-y-1">
        <div v-show="IsDropdownOpenBoolean" @click.stop :id="`reactionDropdown${PostId}`" class="reaction-dropdown-bg  absolute  mb-2 left-1/2 transform -translate-x-1/2 
          bottom-full
          w-[13rem] h-[25rem]
          sm:w-[19rem] sm:h-[25rem]
          md:w-[27rem] md:h-[25rem]
          lg:w-[36.4rem] lg:h-[25rem]
          xl:w-[36.4rem] xl:h-[25rem]

          rounded-xl shadow-xl border border-[--ReactionBorderColor] z-20 overflow-hidden">
          <!-- #!! -->
          <div id="SearchDiv" class="px-4 pt-3 pb-2">
            <!-- <SearchReaction ref="searchReactionRef" :post-id="PostId" :placeholder="reactionPlaceholder"
              @update-reactions="ApplySearchResults" /> -->
          </div>
          <!-- #!! -->

          <!-- Фиксированная высота контейнера -->
          <div class="flex w-full  h-[calc(100%-44px)]">
            <!-- Левая колонка: паки -->
            <div class="reaction-packs-bg 
            w-[2.9rem]
            sm:w-[2.8rem]
            md:w-[3.4rem] 
            lg:w-[3.4rem]   
            
            px-1  flex-shrink-0 ">

              <div class="vertical-scroll-container overflow-y-auto h-full flex flex-col items-center pt-3  ">
                <button v-for="Pack in ReactionPacksList" :key="Pack.id" @click="() => LoadCertainPack(Pack.id) "
                  class="flex flex-col items-center justify-center 
                  w-[2.1rem]
                  sm:w-[2.2rem]
                  md:w-[2.5rem] 
                  lg:w-[2.5rem]  
                  mb-[0.45rem]
                  aspect-square rounded-md transition-all duration-150 group hover:bg-[--ReactionPackBaseHover] active:scale-95 " :class="{
                    'bg-[--ReactionGlobalBg] ring-2 ring-[--ReactionRingIfIsUser]': SelectedPackId === Pack.id,
                  }" @mouseenter="(e) => showTooltip(e, Pack.name)" @mouseleave="hideTooltip"
                  @focus="(e) => showTooltip(e, Pack.name)" @blur="hideTooltip" >


                  <!-- Если это видео -->
                  <video v-if="Pack.preview_img.endsWith('.webm')" autoplay loop muted playsinline
                    class="w-full h-full object-cover rounded-sm">
                    <source :src="'/storage/' + Pack.preview_img" type="video/webm" />
                  </video>

                  <!-- Если это обычная картинка -->
                  <img v-else :src="'/storage/' + Pack.preview_img" :alt="Pack.name"
                    class="w-full h-full object-cover rounded-sm" />

                </button>
              </div>
            </div>

            <!-- Правая колонка: реакции -->
            <div class="flex-grow ">
              <div class="vertical-scroll-container overflow-y-auto h-full w-full relative">
                <div
                  class="grid 
                  grid-cols-1 
                  sm:grid-cols-2 
                  md:grid-cols-3 
                  lg:grid-cols-4 
                  xl:grid-cols-4 
                  py-4  gap-5 lg:gap-[1.1rem]  md:gap-y-5  pe-[1rem] ps-[1rem] items-center justify-center place-content-center ">

                  <button v-for="Reaction in SelectedPackArray" :key="Reaction.id" @click="toggleReaction(Reaction.id)"
                    @mouseover="handleReactionHover(Reaction)" class="   items-center justify-center rounded-md transition-all duration-150 group hover:bg-[--ReactionGlobalBgHover] active:scale-95  
                    h-[4.6rem] w-[8rem]
                    sm:h-[5rem] sm:w-[6rem]
                    md:h-[5rem] md:w-[6.5rem]
                    lg:h-[5rem] lg:w-[6.8rem]
                    xl:h-[5rem] xl:w-[6rem]

                    reaction-base
                    " :class="{
                      'bg-[--ReactionGlobalBg] hover:bg-[--ReactionGlobalBgHover] ring-[0.067rem] ring-[--ReactionRingIfIsUser] reaction-glow-on-user ': UserReactionsSet.has(Reaction.id)
                    }" >

                    <!--#! lottie -->
                    <div v-if="Reaction.sticker == 1" class=" w-full h-full  items-center justify-center rounded-md "
                      style="overflow: hidden;" :class="{
                        'bg-[--ReactionGlobalBg] hover:bg-[--ReactionGlobalBgHover] ring-[0.067rem] ring-[--ReactionRingIfIsUser]': UserReactionsSet.has(Reaction.id)
                      }">
                      <div class="items-center justify-center  w-full h-full object-contain"
                        :id="`dropdown-lottie-sticker-${PostId}-${Reaction.id}`"></div>
                    </div>

                    <!--#! Картинки (не стикеры) -->
                    <div v-else class="w-full h-full  items-center justify-center rounded-md" :class="{
                      'bg-[--ReactionGlobalBg] hover:bg-[--ReactionGlobalBgHover] ring-[0.067rem] ring-[--ReactionRingIfIsUser]': UserReactionsSet.has(Reaction.id)
                    }">

                      <!-- Если это видео -->
                      <video v-if="Reaction.path.endsWith('.webm')" autoplay loop muted playsinline
                        class="items-center justify-center w-full h-full object-contain">
                        <source :src="Reaction.path" type="video/webm" />
                      </video>

                      <img v-else :src="Reaction.path" :alt="Reaction.label"
                        class="items-center justify-center w-full h-full object-contain" />


                    </div>


                  </button>
                </div>

                <!-- Декоративная линия внизу -->
                <div class="h-[0.1rem] bg-[--ReactionPacksBgGradientTo] mx-4 mb-[0.3rem]  "></div>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </div>

    <!-- Активные реакции -->
    <div v-if="ActiveReactions.length > 0" class="flex flex-col items-center mt-[0.11rem] w-full gap-[0.8rem]">
      <div class="flex flex-wrap justify-center gap-[0.9rem]">
        <div v-for="Reaction in VisibleReactions" :key="`active-${Reaction.id}`" @click="toggleReaction(Reaction.id)"
          class="group relative flex items-center justify-center transition-all hover:scale-105 active:scale-95 
            h-[4.6rem] w-[8rem]
            sm:h-[5rem] sm:w-[6rem]
            md:h-[5rem] md:w-[6.5rem]
            lg:h-[5rem] lg:w-[6.8rem]
            xl:h-[5.4rem] xl:w-[7rem]
          
          " >
          <!-- Lottie-стикеры -->
          <div v-if="Reaction.sticker == 1" :id="`post-reactions-lottie-sticker-${PostId}-${Reaction.id}`"
            class="w-full h-full rounded-md reaction-base" :class="{
              'bg-[--ReactionGlobalBg] hover:bg-[--ReactionGlobalBgHover] ring-[0.067rem] ring-[--ReactionRingIfIsUser] reaction-glow-on-user': UserReactionsSet.has(Reaction.id)
            }"></div>

          <!-- Картинки (не стикеры) -->
          <div v-else class="w-full h-full flex items-center justify-center rounded-md reaction-base" :class="{
            'bg-[--ReactionGlobalBg] hover:bg-[--ReactionGlobalBgHover] ring-[0.067rem] ring-[--ReactionRingIfIsUser] reaction-glow-on-user': UserReactionsSet.has(Reaction.id)
          }">


            <video v-if="Reaction.path.endsWith('.webm')" autoplay loop muted playsinline
              class="max-w-full max-h-full w-full object-contain">
              <source :src="Reaction.path" type="video/webm" />
            </video>

            <img v-else :src="Reaction.path" :alt="Reaction.label"
              class="max-w-full max-h-full w-full object-contain" />

          </div>

          <!-- Счётчик -->
          <span
            class="absolute  -bottom-1.5 left-1/2 transform -translate-x-1/2 flex items-center justify-center w-5 h-5 rounded-full text-[0.65rem] font-bold"
            :class="{
              'bg-[--ReactionCounterBgIsUser] text-black': Reaction.isUser,
              'bg-[--ReactionCounterBgIsNotUser] text-[--ReactionCounterTextIsNotUser]': !Reaction.isUser
            }">
            {{ Reaction.count }}
          </span>
        </div>
      </div>

      <!-- Кнопка "Показать ещё" / "Скрыть" -->
      <button v-if="HasMoreReactions" @click="ShowAllReactions = !ShowAllReactions" class="reaction-toggle-btn"
        :class="{ 'is-expanded': ShowAllReactions }"></button>
    </div>

    <FlashMessage v-if="page.props.flash?.message" :message="page.props.flash.message" :type="page.props.flash.type"
      @close="clearFlash" />
  </div>



  <!-- Popover для подсказок справа -->
  <Teleport to="body">
    <Transition enter-active-class="transition-opacity duration-200"
      leave-active-class="transition-opacity duration-150" enter-from-class="opacity-0" leave-to-class="opacity-0">
      <div v-if="tooltip.visible"
        class="fixed z-[9999] bg-[var(--ui-bg-base)] p-2 rounded-md  shadow-lg shadow-[ui-shadow] border border-[var(--ui-border)] max-w-xs text-sm text-[var(--TextColor)]"
        :style="{ left: tooltip.x + 'px', top: tooltip.y + 'px' }">
        <p class="font-medium font-[Roboto] text-[var(--ui-heading)]">{{ tooltip.title }}</p>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #4b5563;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #6b7280;
}

/* Скрытие скроллбара во всех браузерах */
.vertical-scroll-container {
  overflow-x: hidden;
  overflow-y: auto;
  -ms-overflow-style: none;
  /* IE/Edge */
  scrollbar-width: none;
  /* Firefox */
  overscroll-behavior-y: contain;

}

.vertical-scroll-container::-webkit-scrollbar {
  display: none;
  /* Chrome/Safari */
}
</style>