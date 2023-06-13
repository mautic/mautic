export default class Logger {
    static namespace: string;
    static filters: string[];
    constructor(editor: any);
    editor: any;
    debug(msg: any, params?: {}): void;
    info(msg: any, params?: {}): void;
    warning(msg: any, params?: {}): void;
    error(msg: any, params?: {}): void;
    /**
     *
     * @param {string} msg log message
     * @param {object} params optional params
     * @param {string} level  log level
     */
    log(msg: string, params: object, level?: string): void;
    /**
     * What kind of logs to display
     * @param {string} filter `log`, `log:info`, `grapesjs-preset`, `grapesjs-preset:info`
     */
    addListener(filter?: string): void;
}
