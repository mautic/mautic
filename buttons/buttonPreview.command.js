export default class ButtonPreviewCommand {
  /**
   * The command name
   */
  static name = 'preset-mautic:preview-email';

  /**
   * Email preview command
   */
  static previewEmail() {
    const emailId = ButtonPreviewCommand.getEmailId();

    ButtonPreviewCommand.openPreview(emailId);
  }

  /**
   * Open  the email preview
   *
   * @param emailId
   */
  static openPreview(emailId) {
    const url = `${window.location.origin}${mauticBaseUrl}email/preview/${emailId}`;

    window.open(url, '_blank');
  }

  /**
   * Get email id for open preview
   *
   * @return string|null
   */
  static getEmailId() {
    const emailForm = ButtonPreviewCommand.getEmailForm();
    const url = emailForm[0].action;
    const regexpEmailId = /emails\/edit\/(\d+)$/g;
    const match = regexpEmailId.exec(url);

    return match ? match[1] : null;
  }

  /**
   * Get a jQuery email form object
   *
   * @return object
   */
  static getEmailForm() {
    return document.getElementsByName('emailform');
  }
}
