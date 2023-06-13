export default class MjmlService {
    /**
     * Get the mjml document from the dom
     *
     * @returns string
     */
    static getOriginalContentMjml(): any;
    /**
     * Get the editors mjml and transform it to html
     * @param {Grapesjs Editor} editor
     * @returns string
     */
    static getEditorHtmlContent(editor: any): any;
    /**
     * Get the editors mjml
     * @param {Grapesjs Editor} editor
     * @returns string
     */
    static getEditorMjmlContent(editor: any): any;
    /**
     * Transform MJML to HTML
     * @todo show validation errors in the UI
     * @returns string
     */
    static mjmlToHtml(mjml: any, endpoint?: string): string;
    /**
     * Transform MJML to HTML via endpoint
     * @returns string
     */
    static mjmlToHtmlViaEndpoint(mjml: any, endpoint: any): any;
}
