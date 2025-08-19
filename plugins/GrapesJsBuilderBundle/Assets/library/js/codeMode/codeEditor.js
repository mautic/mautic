// import ContentService from '../../../../../../../grapesjs-preset-mautic/src/content.service';
import MjmlService from 'grapesjs-preset-mautic/dist/mjml/mjml.service';
import ContentService from 'grapesjs-preset-mautic/dist/content.service';

/* -------------------------- helpers -------------------------- */

// Extract only <body> contents if a full HTML document string is provided.
// Pass-through if it's already body-only.
function extractBody(html) {
    if (typeof html !== 'string') return html;
    if (!/(<!doctype|<html|<head)/i.test(html)) return html; // already body-only
    try {
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var body = doc && doc.body ? doc.body.innerHTML : null;
        if (body != null) return body;
    } catch (e) {
        // fall through to regex strip
    }
    return html
        .replace(/<head[\s\S]*?<\/head>/i, '')
        .replace(/<\/?html[^>]*>/gi, '');
}

function discardMsg() {
    var key = 'mautic.core.discard_changes';
    try {
        var t = Mautic.translate(key);
        if (t && typeof t === 'string' && t !== key) return t;
    } catch (_) { }
    return 'Discard unsaved changes?';
}

// Remove truly empty <p> (only whitespace/&nbsp;/<br>) but KEEP any <p> with attributes.
function sanitizeBodyHtml(html) {
    if (typeof html !== 'string') return html;

    var doc = null;
    try {
        doc = new DOMParser().parseFromString(
            /<html|<head|<!doctype/i.test(html) ? html : '<body>' + html + '</body>',
            'text/html'
        );
    } catch (_) {
        doc = null;
    }

    if (!doc || !doc.body) {
        var container = document.createElement('div');
        container.innerHTML = html;
        pruneEmptyPsIn(container);
        return container.innerHTML;
    }

    pruneEmptyPsIn(doc.body);
    // Remove empty <style> tags inside body
    var styles = Array.prototype.slice.call(doc.body.querySelectorAll('style'));
    for (var i = 0; i < styles.length; i++) {
        var s = styles[i];
        var css = (s.textContent || '').trim();
        if (css === '') s.parentNode && s.parentNode.removeChild(s);
    }

    return doc.body.innerHTML;

    function pruneEmptyPsIn(root) {
        var ps = Array.prototype.slice.call(root.querySelectorAll('p'));
        for (var i = 0; i < ps.length; i++) {
            var p = ps[i];
            if (!p || p.nodeType !== 1) continue; // 1 = ELEMENT_NODE
            // Keep <p> with any attributes (classes/ids/hooks)
            if (p.attributes && p.attributes.length > 0) continue;
            if (isTrulyEmptyP(p)) p.parentNode && p.parentNode.removeChild(p);
        }
    }

    function isTrulyEmptyP(p) {
        var children = Array.prototype.slice.call(p.childNodes || []);
        if (children.length === 0) return true;

        for (var i = 0; i < children.length; i++) {
            var n = children[i];
            // 1 = element, 3 = text
            if (n.nodeType === 1) {
                var tag = (n.tagName || '').toUpperCase();
                if (tag !== 'BR') return false; // any non-BR element makes it non-empty
            } else if (n.nodeType === 3) {
                var txt = (n.nodeValue || '').replace(/\u00a0/g, ' ').trim();
                if (txt !== '') return false;
            } else {
                // other node types: treat as content
                return false;
            }
        }
        return true;
    }
}

/* ------------------------------------------------------------- */

class CodeEditor {
    constructor(editor, opts) {
        if (opts === void 0) opts = {};
        this.editor = editor;
        this.opts = opts;

        this.codeEditor = this.buildCodeEditor();
        this.codePopup = this.buildCodePopup();

        // refs / state
        this.textarea = null;
        this.initialContent = '';
        this.dirty = false;

        // listeners
        this._onDocMouseDown = null;
        this._onDocClick = null;
        this._onKeydown = null;
        this._onDocClickCloseBtn = null;
    }

