export default class DynamicContentService {
    /**
     * Get dec ID from the store items identifier: e.g. #emailform_dynamicContent_1
     * @returns integer
     */
    static getDecId(storeItemIdentifier: any): number;
    /**
     * Returns the decId from the component
     * @returns integer
     */
    static getDataParamDecid(component: any): number;
    /**
     * Extract the dynamic content index and the html id from a string:
     * e.g. href from dcTarget
     * decId = the readable number in the token. Starts with 1
     * htmlId = the html store item Id. It stars with 0
     *
     * @param {string} identifier e.g. http://localhost:1234/#emailform_dynamicContent_1
     */
    static getDcTarget(identifier: string): {
        htmlId: string;
        decId: number;
        content: string;
    };
    /**
     * Get the default dynamic content name (tokenName)
     * @param {string} id id of the input field
     * @returns string of the field
     */
    static getDcName(target: any): string;
    /**
     * Get the default content
     * @param {string} id id of the textarea
     * @returns string with the html in the textarea
     */
    static getDcContent(target: any): any;
    constructor(editor: any);
    /**
     * Dynamic content tabs on the base html item store (hidden)
     * Each "tab" corresponds to a dynamic content block on the canvas.
     * References by the tab.title (e.g. Dynamic Content)
     */
    dcStoreItems: any[];
    /**
     * Components currently on the canvas/editor
     */
    dcComponents: any[];
    editor: any;
    logger: Logger;
    /**
     * Get the name of the dynamic content element.
     * Used as identifier
     * E.g. Dynamic Content from: {dynamiccontent="Dynamic Content"}
     * @param {GrapesJS Component} component
     * @returns string | null
     */
    getTokenName(component: any): string;
    /**
     * Link the component on the canvas with the item in the HTML store
     * If it does not exist, create a new store item
     */
    linkComponentToStoreItem(component: any): number;
    /**
     * Get the content from the Html store and put the default content on the canvas.
     * Creates a store item (filter) in Mautic Form if new.
     * Wires up the ids.
     * E.g. if they are initialized from a {token}
     *
     * @param {GrapesJS Component} component
     */
    updateComponentFromDcStore(component: any): boolean;
    /**
     * if the editors modal is closed/stopped the Components content visible
     * on the canvas and the html store item has to be updated
     */
    updateDcStoreItem(): void;
    /**
     * Get a dynamic content item from its html store
     */
    getStoreItem(decId: any): any;
    /**
     * Set the html/content of the visible component on the canvas
     */
    updateComponent(component: any, dcItem: any): any;
    /**
     * If dynamic content item in html store doesn't exist -> create
     * @todo replace mQuery('#dynamicContentTabs') with class property
     *
     * @param {GrapesJS Component} component
     * @param {string}
     */
    createNewStoreItem(component: any): any;
    /**
     * Get all Dynamic Content items from the HTML store
     * @returns array of objects with title and href
     */
    getDcStoreItems(): void;
    /**
     * Get all the dynamic content components currently
     * on the canvas (in the editor).
     * @return {GrapesJS Component} array
     */
    getDcComponents(): GrapesJS;
}
import Logger from '../logger';
