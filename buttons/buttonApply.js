import ButtonApplyCommand from './buttonApply.command';

export default class ButtonApply {
  editor;

  constructor(editor) {
    if (!editor) {
      throw new Error('no editor');
    }

    this.editor = editor;
  }

  /**
   * Add the save button before the close button
   */
  addButton() {
    const emailForm = ButtonApply.getEmailForm();
    const emailFormList = ButtonApply.getEmailFormList(emailForm);
    const emailType = ButtonApply.getEmailType(emailForm);
    const emailTypeSegment = 'list';

    let title = Mautic.translate('grapesjsbuilder.panelsViewsButtonsApplyTitle');
    let disable = false;
    let command = this.getCommand();

    if (emailType.val() === emailTypeSegment && !emailFormList.val().length) {
      title = Mautic.translate('grapesjsbuilder.panelsViewsButtonsApplyTitleError');
      disable = true;
      command = '';
    }

    this.editor.Panels.addButton('views', [
      {
        id: 'views-apply',
        className: `fa fa-check`,
        active: false,
        disable,
        attributes: {
          id: 'btn-views-apply',
          title,
        },
        command,
        context: 'views-apply',
      },
    ]);
  }

  addCommand() {
    this.editor.Commands.add(ButtonApplyCommand.name, {
      run: this.getCallback(),
    });
  }

  /**
   * Get the apply command name based on the editor mode
   *
   * @returns String
   */
  getCommand() {
    return ButtonApplyCommand.name;
  }

  /**
   * Get the actual Command/Function to be executed on closing of the editor
   *
   * @returns Function
   */
  getCallback() {
    return ButtonApplyCommand.applyEmail;
  }

  static getEmailForm() {
    return mQuery('form[name=emailform]');
  }

  static getEmailFormList(emailForm) {
    return emailForm.find('#emailform_lists');
  }

  static getEmailType(emailForm) {
    return emailForm.find('#emailform_emailType');
  }
}
