export default class Logger {
  editor;

  static namespace = 'grapesjs-preset';

  static filters = ['log:debug', 'log:info', 'log:warning'];

  constructor(editor) {
    if (!editor) {
      throw new Error('Editor is required');
    }
    this.editor = editor;
  }

  debug(msg, params = {}) {
    this.log(msg, params, 'debug');
  }

  info(msg, params = {}) {
    this.log(msg, params, 'info');
  }

  warning(msg, params = {}) {
    this.log(msg, params, 'warning');
  }

  error(msg, params = {}) {
    this.log(msg, params, 'error');
  }

  /**
   *
   * @param {string} msg log message
   * @param {object} params optional params
   * @param {string} level  log level
   */
  log(msg, params, level = 'debug') {
    const options = { ...{ ns: Logger.namespace, level }, ...params };
    this.editor.log(msg, options);
  }

  /**
   * What kind of logs to display
   * @param {string} filter `log`, `log:info`, `grapesjs-preset`, `grapesjs-preset:info`
   */
  addListener(filter = 'log:debug') {
    // find the severity for debug, info, warning.
    const displaySeverity = Logger.filters.findIndex((element) => element === filter);

    // severity only works with items in Logger.filters. All other filters are applied directly
    if (displaySeverity === -1) {
      // this.editor.on(filter, (msg, opts) => console.info(msg, opts));
    } else {
      // listen for all logs with a severity > than the current setting.
      Logger.filters.forEach((item, severity) => {
        // @todo severity >1 (warning and error) is already logged via backbone. find out how it works.
        if (displaySeverity <= severity && severity <= 1) {
          this.editor.on(item, (msg, opts) => console.info(msg, opts));
        }
      });
    }
  }
}
