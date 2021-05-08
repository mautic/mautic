import DynamicContentListeners from './dynymicContent/dynamicContent.listeners';

export default (editor, options) => {
  const dynamicContentTabs = [];

  const dcListener = new DynamicContentListeners(editor, dynamicContentTabs);
  dcListener.onLoad();
};
