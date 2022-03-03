import ButtonApplyCommand from './buttonApply.command';
import ButtonsService from './buttons.service';

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
    const formType = ButtonsService.getFormType();

    let title = Mautic.translate('grapesjsbuilder.panelsViewsButtonsApplyTitle');
    let disable = false;
    let command = ButtonApply.getCommand();

    if (formType === 'email') {
      const emailFormList = ButtonsService.getFormItemById('emailform_lists');
      const emailType = ButtonsService.getFormItemById('emailform_emailType');
      const emailTypeSegment = 'list';

      if (emailType.value === emailTypeSegment && !emailFormList.value.length) {
        title = Mautic.translate('grapesjsbuilder.panelsViewsButtonsApplyTitleError');
        disable = true;
        command = '';
      }
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
      run: ButtonApply.getCallback(),
    });
  }

  /**
   * Get the apply command name based on the editor mode
   *
   * @returns String
   */
  static getCommand() {
    return ButtonApplyCommand.name;
  }

  /**
   * Get the actual Command/Function to be executed on closing of the editor
   *
   * @returns Function
   */
  static getCallback() {
    return ButtonApplyCommand.applyForm;
  }
}
