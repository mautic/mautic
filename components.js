/* eslint-disable no-else-return */
import DynamicContentDomComponents from './dynamicContent/dynamicContent.domcomponents';
import PreferenceCenterDomComponents from './preferenceCenter/preferenceCenter.domcomponents';
import ContentService from './content.service';

// https://grapesjs.com/docs/api/component.html
export default (editor) => {
  const mode = ContentService.getMode(editor);
  if (mode === ContentService.modeEmailHtml) {
    const dcdc = new DynamicContentDomComponents(editor);
    dcdc.addDynamicContentType();
  }
  if (mode === ContentService.modePageHtml) {
    const pcdc = new PreferenceCenterDomComponents(editor);
    pcdc.addPreferenceCenterType();
  }
};
