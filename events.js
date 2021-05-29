import DynamicContentEvents from './dynamicContent/dynamicContent.events';

export default (editor) => {
  const dce = new DynamicContentEvents(editor);
  dce.onComponentRemove();
};
