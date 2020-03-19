import loadButtons from './buttons';
import loadBlocks from './blocks';

export default (editor, opts = {}) => {
    const am = editor.AssetManager;
    const pm = editor.Panels;
    const cfg = editor.getConfig();

    let config = {
        sourceEdit: 1,
        sourceEditBtnLabel: 'Edit',
        sourceEditModalTitle: 'Edit code',
        deleteAssetConfirmText: 'Are you sure?',
        showLayersManager: 0,
        showImportButton: 0,
        mjmlTemplate: false,
        replaceRteWithFroala: true,
        ...opts,
    };

    // Check if MJML plugin is on
    if (cfg.plugins.includes('grapesjs-mjml')) {
        config.mjmlTemplate = true;
    }

    // Extend the original `image` and add a confirm dialog before removing it
    am.addType('image', {
        // As you adding on top of an already defined type you can avoid indicating
        // `am.getType('image').view.extend({...` the editor will do it by default
        // but you can eventually extend some other type
        view: {
            // If you want to see more methods to extend check out
            // https://github.com/artf/grapesjs/blob/dev/src/asset_manager/view/AssetImageView.js
            onRemove(e) {
                e.stopImmediatePropagation();
                const model = this.model;

                if (confirm(config.deleteAssetConfirmText)) {
                    model.collection.remove(model);
                }
            }
        },
    });

    if (config.replaceRteWithFroala && typeof mQuery.FroalaEditor !== 'undefined') {
        // Hiding other toolbars already created
        let rteToolbar = editor.RichTextEditor.getToolbarEl();
        [].forEach.call(rteToolbar.children, (child) => {
            child.style.display = 'none';
        });

        editor.setCustomRte({
            enable: function(el, rte) {
                rte = mQuery(el).froalaEditor({
                    enter: mQuery.FroalaEditor.ENTER_BR,
                    pastePlain: true,

                    htmlAllowedTags: ['a', 'abbr', 'address', 'area', 'article', 'aside', 'audio', 'b', 'base', 'bdi', 'bdo', 'blockquote', 'br', 'button', 'canvas', 'caption', 'cite', 'code', 'col', 'colgroup', 'datalist', 'dd', 'del', 'details', 'dfn', 'dialog', 'div', 'dl', 'dt', 'em', 'embed', 'fieldset', 'figcaption', 'figure', 'footer', 'form', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'header', 'hgroup', 'hr', 'i', 'iframe', 'img', 'input', 'ins', 'kbd', 'keygen', 'label', 'legend', 'li', 'link', 'main', 'map', 'mark', 'menu', 'menuitem', 'meter', 'nav', 'noscript', 'object', 'ol', 'optgroup', 'option', 'output', 'p', 'param', 'pre', 'progress', 'queue', 'rp', 'rt', 'ruby', 's', 'samp', 'script', 'style', 'section', 'select', 'small', 'source', 'span', 'strike', 'strong', 'sub', 'summary', 'sup', 'table', 'tbody', 'td', 'textarea', 'tfoot', 'th', 'thead', 'time', 'title', 'tr', 'track', 'u', 'ul', 'var', 'video', 'wbr', 'center'],
                    htmlAllowedAttrs: ['data-atwho-at-query', 'data-section', 'data-section-wrapper', 'accept', 'accept-charset', 'accesskey', 'action', 'align', 'allowfullscreen', 'alt', 'async', 'autocomplete', 'autofocus', 'autoplay', 'autosave', 'background', 'bgcolor', 'border', 'charset', 'cellpadding', 'cellspacing', 'checked', 'cite', 'class', 'color', 'cols', 'colspan', 'content', 'contenteditable', 'contextmenu', 'controls', 'coords', 'data', 'data-.*', 'datetime', 'default', 'defer', 'dir', 'dirname', 'disabled', 'download', 'draggable', 'dropzone', 'enctype', 'for', 'form', 'formaction', 'frameborder', 'headers', 'height', 'hidden', 'high', 'href', 'hreflang', 'http-equiv', 'icon', 'id', 'ismap', 'itemprop', 'keytype', 'kind', 'label', 'lang', 'language', 'list', 'loop', 'low', 'max', 'maxlength', 'media', 'method', 'min', 'mozallowfullscreen', 'multiple', 'name', 'novalidate', 'open', 'optimum', 'pattern', 'ping', 'placeholder', 'poster', 'preload', 'pubdate', 'radiogroup', 'readonly', 'rel', 'required', 'reversed', 'rows', 'rowspan', 'sandbox', 'scope', 'scoped', 'scrolling', 'seamless', 'selected', 'shape', 'size', 'sizes', 'span', 'src', 'srcdoc', 'srclang', 'srcset', 'start', 'step', 'summary', 'spellcheck', 'style', 'tabindex', 'target', 'title', 'type', 'translate', 'usemap', 'value', 'valign', 'webkitallowfullscreen', 'width', 'wrap'],

                    toolbarButtons: ['bold', 'italic', 'underline', 'strikeThrough', 'quote', 'clearFormatting', '-', 'formatOL', 'formatUL', 'indent', 'outdent', 'token', 'insertLink'],

                    toolbarContainer: editor.RichTextEditor.getToolbarEl(),
                    linkEditButtons: ['linkOpen', 'linkRemove'],
                });

                mQuery(el).on('froalaEditor.popups.show.link.edit', function (e, editor) {
                    // Get the link DOM object of the current selection.
                    let currentLink = mQuery(el).froalaEditor('link.get');

                    // Get popup link.edit
                    let popupLink = mQuery(el).froalaEditor('popups.get', 'link.edit');

                    if (typeof currentLink !== 'undefined') {
                        let top = currentLink.getBoundingClientRect().top;
                        let height = mQuery(currentLink).outerHeight();

                        // Set position of popup
                        popupLink.css('top', parseInt(top) + parseInt(height) + 35);
                    }
                });

                return rte;
            },
            disable: function(el, rte) {
                // Remove events and destroy editor
                mQuery(el).off('froalaEditor.popups.show.link.edit');
                mQuery(el).froalaEditor('destroy');
            },
        });
    }

    editor.on('load', function() {
        let $ = grapesjs.$;

        // Load and show settings and style manager
        let openTmBtn = pm.getButton('views', 'open-tm');
        openTmBtn && openTmBtn.set('active', 1);
        let openSm = pm.getButton('views', 'open-sm');
        openSm && openSm.set('active', 1);

        pm.removeButton("views", "open-tm");

        // Add Settings Sector
        let traitsSector = $('<div class="gjs-sm-sector no-select">'+
            '<div class="gjs-sm-title"><span class="icon-settings fa fa-cog"></span> Settings</div>' +
            '<div class="gjs-sm-properties" style="display: none;"></div></div>');
        let traitsProps = traitsSector.find('.gjs-sm-properties');

        traitsProps.append($('.gjs-trt-traits'));
        $('.gjs-sm-sectors').before(traitsSector);
        traitsSector.find('.gjs-sm-title').on('click', function(){
            let traitStyle = traitsProps.get(0).style;
            let hidden = traitStyle.display === 'none';

            if (hidden) {
                traitStyle.display = 'block';
            } else {
                traitStyle.display = 'none';
            }
        });

        // Open settings
        traitsProps.get(0).style.display = 'block';

        // Open block manager
        let openBlocksBtn = editor.Panels.getButton('views', 'open-blocks');
        openBlocksBtn && openBlocksBtn.set('active', 1);
    });

    loadButtons(editor, config);
    loadBlocks(editor, config);
};
