// resources/js/EditorJsTools/SuccessTool.js

/**
 * Success Tool for Editor.js
 * Uses input for title and textarea for message inside the editor.
 */
export default class SuccessTool {
  /**
   * Get Tool toolbox settings
   */
  static get toolbox() {
    return {
      title: 'Success',
      icon: '<svg xmlns="http://www.w3.org/2000/svg" id="Outline" viewBox="0 0 24 24" width="17" height="17"><path d="M22.314,4.686a2.992,2.992,0,0,0-4.242,0L9.814,12.945l-4.2-4.2a2.992,2.992,0,0,0-4.242,0,2.992,2.992,0,0,0,0,4.242l6.32,6.32a2.935,2.935,0,0,0,4.242,0L22.314,8.928A2.992,2.992,0,0,0,22.314,4.686Z"/></svg>'
    };
  }

  /**
   * Read-only mode is supported
   * @returns {boolean}
   */
  static get isReadOnlySupported() {
    return true; // Указываем, что поддерживаем режим только для чтения
  }

  /**
   * Default placeholder for success title
   */
  static get DEFAULT_TITLE_PLACEHOLDER() {
    return 'Success Title';
  }

  /**
   * Default placeholder for success message
   */
  static get DEFAULT_MESSAGE_PLACEHOLDER() {
    return 'Success message';
  }

  /**
   * Constructor for Success Tool
   */
  constructor({ data, config, api, readOnly }) {
    this.api = api;
    this.readOnly = readOnly;

    this.CSS = {
      baseClass: this.api.styles.block,
      wrapper: 'ce-success',
      title: 'ce-success__title',
      message: 'ce-success__message',
      input: this.api.styles.input, // Стили от Editor.js для input/textarea
    };

    this.titlePlaceholder = config?.titlePlaceholder || SuccessTool.DEFAULT_TITLE_PLACEHOLDER;
    this.messagePlaceholder = config?.messagePlaceholder || SuccessTool.DEFAULT_MESSAGE_PLACEHOLDER;

    // Сохраняем данные
    this.data = {
      title: data?.title ?? '',
      message: data?.message ?? '',
    };
  }

  /**
   * Create Success Tool container with input and textarea
   */
  render() {
    const wrapper = document.createElement('div');
    wrapper.classList.add(this.CSS.baseClass, this.CSS.wrapper);

    // Input for title
    this.titleInput = document.createElement('input');
    this.titleInput.classList.add(this.CSS.input, this.CSS.title);
    this.titleInput.placeholder = this.titlePlaceholder;
    this.titleInput.value = this.data.title; // Устанавливаем начальное значение
    this.titleInput.disabled = this.readOnly; // Блокируем в режиме только для чтения

    // Textarea for message
    this.messageTextarea = document.createElement('textarea');
    this.messageTextarea.classList.add(this.CSS.input, this.CSS.message);
    this.messageTextarea.placeholder = this.messagePlaceholder;
    this.messageTextarea.value = this.data.message; // Устанавливаем начальное значение
    this.messageTextarea.disabled = this.readOnly; // Блокируем в режиме только для чтения

    // Обновляем внутренние данные при изменении
    this.titleInput.addEventListener('input', () => {
      this.data.title = this.titleInput.value;
    });

    this.messageTextarea.addEventListener('input', () => {
      this.data.message = this.messageTextarea.value;
    });

    wrapper.appendChild(this.titleInput);
    wrapper.appendChild(this.messageTextarea);

    return wrapper;
  }

  /**
   * Extract Success data from Success Tool element
   */
  save(blockContent) {
    // Возвращаем данные из input и textarea
    return {
      title: this.titleInput.value.trim(), // Используем сохранённые ссылки на элементы
      message: this.messageTextarea.value.trim(),
    };
  }

  /**
   * Sanitizer config for Success Tool saved data
   */
  static get sanitize() {
    return {
      title: true, // Очищаем title от HTML
      message: true, // Очищаем message от HTML
    };
  }
}