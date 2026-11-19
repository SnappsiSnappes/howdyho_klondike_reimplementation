<!-- List.vue -->
<script setup>
import { computed } from 'vue';

const props = defineProps({
  BlockData: { type: Object, required: true },
  depth: { type: Number, default: 0 }
});

// Функция декодирования HTML сущностей
const decodeHtmlEntities = (html) => {
  if (!html || typeof html !== 'string') return '';
  const textarea = document.createElement('textarea');
  textarea.innerHTML = html;
  return textarea.value;
};

// Новая функция очистки, которая НЕ удаляет картинки и классы реакций
const cleanContent = (html) => {
  const decoded = decodeHtmlEntities(html);
  
  // Создаем временный контейнер для парсинга
  const tempDiv = document.createElement('div');
  tempDiv.innerHTML = decoded;

  // Находим все потенциально опасные теги, но СОХРАНЯЕМ img и span с реакциями
  // Мы удаляем только скрипты и события, но оставляем структуру для реакций
  const allElements = tempDiv.querySelectorAll('*');
  
  allElements.forEach(el => {
    const tagName = el.tagName.toLowerCase();
    
    // Разрешаем img, span (для реакций), br, a, b, strong, i, em, u, code
    const allowedTags = ['img', 'span', 'br', 'a', 'b', 'strong', 'i', 'em', 'u', 'code', 's', 'mark'];
    
    if (!allowedTags.includes(tagName)) {
      // Если тег не из списка разрешенных и это не стандартный форматтер текста - можно удалить или оставить контент
      // Но для реакций критично оставить span и img
      if (tagName === 'script' || tagName === 'iframe' || tagName === 'object') {
        el.remove();
      }
    }



    if (tagName === 'span' && el.classList.contains('InlineRender')) {
      // Это реакция, ничего не трогаем, просто убеждаемся, что она видимая
      el.style.display = 'inline-block';
      el.style.verticalAlign = 'middle';
    }
  });

  return tempDiv.innerHTML;
};
</script>

<template>
  <!-- Чек-лист -->
  <ul v-if="BlockData.style === 'checklist'" class="list-checklist" :class="{ 'list-nested': depth > 0 }">
    <li v-for="(item, idx) in BlockData.items" :key="idx" class="list-item" :data-checked="!!item.meta?.checked">
      <span v-html="cleanContent(item.content)"></span>
      <List v-if="item.items?.length" :BlockData="{ style: 'checklist', items: item.items }" :depth="depth + 1" />
    </li>
  </ul>

  <!-- Маркированный -->
  <ul v-else-if="BlockData.style === 'unordered'" class="list-unordered" :class="{ 'list-nested': depth > 0 }">
    <li v-for="(item, idx) in BlockData.items" :key="idx" class="list-item">
      <span v-html="cleanContent(item.content)"></span>
      <List v-if="item.items?.length" :BlockData="{ style: 'unordered', items: item.items }" :depth="depth + 1" />
    </li>
  </ul>

  <!-- Нумерованный -->
  <ol v-else class="list-ordered" :class="{ 'list-nested': depth > 0 }">
    <li v-for="(item, idx) in BlockData.items" :key="idx" class="list-item">
      <span v-html="cleanContent(item.content)"></span>
      <List v-if="item.items?.length" :BlockData="{ style: 'ordered', items: item.items }" :depth="depth + 1" />
    </li>
  </ol>
</template>

<style scoped>
/* Ваши стили остаются без изменений */
.list-item {
  @apply break-words;
  position: relative;
  padding: 0.5rem 0;
  min-height: 2.5rem;
  color: var(--TextColor);
  transition: color 0.075s;
  break-words: normal;
  hyphens: auto;
  padding-left: 1.5rem;
}

.list-item:hover {
  color: var(--secondary-light);
}



.list-nested {
  margin-left: 1.5rem;
}

/* 🔘 Маркированный список */
.list-unordered>.list-item::before {
  content: "";
  position: absolute;
  left: 0;
  top: 1rem;
  width: 0.375rem;
  height: 0.375rem;
  border-radius: 50%;
  background-color: var(--secondary);
}

/* 🔢 Нумерованный список */
.list-ordered {
  counter-reset: item;
  list-style: none;
  padding-left: 0;
}

.list-ordered>.list-item {
  counter-increment: item;
}

.list-ordered>.list-item::before {
  content: counters(item, ".") ".";
  position: absolute;
  left: -1.2rem;
  top: 0.5rem;
  font-weight: 600;
  font-size: 0.875rem;
  color: var(--secondary);
  background-color: var(--ui-bg-hover);
  padding: 0.125rem 0.5rem;
  border-radius: 0.25rem;
}

/* ✅ Чек-лист */
.list-checklist {
  list-style: none;
  padding-left: 0;
}

.list-checklist>.list-item {
  padding-left: 2rem;
}

.list-checklist>.list-item::before {
  content: "";
  position: absolute;
  left: 0;
  top: 0.5rem;
  width: 1.25rem;
  height: 1.25rem;
  border: 1px solid var(--ui-border);
  border-radius: 0.25rem;
  background-color: var(--ui-bg-base);
  display: flex;
  align-items: center;
  justify-content: center;
}

.list-checklist>.list-item[data-checked="true"]::before {
  background-color: var(--secondary);
  border-color: var(--secondary);
  content: "";
}

.list-checklist>.list-item[data-checked="true"]::after {
  content: "";
  position: absolute;
  left: 0.375rem;
  top: 0.875rem;
  width: 0.375rem;
  height: 0.125rem;
  border: 2px solid white;
  border-top: none;
  border-right: none;
  transform: rotate(-45deg);
}
</style>