import DynamicContentService from './dynamicContent.service';
import CodeEditor from '../codeEditor';
import DynamicContent from './dynamicContent';

export default class DynamicContentCommands {
  /**
   * Launch Code Editor popup
   */
  // static launchCodeEdit(editor, sender) {
  //   const codeEditor = new CodeEditor(editor, this.opts);

  //   if (sender) {
  //     sender.set('active', 0);
  //   }

  //   // Transform DC to token
  //   console.debug('Transform DC to token');
  //   DynamicContentService.grapesConvertDynamicContentSlotsToTokens(editor);
  //   codeEditor.showCodePopup();
  // }

  static launchDynamicContent(editor, sender, options = {}) {
    const { target } = options;
    const component = target || editor.getSelected();

    const dynamicContent = new DynamicContent(editor);
    console.log('command: launchDynamicContent : show popup',component);
    dynamicContent.showCodePopup(component);

    // Transform DC to token
    DynamicContentService.grapesConvertDynamicContentSlotsToTokens(editor);
  }

  /**
   * Convert dynamic content tokens to slot and load content
   * used in grapesjs-preset-mautic
   */
  static grapesConvertDynamicContentTokenToSlot(editor) {
    const dc = editor.DomComponents;

    const dynamicContents = dc.getWrapper().find('[data-slot="dynamicContent"]');

    if (dynamicContents.length) {
      dynamicContents.forEach((dynamicContent) => {
        DynamicContentService.manageDynamicContentTokenToSlot(dynamicContent);
      });
    }
  }
}
