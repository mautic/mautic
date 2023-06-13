export default class ButtonCloseCommands {
    static closeEditorPageHtml(editor: any): void;
    static closeEditorEmailHtml(editor: any): void;
    static closeEditorEmailMjml(editor: any): void;
    /**
     * Save the html and mjml
     * @param {string} html
     * @param {string} mjml
     */
    static returnContentToTextarea(editor: any, html: string, mjml: string): void;
    static resetHtml(): void;
}
