/**
 * Code editor used by the File Manager (resources/views/livewire/files/
 * file-manager.blade.php). A plain <textarea> is always rendered and works
 * on its own; Monaco is then loaded on demand from the jsDelivr CDN and
 * swapped in if it loads. Offline, or with the CDN blocked, the textarea
 * simply stays — the editor never ends up blank.
 */
const MONACO_BASE = 'https://cdn.jsdelivr.net/npm/monaco-editor@0.50.0/min/vs';

const LANGUAGES = {
    php: 'php', js: 'javascript', mjs: 'javascript', ts: 'typescript', css: 'css', scss: 'scss',
    html: 'html', htm: 'html', json: 'json', md: 'markdown', yml: 'yaml', yaml: 'yaml',
    sql: 'sql', xml: 'xml', py: 'python', sh: 'shell', vue: 'html',
};

let monacoPromise = null;

function loadMonaco() {
    if (window.monaco) {
        return Promise.resolve(window.monaco);
    }

    if (! monacoPromise) {
        monacoPromise = new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = `${MONACO_BASE}/loader.js`;
            script.onload = () => {
                window.require.config({ paths: { vs: MONACO_BASE } });
                window.require(['vs/editor/editor.main'], () => resolve(window.monaco), reject);
            };
            script.onerror = reject;
            document.head.appendChild(script);
        });

        // Let a later attempt retry (e.g. after the connection comes back).
        monacoPromise.catch(() => { monacoPromise = null; });
    }

    return monacoPromise;
}

window.portwayCodeEditor = (content, extension) => ({
    content,
    editor: null,

    init() {
        loadMonaco()
            .then((monaco) => {
                this.editor = monaco.editor.create(this.$refs.monaco, {
                    value: this.content ?? '',
                    language: LANGUAGES[(extension || '').toLowerCase()] || 'plaintext',
                    theme: document.documentElement.classList.contains('dark') ? 'vs-dark' : 'vs',
                    automaticLayout: true,
                    minimap: { enabled: false },
                    fontSize: 13,
                });

                this.editor.onDidChangeModelContent(() => {
                    this.content = this.editor.getValue();
                });

                this.$refs.textarea.classList.add('hidden');
                this.$refs.monaco.classList.remove('hidden');
            })
            .catch(() => {
                // Keep the textarea.
            });
    },

    destroy() {
        this.editor?.dispose();
    },
});
