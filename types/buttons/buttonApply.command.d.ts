export default class ButtonApplyCommand {
    /**
     * The command name
     */
    static name: string;
    /**
     * Command saves the email into Database
     *
     * @param editor
     * @param sender
     */
    static applyForm(editor: any, sender: any): void;
    /**
     * Send POST request for sending the form, get and handle response
     * Use the global Mautic postForm function
     *
     * @param editor
     * @param sender
     */
    static postForm(editor: any, sender: any): void;
    /**
     * Get and handle response
     * Use the global Mautic functions
     *
     * @param editor
     * @param sender
     * @param response
     */
    static postFormResponse(editor: any, sender: any, response: any): void;
    /**
     * Create modal to show information about saving email
     *
     * @param editor
     * @param title
     * @param text
     */
    static showModal(editor: any, title: any, text: any): void;
    /**
     * Set a default value for the required form items.
     * The email form has subject and internal name fields as a required.
     * The page form has a title field as a required.
     */
    static setDefaultValues(editor: any): void;
}
