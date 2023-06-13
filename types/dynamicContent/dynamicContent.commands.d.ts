export default class DynamicContentCommands {
    /**
     * Build the basic popup/modal frame to hold the dynamic content editor
     * @returns HTMLDivElement
     */
    static buildDynamicContentPopup(): HTMLDivElement;
    /**
     * Get the DynamicContent identifier of the html store item
     * based on the token nr (decId)
     * e.g. emailform_dynamicContent_0
     * @param {integer} decId
     * @returns string
     */
    static getDcStoreId(decId: integer): string;
    constructor(editor: any);
    editor: any;
    dcPopup: any;
    dcService: DynamicContentService;
    logger: Logger;
    stopDynamicContentPopup(): void;
    /**
     * Update and wire all dynamic content components on the canvas to
     * human readable texts/slots/components.
     * E.g. if they are initialized from a {token}
     */
    updateComponentsFromDcStore(): void;
    /**
     * Convert dynamic content components to tokens
     * Dynamic Content => {dynamicContent}
     *
     * @param editor
     * @returns integer nr of dynamic content slots found
     */
    convertDynamicContentComponentsToTokens(editor: any): any;
    /**
     * Build and display the Dynamic Content editor popup/modal window.
     * Hint: the passed in editor is the main grapesjs editor.
     *
     * @param {Model} component The current grapesjs component
     */
    showDynamicContentPopup(editor: any, sender: any, options: any): void;
    /**
     * Load Dynamic Content editor and append to the codePopup Modal
     */
    addDynamicContentEditor(editor: any, options: any): void;
    linkComponentToStoreItem(edtr: any, sender: any, options: any): void;
    /**
     * Delete DynamicContent on Mautic side
     *
     * @param component
     */
    deleteDynamicContentStoreItem(editor: any, sender: any, options: any): void;
}
import DynamicContentService from './dynamicContent.service';
import Logger from '../logger';