    // Build codeEditor (CodeMirror instance)
    buildCodeEditor() {
        var codeEditor = this.editor.CodeManager.getViewer('CodeMirror').clone();

        codeEditor.set({
            codeName: 'htmlmixed',
            readOnly: false,
            theme: 'hopscotch',
            autoBeautify: true,
            autoCloseTags: true,
            autoCloseBrackets: true,
            lineWrapping: true,
            styleActiveLine: true,
            smartIndent: true,
            indentWithTabs: true,
        });

        return codeEditor;
    }

    // Build popup content, codeEditor area and buttons
    buildCodePopup() {
        var cfg = this.editor.getConfig();

        var codePopup = document.createElement('div');
        var btnEdit = document.createElement('button');
        var btnCancel = document.createElement('button');
        var textarea = document.createElement('textarea');

        btnEdit.innerHTML = Mautic.translate('grapesjsbuilder.sourceEditBtnLabel');
        btnEdit.className = cfg.stylePrefix + 'btn-prim ' + cfg.stylePrefix + 'btn-code-edit';
        btnEdit.onclick = this.updateCode.bind(this);

        btnCancel.innerHTML = Mautic.translate('grapesjsbuilder.sourceCancelBtnLabel');
        btnCancel.className = cfg.stylePrefix + 'btn-prim ' + cfg.stylePrefix + 'btn-code-cancel';
        btnCancel.onclick = this.cancelCode.bind(this);

        codePopup.appendChild(textarea);
        codePopup.appendChild(btnEdit);
        codePopup.appendChild(btnCancel);

        this.codeEditor.init(textarea);
        this.textarea = textarea;

        // Track dirtiness (fallback for when CodeMirror isn't fully wired)
        var self = this;
        this.textarea.addEventListener('input', function () {
            self.dirty = self.isDirty();
        });

        return codePopup;
    }

    // Compare current editor value to what we loaded initially
    isDirty() {
        try {
            var current = (this.codeEditor && this.codeEditor.editor)
                ? String(this.codeEditor.editor.getValue() || '')
                : (this.textarea ? String(this.textarea.value || '') : '');
            return current !== String(this.initialContent || '');
        } catch (e) {
            return false;
        }
    }

    // Centralized close with confirm when dirty
    tryClose() {
        // Recompute on demand
        this.dirty = this.isDirty();
        var msg = discardMsg();
        if (!this.dirty || window.confirm(msg)) {
            this.dirty = false;
            this.cleanupModalGuards();
            this.editor.Modal.close();
        }
        // else: keep open
    }

    // Load content and show popup
    showCodePopup(editor) {
        this.updateEditorContents();         // sets editor content
        this.initialContent = (this.codeEditor && this.codeEditor.editor)
            ? String(this.codeEditor.editor.getValue() || '')
            : (this.textarea ? String(this.textarea.value || '') : '');
        this.dirty = false;

        editor.Modal.setContent(this.codePopup);
        editor.Modal.setTitle(Mautic.translate('grapesjsbuilder.sourceEditModalTitle'));
        editor.Modal.open();

        // Attach guards after DOM is in place (next tick to ensure modal markup is rendered)
        var self = this;
        setTimeout(function () {
            self.attachModalGuards();
        }, 0);

        editor.Modal.onceClose(function () {
            self.cleanupModalGuards();
            editor.stopCommand('preset-mautic:code-edit');
        });
    }

    // Intercept ALL close paths in capture phase and route through tryClose()
    attachModalGuards() {
        var self = this;

        function insideDialog(node) {
            var dialog = document.querySelector('.gjs-mdl-dialog, .gjs-mdl-content');
            var container = dialog || self.codePopup;
            return container ? container.contains(node) : false;
        }

        // 1) Outside click (mousedown + click): stop first, then confirm
        this._onDocMouseDown = function (e) {
            if (!insideDialog(e.target)) {
                if (e.stopImmediatePropagation) e.stopImmediatePropagation();
                e.stopPropagation(); e.preventDefault();
            }
        };
        this._onDocClick = function (e) {
            if (!insideDialog(e.target)) {
                if (e.stopImmediatePropagation) e.stopImmediatePropagation();
                e.stopPropagation(); e.preventDefault();
                self.tryClose();
            }
        };

        // 2) Esc key: stop first, then confirm
        this._onKeydown = function (e) {
            if (e.key === 'Escape') {
                if (e.stopImmediatePropagation) e.stopImmediatePropagation();
                e.preventDefault(); e.stopPropagation();
                self.tryClose();
            }
        };

        // 3) X header button (common selectors across GrapesJS versions)
        this._onDocClickCloseBtn = function (e) {
            var target = e.target;
            var btn = target && (target.closest
                ? target.closest('.gjs-mdl-btn-close, .gjs-mdl-close, .gjs-btn--close, .gjs-mdl .gjs-btn')
                : null);
            // If it looks like the modal header close button (or generic modal button)
            if (btn && !self.codePopup.contains(btn)) {
                if (e.stopImmediatePropagation) e.stopImmediatePropagation();
                e.preventDefault(); e.stopPropagation();
                self.tryClose();
            }
        };

        // Attach in capture phase so we beat GrapesJS’ own handlers
        document.addEventListener('mousedown', this._onDocMouseDown, true);
        document.addEventListener('click', this._onDocClick, true);
        document.addEventListener('keydown', this._onKeydown, true);
        document.addEventListener('click', this._onDocClickCloseBtn, true);
    }

