export default (editor, opts = {}) => {
    let ckEditorInstance = null;

    const SIMPLE_EDITING_TYPES = ['mj-button', 'link'];

    editor.on('rte:enable', (view, gjsRte) => {
        if (!isSimpleEditingEl(view.el)) {
            openCKEditorModal(gjsRte.el, view);
            editor.RichTextEditor.hideToolbar();
        }
    });

    function isSimpleEditingEl(el) {
        return el && el.getAttribute && SIMPLE_EDITING_TYPES.includes(el.getAttribute('data-gjs-type'));
    }

    function openCKEditorModal(el, view) {
        const ckEditorElementId = `ckeditor-${Date.now()}`;
        const modal = editor.Modal;

        modal.onceOpen(() => initCKEditor(ckEditorElementId));
        modal.onceClose(() => destroyCKEditor());
        modal.config.backdrop = false;
        modal.open({
            title: 'Edit',
            content: `
                <div id="${ckEditorElementId}">${el.innerHTML}</div>
                <button type="button" class="gjs-btn-prim" id="gjs-cke-save-btn">Save</button>
                <button type="button" class="gjs-btn-prim" id="gjs-cke-close-btn">Cancel</button>
            `,
            attributes: {
                class: 'cke-modal'
            }
        });

        const { backgroundColor, color } = getRealColors(el);
        setEditorStyle(color, backgroundColor);

        document.getElementById('gjs-cke-save-btn').onclick = () => saveContent(view, modal);
        document.getElementById('gjs-cke-close-btn').onclick = () => modal.close();
    }

    function initCKEditor(elementId) {
        if (typeof ClassicEditor === 'undefined') {
            throw new Error('CKEDITOR instance not found');
        }
        ClassicEditor.create(document.getElementById(elementId), opts)
            .then(instance => {
                ckEditorInstance = instance;
            })
            .catch(error => {
                console.error('Error initializing CKEditor:', error);
            });
    }

    function destroyCKEditor() {
        if (ckEditorInstance) {
            ckEditorInstance.destroy()
                .catch(error => {
                    console.error('Error destroying CKEditor instance:', error);
                });
        }
    }

    function saveContent(view, modal) {
        if (ckEditorInstance) {
            const content = ckEditorInstance.getData();
            const selectedElement = view.model;
            const currentContent = selectedElement.get('content');
            if (currentContent !== content) {
                // Clear existing components to avoid conflicts
                selectedElement.components('');
                // Set the new content
                selectedElement.set('content', content);
            }
        }
        modal.close();
    }

    function setEditorStyle(color, backgroundColor) {
        const STYLE_ID = 'gjs-ckeditor-styles';
        let styleElement = document.getElementById(STYLE_ID);

        if (!styleElement) {
            styleElement = document.createElement('style');
            styleElement.id = STYLE_ID;
            document.head.appendChild(styleElement);
        }

        styleElement.innerHTML = `
            .cke-modal .ck-editor .ck-content {
                background: ${backgroundColor};
                color: ${color};
            }
        `;
    }

    function getRealColors(elem, maxDepth = 100) {
        const transparent = ['rgba(0, 0, 0, 0)', 'transparent'];
        const defaults = { backgroundColor: 'rgba(0, 0, 0, 0)', color: 'rgb(0, 0, 0)' };

        function getColors(el, depth) {
            if (!el || depth <= 0) return defaults;
            try {
                const style = getComputedStyle(el);
                const bg = style.backgroundColor;
                const color = style.color;
                const result = {};

                if (!transparent.includes(bg)) result.backgroundColor = bg;
                if (color && !transparent.includes(color)) result.color = color;

                if (result.backgroundColor && result.color) return result;

                const parentColors = getColors(el.parentElement, depth - 1);
                return {
                    backgroundColor: result.backgroundColor || parentColors.backgroundColor,
                    color: result.color || parentColors.color
                };
            } catch (error) {
                console.warn('Error computing colors:', error);
                return defaults;
            }
        }

        return getColors(elem, maxDepth);
    }

};
