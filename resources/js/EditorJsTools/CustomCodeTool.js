// resources/js/EditroJsTools/CustomCodeTool.js
// (Note: fix typo in folder name → "EditorJsTools")
export default class CustomCodeTool {
    static get toolbox() {
        return {
            title: 'Code',
            icon: '<svg width="800px" height="800px" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" fill="#000000" class="bi bi-code"><path d="M5.854 4.854a.5.5 0 1 0-.708-.708l-3.5 3.5a.5.5 0 0 0 0 .708l3.5 3.5a.5.5 0 0 0 .708-.708L2.707 8l3.147-3.146zm4.292 0a.5.5 0 0 1 .708-.708l3.5 3.5a.5.5 0 0 1 0 .708l-3.5 3.5a.5.5 0 0 1-.708-.708L13.293 8l-3.147-3.146z"/></svg>'
        };
    }

    constructor({ data }) {
        // ✅ Load initial data
        this._data = {
            code: typeof data?.code === 'string' ? data.code : '',
            language: this._isValidLanguage(data?.language) ? data.language : 'plaintext'
        };
    }

    _isValidLanguage(lang) {
        const valid = ['plaintext', 'php', 'javascript', 'typescript', 'xml', 'css', 'json', 'python', 'bash', 'sql', 'yaml'];
        return valid.includes(lang);
    }

    render() {
        const wrapper = document.createElement('div');
        wrapper.style.display = 'flex';
        wrapper.style.flexDirection = 'column';
        wrapper.style.gap = '8px';

        // Language select
        const select = document.createElement('select');
        const languages = [
            { value: 'plaintext', label: 'Plain Text' },
            { value: 'php', label: 'PHP' },
            { value: 'javascript', label: 'JavaScript' },
            { value: 'typescript', label: 'TypeScript' },
            { value: 'xml', label: 'HTML' },
            { value: 'css', label: 'CSS' },
            { value: 'json', label: 'JSON' },
            { value: 'python', label: 'Python' },
            { value: 'bash', label: 'Bash' },
            { value: 'sql', label: 'SQL' },
            { value: 'yaml', label: 'YAML' },
        ];

        languages.forEach(lang => {
            const option = document.createElement('option');
            option.value = lang.value;
            option.textContent = lang.label;
            // ✅ Set selected based on initial data
            if (lang.value === this._data.language) {
                option.selected = true;
            }
            select.appendChild(option);
        });

        // Textarea
        const textarea = document.createElement('textarea');
        textarea.placeholder = 'Enter code...';
        textarea.value = this._data.code; // ✅ Load initial code!
        
        textarea.classList.add('hljs')
        


        // Update internal state on input
        textarea.addEventListener('input', () => {
            this._data.code = textarea.value;
        });

        select.addEventListener('change', () => {
            this._data.language = select.value;
        });

        wrapper.appendChild(select);
        wrapper.appendChild(textarea);
        return wrapper;
    }

    save(blockContent) {
        const select = blockContent.querySelector('select');
        const textarea = blockContent.querySelector('textarea');
        return {
            code: textarea.value.trim(),
            language: select.value,
        };
    }
}