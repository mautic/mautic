import DynamicContentListeners from './dynymicContent/dynamicContent.listeners';
import Logger from './logger';

export default (editor, options) => {
  const dynamicContentTabs = [];

  const dcListener = new DynamicContentListeners(editor, dynamicContentTabs);
  dcListener.onLoad();

  const logger = new Logger(editor);
  const logFilter = options.logFilter || 'log:warning';
  logger.addListener(logFilter);
};
