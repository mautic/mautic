import ButtonsService from './buttons.service';
import ContentService from '../content.service';

export default class ButtonPreviewCommand {
  /**
   * The command name
   */
  static name = 'preset-mautic:preview-form';

  /**
   * Email preview command
   *
   * @param editor
   */
  static previewForm(editor) {
    const form = ButtonsService.getForm();
    const instanceId = ButtonsService.getInstanceId(form);

    ButtonPreviewCommand.openPreview(editor, instanceId);
  }

  /**
   * Open  the email preview
   *
   * @param editor
   * @param emailId
   */
  static openPreview(editor, emailId) {
    const mode = ContentService.getMode(editor);
    const url = `${window.location.origin}${mauticBaseUrl}${mode.split('-')[0]}/preview/${emailId}`;

    window.open(url, '_blank');
  }
}
