export default class ContentService {
    static modeEmailHtml: string;
    static modeEmailMjml: string;
    static modePageHtml: string;
    static isMjmlMode(editor: any): boolean;
    static getMode(editor: any): any;
    /**
     * Get the current Canvas content as complete HTML document:
     * Combine original doctype, header, editor styles and content
     *
     * @param {GrapesJs Editor} editor
     * @returns HTMLDocument
     */
    static getCanvasAsHtmlDocument(editor: any): Document;
    /**
     * Get complete current html. Including doctype and original header.
     * @returns string
     */
    static getEditorHtmlContent(editor: any): string;
    /**
     * Serialize a HTML Document to a string
     * @param {DocumentHTML} contentDocument
     */
    static serializeHtmlDocument(contentDocument: DocumentHTML): string;
    /**
     * Returns the correct string for valid (HTML5) doctypes, eg:
     * <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0//EN" "http://www.w3.org/TR/REC-html40/strict.dtd">
     *
     * @param {DocumentType}
     * @returns string
     */
    static serializeDoctype(doctype: any): string;
    /**
     * Get the selected themes original or the users last saved
     * content from the db. Loaded via Mautic PHP into the textarea.
     * @returns HTMLDocument
     */
    static getOriginalContentHtml(): Document;
    /**
     * Extract all stylesheets from the template <head>
     * @todo use DocumentHTML Styles directly
     */
    static getStyles(): any[];
}
