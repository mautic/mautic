import DynamicContentService from '../dynymicContent/dynamicContent.service';

export default class EditorCommands {
  closeEditorPageHtml(editor) {
    if (!editor) {
      throw new Error('no page-html editor');
    }

    // restore the original <head> if set
    // @todo
    // if (this.head && this.head.innerHTML) {
    //   // const fullHtml = parser.parseFromString(this.canvasContent, 'text/html');
    //   // const fullHtml = editor.getHtml();
    //   fullHtml.head.innerHTML = this.head.innerHTML;
    // }

    DynamicContentService.grapesConvertDynamicContentSlotsToTokens(editor);

    // Combine editor styles and editor html and save it to Mautic textarea
    // This part is different from other modes
    const fullHtml = `${editor.getHtml()}<style>${editor.getCss({
      avoidProtected: true,
    })}</style>`;
    mQuery('textarea.builder-html').val(fullHtml.documentElement.outerHTML);

    // Reset HTML
    this.resetHtml(editor);
  }

  closeEditorEmailMjml(editor) {
    if (!editor) {
      throw new Error('no email-mjml editor');
    }
    DynamicContentService.grapesConvertDynamicContentSlotsToTokens(editor);

    let code = '';

    // Try catch for mjml parser error
    try {
      code = this.editor.runCommand('mjml-get-code');
    } catch (error) {
      // eslint-disable-next-line no-console
      console.log(error.message);
      // eslint-disable-next-line no-alert, no-restricted-globals
      alert('Errors inside your template. Template will not be saved.');
    }

    // Update textarea for save
    if (!code.length) {
      mQuery('textarea.builder-html').val(code.html);
      mQuery('textarea.builder-mjml').val(editor.getHtml());
    }

    // Reset HTML
    this.resetHtml(editor);
  }

  static closeEditorEmailHtml(editor) {
    if (!editor) {
      throw new Error('no email-html editor');
    }

    DynamicContentService.grapesConvertDynamicContentSlotsToTokens(editor);

    // Update textarea for save
    const innerHTML = editor.runCommand('gjs-get-inlined-html');
    // eslint-disable-next-line no-console
    console.warn({ innerHTML });
    mQuery('textarea.builder-html').val(innerHTML);
    // was: mQuery('textarea.builder-html').val(fullHtml.documentElement.outerHTML);
    // as reference: const parser = new DOMParser();
    // const fullHtml = parser.parseFromString(this.canvasContent, 'text/html');

    // Reset HTML
    this.resetHtml(editor);
  }

  static resetHtml(editor) {
    mQuery('.builder').removeClass('builder-active').addClass('hide');
    mQuery('html').css('font-size', '');
    mQuery('body').css('overflow-y', '');

    // Destroy GrapesJS
    // workaround: throws typeError: Cannot read property 'trigger'
    // since editior is destroyed, command can not be stopped anymore
    mQuery('.builder-panel').css('display', 'none');
    setTimeout(() => editor.destroy(), 1000);
    // editor.destroy();
  }
}
