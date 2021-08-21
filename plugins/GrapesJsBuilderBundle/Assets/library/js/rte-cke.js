export default class RteCke {
  editor;

  rte;

  constructor(editor, ckeditor) {
    if (!editor) {
      throw Error('No editor found');
    }
    if (!ckeditor) {
      throw Error('No ckeditor found');
    }

    this.editor = editor;

    // fixes issue to edit buttons: https://github.com/artf/grapesjs/issues/1338
    ckeditor.dtd.$editable.a = 1;
    this.rte = ckeditor;
  }

  /**
   * Enabling the custom RTE
   * @param  {HTMLElement} el This is the HTML node which was selected to be edited
   * @param  {Object} rte It's the instance you'd return from the first call of enable().
   *                      At the first call it'd be undefined. This is useful when you need
   *                      to check if the RTE is already enabled on the component
   * @return {Object} The return should be the RTE initialized instance
   */
  enable(el, rte) {
    // If already exists just focus
    if (rte) {
      this.focus(el, rte); // implemented later
      return rte;
    }

    // CKEditor initialization
    const rteInline = this.rte.inline(el, {
      // Your configurations...
      toolbar: [
        {
          name: 'clipboard',
          items: ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo'],
        },
        { name: 'editing', items: ['Scayt'] },
        { name: 'links', items: ['Link', 'Unlink', 'Anchor'] },
        { name: 'insert', items: ['Image', 'Table', 'HorizontalRule', 'SpecialChar'] },
        { name: 'tools', items: ['Maximize'] },
        { name: 'document', items: ['Source'] },
        '/',
        { name: 'basicstyles', items: ['Bold', 'Italic', 'Strike', '-', 'RemoveFormat'] },
        {
          name: 'paragraph',
          items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote'],
        },
        { name: 'styles', items: ['Styles', 'Format'] },
      ],
      // IMPORTANT
      // Generally, inline editors are attached exactly at the same position of
      // the selected element but in this case it'd work until you start to scroll
      // the canvas. For this reason you have to move the RTE's toolbar inside the
      // one from GrapesJS. For this purpose we used a plugin which simplify
      // this process and move all next CKEditor's toolbars inside our indicated
      // element
      sharedSpaces: {
        top: this.editor.RichTextEditor.getToolbarEl(),
      },
    });

    this.focus(el, rteInline); // implemented later
    return rteInline;
  }

  // eslint-disable-next-line class-methods-use-this
  disable(el, rte) {
    el.contentEditable = false;
    if (rte && rte.focusManager) {
      rte.focusManager.blur(true);
    }
  }

  // eslint-disable-next-line class-methods-use-this
  focus(el, rte) {
    // Do nothing if already focused
    if (rte && rte.focusManager.hasFocus) {
      return;
    }
    el.contentEditable = true;
    rte && rte.focus();
  }
}
