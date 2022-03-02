import ButtonsService from './buttons.service';

export default class ButtonPreviewCommand {
  /**
   * The command name
   */
  static name = 'preset-mautic:preview-form';

  /**
   * Email preview command
   */
  static previewForm() {
    const form = ButtonsService.getForm();
    const instanceId = ButtonsService.getInstanceId(form);

    ButtonPreviewCommand.openPreview(instanceId);
  }

  /**
   * Open  the email preview
   *
   * @param emailId
   */
  static openPreview(emailId) {
    const type = ButtonsService.getType();
    const url = `${window.location.origin}${mauticBaseUrl}${type}/preview/${emailId}`;

    window.open(url, '_blank');
  }
}
