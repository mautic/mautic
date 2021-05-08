import DynamicContentEvents from './dynymicContent/dynamicContent.events';

export default (editor) => {
  const dce = new DynamicContentEvents(editor);
  dce.onComponentRemove();
};