    cleanupModalGuards() {
        if (this._onDocMouseDown) {
            document.removeEventListener('mousedown', this._onDocMouseDown, true);
            this._onDocMouseDown = null;
        }
        if (this._onDocClick) {
            document.removeEventListener('click', this._onDocClick, true);
            this._onDocClick = null;
        }
        if (this._onKeydown) {
            document.removeEventListener('keydown', this._onKeydown, true);
            this._onKeydown = null;
        }
        if (this._onDocClickCloseBtn) {
            document.removeEventListener('click', this._onDocClickCloseBtn, true);
            this._onDocClickCloseBtn = null;
        }
    }

    /**
     * Update the main editor's canvas content with the
     * content from the modal's editor.
     * - Non-MJML: extract <body>, sanitize (remove empty <p>), reset wrapper, setComponents({clear:true})
     * - MJML: validate and set MJML, then reinitialize parsed content (per existing workaround), using clear
     */
    updateCode() {
        var raw = this.codeEditor.editor.getValue();

        // If MJML mode, validate input; otherwise operate on HTML
        if (ContentService.isMjmlMode(this.editor)) {
            try {
                MjmlService.mjmlToHtml(raw); // throws on invalid
            } catch (e) {
                window.alert(Mautic.translate('grapesjsbuilder.sourceSyntaxError') + ' \n' + e.message);
                return;
            }
        }

        try {
            var wrapper = (this.editor && this.editor.DomComponents && this.editor.DomComponents.getWrapper)
                ? this.editor.DomComponents.getWrapper()
                : null;
            if (wrapper && wrapper.components) {
                // ensure we don't accumulate on repeated saves
                wrapper.components().reset();
            }

            if (ContentService.isMjmlMode(this.editor)) {
                // 1) apply MJML string
                this.editor.setComponents((raw || '').trim(), { clear: true });

                // 2) reinitialize the content after parsing MJML (existing workaround)
                var parsedContent = MjmlService.getEditorMjmlContent(this.editor);
                this.editor.setComponents(parsedContent, { clear: true });
            } else {
                // HTML mode: extract body, sanitize empty <p>, then apply
                var bodyOnly = extractBody(raw || '');
                var cleaned = sanitizeBodyHtml(bodyOnly);
                this.editor.setComponents(cleaned, { clear: true });
            }

            // Saved successfully — reset dirty baseline and close without prompt
            this.initialContent = (this.codeEditor && this.codeEditor.editor)
                ? String(this.codeEditor.editor.getValue() || '')
                : (this.textarea ? String(this.textarea.value || '') : '');
            this.dirty = false;

            this.cleanupModalGuards();
            this.editor.Modal.close();
        } catch (e) {
            window.alert(Mautic.translate('grapesjsbuilder.sourceSyntaxError') + ' \n' + e.message);
        }
    }

    // Close popup (always route through central confirm path)
    cancelCode() {
        this.tryClose();
    }

    /**
     * Set the content to be edited in the popup editor
     */
    updateEditorContents() {
        var content;
        if (ContentService.isMjmlMode(this.editor)) {
            content = MjmlService.getEditorMjmlContent(this.editor);
        } else {
            content = ContentService.getEditorHtmlContent(this.editor);
        }
        this.codeEditor.setContent(content || '');
        if (this.textarea) this.textarea.value = content || '';
    }
}

export default CodeEditor;
