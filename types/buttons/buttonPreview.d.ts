export default class ButtonPreview {
    /**
     * Get the Preview command name based on the editor mode
     *
     * @returns String
     */
    static getCommand(): string;
    /**
     * Get the actual Command/Function to be executed on closing of the editor
     *
     * @returns Function
     */
    static getCallback(): typeof ButtonPreviewCommand.previewForm;
    constructor(editor: any);
    editor: any;
    /**
     * Add the preview button
     */
    addButton(): void;
    /**
     * Add Preview command
     */
    addCommand(): void;
}
import ButtonPreviewCommand from './buttonPreview.command';
