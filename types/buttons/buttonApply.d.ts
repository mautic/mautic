export default class ButtonApply {
    /**
     * Get the apply command name based on the editor mode
     *
     * @returns String
     */
    static getCommand(): string;
    /**
     * Get the actual Command/Function to be executed on closing of the editor
     *
     * @returns Function
     */
    static getCallback(): typeof ButtonApplyCommand.applyForm;
    constructor(editor: any);
    editor: any;
    /**
     * Add the save button before the close button
     */
    addButton(): void;
    addCommand(): void;
}
import ButtonApplyCommand from './buttonApply.command';
