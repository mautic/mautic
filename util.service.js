export default class UtilService {
  static modeEmailHtml = 'email-html';

  static modeEmailMjml = 'email-mjml';

  static modePageHtml = 'page-html';

  static getMode(editor) {
    const cfg = editor.getConfig();

    if (!cfg.pluginsOpts || !cfg.pluginsOpts.grapesjsmautic) {
      throw new Error('Wrong Mautic Grapesjs mode');
    }

    return cfg.pluginsOpts.grapesjsmautic.mode;
  }
}
