export default class ButtonPreviewCommand {
    /**
     * The command name
     */
    static name: string;
    /**
     * Email preview command
     *
     * @param editor
     */
    static previewForm(editor: any): void;
    /**
     * Open  the email preview
     *
     * @param editor
     * @param emailId
     */
    static openPreview(editor: any, emailId: any): void;
}
