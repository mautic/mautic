import DynamicContentEvents from './dynamicContent/dynamicContent.events';
import PreferenceCenterEvents from './preferenceCenter/preferenceCenter.events';

export default (editor) => {
  const dce = new DynamicContentEvents(editor);
  dce.onComponentRemove();

  const pce = new PreferenceCenterEvents(editor);
  pce.onComponentRemove();
};
