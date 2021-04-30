import DynamicContentListeners from './dynymicContent/dynamicContent.listeners';

export default (editor) => {
  const dynamicContentTabs = [];

  const dcListener = new DynamicContentListeners(editor, dynamicContentTabs);
  dcListener.onLoad();
};
