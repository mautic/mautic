export default class ButtonClose {
    /**
     * Add close button with save for Mautic
     */
    constructor(editor: any);
    editor: any;
    /**
     * The close command based on the editor mode
     */
    command: string;
    addButton(): void;
    addCommand(): void;
    /**
     * Get the close command based on the editor mode
     */
    getCommand(): "mautic-editor-page-html-close" | "mautic-editor-email-html-close" | "mautic-editor-email-mjml-close";
    /**
     * get the actual Command/Function to be executed on closing of the editor
     * @returns Function
     */
    getCallback(): typeof ButtonCloseCommands.closeEditorPageHtml;
}
import ButtonCloseCommands from './buttonClose.command';
