import ContentService from '../content.service';
import MjmlService from '../mjml/mjml.service';
import ButtonCloseCommands from './buttonClose.command';
import ButtonsService from './buttons.service';

export default class ButtonApplyCommand {
  /**
   * The command name
   */
  static name = 'preset-mautic:apply-form';

  /**
   * Command saves the email into Database
   *
   * @param editor
   * @param sender
   */
  static applyForm(editor, sender) {
    editor.runCommand('preset-mautic:dynamic-content-components-to-tokens');

    if (ContentService.isMjmlMode(editor)) {
      const htmlCode = MjmlService.getEditorHtmlContent(editor);
      const mjmlCode = MjmlService.getEditorMjmlContent(editor);

      if (!htmlCode || !mjmlCode) {
        throw new Error('Could not generate html from MJML');
      }

      ButtonCloseCommands.returnContentToTextarea(editor, htmlCode, mjmlCode);
    } else {
      const html = ContentService.getEditorHtmlContent(editor);
      ButtonCloseCommands.returnContentToTextarea(editor, html);
    }

    ButtonApplyCommand.postForm(editor, sender);
  }

  /**
   * Send POST request for sending the form, get and handle response
   * Use the global Mautic postForm function
   *
   * @param editor
   * @param sender
   */
  static postForm(editor, sender) {
    const mauticForm = ButtonsService.getMauticForm();

    sender.set('className', 'fa fa-spinner fa-spin');

    ButtonApplyCommand.setDefaultValues(editor);

    Mautic.inBuilderSubmissionOn(mauticForm);
    Mautic.postForm(mauticForm, ButtonApplyCommand.postFormResponse.bind(this, editor, sender));
    Mautic.inBuilderSubmissionOff();
  }

  /**
   * Get and handle response
   * Use the global Mautic functions
   *
   * @param editor
   * @param sender
   * @param response
   */
  static postFormResponse(editor, sender, response) {
    const mauticForm = ButtonsService.getMauticForm();

    if (response.validationError !== null) {
      const title = Mautic.translate('grapesjsbuilder.panelsViewsCommandModalTitleError');
      ButtonApplyCommand.showModal(editor, title, response.validationError);
    } else {
      if (response.route) {
        // update URL in address bar
        MauticVars.manualStateChange = false;
        History.pushState(null, 'Mautic', response.route);

        // update Title
        Mautic.generatePageTitle(response.route);
      }

      // update form action
      if (mauticForm[0].baseURI !== mauticForm[0].action) {
        mauticForm[0].action = mauticForm[0].baseURI;
      }
    }

    sender.set('className', 'fa fa-check');
  }

  /**
   * Create modal to show information about saving email
   *
   * @param editor
   * @param title
   * @param text
   */
  static showModal(editor, title, text) {
    const modal = editor.Modal;
    const body = document.createElement('div');
    const content = document.createElement('div');
    const footer = document.createElement('div');
    const btnClose = document.createElement('button');

    modal.setTitle(`<h4 class="text-danger">${title}</h4>`);

    content.classList.add('panel-body');
    content.innerText = text;
    body.appendChild(content);

    btnClose.classList.add('btn', 'btn-lg', 'btn-default', 'text-primary');
    btnClose.innerText = 'Close';
    btnClose.onclick = () => {
      modal.close();
    };
    footer.classList.add('panel-footer', 'text-center');
    footer.appendChild(btnClose);
    body.appendChild(footer);

    modal.setContent(body);
    modal.open({
      attributes: {
        class: 'modal-content',
      },
    });
  }

  /**
   * Set a default value for the required form items.
   * The email form has subject and internal name fields as a required.
   * The page form has a title field as a required.
   */
  static setDefaultValues(editor) {
    const mode = ContentService.getMode(editor);

    if (mode === ContentService.modeEmailHtml || mode === ContentService.modeEmailMjml) {
      let formEmailSubject = ButtonsService.getElementValue('emailform_subject');
      let formEmailName = ButtonsService.getElementValue('emailform_name');

      if (formEmailSubject.lenght === 0) {
        formEmailSubject = ButtonsService.getDefaultValue(mode.split('-')[0]);
      }
      if (formEmailName.length === 0) {
        formEmailName = ButtonsService.getDefaultValue(mode.split('-')[0]);
      }
    }

    if (mode === ContentService.modePageHtml) {
      let formPageTitle = ButtonsService.getElementValue('page_title');

      if (formPageTitle.length === 0) {
        formPageTitle = ButtonsService.getDefaultValue(mode.split('-')[0]);
      }
    }
  }
}
