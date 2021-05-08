export default class Logger {
  editor;

  static namespace = 'grapesjs-preset';

  constructor(editor) {
    this.editor = editor;
  }

  debug(msg) {
    this.log(msg, 'debug');
  }

  info(msg) {
    this.log(msg, 'info');
  }

  warning(msg) {
    this.log(msg, 'warning');
  }

  log(msg, level = 'debug') {
    this.editor.log(msg, { ns: Logger.namespace, level });
  }

  /**
   * What kind of logs to display
   * @param {string} filter `log`, `log:info`, `log-from-plugin-x`, `log-from-plugin-x:info`
   */
  addListener(filter = 'log:debug') {
    this.editor.on(filter, (msg, opts) => console.info(msg, opts));
  }
}
