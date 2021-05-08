import DynamicContentService from '../dynymicContent/dynamicContent.service';

export default class ButtonCloseCommands {
  dcService;

  constructor() {
    this.dcService = new DynamicContentService();
  }

  static closeEditorPageHtml(editor) {
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
    console.warn('close');
    editor.runCommand('preset-mautic:dynamic-content-slots-to-tokens');

    // Combine editor styles and editor html and save it to Mautic textarea
    // This part is different from other modes
    const fullHtml = `${editor.getHtml()}<style>${editor.getCss({
      avoidProtected: true,
    })}</style>`;
    mQuery('textarea.builder-html').val(fullHtml.documentElement.outerHTML);

    // Reset HTML
    ButtonCloseCommands.resetHtml(editor);
  }

  static closeEditorEmailMjml(editor) {
    if (!editor) {
      throw new Error('no email-mjml editor');
    }
    editor.runCommand('preset-mautic:dynamic-content-slots-to-tokens');

    let code = '';

    // Try catch for mjml parser error
    try {
      code = editor.runCommand('mjml-get-code');
    } catch (error) {
      console.log(error.message);
      alert('Errors inside your template. Template will not be saved.');
    }

    // Update textarea for save
    if (!code.length) {
      mQuery('textarea.builder-html').val(code.html);
      mQuery('textarea.builder-mjml').val(editor.getHtml());
    }

    // Reset HTML
    ButtonCloseCommands.resetHtml(editor);
  }

  static closeEditorEmailHtml(editor) {
    if (!editor) {
      throw new Error('no email-html editor');
    }

    editor.runCommand('preset-mautic:dynamic-content-slots-to-tokens');

    // Update textarea for save
    const innerHTML = editor.runCommand('gjs-get-inlined-html');
    console.warn({ innerHTML });
    mQuery('textarea.builder-html').val(innerHTML);
    // was: mQuery('textarea.builder-html').val(fullHtml.documentElement.outerHTML);
    // as reference: const parser = new DOMParser();
    // const fullHtml = parser.parseFromString(this.canvasContent, 'text/html');

    // Reset HTML
    ButtonCloseCommands.resetHtml(editor);
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
