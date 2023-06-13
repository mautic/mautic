export default class ButtonService {
    /**
     * Get NodeList form object
     *
     * @return NodeListOf<HTMLElement>
     */
    static getForm(): NodeListOf<HTMLElement>;
    /**
     * Get a form fields value by ID
     * @param string elementId
     * @return string
     */
    static getElementValue(elementId: any): any;
    /**
     * Get the email|page id to open preview
     *
     * @return string|null
     */
    static getInstanceId(form: any): string;
    /**
     * Get jQuery email form object
     * Sent as a parameter to the Mautic functions
     *
     * @return object
     */
    static getMauticForm(): any;
    /**
     * Check if the the entity ID is temporary (for new entities)
     *
     * @return array|null
     */
    static isNewEntity(): any;
    /**
     * Generate a default value
     *
     * @return string
     */
    static getDefaultValue(value: any): string;
    /**
     * Replace the first letter with a capital letter
     *
     * @return string
     */
    static capitalizeFirstLetter(string: any): any;
    /**
     * Get the current date
     *
     * @return string
     */
    static getCurrentDate(): string;
}
