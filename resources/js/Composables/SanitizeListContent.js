import DOMPurify from 'dompurify';

export const sanitizeListContent = (html) => {
  if (!html || typeof html !== 'string') return '';
  
  // Запрещаем span, разрешаем безопасные теги
  return DOMPurify.sanitize(html, {
    ALLOWED_TAGS: ['a', 'strong', 'b', 'em', 'i', 'u', 'code', 'br', 'p'],
    ALLOWED_ATTR: ['href', 'target', 'rel', 'title'],
    FORBID_TAGS: ['span', 'script', 'style']
  });
};